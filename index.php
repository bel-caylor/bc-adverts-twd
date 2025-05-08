<?php
/*
Plugin Name: Hope Church Adverts, Events, and Signups
Description: Streamlines advert/event creation with FB image generation and sign-up form handling.
Version: 1.0
Author: Belinda Caylor
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('BCAD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BCAD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core plugin files
require_once BCAD_PLUGIN_PATH . 'includes/admin.php';

// Register Scripts and Styles
function bcad_enqueue_scripts() {
    wp_enqueue_style(
        'tailwind-css',
        BCAD_PLUGIN_URL . 'build/main.css',
        [],
        filemtime(BCAD_PLUGIN_PATH . 'build/main.css')
    );

    wp_enqueue_script(
        'tailwind-js',
        BCAD_PLUGIN_URL . 'build/main.js',
        [],
        filemtime(BCAD_PLUGIN_PATH . 'build/main.js'),
        true
    );
}

add_action('wp_enqueue_scripts', 'bcad_enqueue_scripts');

// Admin Scripts
function bcad_admin_enqueue_scripts($hook) {
    global $post;

    $admin_css_path = BCAD_PLUGIN_PATH . 'build/admin.css';
    $admin_css_url = BCAD_PLUGIN_URL . 'build/admin.css';
    
    if (file_exists($admin_css_path)) {
        wp_enqueue_style(
            'tailwind-admin-css',
            $admin_css_url,
            [],
            filemtime($admin_css_path)
        );
    } else {
        error_log("🚨 Warning: Admin CSS not found at $admin_css_path");
    }    

    wp_enqueue_script(
        'tailwind-admin-js',
        BCAD_PLUGIN_URL . 'build/admin.js',
        ['jquery'],
        filemtime(BCAD_PLUGIN_PATH . 'build/admin.js'),
        true
    );

    if (($hook === 'post.php' || $hook === 'post-new.php') && $post && $post->post_type === 'advert') {
        wp_enqueue_script(
            'bc-adverts-image-generator',
            BCAD_PLUGIN_URL . 'assets/generator.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('bc-adverts-image-generator', 'BCAdverts', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bc_adverts_nonce'),
            'post_id' => get_the_ID(),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'bcad_admin_enqueue_scripts');

// Enqueue Block Editor Assets for CPT 'advert'
add_action('enqueue_block_editor_assets', function () {
    global $post;
    if (!$post || $post->post_type !== 'advert') return;

    wp_enqueue_script(
        'bc-adverts-sidebar',
        BCAD_PLUGIN_URL . 'build/editor-sidebar.js',
        ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'],
        filemtime(BCAD_PLUGIN_PATH . 'build/editor-sidebar.js'),
        true
    );

    wp_localize_script('bc-adverts-sidebar', 'BCAdverts', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bc_adverts_nonce'),
        'post_id' => get_the_ID(),
    ]);
});

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