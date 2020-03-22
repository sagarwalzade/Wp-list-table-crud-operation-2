<?php

/**
 * Uninstalling database tables created by plugin.
 */

global $wpdb;
$table_name1 = $wpdb->prefix . 'swlp_country';
$wpdb->query('DROP TABLE IF EXISTS ' . $table_name1);

$table_name2 = $wpdb->prefix . 'swlp_state';
$wpdb->query('DROP TABLE IF EXISTS ' . $table_name2);

$table_name3 = $wpdb->prefix . 'swlp_city';
$wpdb->query('DROP TABLE IF EXISTS ' . $table_name3);