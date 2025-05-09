<?php
/*
Plugin Name: BC Adverts TWD
Description: Custom adverts and image generator.
Version: 1.0.0
Author: Belinda Caylor
*/

// Define Plugin Path Constants
if (!defined('BCAD_PLUGIN_PATH')) {
    define('BCAD_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('BCAD_PLUGIN_URL')) {
    define('BCAD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

function bcad_enqueue_assets() {
    global $pagenow, $post;

    // Only on post edit/new screens, and only for our ‘advert’ CPT
    $is_post_screen = in_array( $pagenow, [ 'post.php', 'post-new.php' ], true );
    if ( ! $is_post_screen || get_post_type( $post ) !== 'advert' ) {
        return;
    }

    $handle    = 'bcad-admin';
    $script    = BCAD_PLUGIN_URL . 'build/assets/admin.js';
    $style     = BCAD_PLUGIN_URL . 'build/assets/admin.css';
    $version   = filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.js' );

    // 1) Register your combined bundle with the proper WP deps
    wp_register_script(
        $handle,
        $script,
        [ 'wp-plugins','wp-edit-post','wp-element','wp-data','wp-components' ],
        $version,
        true
    );

    // 2) Localize / inline our PHP data *before* the bundle executes
    $data = [
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'post_id'         => $post->ID,
        'nonce'           => wp_create_nonce( 'bc_adverts_nonce' ),
        'css_url'         => BCAD_PLUGIN_URL . 'build/assets/admin.css',
        'wrapperSelector' => '.ad-image',
    ];

    // You can use either wp_localize_script() or wp_add_inline_script():
    wp_add_inline_script( 
        $handle, 
        'window.BCAdverts = ' . wp_json_encode( $data ) . ';', 
        'before' 
    );

    // 3) Finally enqueue script + style
    wp_enqueue_script( $handle );
    wp_enqueue_style( 
        'bcad-admin-css', 
        $style, 
        [], 
        filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.css' ) 
    );
}
// hook for classic editor screens
add_action( 'admin_enqueue_scripts', 'bcad_enqueue_assets' );
// hook for the block editor (Gutenberg) sidebar
add_action( 'enqueue_block_editor_assets', 'bcad_enqueue_assets' );



// Register Admin Scripts and Styles
function bcad_admin_enqueue_scripts($hook) {
    global $post;

    if ( ! in_array($hook, ['post.php','post-new.php'], true) || get_post_type($post) !== 'advert' ) {
        return;
    }

    // your existing BCAdverts data
    wp_localize_script('bcad-admin', 'BCAdverts', [
        'ajax_url'        => admin_url('admin-ajax.php'),
        'post_id'         => $post->ID,
        'nonce'           => wp_create_nonce('bc_adverts_nonce'),
        'css_url'         => BCAD_PLUGIN_URL . 'build/assets/admin.css',
        'wrapperSelector' => get_option('bc_adverts_wrapper_selector'), // if you have one
    ]);

    wp_enqueue_script(
        'bcad-admin',
        BCAD_PLUGIN_URL . 'build/assets/admin.js',
        [ 'wp-plugins','wp-edit-post','wp-element','wp-data','wp-components' ],
        filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.js' )
    );
    wp_enqueue_style(
        'bcad-admin-css',
        BCAD_PLUGIN_URL . 'build/assets/admin.css',
        [],
        filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.css' )
    );
}
add_action('admin_enqueue_scripts', 'bcad_admin_enqueue_scripts');



// Enqueue Block Editor Assets for CPT 'advert'
add_action( 'enqueue_block_editor_assets', function() {
    $js_file   = BCAD_PLUGIN_PATH . 'build/assets/editor-sidebar.js';
    $asset_php = BCAD_PLUGIN_PATH . 'build/assets/editor-sidebar.asset.php';
    $css_file  = BCAD_PLUGIN_PATH . 'build/assets/main.css';

    if ( ! file_exists( $js_file ) || ! file_exists( $asset_php ) ) {
        error_log( "🚨 Missing sidebar build files" );
        return;
    }

    // Load JS (as you already have)
    $asset = include $asset_php;
    wp_enqueue_script(
        'bc-adverts-sidebar',
        BCAD_PLUGIN_URL . 'build/assets/editor-sidebar.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_localize_script( 'bc-adverts-sidebar', 'BCAdverts', [
        'post_id'  => get_the_ID(),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'bc_adverts_nonce' ),
    ] );

    // *** NEW: enqueue the editor styles too ***
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'bc-adverts-editor-css',
            BCAD_PLUGIN_URL . 'build/assets/main.css',
            [], 
            filemtime( $css_file )
        );
    } else {
        error_log( "🚨 Missing editor CSS: $css_file" );
    }
} );




// Handle AJAX image generation request
add_action('wp_ajax_bc_generate_advert_image', function () {
    check_ajax_referer('bc_adverts_nonce', 'nonce');

    $post_id = absint($_POST['post_id'] ?? 0);
    $html = wp_unslash($_POST['html'] ?? '');

    $user_id = trim(get_option('bc_adverts_user_id'));
    $api_key = trim(get_option('bc_adverts_api_key'));

    if (!$user_id || !$api_key) {
        wp_send_json_error(['message' => 'Missing HCTI credentials.']);
    }

    $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);

    $css = 'body { font-family: Helvetica Neue, sans-serif; padding: 2rem; margin: 0; background: #ffffff; }';

    $response = wp_remote_post('https://hcti.io/v1/image', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($user_id . ':' . $api_key),
        ],
        'body' => [
            'html' => $html,
            'css' => $css,
        ],
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Image API request failed.']);
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    $image_url = $response_body['url'] ?? '';

    if (!$image_url || !$post_id) {
        wp_send_json_error(['message' => 'Invalid image or post ID.']);
    }

    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) {
        wp_send_json_error(['message' => 'Image download failed.']);
    }

    $file_array = [
        'name' => 'generated-advert.jpg',
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        wp_send_json_error(['message' => 'Media handle failed.']);
    }

    set_post_thumbnail($post_id, $attachment_id);
    wp_send_json_success(['url' => $image_url]);
});


// Register ACF block type for Advert Image
add_action('acf/init', function () {
    if (function_exists('acf_register_block_type')) {
        acf_register_block_type([
            'name' => 'advert-image',
            'title' => __('Advert Image'),
            'description' => __('Displays the Advert title with a background image.'),
            'render_template' => plugin_dir_path(__FILE__) . 'src/blocks/advert-image.php',
            'category' => 'formatting',
            'icon' => 'format-image',
            'keywords' => ['advert', 'image', 'background'],
            'post_types' => ['advert'],
            'mode' => 'preview',
            'supports' => ['align' => true, 'mode' => false],
        ]);
    }
});