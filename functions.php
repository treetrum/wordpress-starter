<?php 

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
	});

	add_filter('template_include', function( $template ) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});

	return;
}

/**
 * Include all our custom classes
 */


/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'templates', 'views' );

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;

/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class SJDSite extends Timber\Site {
	/** Add timber support. */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_action( 'admin_menu', array( $this, 'remove_comments_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'additional_setup' ) );
		add_action( 'init', array( $this, 'remove_comment_support' ) );
		add_action( 'init', array($this, 'add_taxonomies') );
		add_action( 'init', array($this, 'add_post_types') );
		parent::__construct();
    }
    
    public function register_scripts_and_styles() {
		// Sigh   - jquery
		// wp_deregister_script('jquery');
		// wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js', array(), null, false);

        // User scripts    
        wp_enqueue_script(
            'bundle',
            get_template_directory_uri() . '/dist/js/bundle.js',
            null,
            md5_file(get_template_directory() . '/dist/js/bundle.js'),
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
            get_template_directory_uri() . '/dist/css/main.css', 
            null, 
            md5_file(get_template_directory() . '/dist/css/main.css') 
        );
	}

	/** This is where you can register custom post types. */
	public function register_post_types() {

	}

	/** This is where you can register custom taxonomies. */
	public function register_taxonomies() {

	}

	/** This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context( $context ) {
		$context['site'] = $this;
		return $context;
	}

	public function theme_supports() {

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5', array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		// add_theme_support( 'menus' );
	}

    public function additional_setup() {
        // Hide admin bar
        show_admin_bar(false);

        // Clean up header
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
	}
	
	public function remove_comments_support() {
		// Remove post type support for each post type
		foreach (get_post_types() as $post_type) {
			if (post_type_supports( $post_type, 'comments' )) {
				// Don't remove comment support for shop orders
				if ($post_type !== 'shop_order') {
					remove_post_type_support( 'page', 'comments' );
				}
			}
		}
		// Uncomment the following to force hiding comment meta boxes
		// remove_meta_box( 'commentsdiv', 'post', 'normal' );
	}

	public function remove_comments_page() {
		remove_menu_page( 'edit-comments.php' );
	}

	public static function create_taxonomy($id = '', $singular = '', $plural = '', $post_types = []) {
		register_taxonomy( $id, $post_types, array(
			"labels" => array(
				"name" => _x( "$plural", "taxonomy general name", "textdomain" ),
				"singular_name" => _x( "$singular", "taxonomy singular name", "textdomain" ),
				"search_items" => __( "Search $plural", "textdomain" ),
				"all_items" => __( "All $plural", "textdomain" ),
				"parent_item" => __( "Parent $singular", "textdomain" ),
				"parent_item_colon" => __( "Parent $singular:", "textdomain" ),
				"edit_item" => __( "Edit $singular", "textdomain" ),
				"update_item" => __( "Update $singular", "textdomain" ),
				"add_new_item" => __( "Add New $singular", "textdomain" ),
				"new_item_name" => __( "New $singular Name", "textdomain" ),
				"menu_name" => __( "$plural", "textdomain" ),
				"separate_items_with_commas" => __( "Separate $plural with commas", "textdomain" ),
				"choose_from_most_used" => __( "Choose from the most used $plural", "textdomain" )
			),
			"hierarchical" => false,
			"show_ui" => false,
			"show_admin_column" => true,
			"query_var" => true
		));
	}
	
	public static function create_post_type($id = '', $singular = '', $plural = '', $archive = false) {
		register_post_type( $id, array(
			'labels' => array(
				"name" => __( "$plural" ),
				"singular_name" => __( "$singular" ),
				"add_new" => __( "Add New" ),
				"add_new_item" => __( "Add New $singular" ),
				"edit" => __( "Edit" ),
				"edit_item" => __( "Edit $singular" ),
				"new_item" => __( "New $singular" ),
				"view" => __( "View $singular" ),
				"view_item" => __( "View $singular" ),
				"search_items" => __( "Search $plural" ),
				"not_found" => __( "No $plural found." ),
				"not_found_in_trash" => __( "No $plural found in Trash." )
			),
			'public' => true,
			'menu_icon' => 'dashicons-admin-page',
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'has_archive' => $archive,
			'hierarchical' => false
		));
	}

}

new SJDSite();

/**
* Registers an editor stylesheet for the theme.
*/
function wpdocs_theme_add_editor_styles() {
    add_editor_style(get_template_directory_uri() . '/dist/css/rte.css?v=' . md5_file(get_template_directory() . '/dist/css/rte.css'));
}
add_action( 'admin_init', 'wpdocs_theme_add_editor_styles' );

/*
|--------------------------------------------------------------------------
| Custom TinyMCE Styles
|--------------------------------------------------------------------------
*/

// Callback function to insert 'styleselect' into the $buttons array
function my_mce_buttons_2( $buttons ) {
	array_unshift( $buttons, 'styleselect' );
	return $buttons;
}
// Register our callback to the appropriate filter
add_filter( 'mce_buttons_2', 'my_mce_buttons_2' );

add_filter( 'tiny_mce_before_init', function($init_array) {

    // Define the style_formats array
	$style_formats = array(
		// Each array child is a format with it's own settings
		array(
			'title' => 'Lead Paragraph',
			'block' => 'span',
			'classes' => 'lead',
			'wrapper' => false
        ),
    );

	// Insert the array, JSON ENCODED, into 'style_formats'
	$init_array['style_formats'] = json_encode( $style_formats );

	return $init_array;

});

// Add editor colours
// ------------------

function my_mce4_options($init) {

	$custom_colours =  '
	"FF0000", "Red",
	"00FF00", "Green",
	"0000FF", "Blue",
	';
  
	// build colour grid default+custom colors
	$init['textcolor_map'] = '['.$custom_colours.']';
  
	// enable 6th row for custom colours in grid
	$init['textcolor_rows'] = 6;
  
	return $init;
  }
  add_filter('tiny_mce_before_init', 'my_mce4_options');


function ancestor_id($offset = 0) {

	$post_to_use = get_queried_object();

	if (!$post_to_use) { return; }

	if ( !($post_to_use instanceof WP_Post) ) {
		// Only run this function if the post to use is a WP_Post
		return;
	}

	// Find the highest ancestor
	if ( $post_to_use->post_parent ) {
		$ancestors = array_reverse( get_post_ancestors( $post_to_use->ID ) );
		return $ancestors[$offset] ?? $post_to_use->ID;
	}

	// Return the highest ancestor
	return $post_to_use->ID;
}


if( function_exists('acf_add_options_page') ) {
	// acf_add_options_page('Settings');
}
