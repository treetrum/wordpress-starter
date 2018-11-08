<?php 

/*
|--------------------------------------------------------------------------
| Enqueue scripts
|--------------------------------------------------------------------------
*/

function sjd_scripts() {

    // SCRIPTS

    // User scripts    
    wp_enqueue_script(
        'bundle',
        get_template_directory_uri() . '/dist/bundle.js',
        null,
        md5_file(get_template_directory() . '/dist/bundle.js'),
        true
    );

    // Pass WordPress data into our JS
    $js_data = array(
        'siteURL' => get_site_url(),
        'themeURL' => get_stylesheet_directory_uri(),
        'ajaxURL' => admin_url('admin-ajax.php'),
        'pageID' => get_the_id()
    );
    wp_localize_script( 'bundle', 'siteData',  $js_data);

    // User styles
    wp_enqueue_style( 
        'style', 
        get_template_directory_uri() . '/dist/main.css', 
        null, 
        md5_file(get_template_directory() . '/dist/main.css') 
    );

}

add_action( 'wp_enqueue_scripts', 'sjd_scripts' );

/*
|--------------------------------------------------------------------------
| Basic setup
|--------------------------------------------------------------------------
*/

// Hide admin bar
show_admin_bar(false);

// Clean up header
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

// Theme support
add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );