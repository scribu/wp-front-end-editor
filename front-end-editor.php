<?php
/*
Plugin Name: Front-end Editor
Version: 1.5a
Description: Allows you to edit your posts without going through the admin interface
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/front-end-editor
Text Domain: front-end-editor
Domain Path: /lang

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Init

define('FEE_PLUGIN_FILE', __FILE__);

_fee_init();

function _fee_init() {
	$dir = dirname(__FILE__);

	// Load translations
	load_plugin_textdomain('front-end-editor', '', basename($dir) . '/lang');

	// Load scbFramework
	require_once $dir . '/scb/load.php';

	// Load files
	require_once $dir . '/core.php';

	require_once $dir . '/fields/post.php';
	require_once $dir . '/fields/other.php';

	// Load options
	$options = new scbOptions('front-end-editor', __FILE__, array(
		'disabled' => array(),
		'rich' => true,
		'chunks' => false,
		'highlight' => true
	));

	frontEditor::init($options, '1.4.1');

	if ( is_admin() ) {
		require_once $dir . '/admin.php';
		new frontEditorAdmin(__FILE__, $options);
	}
}

add_action('init', 'fee_register_defaults');
function fee_register_defaults() {
	$fields = array(
		'the_title' => array(
			'title' => __('Post title', 'front-end-editor'),
			'class' => 'frontEd_basic',
			'type' => 'input',
		),

		'the_content' => array(
			'title' => __('Post content', 'front-end-editor'),
			'class' => frontEditor::$options->chunks ? 'frontEd_chunks' : 'frontEd_basic',
			'type' => frontEditor::$options->rich ? 'rich' : 'textarea',
		),

		'the_excerpt' => array(
			'title' => __('Post excerpt', 'front-end-editor'),
			'class' => 'frontEd_excerpt',
			'type' => 'textarea',
		),

		'the_tags' => array(
			'title' => __('Post tags', 'front-end-editor'),
			'class' => 'frontEd_tags',
			'argc' => 4,
		),

		'the_terms' => array(
			'title' => __('Post terms', 'front-end-editor'),
			'class' => 'frontEd_terms',
			'argc' => 5,
		),

		'post_meta' => array(
			'title' => __('Post custom fields', 'front-end-editor'),
			'class' => 'frontEd_meta',
			'argc' => 4,
		),

		'comment_text' => array(
			'title' => __('Comment text', 'front-end-editor'),
			'class' => 'frontEd_comment',
			'type' => 'textarea',
		),

		'single_cat_title' => array(
			'title' => __('Category title', 'front-end-editor'),
			'class' => 'frontEd_single_title',
		),

		'single_tag_title' => array(
			'title' => __('Tag title', 'front-end-editor'),
			'class' => 'frontEd_single_title',
		),

		'the_author_description' => array(
			'title' => __('Author description', 'front-end-editor'),
			'class' => 'frontEd_author_desc',
			'type' => 'textarea',
			'argc' => 2,
		),

		'widget_text' => array(
			'title' => __('Text widget content', 'front-end-editor'),
			'class' => 'frontEd_widget',
			'type' => 'textarea',
		),

		'widget_title' => array(
			'title' => __('Text widget title', 'front-end-editor'),
			'class' => 'frontEd_widget',
		),

		'bloginfo' => array(
			'title' => __('Site title and description', 'front-end-editor'),
			'class' => 'frontEd_bloginfo',
			'argc' => 2,
		),

		'editable_image' => array(
			'title' => __('Theme images', 'front-end-editor'),
			'class' => 'frontEd_image',
			'type' => 'image',
			'argc' => 2,
		),
	);

	foreach ( $fields as $filter => $args )
		register_fronted_field($filter, $args);

	// Safe hook for new editable fields to be registered
	do_action('front_ed_fields');
}

