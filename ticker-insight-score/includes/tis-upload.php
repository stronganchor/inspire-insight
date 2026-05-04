<?php

function tis_upload_page() {
    global $wpdb;

    // Clear the ticker database if the button was clicked
    if (isset($_POST['clear_database'])) {
        check_admin_referer('tis_clear_database', 'tis_clear_database_nonce');
        $table_name = $wpdb->prefix . 'ticker_insight_scores';
        $wpdb->query("TRUNCATE TABLE $table_name");
        echo '<div class="updated"><p>The ticker database has been cleared.</p></div>';
    }

    ?>
    <div class="wrap">
        <h2>Upload CSV</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('tis_upload_csv', 'tis_upload_nonce'); ?>
            <input type="file" name="tis_csv" accept=".csv">
            <input type="submit" name="upload" class="button button-primary" value="Upload">
        </form>

        <!-- Button to Clear the Ticker Database -->
        <h2>Clear Ticker Database</h2>
        <form method="post">
            <?php wp_nonce_field('tis_clear_database', 'tis_clear_database_nonce'); ?>
            <input type="submit" name="clear_database" class="button button-danger" value="Clear Database">
        </form>
    </div>
    <?php

    if (isset($_POST['upload'])) {
        check_admin_referer('tis_upload_csv', 'tis_upload_nonce');
        tis_handle_upload();
    }
}

function tis_handle_upload() {
    if (!empty($_FILES['tis_csv']['tmp_name'])) {
        $file = sanitize_text_field(wp_unslash($_FILES['tis_csv']['tmp_name']));
        $name = isset($_FILES['tis_csv']['name']) ? sanitize_file_name(wp_unslash($_FILES['tis_csv']['name'])) : '';

        if (!is_uploaded_file($file) || 'csv' !== strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            echo '<div class="error"><p>Please upload a valid CSV file.</p></div>';
            return;
        }

        $handle = fopen($file, 'r');

        if ($handle !== FALSE) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ticker_insight_scores';

            // Get the header row to determine the indices of the required columns
            $header = fgetcsv($handle, 1000, ',');

            // Normalize the header row
            $normalized_header = array_map('strtolower', array_map('trim', $header));

            $ticker_index = array_search('ticker', $normalized_header);
            $score_index = array_search('upcoming score', $normalized_header);

            if ($ticker_index === FALSE || $score_index === FALSE) {
                echo '<div class="error"><p>CSV file does not have the required columns: ticker and upcoming score.</p></div>';
                fclose($handle);
                return;
            }

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $ticker = sanitize_text_field($row[$ticker_index]);
                $score = floatval($row[$score_index]);
                $update_date = date('Y-m-d H:i:s'); // Current date and time

                $wpdb->replace($table_name, [
                    'ticker' => $ticker,
                    'score' => $score,
                    'update_date' => $update_date
                ], ['%s', '%f', '%s']);
            }

            fclose($handle);
        } else {
            echo '<div class="error"><p>Failed to open the uploaded CSV file.</p></div>';
        }
    }
}
