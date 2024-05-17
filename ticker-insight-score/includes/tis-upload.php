<?php

function tis_upload_page() {
    ?>
    <div class="wrap">
        <h2>Upload Spreadsheet</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="tis_spreadsheet" accept=".xlsx">
            <input type="submit" name="upload" class="button button-primary" value="Upload">
        </form>
    </div>
    <?php

    if (isset($_POST['upload'])) {
        tis_handle_upload();
    }
}

function tis_handle_upload() {
    if (!empty($_FILES['tis_spreadsheet']['tmp_name'])) {
        require_once plugin_dir_path(__FILE__) . '../lib/SimpleXLSX.php';

        if ($xlsx = SimpleXLSX::parse($_FILES['tis_spreadsheet']['tmp_name'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ticker_insight_scores';

            foreach ($xlsx->sheetNames() as $sheet_index => $sheet_name) {
                $rows = $xlsx->rows($sheet_index);
                foreach ($rows as $row) {
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
            }
        } else {
            echo SimpleXLSX::parseError();
        }
    }
}
