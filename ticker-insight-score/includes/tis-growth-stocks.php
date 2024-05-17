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

    // Get the inspire scores for each stock
    $growth_stocks = tis_get_inspire_scores($all_stocks);

    // Save the information in the database
    tis_save_growth_stocks($growth_stocks);
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
            $ticker = trim(explode('(', explode(')', $text)[0])[1]);
            $tickers[] = $ticker;
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
        $ticker = explode(":", explode("/", $href)[-2])[1];
        $tickers[] = $ticker;
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
    foreach ($tickers as $ticker) {
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ticker = %s", $ticker));
        if ($result) {
            $growth_stocks[] = [
                'ticker' => $ticker,
                'score' => $result->score,
                'update_date' => $result->update_date
            ];
        }
    }

    return $growth_stocks;
}

function tis_save_growth_stocks($growth_stocks) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'growth_stocks';

    foreach ($growth_stocks as $stock) {
        $result = $wpdb->replace($table_name, [
            'ticker' => $stock['ticker'],
            'score' => $stock['score'],
            'update_date' => $stock['update_date'],
            'month_year' => date('F Y')
        ]);

        if ($result === false) {
            error_log("Failed to insert/update growth stock: {$stock['ticker']}");
        }
    }
}
