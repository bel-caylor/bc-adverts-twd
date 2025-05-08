<?php


add_action('admin_menu', function() {
    add_options_page(
        'BC Adverts Settings',
        'BC Adverts',
        'manage_options',
        'bc-adverts-settings',
        'bc_adverts_settings_page'
    );
});

function bc_adverts_settings_page() {
    ?>
    <div class="wrap">
        <h1>BC Adverts Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('bc_adverts_settings');
                do_settings_sections('bc-adverts-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}


add_action('admin_init', function() {
    register_setting('bc_adverts_settings', 'bc_adverts_user_id');
    register_setting('bc_adverts_settings', 'bc_adverts_api_key');

    add_settings_section('bc_adverts_main', 'API Configuration', null, 'bc-adverts-settings');

    add_settings_field(
        'bc_adverts_user_id',
        'htmlcsstoimage.com User ID',
        function() {
            $value = esc_attr(get_option('bc_adverts_user_id'));
            echo "<input type='text' name='bc_adverts_user_id' value='$value' class='regular-text'>";
        },
        'bc-adverts-settings',
        'bc_adverts_main'
    );

    add_settings_field(
        'bc_adverts_api_key',
        'htmlcsstoimage.com API Key',
        function() {
            $value = esc_attr(get_option('bc_adverts_api_key'));
            echo "<input type='text' name='bc_adverts_api_key' value='$value' class='regular-text'>";
        },
        'bc-adverts-settings',
        'bc_adverts_main'
    );
});

