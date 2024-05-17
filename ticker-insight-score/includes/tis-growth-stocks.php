<?php

function tis_growth_stocks_page() {
    ?>
    <div class="wrap">
        <h2>Fetch Growth Stocks</h2>
        <form method="post">
            <input type="submit" name="fetch_growth_stocks" class="button button-primary" value="Fetch Growth Stocks">
        </form>
    </div>
    <?php

    if (isset($_POST['fetch_growth_stocks'])) {
        tis_fetch_growth_stocks();
    }
}

function tis_fetch_growth_stocks() {
    // Fetch growth stocks from Forbes and Motley Fool
    $forbes_stocks = tis_get_forbes_growth_stocks();
    $motley_fool_stocks = tis_get_motley_fool_growth_stocks();

    // Combine the two lists of stocks
    $all_stocks = array_merge($forbes_stocks, $motley_fool_stocks);

    // Get the inspire scores for each stock and save them
    $growth_stocks = tis_get_inspire_scores($all_stocks);

    // Save the positive growth stocks in the database
    tis_save_positive_growth_stocks($growth_stocks);

    // Display fetched data for debugging
    tis_display_debug_info($all_stocks, $growth_stocks);
}

function tis_get_forbes_growth_stocks() {
    $url = "https://www.forbes.com/advisor/investing/best-growth-stocks/";
    $html = tis_fetch_html($url);

    if (!$html) {
        error_log("Failed to fetch Forbes page.");
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//td[contains(@class, 'wysiwyg-editor')]//div[contains(@class, 'cell-content')]");

    $tickers = [];
    foreach ($nodes as $node) {
        $text = $node->textContent;
        if (strpos($text, '(') !== false && strpos($text, ')') !== false) {
            $parts = explode('(', $text);
            if (isset($parts[1])) {
                $ticker = trim(explode(')', $parts[1])[0]);
                if (!empty($ticker)) {
                    $tickers[] = ['ticker' => $ticker, 'source' => 'Forbes'];
                }
            }
        }
    }

    return $tickers;
}

function tis_get_motley_fool_growth_stocks() {
    $url = "https://www.fool.com/investing/stock-market/types-of-stocks/growth-stocks/";
    $html = tis_fetch_html($url);

    if (!$html) {
        error_log("Failed to fetch Motley Fool page.");
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//table/tbody/tr/th/a");

    $tickers = [];
    foreach ($nodes as $node) {
        $href = $node->getAttribute("href");
        $parts = explode("/", $href);
        if (isset($parts[-2])) {
            $ticker_parts = explode(":", $parts[-2]);
            if (isset($ticker_parts[1])) {
                $ticker = $ticker_parts[1];
                if (!empty($ticker)) {
                    $tickers[] = ['ticker' => $ticker, 'source' => 'Motley Fool'];
                }
            }
        }
    }

    return $tickers;
}

function tis_fetch_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function tis_get_inspire_scores($tickers) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_insight_scores';

    $growth_stocks = [];
    $api_key = get_option('tis_alpha_vantage_api_key');

    foreach ($tickers as $entry) {
        $ticker = $entry['ticker'];
        $source = $entry['source'];
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ticker = %s", $ticker));
        if ($result) {
            $company_info = tis_get_company_info($ticker, $api_key);
            $growth_stocks[] = [
                'ticker' => $ticker,
                'score' => $result->score,
                'update_date' => $result->update_date,
                'source' => $source,
                'company_name' => $company_info['name'],
                'cached_price' => $company_info['price'],
                'price_timestamp' => current_time('mysql')
            ];
        } else {
            // If no score is found, add the ticker with a score of 'Not found'
            $growth_stocks[] = [
                'ticker' => $ticker,
                'score' => 'Not found',
                'update_date' => 'N/A',
                'source' => $source,
                'company_name' => 'N/A',
                'cached_price' => 'N/A',
                'price_timestamp' => 'N/A'
            ];
        }
    }

    return $growth_stocks;
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
            $market_price = number_format((float)$price_data['Global Quote']['05. price'], 2, '.', '');
            $market_price = '$' . $market_price;
        }
    }

    return [
        'name' => $company_name,
        'price' => $market_price
    ];
}

function tis_save_positive_growth_stocks($growth_stocks) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'growth_stocks';

    // Clear out old growth stock information
    $wpdb->query("TRUNCATE TABLE $table_name");

    foreach ($growth_stocks as $stock) {
        if (isset($stock['score']) && is_numeric($stock['score']) && $stock['score'] > 0) {
            // Check if the ticker already exists
            $existing_stock = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ticker = %s", $stock['ticker']));
            if ($existing_stock) {
                // If it exists, update the source if not already included
                $new_source = $existing_stock->source;
                if (strpos($existing_stock->source, $stock['source']) === false) {
                    $new_source .= ', ' . $stock['source'];
                }
                $wpdb->update(
                    $table_name,
                    [
                        'source' => $new_source,
                        'company_name' => $stock['company_name'],
                        'cached_price' => $stock['cached_price'],
                        'price_timestamp' => $stock['price_timestamp']
                    ],
                    ['ticker' => $stock['ticker']]
                );
            } else {
                // If it doesn't exist, insert a new row
                $wpdb->replace($table_name, [
                    'ticker' => $stock['ticker'],
                    'score' => $stock['score'],
                    'update_date' => $stock['update_date'],
                    'month_year' => date('F Y'),
                    'source' => $stock['source'],
                    'company_name' => $stock['company_name'],
                    'cached_price' => $stock['cached_price'],
                    'price_timestamp' => $stock['price_timestamp']
                ]);
            }
        }
    }
}

function tis_display_debug_info($all_stocks, $growth_stocks) {
    echo '<div class="wrap">';
    echo '<h2>Fetched Growth Stocks</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Ticker</th><th>Score</th><th>Source</th></tr></thead><tbody>';

    foreach ($all_stocks as $stock) {
        $score = 'Not found';
        foreach ($growth_stocks as $growth_stock) {
            if ($growth_stock['ticker'] == $stock['ticker']) {
                $score = $growth_stock['score'];
                break;
            }
        }
        $ticker_url = "https://inspireinsight.com/{$stock['ticker']}/US";
        echo '<tr>';
        echo '<td><a href="' . $ticker_url . '" target="_blank">' . $stock['ticker'] . '</a></td>';
        echo '<td>' . $score . '</td>';
        echo '<td>' . $stock['source'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
