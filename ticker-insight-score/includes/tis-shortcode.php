<?php

function tis_growth_stocks_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'growth_stocks';

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    if (empty($results)) {
        return '<p>No growth stocks found.</p>';
    }

    $output = '<table class="wp-list-table widefat fixed striped">';
    $output .= '<thead><tr><th>Ticker</th><th>Score</th><th>Update Date</th><th>Month/Year</th></tr></thead><tbody>';

    foreach ($results as $row) {
        $output .= '<tr>';
        $output .= "<td>{$row->ticker}</td>";
        $output .= "<td>{$row->score}</td>";
        $output .= "<td>{$row->update_date}</td>";
        $output .= "<td>{$row->month_year}</td>";
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

add_shortcode('growth_stocks', 'tis_growth_stocks_shortcode');