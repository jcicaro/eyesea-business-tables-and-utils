<?php

class ESS_Component {
	
	public static function the_acf_form($post_id, $post_type) {

		$post_type_label = is_archive() ? get_queried_object()->labels->singular_name : get_post_type_object($post_type)->labels->singular_name; 
		
		?>
		<div class="card">
			<div class="card-header">
				<div class="container d-flex justify-content-between align-content-center">
					<span class="nav-item dropdown">
						<a class="nav-link dropdown-toggle btn-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
							<strong><?php echo $post_type_label; ?></strong>
						</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" href="<?php echo esc_url(site_url('/form/?get_post=new_post&get_type=' . $post_type)); ?>">Create new</a>
						  	<a class="dropdown-item" href="<?php echo esc_url(site_url('/' . $post_type . '/')); ?>">Back to list</a>
						</div>
					</span>
					<span class="pr-3 pt-1">
						<?php if (is_single()) { the_title(); } ?>
					</span>
				</div>
			</div>
			<div class="card-body create-form">
				<div class="col card-body">
					<?php acf_form(array(
						'post_id'		=> $post_id,
						'post_title'	=> false,
						'post_content'	=> false,
						'form' => true,
						'html_submit_button'  => '<div class="acf-fields"><div class="acf-field"><input type="submit" class="acf-button button button-primary button-large btn btn-primary"" value="%s" /></div></div>',
						'new_post'		=> array(
							'post_type'		=> $post_type,
							'post_status'	=> 'private'
						)
					)); ?>
				</div>
			</div>
		</div>
		<?php
	}
	
	public static function the_table_header() {
		
		$meta_key = array_key_exists('order_key', $_GET) ? $_GET['order_key'] : '';
		$order = array_key_exists('order', $_GET) ? $_GET['order'] : 'ASC';
		$link_order = $order == 'ASC' ? 'DESC' : 'ASC';

		$post_type = is_archive() ? get_queried_object()->name : '';
		$class_name = ESS_Post::get_class_name($post_type);
		$fields = call_user_func($class_name . '::get_list_fields');
		?>
		
		<tr>
			<?php 

			foreach ($fields as $field_obj) { $field = $field_obj['name'];

			?>
			<th scope="col">
				<a class="text-dark" href="<?php echo '/' . $post_type . '/?order_key=' . $field . '&order=' . $link_order; ?>">
					<?php echo $field_obj['label']; // get_field_object($field)['label'] ?>

					<?php if ($meta_key == $field && $link_order == 'ASC') { ?>
					<i class="fa fa-angle-up" aria-hidden="true"></i>
					<?php } ?>

					<?php if ($meta_key == $field && $link_order == 'DESC') { ?>
					<i class="fa fa-angle-down" aria-hidden="true"></i>
					<?php } ?>
				</a>

			</th>
			<?php } ?>
		</tr>
		
		<?php
	}
	
	
	public static function the_cell($post_id, $field_obj, $col_num, $is_td=false) {
		$field = $field_obj['name'];
		$is_rel = $field_obj['is_relationship'] == true;
		?>

		<?php if($is_td) { echo '<td>'; } else { echo '<span>'; } ?>
			<?php if ($col_num == 0) { ?> 
			
			<a href="<?php the_permalink(); ?>">
				<?php echo get_field($field, $post_id); ?>
			</a>
			
			<?php } elseif ($is_rel) { if (get_field($field, $post_id)) { ?>
			
				<?php foreach(get_field($field, $post_id) as $f) {  ?>

				<a href="<?php echo get_permalink($f); ?>">
					<?php echo get_the_title($f); ?>
				</a>

				<?php } } ?>
			
			<?php } else { ?>
			
			<?php echo get_field($field, $post_id); ?>
			
			<?php } ?>
		
		<?php if($is_td) { echo '</td>'; } else { echo '</span>'; }  ?>


		<?php
	}
	
	
	public static function the_table_rows() {
		
		$meta_key = array_key_exists('order_key', $_GET) ? $_GET['order_key'] : '';
		$order = array_key_exists('order', $_GET) ? $_GET['order'] : 'ASC';

		$post_type = is_archive() ? get_queried_object()->name : '';
		$class_name = ESS_Post::get_class_name($post_type);
		$fields = call_user_func($class_name . '::get_list_fields');
		
		// Sample URL: https://wpmain-jci.codeanyapp.com/expense/?order_key=vendor&order=DESC
						
		global $wp_query;
		$args = array_merge( $wp_query->query_vars, 
							['orderby' => 'meta_value', 'meta_key' => $meta_key, 'order' => $order] );
		query_posts($args);

		while(have_posts()) { the_post(); $post_id = get_the_ID(); 

		?>

		<tr>
			<?php 
			foreach ($fields as $i=>$field_obj) { 
				self::the_cell($post_id, $field_obj, $i, true);
			} ?>
		</tr>

		<?php 
		} 
 		
	}
	
	public static function the_list() {
		
		?>
		<div class="row table-list">
			<div class="col table-responsive">

				<table class="table table-striped">
					<thead>
						<?php self::the_table_header(); ?>
					</thead>
					<tbody>
						<?php self::the_table_rows(); ?>
					<tbody>
				</table>
				
				<div class="d-flex justify-content-center">
					<?php self::bootstrap_pagination(); ?>
				</div>
				
				
				<?php wp_reset_query(); ?>
			</div>

		</div>
		<?php
	}
	
	public static function the_list_with_create() {
		$post_type = is_archive() ? get_queried_object()->name : '';
		$post_type_label = is_archive() ? get_queried_object()->labels->name : ''; 
		?>

		<div class="">
			<div class="container d-flex justify-content-between align-content-center mb-2 pl-0 pr-0">
				<span class="nav-item dropdown">
					<a class="nav-link dropdown-toggle btn-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
						<strong><?php echo $post_type_label; ?></strong>
					</a>
					<div class="dropdown-menu">
					  <a class="dropdown-item" href="<?= '/wp-json/sn/v1/update/' . $post_type ?>">Fetch records</a>
					</div>
				</span>
				
				<a href="<?php echo '/form/?get_post=new_post&get_type=' . $post_type; ?>">
					<button type="button" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i></button>
				</a>
			</div>


		<?php ESS_Component::the_list(); // get_template_part('content'); ?>

		</div>
		<?php
	}
	
	/*
	 * custom pagination with bootstrap .pagination class
	 * source: http://www.ordinarycoder.com/paginate_links-class-ul-li-bootstrap/
	 */
	public static function bootstrap_pagination( $echo = true ) {
		global $wp_query;

		$big = 999999999; // need an unlikely integer

		$pages = paginate_links( array(
				'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format' => '?paged=%#%',
				'current' => max( 1, get_query_var('paged') ),
				'total' => $wp_query->max_num_pages,
				'type'  => 'array',
				'prev_next'   => true,
				'prev_text'    => __('« Prev'),
				'next_text'    => __('Next »'),
			)
		);

		if( is_array( $pages ) ) {
			$paged = ( get_query_var('paged') == 0 ) ? 1 : get_query_var('paged');

			$pagination = '<ul class="pagination pagination-sm">';

			foreach ( $pages as $page ) {
				$pagination .= '<li class="page-item">' . $page . '</li>';
			}

			$pagination .= '</ul>';

			if ( $echo ) {
				echo $pagination;
			} else {
				return $pagination;
			}
		}
	}

	
}

