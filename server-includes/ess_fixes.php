<?php
// remove the 32px margin at the top
// add_action('get_header', function() {
// 	remove_action('wp_head', '_admin_bar_bump_cb');
// }); 

// Remove admin bar on the front end
// add_filter('show_admin_bar', '__return_false');

// enable featured image in posts
add_theme_support('post-thumbnails');


// show private posts on the front end
// add_action('pre_get_posts',function($query){
//     if( is_admin() || ! $query->is_main_query() ) return;
//         if( current_user_can('edit_private_posts') ) {
// 	if (in_array('administrator',  wp_get_current_user()->roles)) {
// 		$query->set('post_status', array('private','publish', 'inherited'));
// 	}
//         }
// });

// remove "Private: " from titles
add_filter('the_title', function($title) {
	$title = str_replace('Private: ', '', $title);
	return $title;
});


// ========================================== 
// ACF
// ========================================== 

// priority is required for this to work, otherwise there will be an error on submit
add_action( 'wp_head', function() { acf_form_head(); }, 2); 


add_action('acf/save_post', ['ESS_Post', 'execute_set_title'], 20);


// Add styles to TinyMCE body content
add_filter('tiny_mce_before_init', function( $mceInit ) {
	
	$styles = '@font-face { font-family: Lato; src: url(' . get_theme_file_uri() . '/fonts/lato-v14-latin-regular.ttf); }';
	$styles .= 'body.mce-content-body { \nfont-family: Lato, Arial, Helvetica, sans-serif; \n font-weight: normal; } ';

    if ( isset( $mceInit['content_style'] ) ) {
        $mceInit['content_style'] .= ' ' . $styles . ' ';
    } else {
        $mceInit['content_style'] = $styles . ' ';
    }
	
    return $mceInit;
});


// ========================================== 
// ACF / Custom field searching
// ========================================== 

// https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
/**
 * Extend WordPress search to include custom fields
 *
 * https://adambalee.com
 */

/**
 * Join posts and postmeta tables
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
 */
function cf_search_join( $join ) {
    global $wpdb;

    if ( is_search() ) {    
//         $join .=' LEFT JOIN '.$wpdb->postmeta. ' cfmeta ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
		$join .=' LEFT JOIN '.$wpdb->postmeta. ' cfmeta ON '. $wpdb->posts . '.ID = cfmeta.post_id ';

    }

    return $join;
}
add_filter('posts_join', 'cf_search_join' );

/**
 * Modify the search query with posts_where
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
 */
function cf_search_where( $where ) {
    global $pagenow, $wpdb;

    if ( is_search() ) {
//         $where = preg_replace(
//             "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
//             "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
		$where = preg_replace(
            "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(".$wpdb->posts.".post_title LIKE $1) OR (cfmeta.meta_value LIKE $1)", $where );

    }

    return $where;
}
add_filter( 'posts_where', 'cf_search_where' );

/**
 * Prevent duplicates
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
 */
function cf_search_distinct( $where ) {
    global $wpdb;

    if ( is_search() ) {
        return "DISTINCT";
    }

    return $where;
}
add_filter( 'posts_distinct', 'cf_search_distinct' );