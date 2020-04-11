<?php

	/*
		Plugin Name: HrefPrompt
		Text Domain: hrefp
		Description: Fügt der Weiterleitung an externe Seiten eine konfigurierbare Bestätigung hinzu.
		Author: Florian Goetzrath <info@floriangoetzrath.de>
		Version: 1.0.0
		Author URI: https://floriangoetzrath.de/
	*/

	// Check wether this instance is running in a designated WordPress environment

	defined('ABSPATH') or die('This plugin is designed to run in a WordPress plugin environment.');

	// Define Core Constants

	define("HREFP_NAME", "Hyper-Reference-Prompt");
	define("HREFP_VERSION", "1.0.0");

	define('PROJECT_ROOT_PATH', dirname(__FILE__));
	define('PROJECT_URL', plugins_url().'/'.plugin_basename(__DIR__));

	define('CONTROLLER_PATH', PROJECT_ROOT_PATH.'/controller');
	define('LIBRARY_PATH', PROJECT_ROOT_PATH.'/lib');
	define('MODEL_PATH', PROJECT_ROOT_PATH.'/model');
	define('VIEWS_PATH', PROJECT_ROOT_PATH.'/views');
	define('PUBLIC_PATH', VIEWS_PATH.'/public');

	define('WP_URL', site_url());

	define('LIBRARY_URL', PROJECT_URL.'/lib');
	define('PUBLIC_URL', PROJECT_URL.'/views/public');
	define('MEDIA_URL', PUBLIC_URL.'/media');

	// Add options custom to the application

	add_option("confirmation_style", "confirmation");

	// Require Base

	require_once(LIBRARY_PATH.'/exceptions/ErrorHandler.class.php');
	require_once(LIBRARY_PATH.'/functions.php');
	require_once(LIBRARY_PATH.'/db.class.php');

	require_once(MODEL_PATH.'/Message.class.php');

	require_once(CONTROLLER_PATH.'/MainController.class.php');

	// Instantiate Global Classes

	$GLOBALS['hrefp_err'] = new ErrorHandler();
	$GLOBALS['hrefp_db'] = new db();
	$GLOBALS['hrefp_mc'] = new MainController();

	// Queue Functions

	if(WP_DEBUG) activate_debug_mode();

	add_action('init', array(&$GLOBALS['hrefp_mc'], 'init'));
	add_action('admin_menu', array(&$GLOBALS['hrefp_mc'], 'admin_menu'));

