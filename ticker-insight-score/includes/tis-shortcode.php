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
    $output .= '<thead><tr><th>Ticker</th><th>Company Name</th><th>Inspire Impact Score</th><th>Market Price</th><th>Source</th></tr></thead><tbody>';

    $source_links = [
        'Forbes' => 'https://www.forbes.com/advisor/investing/best-growth-stocks/',
        'Motley Fool' => 'https://www.fool.com/investing/stock-market/types-of-stocks/growth-stocks/'
    ];

    $api_key = get_option('tis_alpha_vantage_api_key');

    foreach ($results as $row) {
        if (is_numeric($row->score)) {
            $ticker = $row->ticker;
            $company_info = tis_get_company_info($ticker, $api_key);
            $company_name = $company_info['name'];
            $market_price = $company_info['price'];

            $ticker_url = "https://inspireinsight.com/{$ticker}/US";
            $source_link = isset($source_links[$row->source]) ? $source_links[$row->source] : '#';

            $output .= '<tr>';
            $output .= "<td>{$ticker}</td>";
            $output .= "<td>{$company_name}</td>";
            $output .= "<td><a href='{$ticker_url}' target='_blank'>{$row->score}</a></td>";
            $output .= "<td>{$market_price}</td>";
            $output .= "<td><a href='{$source_link}' target='_blank'>{$row->source}</a></td>";
            $output .= '</tr>';
        }
    }

    $output .= '</tbody></table>';

    return $output;
}

function tis_get_company_info($ticker, $api_key) {
    $company_name = 'N/A';
    $market_price = 'N/A';

    // Fetch company name
    $name_url = "https://www.alphavantage.co/query?function=OVERVIEW&symbol={$ticker}&apikey={$api_key}";
    $name_response = file_get_contents($name_url);
    if ($name_response) {
        $name_data = json_decode($name_response, true);
        if (isset($name_data['Name'])) {
            $company_name = $name_data['Name'];
        }
    }

    // Fetch market price
    $price_url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$ticker}&apikey={$api_key}";
    $price_response = file_get_contents($price_url);
    if ($price_response) {
        $price_data = json_decode($price_response, true);
        if (isset($price_data['Global Quote']['05. price'])) {
            $market_price = $price_data['Global Quote']['05. price'];
        }
    }

    return [
        'name' => $company_name,
        'price' => $market_price
    ];
}

add_shortcode('growth_stocks', 'tis_growth_stocks_shortcode');
