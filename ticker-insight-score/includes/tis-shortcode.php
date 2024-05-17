<?php

function tis_growth_stocks_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'growth_stocks';

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($results)) {
        return '<p>No growth stocks found.</p>';
    }

    $current_month_year = date('F Y');
    $output = '<h2>Recommended Growth Stocks for ' . $current_month_year . '</h2>';
    $output .= '<table class="wp-list-table widefat fixed striped">';
    $output .= '<thead><tr><th>Ticker</th><th>Score</th><th>Source</th></tr></thead><tbody>';

    foreach ($results as $row) {
        if ($row->score != 'Not found') {
            $ticker_url = "https://inspireinsight.com/{$row->ticker}/US";
            $output .= '<tr>';
            $output .= "<td><a href='{$ticker_url}' target='_blank'>{$row->ticker}</a></td>";
            $output .= "<td>{$row->score}</td>";
            $output .= "<td>{$row->source}</td>";
            $output .= '</tr>';
        }
    }

    $output .= '</tbody></table>';

    return $output;
}

add_shortcode('growth_stocks', 'tis_growth_stocks_shortcode');
