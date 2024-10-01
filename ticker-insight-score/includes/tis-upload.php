<?php

function tis_upload_page() {
    global $wpdb;

    // Clear the ticker database if the button was clicked
    if (isset($_POST['clear_database'])) {
        $table_name = $wpdb->prefix . 'ticker_insight_scores';
        $wpdb->query("TRUNCATE TABLE $table_name");
        echo '<div class="updated"><p>The ticker database has been cleared.</p></div>';
    }

    ?>
    <div class="wrap">
        <h2>Upload CSV</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="tis_csv" accept=".csv">
            <input type="submit" name="upload" class="button button-primary" value="Upload">
        </form>

        <!-- Button to Clear the Ticker Database -->
        <h2>Clear Ticker Database</h2>
        <form method="post">
            <input type="submit" name="clear_database" class="button button-danger" value="Clear Database">
        </form>
    </div>
    <?php

    if (isset($_POST['upload'])) {
        tis_handle_upload();
    }
}

function tis_handle_upload() {
    if (!empty($_FILES['tis_csv']['tmp_name'])) {
        $file = $_FILES['tis_csv']['tmp_name'];
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
                $ticker = $row[$ticker_index];
                $score = $row[$score_index];
                $update_date = date('Y-m-d H:i:s'); // Current date and time

                $wpdb->replace($table_name, [
                    'ticker' => $ticker,
                    'score' => $score,
                    'update_date' => $update_date
                ]);
            }

            fclose($handle);
        } else {
            echo '<div class="error"><p>Failed to open the uploaded CSV file.</p></div>';
        }
    }
}
