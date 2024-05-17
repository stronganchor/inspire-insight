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
    // Implement the logic to fetch growth stocks from Forbes
    // Example: return an array of tickers
    return ['AAPL', 'MSFT', 'GOOGL'];
}

function tis_get_motley_fool_growth_stocks() {
    // Implement the logic to fetch growth stocks from Motley Fool
    // Example: return an array of tickers
    return ['AMZN', 'TSLA', 'FB'];
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

    // Save each growth stock to the database
    foreach ($growth_stocks as $stock) {
        $wpdb->replace($table_name, [
            'ticker' => $stock['ticker'],
            'score' => $stock['score'],
            'update_date' => $stock['update_date'],
            'month_year' => date('F Y')
        ]);
    }
}
