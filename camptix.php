<?php
/*
Plugin Name: Gravatar Badge Creation
Plugin URI: https://github.com/devenbansod/Camptix---Automatic-Gravatar-Fetch-and-Export
Description: Automate Gravatar Fetching for Badge Creation in InDesign - an Addon for CampTix 
Version: 0.1
Author: Deven Bansod
Author URI: http://www.facebook.com/bansoddeven
License: GPL2
*/

define( 'FETCH_GRAVATARS_PLUGIN_URL' ,  plugin_dir_path( __FILE__ ) );

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Load the Addon Class for Gravatar Fetch and Export
add_action( 'camptix_load_addons', 'camptix_gravatar_fetch_and_export_method' );
function camptix_gravatar_fetch_and_export_method() {
	if ( ! class_exists( 'CampTix_Addon_Gravatar_Fetch' ) )
		require_once FETCH_GRAVATARS_PLUGIN_URL . 'classes/fetch-export-gravatars.php';
	camptix_register_addon( 'CampTix_Addon_Gravatar_Fetch' );
}

