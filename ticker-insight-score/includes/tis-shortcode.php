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
    
    // Check for missing information
    $show_company_name = false;
    $show_market_price = false;

    foreach ($results as $row) {
        if ($row->company_name !== 'N/A' && !empty($row->company_name)) {
            $show_company_name = true;
        }
        if ($row->cached_price !== 'N/A' && !empty($row->cached_price)) {
            $show_market_price = true;
        }
        if ($show_company_name && $show_market_price) {
            break;
        }
    }

    // Build the table header
    $output .= '<thead><tr><th>Ticker</th>';
    if ($show_company_name) {
        $output .= '<th>Company Name</th>';
    }
    $output .= '<th>Inspire Impact Score</th>';
    if ($show_market_price) {
        $output .= '<th>Market Price</th>';
    }
    $output .= '<th>Source</th></tr></thead><tbody>';

    $source_links = [
        'Forbes' => 'https://www.forbes.com/advisor/investing/best-growth-stocks/',
        'Motley Fool' => 'https://www.fool.com/investing/stock-market/types-of-stocks/growth-stocks/'
    ];

    foreach ($results as $row) {
        if (is_numeric($row->score)) {
            $ticker = $row->ticker;
            $company_name = $row->company_name;
            $market_price = $row->cached_price;
            $price_timestamp = $row->price_timestamp;

            $ticker_url = "https://inspireinsight.com/{$ticker}/US";
            $source_link = isset($source_links[$row->source]) ? $source_links[$row->source] : '#';

            $output .= '<tr>';
            $output .= "<td>{$ticker}</td>";
            if ($show_company_name) {
                $output .= "<td>{$company_name}</td>";
            }
            $output .= "<td><a href='{$ticker_url}' target='_blank'>{$row->score}</a></td>";
            if ($show_market_price) {
                $output .= "<td>{$market_price} as of " . date('F j, g:i a', strtotime($price_timestamp)) . "</td>";
            }
            $output .= "<td><a href='{$source_link}' target='_blank'>{$row->source}</a></td>";
            $output .= '</tr>';
        }
    }

    $output .= '</tbody></table>';

    return $output;
}

add_shortcode('growth_stocks', 'tis_growth_stocks_shortcode');
