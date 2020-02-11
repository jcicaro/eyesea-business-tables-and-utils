<?php

add_action('init', function() {
    register_post_type('expense', array(
        'labels' => array(
            'name' => __('Expenses'),
            'singular_name' => __('Expense'),
            'add_new' => __('Create Expense'),
            'add_new_item' => __('Create New Expense'),
            'edit_item' => __('Edit Expense'),
            'search_items' => __('Search Expenses')
        ),
        'menu_position' => 1000,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
		// 		'capability_type' => 'ess_record',
		// 		'map_meta_cap' => true,
        'supports' => array('title', 'thumbnail', 'comments')

    ));
	
	register_post_type('income', array(
        'labels' => array(
            'name' => __('Income'),
            'singular_name' => __('Income'),
            'add_new' => __('Create Income'),
            'add_new_item' => __('Create New Income'),
            'edit_item' => __('Edit Income'),
            'search_items' => __('Search Income')
        ),
        'menu_position' => 1100,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
        'supports' => array('title', 'thumbnail', 'comments')
    ));
	
	register_post_type('invoice', array(
        'labels' => array(
            'name' => __('Invoices'),
            'singular_name' => __('Invoice'),
            'add_new' => __('Create Invoice'),
            'add_new_item' => __('Create New Invoice'),
            'edit_item' => __('Edit Invoice'),
            'search_items' => __('Search Invoices')
        ),
        'menu_position' => 1200,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
        'supports' => array('title', 'thumbnail', 'comments')
    ));
	
	register_post_type('workshop', array(
        'labels' => array(
            'name' => __('Workshops'),
            'singular_name' => __('Workshop'),
            'add_new' => __('Create Workshop'),
            'add_new_item' => __('Create New Workshop'),
            'edit_item' => __('Edit Workshop'),
            'search_items' => __('Search Workshops')
        ),
        'menu_position' => 1300,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
        'supports' => array('title', 'thumbnail', 'comments')
    ));
	
	register_post_type('customer', array(
        'labels' => array(
            'name' => __('Customers'),
            'singular_name' => __('Customer'),
            'add_new' => __('Create Customer'),
            'add_new_item' => __('Create New Customer'),
            'edit_item' => __('Edit Customer'),
            'search_items' => __('Search Customers')
        ),
        'menu_position' => 1400,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
        'supports' => array('title', 'thumbnail', 'comments')
    ));
	
	register_post_type('vendor', array(
        'labels' => array(
            'name' => __('Vendors'),
            'singular_name' => __('Vendor'),
            'add_new' => __('Create Vendor'),
            'add_new_item' => __('Create New Vendor'),
            'edit_item' => __('Edit Vendor'),
            'search_items' => __('Search Vendors')
        ),
        'menu_position' => 1500,
        'public' => true,
        'has_archive' => true,
        // 'register_meta_box_cb' => 'lil_meta_box_cb',
        'supports' => array('title', 'thumbnail', 'comments')
    ));
	
	flush_rewrite_rules();
	
});








 