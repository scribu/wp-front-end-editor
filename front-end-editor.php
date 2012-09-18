<?php
/*
Plugin Name: Front-end Editor
Version: 2.3.1-alpha
Description: Allows you to edit your posts without going through the admin interface
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/front-end-editor
Text Domain: front-end-editor
Domain Path: /lang
*/

define( 'FEE_VERSION', '2.3.1-alpha' );
define( 'ALOHA_VERSION', '0.20.16' );

define( 'FEE_MAIN_FILE', __FILE__ );

// Load scbFramework
require dirname( __FILE__ ) . '/lib/scb/load.php';

function _fee_init() {
	load_plugin_textdomain( 'front-end-editor', '', basename( dirname( __FILE__ ) ) . '/lang' );

	$dir = dirname( __FILE__ ) . '/php';

	require_once $dir . '/template-tags.php';
	require_once $dir . '/core.php';

	foreach ( array( 'base', 'post', 'widget', 'other' ) as $name ) {
		require_once "$dir/fields/$name.php";
	}

	$options = new scbOptions( 'front-end-editor', __FILE__, array(
		'disabled' => array('bloginfo'),
		'rich' => true,
		'group_post' => false,
		'taxonomy_ui' => 'termselect'
	) );

	FEE_Core::init( $options );

	FEE_Field_Option::init( __FILE__ );
	FEE_Field_Image::init( __FILE__ );

	if ( is_admin() ) {
		require_once dirname( __FILE__ ) . '/admin/admin.php';
		scbAdminPage::register( 'FEE_Admin', __FILE__, $options );
	}

	add_action( 'front_end_editor_fields', 'fee_register_defaults', 0 );
}
scb_init( '_fee_init' );

function fee_register_defaults() {
	$fields = array(
		// Post fields
		'the_title' => array(
			'title' => __( 'Title', 'front-end-editor' ),
			'class' => 'FEE_Field_Post_Title',
			'type'  => 'input',
			'argc'  => 2,
		),

		'the_content' => array(
			'title' => __( 'Content', 'front-end-editor' ),
			'class' => 'FEE_Field_Post_Content',
			'type'  => 'rich',
		),

		'the_excerpt' => array(
			'title' => __( 'Excerpt', 'front-end-editor' ),
			'class' => 'FEE_Field_Post_Excerpt',
			'type'  => 'textarea',
		),

		'post_meta' => array(
			'title' => __( 'Custom fields', 'front-end-editor' ),
			'class' => 'FEE_Field_Post_Meta',
			'argc'  => 5,
		),

		'post_thumbnail_html' => array(
			'title' => __( 'Thumbnail', 'front-end-editor' ),
			'class' => 'FEE_Field_Post_Thumbnail',
			'type'  => 'thumbnail',
			'argc'  => 4,
		),

		// Taxonomy fields
		'the_category' => array(
			'title' => __( 'Categories', 'front-end-editor' ),
			'class' => 'FEE_Field_Category',
			'argc'  => 3,
		),

		'the_tags' => array(
			'title' => __( 'Tags', 'front-end-editor' ),
			'class' => 'FEE_Field_Tags',
			'argc'  => 4,
		),

		'the_terms' => array(
			'title' => __( 'Terms', 'front-end-editor' ),
			'class' => 'FEE_Field_Terms',
			'argc'  => 5,
		),

		// Widget fields
		'widget_title' => array(
			'title' => __( 'Text widget title', 'front-end-editor' ),
			'class' => 'FEE_Field_Widget_Text',
			'type'  => 'input',
			'argc'  => 3,
		),

		'widget_text' => array(
			'title' => __( 'Text widget content', 'front-end-editor' ),
			'class' => 'FEE_Field_Widget_Text',
			'type' => 'rich',
			'argc'  => 2,
		),

		'dynamic_sidebar_params' => array(
			'title' => __( 'Other widgets', 'front-end-editor' ),
			'class' => 'FEE_Field_Widget',
			'type'  => 'widget',
		),

		// Other fields
		'comment_text' => array(
			'title' => __( 'Comment text', 'front-end-editor' ),
			'class' => 'FEE_Field_Comment',
			'type'  => 'textarea',
		),

		'single_cat_title' => array(
			'title' => __( 'Category title', 'front-end-editor' ),
			'class' => 'FEE_Field_Single_Title',
		),

		'single_tag_title' => array(
			'title' => __( 'Tag title', 'front-end-editor' ),
			'class' => 'FEE_Field_Single_Title',
		),

		'single_term_title' => array(
			'title' => __( 'Term title', 'front-end-editor' ),
			'class' => 'FEE_Field_Single_Title',
		),

		'term_description' => array(
			'title' => __( 'Term description', 'front-end-editor' ),
			'class' => 'FEE_Field_Term_Field',
			'type'  => 'rich',
			'argc'  => 3,
		),

		'get_the_author_description' => array(
			'title' => __( 'Author description', 'front-end-editor' ),
			'class' => 'FEE_Field_Author_Desc',
			'type'  => 'textarea',
			'argc'  => 2,
		),

		'bloginfo' => array(
			'title' => __( 'Site title and description', 'front-end-editor' ),
			'class' => 'FEE_Field_Bloginfo',
			'argc'  => 2,
		),

		'editable_option' => array(
			'title' => __( 'Site options', 'front-end-editor' ),
			'class' => 'FEE_Field_Option',
			'argc'  => 3,
		),

		'editable_image' => array(
			'title' => __( 'Theme images', 'front-end-editor' ),
			'class' => 'FEE_Field_Image',
			'type'  => 'image',
			'argc'  => 2,
		),
	);

	foreach ( $fields as $filter => $args ) {
		fee_register_field( $filter, $args );
	}
}

