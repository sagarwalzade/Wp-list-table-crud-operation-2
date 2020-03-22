<?php

/*
  Plugin Name: Locations plugin
  description: A plugin to add countries, states and cities data to custom database tables.
  Version: 1.0.0
  Author: Sagar Walzade
  Author URI: http://sagarwalzade.com/
  Text Domain:  swlp
 */

/*
 * Define Constants 
 */

define('SWLP_FILE', __FILE__);
define('SWLP_PATH', plugin_dir_path(__FILE__));
define('SWLP_URI', plugin_dir_url(__FILE__));
define('SWLP_PLUGIN_NAME', plugin_basename(__FILE__));

/**
 * do some default settings when plugin activate
 * create 3 custom sql table to store countries, states and cities with relations to each other.
 */

if (!function_exists("swlp_default_settings_callback")) {

	register_activation_hook(SWLP_FILE, 'swlp_default_settings_callback');

	function swlp_default_settings_callback() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// table 1
		$table_name1 = $wpdb->prefix . 'swlp_country';

		$table_sql1 = "CREATE TABLE IF NOT EXISTS $table_name1 (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		country text DEFAULT '' NOT NULL,
		created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY id (id) ) $charset_collate;";

		// table 2
		$table_name2 = $wpdb->prefix . 'swlp_state';

		$table_sql2 = "CREATE TABLE IF NOT EXISTS $table_name2 (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		state text DEFAULT '' NOT NULL,
		country_id bigint(20) NOT NULL,
		created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY id (id) ) $charset_collate;";

		// table 3
		$table_name3 = $wpdb->prefix . 'swlp_city';

		$table_sql3 = "CREATE TABLE IF NOT EXISTS $table_name3 (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		city text DEFAULT '' NOT NULL,
		state_id bigint(20) NOT NULL,
		created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY id (id) ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($table_sql1);
		dbDelta($table_sql2);
		dbDelta($table_sql3);
	}

}

if (!function_exists("swlp_enqueue_scripts_callback")) {

	function swlp_enqueue_scripts_callback() {
		wp_enqueue_style('swlp_css', plugins_url('assets/swlp_style.css', __FILE__));
		wp_register_script( "swlp_ajax", plugins_url('assets/swlp_script.js', __FILE__), array('jquery') );
		wp_localize_script( 'swlp_ajax', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'swlp_ajax' );
	}

	add_action('admin_enqueue_scripts', 'swlp_enqueue_scripts_callback', 501);

}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/*
 * Include Files 
 */

include 'admin/country.php';
include 'admin/state.php';
include 'admin/city.php';