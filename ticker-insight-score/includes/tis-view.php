<?php

function tis_view_page() {
    ?>
    <div class="wrap">
        <h2>View Tickers</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Ticker</th>
                    <th>Score</th>
                    <th>Update Date</th>
                </tr>
            </thead>
            <tbody>
                <?php tis_display_tickers(); ?>
            </tbody>
        </table>
    </div>
    <?php
}

function tis_display_tickers() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_insight_scores';

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row->ticker}</td>";
        echo "<td>{$row->score}</td>";
        echo "<td>{$row->update_date}</td>";
        echo "</tr>";
    }
}
