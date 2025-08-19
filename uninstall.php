<?php
/**
 * Uninstall script for CF Login Customizer
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It removes all plugin data and settings.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('cf_login_options');
delete_option('cf_login_security_logs');

// Clear any transients created by the plugin
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cf_login_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cf_login_%'");

// Flush rewrite rules to remove custom login URL
flush_rewrite_rules(); 