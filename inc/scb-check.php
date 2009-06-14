<?php

/*
This code allows users to get a nice notice to install scbFramework 
when they activate a plugin that uses it.

Add these two lines in your main plugin file, at the beginning:

	require_once dirname(__FILE__) . '/scb-check.php';
	if ( ! scb_check(__FILE__) ) return;
*/

if ( !function_exists('scb_check') ):
function scb_check($file)
{
	if ( class_exists('scbFramework') )
		return true;

	// Deactivate plugin
	$plugin = plugin_basename($file);
	$current = get_option('active_plugins');

	array_splice($current, array_search($plugin, $current), 1 ); // Fixed Array-fu!

	update_option('active_plugins', $current);

	add_action('admin_notices', 'scb_notice', 1);

	return false;
}
endif;

if ( !function_exists('scb_notice') ):
function scb_notice()
{
	remove_action('admin_notices', 'plugin_notices');

	global $wp_version;

	if ( version_compare('2.7', $wp_version, '<=') )
	{
		$slug = 'scb-framework';

		$link = '[<a href="' . admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug .
			'&amp;TB_iframe=true&amp;width=600&amp;height=550') . '" class="thickbox onclick">' . __('Install now') . '</a>]';
	}

	echo "<div class='updated fade'><p>One or more plugins require that <a href='http://scribu.net/wordpress/scb-framework'>scbFramework</a> be installed and active. $link</p></div>";
}
endif;

