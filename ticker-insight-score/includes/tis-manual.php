<?php

function tis_manual_page() {
    ?>
    <div class="wrap">
        <h2>Add/Edit Ticker</h2>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Ticker</th>
                    <td><input type="text" name="ticker" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Score</th>
                    <td><input type="number" step="0.01" name="score" required /></td>
                </tr>
            </table>
            <input type="submit" name="save_ticker" class="button button-primary" value="Save Ticker">
        </form>
    </div>
    <?php

    if (isset($_POST['save_ticker'])) {
        tis_handle_manual_save();
    }
}

function tis_handle_manual_save() {
    if (!empty($_POST['ticker']) && !empty($_POST['score'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ticker_insight_scores';

        $ticker = sanitize_text_field($_POST['ticker']);
        $score = floatval($_POST['score']);
        $update_date = date('Y-m-d H:i:s'); // Current date and time

        $wpdb->replace($table_name, [
            'ticker' => $ticker,
            'score' => $score,
            'update_date' => $update_date
        ]);

        echo '<div class="updated"><p>Ticker saved successfully.</p></div>';
    } else {
        echo '<div class="error"><p>Please fill in all fields.</p></div>';
    }
}
