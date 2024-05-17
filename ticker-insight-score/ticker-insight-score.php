<?php
/**
 * Plugin Name: Ticker Insight Score
 * Description: A plugin to manage and display stock tickers with their inspire insight scores.
 * Version: 1.0
 * Author: Strong Anchor Tech
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook for adding admin menus
add_action('admin_menu', 'tis_add_admin_pages');

function tis_add_admin_pages() {
    add_menu_page('Ticker Insight Score', 'Ticker Insight Score', 'manage_options', 'tis-main-menu', 'tis_upload_page', 'dashicons-chart-line', 6);
    add_submenu_page('tis-main-menu', 'Upload CSV', 'Upload CSV', 'manage_options', 'tis-upload-spreadsheet', 'tis_upload_page');
    add_submenu_page('tis-main-menu', 'View Tickers', 'View Tickers', 'manage_options', 'tis-view-tickers', 'tis_view_page');
    add_submenu_page('tis-main-menu', 'Add/Edit Ticker', 'Add/Edit Ticker', 'manage_options', 'tis-manual-ticker', 'tis_manual_page');
}

// Include required files
include_once plugin_dir_path(__FILE__) . 'includes/tis-upload.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-view.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-manual.php';

// Register activation hook to create database table
register_activation_hook(__FILE__, 'tis_create_database_table');

function tis_create_database_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_insight_scores';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ticker varchar(10) NOT NULL,
        score float NOT NULL,
        update_date datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'tis_enqueue_scripts');

function tis_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_tis-main-menu' && $hook != 'ticker-insight-score_page_tis-view-tickers') {
        return;
    }

    wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', array('jquery'), null, true);
    wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css');
    wp_enqueue_script('tis-custom', plugin_dir_url(__FILE__) . 'js/tis-custom.js', array('jquery', 'datatables'), null, true);
}