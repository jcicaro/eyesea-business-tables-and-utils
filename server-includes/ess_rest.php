<?php

add_action( 'rest_api_init', function () {
	
// 	register_rest_route( 'test/v1', '/create', array(
// 		'methods' => 'GET',
// 		'callback' => function() {
// 			return ESS_AcfHelper::createExpense();
// 		}
// 	));
	
// 	register_rest_route( 'test/v1', '/find', array(
// 		'methods' => 'GET',
// 		'callback' => function() {
// 			return ESS_AcfHelper::getPostsByKey('vendor', 'name', 'Test Vendor');
// 		}
// 	));
	
	register_rest_route( 'sn/v1', '/update/all', array(
		'methods' => 'GET',
		'callback' => function() {
			ESS_Vendor::update_records_from_external();
			ESS_Customer::update_records_from_external();
			ESS_Workshop::update_records_from_external();
			ESS_Invoice::update_records_from_external();
			ESS_Income::update_records_from_external();
			ESS_Expense::update_records_from_external();
			
			return 'Finished!';
		}
	));
	
	register_rest_route( 'sn/v1', '/update/vendor', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Vendor::update_records_from_external();
		}
	));
	
	register_rest_route( 'sn/v1', '/update/customer', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Customer::update_records_from_external();
		}
	));
	
	register_rest_route( 'sn/v1', '/update/workshop', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Workshop::update_records_from_external();
		}
	));
	
	register_rest_route( 'sn/v1', '/update/invoice', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Invoice::update_records_from_external();
		}
	));
	
	register_rest_route( 'sn/v1', '/update/income', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Income::update_records_from_external();
		}
	));
	
	register_rest_route( 'sn/v1', '/update/expense', array(
		'methods' => 'GET',
		'callback' => function() {
			return ESS_Expense::update_records_from_external();
		}
	));
	
});