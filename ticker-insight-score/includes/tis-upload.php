<?php

function tis_upload_page() {
    ?>
    <div class="wrap">
        <h2>Upload CSV</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="tis_csv" accept=".csv">
            <input type="submit" name="upload" class="button button-primary" value="Upload">
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

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Process each row
                $ticker = $row[0];
                $score = $row[1];
                $update_date = date('Y-m-d', strtotime("2024-04-01")); // Example date for Q2 2024

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

