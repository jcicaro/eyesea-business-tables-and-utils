<?php

class ESS_RestHelper {
	public static function get_url($endpoint, $username, $password) { 
		
		$args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
				'method'    => 'GET',
		  	]
		];
		$req = wp_remote_request( $endpoint, $args );
// 		$response_code = wp_remote_retrieve_response_code($req);
// 		$response_msg = wp_remote_retrieve_response_message($req);

// 		echo json_encode($req);
// 		echo json_encode($req['headers']);
// 		echo json_encode($req['body']);
		
		return $req['body'];
	}
}

interface ESS_PostChild {
	public static function get_list_fields();
	public static function update_record($post_id, $meta);
	public static function get_external_records();
	public static function upsert_record($meta);
}

interface ESS_ExtIntegration {
	public static function get_external_records();
	public static function update_records_from_external();
}


class ESS_Post implements ESS_PostChild {
	
	public static $post_type = 'post';
	
	// Below methods should be implemented by children
	public static function get_list_fields() {}
	public static function update_record($post_id, $meta) {}
	public static function get_external_records() {}
	public static function upsert_record($meta) {}
	
	
	public static function execute_set_title($post_id) {
		$post_type = get_post_type($post_id);
		$class_name = self::get_class_name($post_type);
		
		if ($class_name) {
			call_user_func($class_name . '::set_title', $post_id);
		}
		
	}
	
	public static function execute_upsert_record($class_name, $post_type, $meta) {
		$post_arr = self::get_posts_by_key($post_type, 'correlation_id', $meta['sys_id']['value'], 'private');
		if (count($post_arr) > 0) {
			call_user_func($class_name . '::update_record', $post_arr[0]->ID, $meta);
		}
		else {
			$post_id = self::insert_record($post_type, 'private');
			call_user_func($class_name . '::update_record', $post_id, $meta);
		}
	}
	
	public static function insert_record($post_type, $post_status) {
		$post_status = $post_status ?? 'private';
		$post_info = ['post_type' => $post_type, 'post_status'   => $post_status];
		$post_id = wp_insert_post($post_info);

		return $post_id;
	}
	
	public static function calculate_title($post_id, $field_names) {
		$title = '--';
		
		$field_obj_arr = [];
		
		foreach($field_names as $field) {
			array_push($field_obj_arr, get_field($field, $post_id));
		}

		$title = implode(' - ', $field_obj_arr);
		
		return $title;
	}
	
	public static function update_title($post_id, $title) {

		// Set the post data
		$post = array(
		  'ID'           => $post_id,
		  'post_title'   => $title,
		);

		// Remove the hook to avoid infinite loop. Please make sure that it has
		// the same priority (20)
		remove_action('acf/save_post', ['ESS_Post', 'execute_set_title'], 20);

		wp_update_post($post);

		// Add the hook back
		add_action('acf/save_post', ['ESS_Post', 'execute_set_title'], 20);
		
	}
	
	
	public static function update_correlation($post_id, $meta) {
		update_field('correlation_id', $meta['sys_id']['value'], $post_id);
		update_field('correlation_meta', json_encode($meta, JSON_PRETTY_PRINT), $post_id);
	}
	
	
	public static function get_class_name($post_type) {
		$map = [
			'expense' => 'ESS_Expense',
			'income' => 'ESS_Income',
			'invoice' => 'ESS_Invoice',
			'workshop' => 'ESS_Workshop',
			'customer' => 'ESS_Customer',
			'vendor' => 'ESS_Vendor'
		];
		if(array_key_exists($post_type, $map)) {
			return $map[$post_type];
		}
		return '';
	}
	
	
	public static function get_posts_by_key($post_type, $meta_key, $meta_value, $post_status) {
		$post_status = $post_status ?? 'publish';
		$post = get_posts([
			'numberposts' => -1,
			'post_type' => $post_type,
			'post_status' => $post_status, // ['private, publish'],
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		]);
		return $post;
	}
	
	public static function execute_update_records_from_external($class_name) {
		$sn_resp = call_user_func($class_name . '::get_external_records'); // self::get_external_records();
		$sn_obj = json_decode($sn_resp, true);
		$sn_list = $sn_obj['result'];

		foreach ($sn_list as $obj) {
			call_user_func($class_name . '::upsert_record', $obj); // self::upsert_record($obj);
		}
		return json_encode($sn_list);
	}
	
}


class ESS_Expense extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'expense';
	
	public static function get_list_fields() {
		$fields = [
			['name' => 'date', 'label' => 'Date', 'is_relationship' => false],
			['name' => 'amount', 'label' => 'Amount', 'is_relationship' => false],
			['name' => 'financial_year', 'label' => 'Financial year', 'is_relationship' => false],
			['name' => 'type', 'label' => 'Type', 'is_relationship' => false],
			['name' => 'vendor', 'label' => 'Vendor', 'is_relationship' => true],
			['name' => 'reference_number', 'label' => 'Reference number', 'is_relationship' => false],
			['name' => 'description', 'label' => 'Description', 'is_relationship' => false]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['date', 'type', 'amount']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {
		$fields = ['date', 'type', 'amount', 'financial_year', 'description', 'reference_number'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['value'], $post_id);
		}
		
		$vendor_res = self::get_posts_by_key('vendor', 'correlation_id', $meta['vendor']['value'], 'private');
		if (count($vendor_res) > 0) {
			update_field('vendor', $vendor_res[0]->ID, $post_id);
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);

	}
	
	public static function get_external_records() {
		$url = SN_BASE_URL . '/api/now/table/x_444953_eyesea_expense?sysparm_display_value=all';
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}


class ESS_Income extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'income';
	
	public static function get_list_fields() {
		$fields = [
			['name' => 'date', 'label' => 'Date', 'is_relationship' => false],
			['name' => 'amount', 'label' => 'Amount', 'is_relationship' => false],
			['name' => 'financial_year', 'label' => 'Financial year', 'is_relationship' => false],
			['name' => 'type', 'label' => 'Type', 'is_relationship' => false],
			['name' => 'workshop', 'label' => 'Workshop', 'is_relationship' => true],
			['name' => 'invoice', 'label' => 'Invoice', 'is_relationship' => true]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['type', 'date']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {
		
		$fields = ['date', 'type', 'amount', 'financial_year'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['value'], $post_id);
		}
		
		if($meta['related_record']['value']) {
			$ws_res = self::get_posts_by_key('workshop', 'correlation_id', $meta['related_record']['value'], 'private');
			if (count($ws_res) > 0) {
				update_field('workshop', $ws_res[0]->ID, $post_id);
			}
		}
		
		if($meta['invoice']['value']) {
			$invoice_res = self::get_posts_by_key('invoice', 'correlation_id', $meta['invoice']['value'], 'private');
			if (count($invoice_res) > 0) {
				update_field('invoice', $invoice_res[0]->ID, $post_id);
			}
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);
		
	}
	
	public static function get_external_records() {
		$url = SN_BASE_URL . '/api/now/table/x_444953_eyesea_income?sysparm_display_value=all';
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}


class ESS_Invoice extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'invoice';
	
	public static function get_list_fields() {
		$fields = [
			['name' => 'invoice_number', 'label' => 'Invoice number', 'is_relationship' => false],
			['name' => 'invoice_date', 'label' => 'Invoice date', 'is_relationship' => false],
			['name' => 'due_date', 'label' => 'Due date', 'is_relationship' => false],
			['name' => 'total', 'label' => 'Total', 'is_relationship' => false],
			['name' => 'name', 'label' => 'Name', 'is_relationship' => false],
			['name' => 'customer', 'label' => 'Customer', 'is_relationship' => true],
			['name' => 'description', 'label' => 'Description', 'is_relationship' => false]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['invoice_number', 'name']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {
		
		$fields = ['invoice_date', 'due_date', 'total', 'name', 'description'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['value'], $post_id);
		}
		
		update_field('invoice_number', $meta['number']['value'], $post_id);
		
		if($meta['customer']['value']) {
			$cust_res = self::get_posts_by_key('customer', 'correlation_id', $meta['customer']['value'], 'private');
			if (count($cust_res) > 0) {
				update_field('customer', $cust_res[0]->ID, $post_id);
			}
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);
		
	}
	
	public static function get_external_records() {
		$url = SN_BASE_URL . '/api/now/table/x_444953_eyesea_invoice?sysparm_display_value=all';
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}


class ESS_Workshop extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'workshop';
	
	public static function get_list_fields() {
		
		$fields = [
			['name' => 'workshop_date', 'label' => 'Workshop date', 'is_relationship' => false],
			['name' => 'platform', 'label' => 'Platform', 'is_relationship' => false],
			['name' => 'number_of_people', 'label' => 'Number of people', 'is_relationship' => false]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['platform', 'workshop_date']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {
		
		$fields = ['workshop_date', 'platform', 'number_of_people'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['value'], $post_id);
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);
		
	}
	
	public static function get_external_records() {
		$url = SN_BASE_URL . '/api/now/table/x_444953_eyesea_workshop?sysparm_display_value=all';
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}


class ESS_Customer extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'customer';
	
	public static function get_list_fields() {
		// return ['name', 'company', 'website', 'email', 'phone', 'street', 'city', 'post_code', 'notes'];
		$fields = [
			['name' => 'name', 'label' => 'Name', 'is_relationship' => false],
			['name' => 'company', 'label' => 'Company', 'is_relationship' => false],
			['name' => 'website', 'label' => 'Website', 'is_relationship' => false],
			['name' => 'email', 'label' => 'Email', 'is_relationship' => false],
			['name' => 'phone', 'label' => 'Phone', 'is_relationship' => false],
			['name' => 'street', 'label' => 'Street', 'is_relationship' => false],
			['name' => 'city', 'label' => 'City', 'is_relationship' => false],
			['name' => 'post_code', 'label' => 'Post code', 'is_relationship' => false],
			['name' => 'notes', 'label' => 'Notes', 'is_relationship' => false]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['name']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {

		$fields = ['name', 'email', 'company'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['display_value'], $post_id);
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);
		
	}
	
	public static function get_external_records() {
		$query_str = '&sysparm_query=companyISNOTEMPTY';
		$url = SN_BASE_URL . '/api/now/table/sys_user?sysparm_display_value=all' . $query_str;
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}


class ESS_Vendor extends ESS_Post implements ESS_PostChild, ESS_ExtIntegration {
	
	public static $post_type = 'vendor';
	
	public static function get_list_fields() {
		$fields = [
			['name' => 'name', 'label' => 'Name', 'is_relationship' => false],
			['name' => 'website', 'label' => 'Website', 'is_relationship' => false],
			['name' => 'contact_person', 'label' => 'Contact person', 'is_relationship' => false],
			['name' => 'phone', 'label' => 'Phone', 'is_relationship' => false],
			['name' => 'notes', 'label' => 'Notes', 'is_relationship' => false]
		];
		
		return $fields;
	}
	
	public static function upsert_record($meta) {
		parent::execute_upsert_record(get_class(), self::$post_type, $meta);
	}
	
	public static function set_title($post_id) {
		$title = self::calculate_title($post_id, ['name']);
		self::update_title($post_id, $title);
	}
	
	public static function update_record($post_id, $meta) {
		
		$fields = ['name', 'website', 'phone'];
		foreach ($fields as $field) {
			update_field($field, $meta[$field]['value'], $post_id);
		}
		
		self::update_correlation($post_id, $meta);
		self::set_title($post_id);
		
	}
	
	public static function get_external_records() {
		$url = SN_BASE_URL . '/api/now/table/core_company?sysparm_display_value=all';
		return ESS_RestHelper::get_url($url, SN_USER, SN_PASSWORD);
	}
	
	public static function update_records_from_external() {
		return parent::execute_update_records_from_external(get_class());
	}
}