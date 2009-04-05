<?php

$GLOBALS['scbPath'] = dirname(__FILE__);

if ( !function_exists('scb_autoload') ) :
function scb_autoload($className) {
	// PHP < 5.1
	if ( class_exists($className) )
		return false;

	if ( substr($className, 0, 3) != 'scb' )
		return false;

	$fname = $GLOBALS['scbPath'] . DIRECTORY_SEPARATOR . substr($className, 3) . '.php';

	if ( ! @file_exists($fname) )
		return false;

	include_once($fname);
	return true;
}
endif;

if ( function_exists('spl_autoload_register') )
	spl_autoload_register('scb_autoload');
else
	// Load all classes manually (PHP < 5.1)
	foreach ( array('scbForms', 'scbOptions', 'scbOptionsPage', 'scbBoxesPage', 'scbWidget') as $class )
		scb_autoload($class);
