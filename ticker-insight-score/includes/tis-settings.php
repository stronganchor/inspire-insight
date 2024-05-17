<?php

function tis_settings_page() {
    ?>
    <div class="wrap">
        <h2>Ticker Insight Score Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('tis_settings_group');
            do_settings_sections('tis-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function tis_register_settings() {
    register_setting('tis_settings_group', 'tis_alpha_vantage_api_key');

    add_settings_section(
        'tis_settings_section',
        'API Settings',
        'tis_settings_section_callback',
        'tis-settings'
    );

    add_settings_field(
        'tis_alpha_vantage_api_key',
        'Alpha Vantage API Key',
        'tis_alpha_vantage_api_key_callback',
        'tis-settings',
        'tis_settings_section'
    );
}

function tis_settings_section_callback() {
    echo 'Enter your API settings below:';
}

function tis_alpha_vantage_api_key_callback() {
    $api_key = get_option('tis_alpha_vantage_api_key');
    echo '<input type="text" name="tis_alpha_vantage_api_key" value="' . esc_attr($api_key) . '" />';
}

add_action('admin_init', 'tis_register_settings');
