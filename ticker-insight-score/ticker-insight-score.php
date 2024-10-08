<?php
/**
 * Plugin Name: Ticker Insight Score
 * Description: A plugin to manage and display stock tickers with their inspire insight scores.
 * Version: 1.1
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
    add_submenu_page('tis-main-menu', 'Fetch Growth Stocks', 'Fetch Growth Stocks', 'manage_options', 'tis-growth-stocks', 'tis_growth_stocks_page');
    add_submenu_page('tis-main-menu', 'Settings', 'Settings', 'manage_options', 'tis-settings', 'tis_settings_page');
    add_submenu_page('tis-main-menu', 'Filter Tickers', 'Filter Tickers', 'manage_options', 'tis-filter-tickers', 'tis_filter_tickers_page'); // New submenu page for filtering tickers
}

// Include required files
include_once plugin_dir_path(__FILE__) . 'includes/tis-upload.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-view.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-manual.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-growth-stocks.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-shortcode.php';
include_once plugin_dir_path(__FILE__) . 'includes/tis-settings.php';

function tis_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for ticker insight scores
    $table_name = $wpdb->prefix . 'ticker_insight_scores';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ticker varchar(10) NOT NULL,
        score float NOT NULL,
        update_date datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Table for growth stocks
    $table_name = $wpdb->prefix . 'growth_stocks';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ticker varchar(10) NOT NULL,
        score float NOT NULL,
        update_date datetime NOT NULL,
        month_year varchar(20) NOT NULL,
        source varchar(255) NOT NULL,
        company_name varchar(255) DEFAULT 'N/A',
        cached_price varchar(20) DEFAULT 'N/A',
        price_timestamp datetime DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);
}

// Ensure this is run during plugin activation
register_activation_hook(__FILE__, 'tis_create_database_tables');

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'tis_enqueue_scripts');

function tis_enqueue_scripts($hook) {
    if ($hook == 'toplevel_page_tis-main-menu' || $hook == 'ticker-insight-score_page_tis-view-tickers' || $hook == 'ticker-insight-score_page_tis-filter-tickers') {
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', array('jquery'), null, true);
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css');
        wp_enqueue_script('tis-custom', plugin_dir_url(__FILE__) . 'js/tis-custom.js', array('jquery', 'datatables'), null, true);
    }
}

// New function to display the filter tickers page
function tis_filter_tickers_page() {
    global $wpdb;
    
    // Process form submission
    if (isset($_POST['ticker_list'])) {
        $tickers = explode(',', sanitize_text_field($_POST['ticker_list']));
        $tickers = array_map('trim', $tickers);
        
        // Prepare SQL query to get positive scores
        $table_name = $wpdb->prefix . 'ticker_insight_scores';
        $placeholders = implode(',', array_fill(0, count($tickers), '%s'));
        $sql = $wpdb->prepare(
            "SELECT ticker, score FROM $table_name WHERE score > 0 AND ticker IN ($placeholders)",
            $tickers
        );
        
        $results = $wpdb->get_results($sql);
    }
    
    ?>
    <div class="wrap">
        <h1>Filter Tickers with Positive Scores</h1>
        <form method="post">
            <label for="ticker_list">Enter a list of comma-separated tickers:</label>
            <input type="text" name="ticker_list" id="ticker_list" style="width: 100%;" required>
            <br><br>
            <input type="submit" class="button button-primary" value="Filter Tickers">
        </form>

        <?php if (isset($results) && !empty($results)) : ?>
            <h2>Tickers with Positive Scores</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->ticker); ?></td>
                            <td><?php echo esc_html($row->score); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($results)) : ?>
            <p>No tickers with positive scores found.</p>
        <?php endif; ?>
    </div>
    <?php
}
