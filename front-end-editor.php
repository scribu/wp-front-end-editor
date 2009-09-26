<?php
/*
Plugin Name: Front-end Editor
Version: 1.3.1
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

_fee_init();

function _fee_init()
{
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	// Load translations
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('front-end-editor', 'wp-content/plugins/'. $plugin_dir . '/lang', $plugin_dir.'/lang');

	// Load files
	foreach ( array('core.php', 'fields.php') as $file )
		require_once dirname(__FILE__) . '/' . $file;

	// Load options
	$options = new scbOptions('front-end-editor', __FILE__, array(
		'disabled' => array(),
		'rich' => true,
		'chunks' => false,
		'highlight' => true
	));

	frontEditor::init($options, '1.3a');

	if ( is_admin() )
	{
		require_once dirname(__FILE__) . '/admin.php';
		new frontEditorAdmin(__FILE__, $options);
	}
}

