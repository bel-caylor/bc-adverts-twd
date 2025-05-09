<?php
/*
Plugin Name: BC Adverts TWD
Description: Custom adverts and image generator.
Version: 1.0.0
Author: Your Name
*/

// Define Plugin Path Constants
if (!defined('BCAD_PLUGIN_PATH')) {
    define('BCAD_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('BCAD_PLUGIN_URL')) {
    define('BCAD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Register Frontend Scripts and Styles
function bcad_enqueue_scripts() {
    $main_css_path = BCAD_PLUGIN_PATH . 'build/assets/main.css';
    $main_css_url = BCAD_PLUGIN_URL . 'build/assets/main.css';
    $main_js_path = BCAD_PLUGIN_PATH . 'build/assets/main.js';
    $main_js_url = BCAD_PLUGIN_URL . 'build/assets/main.js';

    // Main CSS
    if (file_exists($main_css_path)) {
        wp_enqueue_style(
            'bcad-main-css',
            $main_css_url,
            [],
            filemtime($main_css_path)
        );
    } else {
        error_log("🚨 Warning: Main CSS not found at $main_css_path");
    }

    // Main JS
    if (file_exists($main_js_path)) {
        wp_enqueue_script(
            'bcad-main-js',
            $main_js_url,
            [],
            filemtime($main_js_path),
            true
        );
    } else {
        error_log("🚨 Warning: Main JS not found at $main_js_path");
    }
}
add_action('wp_enqueue_scripts', 'bcad_enqueue_scripts');


// Register Admin Scripts and Styles
function bcad_admin_enqueue_scripts($hook) {
    global $post;

    $admin_css_path = BCAD_PLUGIN_PATH . 'build/assets/admin.css';
    $admin_css_url = BCAD_PLUGIN_URL . 'build/assets/admin.css';
    $admin_js_path = BCAD_PLUGIN_PATH . 'build/assets/admin.js';
    $admin_js_url = BCAD_PLUGIN_URL . 'build/assets/admin.js';
    $sidebar_js_path = BCAD_PLUGIN_PATH . 'build/assets/editor-sidebar.js';
    $sidebar_js_url = BCAD_PLUGIN_URL . 'build/assets/editor-sidebar.js';

    // Admin CSS
    if (file_exists($admin_css_path)) {
        wp_enqueue_style(
            'bcad-admin-css',
            $admin_css_url,
            [],
            filemtime($admin_css_path)
        );
    } else {
        error_log("🚨 Warning: Admin CSS not found at $admin_css_path");
    }

    // Admin JS
    if (file_exists($admin_js_path)) {
        wp_enqueue_script(
            'bcad-admin-js',
            $admin_js_url,
            ['jquery'],
            filemtime($admin_js_path),
            true
        );
    } else {
        error_log("🚨 Warning: Admin JS not found at $admin_js_path");
    }

    // Sidebar JS
    if (file_exists($sidebar_js_path)) {
        wp_enqueue_script(
            'bcad-sidebar-js',
            $sidebar_js_url,
            ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'],
            filemtime($sidebar_js_path),
            true
        );
    } else {
        error_log("🚨 Warning: Sidebar JS not found at $sidebar_js_path");
    }

    // Enqueue Advert-specific scripts
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post && $post->post_type === 'advert') {
        $generator_js_path = BCAD_PLUGIN_PATH . 'build/assets/generator.js';
        $generator_js_url = BCAD_PLUGIN_URL . 'build/assets/generator.js';

        if (file_exists($generator_js_path)) {
            wp_enqueue_script(
                'bc-adverts-image-generator',
                $generator_js_url,
                ['jquery'],
                filemtime($generator_js_path),
                true
            );

            wp_localize_script('bc-adverts-image-generator', 'BCAdverts', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bc_adverts_nonce'),
                'post_id' => get_the_ID(),
            ]);
        } else {
            error_log("🚨 Warning: Image Generator JS not found at $generator_js_path");
        }
    }
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