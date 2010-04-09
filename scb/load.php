<?php
/*
To load scbFramework, you just need to add this line at the beginning of your plugin:

require_once dirname(__FILE__) . '/scb/load.php';
*/

if ( !class_exists('scbLoad3') ) :
class scbLoad3 {

	private static $candidates;
	private static $loaded;

	static function init($rev, $file, $classes) {
		$dir = dirname($file);

		self::$candidates[$rev] = $dir;

		self::load($dir . '/', $classes);

		add_action('activated_plugin', array(__CLASS__, 'reorder'));
	}

	static function reorder() {
		krsort(self::$candidates);

		$dir = dirname(plugin_basename(reset(self::$candidates)));

		$current = get_option('active_plugins', array());

		$found = false;
		foreach ( $current as $i => $plugin ) {
			$plugin_dir = dirname($plugin);

			if ( $plugin_dir == $dir ) {
				$found = true;
				break;
			}
		}

		if ( !$found || 0 == $i )
			return;

		unset($current[$i]);
		array_unshift($current, $plugin);

		update_option('active_plugins', $current);
	}

	private static function load($path, $classes) {
		foreach ( $classes as $class_name ) {
			if ( class_exists($class_name) )
				continue;

			$fpath = $path . substr($class_name, 3) . '.php';

			if ( file_exists($fpath) ) {
				self::$loaded[$class_name] = $fpath;
				include $fpath;
			}
		}
	}

	static function get_info() {
		krsort(self::$candidates);

		return array(self::$loaded, self::$candidates);
	}
}
endif;

scbLoad3::init(5, __FILE__, array(
	'scbOptions', 'scbForms', 'scbAdminPage', 'scbBoxesPage',
	'scbWidget', 'scbCron', 'scbTable', 'scbUtil', 'scbRewrite',
));

