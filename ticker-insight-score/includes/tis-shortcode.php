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
    $output .= '<thead><tr><th>Ticker</th><th>Inspire Impact Score</th><th>Source</th></tr></thead><tbody>';

    $source_links = [
        'Forbes' => 'https://www.forbes.com/advisor/investing/best-growth-stocks/',
        'Motley Fool' => 'https://www.fool.com/investing/stock-market/types-of-stocks/growth-stocks/'
    ];

    foreach ($results as $row) {
        if (is_numeric($row->score)) {
            $ticker_url = "https://inspireinsight.com/{$row->ticker}/US";
            $source_link = isset($source_links[$row->source]) ? $source_links[$row->source] : '#';

            $output .= '<tr>';
            $output .= "<td>{$row->ticker}</td>";
            $output .= "<td><a href='{$ticker_url}' target='_blank'>{$row->score}</a></td>";
            $output .= "<td><a href='{$source_link}' target='_blank'>{$row->source}</a></td>";
            $output .= '</tr>';
        }
    }

    $output .= '</tbody></table>';

    return $output;
}

add_shortcode('growth_stocks', 'tis_growth_stocks_shortcode');
