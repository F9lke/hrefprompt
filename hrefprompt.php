<?php

	/*
		Plugin Name: HrefPrompt
		Text Domain: hrefp
		Description: Adds an additional layer of configurable confirmation to external site redirection.
		Author: Florian Goetzrath <info@floriangoetzrath.de>
		Version: 1.0.6
		Author URI: https://floriangoetzrath.de/
	*/

	// Check wether this instance is running in a designated WordPress environment

	defined('ABSPATH') or die('This plugin is designed to run in a WordPress plugin environment.');

	// Define Core Constants

	define("HREFP_NAME", "Hyper-Reference-Prompt");
	define("HREFP_VERSION", "1.0.6");

	define('HREFP_ROOT_PATH', dirname(__FILE__));
	define('HREFP_URL', plugins_url().'/'.plugin_basename(__DIR__));

	define('HREFP_CONTROLLER_PATH', HREFP_ROOT_PATH.'/controller');
	define('HREFP_LIBRARY_PATH', HREFP_ROOT_PATH.'/lib');
	define('HREFP_LOG_PATH', HREFP_ROOT_PATH.'/logs');
	define('HREFP_MODEL_PATH', HREFP_ROOT_PATH.'/model');
	define('HREFP_VIEWS_PATH', HREFP_ROOT_PATH.'/views');
	define('HREFP_PUBLIC_PATH', HREFP_VIEWS_PATH.'/public');

	define('HREFP_WP_URL', site_url());

	define('HREFP_LIBRARY_URL', HREFP_URL.'/lib');
	define('HREFP_PUBLIC_URL', HREFP_URL.'/views/public');
	define('HREFP_MEDIA_URL', HREFP_PUBLIC_URL.'/media');

	// Add options custom to the application

	add_option("hrefp_confirmation_style", "confirmation");

	// Require Base

	require_once(HREFP_LIBRARY_PATH.'/exceptions/ErrorHandler.class.php');
	require_once(HREFP_LIBRARY_PATH.'/functions.php');
	require_once(HREFP_LIBRARY_PATH.'/db.class.php');

	require_once(HREFP_MODEL_PATH.'/Message.class.php');

	require_once(HREFP_CONTROLLER_PATH.'/MainController.class.php');

	// Instantiate Global Classes

	$GLOBALS['hrefp_err'] = new hrefp_ErrorHandler();
	$GLOBALS['hrefp_db'] = new hrefp_db();
	$GLOBALS['hrefp_mc'] = new hrefp_MainController();

	// Queue Functions

	if(WP_DEBUG) hrefp_activate_debug_mode();

	add_action('init', array(&$GLOBALS['hrefp_mc'], 'init'));
	add_action('admin_menu', array(&$GLOBALS['hrefp_mc'], 'admin_menu'));

