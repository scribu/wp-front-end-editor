<?php
/*
You can use this code to autoload the available classes of the scbFramework.
This has the advantage that the user is not required to install scbFramework as a separate plugin.
Things to note:
- you have to include the class files with each plugin
- you will have to update the framework manually

To load the classes, you just need to require the file, like so:

	require_once dirname(__FILE__) . '/scb/load.php';

This file needs to be in the same directory as the class files.
*/

if ( !class_exists('scbLoad2') ) :
abstract class scbLoad2 {
	private static $path;

	static function init($file, $classes) {
		self::$path = dirname($file) . '/';

		if ( class_exists('scbFramework') )
			self::$path .= 'classes/';

		foreach ( $classes as $className )
			self::load($className);
	}

	private static function load($className) {
		if ( class_exists($className) )
			return false;

		$fpath = self::$path . substr($className, 3) . '.php';

		if ( ! @file_exists($fpath) )
			return false;

		include $fpath;

		return true;
	}
}
endif;

scbLoad2::init(__FILE__, array(
	'scbOptions', 'scbForms', 'scbAdminPage', 'scbBoxesPage',
	'scbWidget', 'scbCron', 'scbTable', 'scbUtil', 'scbRewrite',
));

