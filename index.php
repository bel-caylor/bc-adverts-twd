<?php
/*
Plugin Name: Hope Church Adverts
Description: Custom adverts block and image‐generator.
Version:     1.0.3
Author:      Belinda Caylor
*/

// Exit early if someone loads this file directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin path/url constants
if ( ! defined( 'BCAD_PLUGIN_PATH' ) ) {
    define( 'BCAD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BCAD_PLUGIN_URL' ) ) {
    define( 'BCAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Frontend: enqueue main CSS/JS for public views.
 */
function bcad_enqueue_public_assets() {
    $css_path = BCAD_PLUGIN_PATH . 'build/assets/main.css';
    $js_path  = BCAD_PLUGIN_PATH . 'build/assets/main.js';

    if ( file_exists( $css_path ) ) {
        wp_enqueue_style(
            'bcad-main-css',
            BCAD_PLUGIN_URL . 'build/assets/main.css',
            [],
            filemtime( $css_path )
        );
    }

    if ( file_exists( $js_path ) ) {
        wp_enqueue_script(
            'bcad-main-js',
            BCAD_PLUGIN_URL . 'build/assets/main.js',
            [],      // no deps on frontend
            filemtime( $js_path ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'bcad_enqueue_public_assets' );

/**
 * Admin (Classic & Gutenberg): enqueue combined bundle and localize BCAdverts data.
 */
function bcad_enqueue_admin_assets() {
    global $pagenow, $post;

    // Only run on edit‐screen of our CPT
    if ( ! in_array( $pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    if ( ! isset( $post ) || $post->post_type !== 'advert' ) {
        return;
    }

    $handle   = 'bcad-admin';
    $js_file  = BCAD_PLUGIN_URL . 'build/assets/admin.js';
    $css_file = BCAD_PLUGIN_URL . 'build/assets/admin.css';
    $ver      = file_exists( BCAD_PLUGIN_PATH . 'build/assets/admin.js' )
                ? filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.js' )
                : null;

    // Register + inline‐data before enqueue
    wp_register_script(
        $handle,
        $js_file,
        [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-components' ],
        $ver,
        true
    );

    wp_add_inline_script(
        $handle,
        'window.BCAdverts=' . wp_json_encode( [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'post_id'         => $post->ID,
            'nonce'           => wp_create_nonce( 'bc_adverts_nonce' ),
            'css_url'         => BCAD_PLUGIN_URL . 'build/assets/main.css',
            'wrapperSelector' => '.ad-image',
        ] ) . ';',
        'before'
    );

    wp_enqueue_script( $handle );

    if ( file_exists( BCAD_PLUGIN_PATH . 'build/assets/admin.css' ) ) {
        wp_enqueue_style(
            'bcad-admin-css',
            $css_file,
            [],
            filemtime( BCAD_PLUGIN_PATH . 'build/assets/admin.css' )
        );
    }
}
// Attach to both the classic meta‐box and block‐editor contexts
add_action( 'admin_enqueue_scripts',          'bcad_enqueue_admin_assets' );
add_action( 'enqueue_block_editor_assets',    'bcad_enqueue_admin_assets' );

/**
 * AJAX handler: generate image with htmlcsstoimage, sideload, set featured.
 */
add_action( 'wp_ajax_bc_generate_advert_image', function() {
    check_ajax_referer( 'bc_adverts_nonce', 'nonce' );

    $post_id = absint( $_POST['post_id'] ?? 0 );
    $html    = wp_unslash( $_POST['html']    ?? '' );
    $css     = wp_unslash( $_POST['css']     ?? '' );

    // Credentials
    $user = trim( get_option( 'bc_adverts_user_id' ) );
    $key  = trim( get_option( 'bc_adverts_api_key' ) );
    if ( ! $user || ! $key ) {
        wp_send_json_error( [ 'message' => 'Missing HCTI credentials.' ] );
    }

    // Clean up any <style> tags & build payload
    $html = preg_replace( '#<style[^>]*>.*?</style>#is', '', $html );
    $body = [ 
        'html' => $html, 
        'css' => $css, 
        'width'  => 1080,
        'height' => 1350,        
    ];

    $response = wp_remote_post(
        'https://hcti.io/v1/image',
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( "$user:$key" ),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => $response->get_error_message() ] );
    }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['url'] ) ) {
        wp_send_json_error( [ 'message' => $data['message'] ?? 'Unknown error' ] );
    }

    // Sideload & set featured image…
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url( esc_url_raw( $data['url'] ) );
    if ( is_wp_error( $tmp ) ) {
        wp_send_json_error( [ 'message' => 'Download failed.' ] );
    }

    $file = [
        'name'     => "advert-{$post_id}.png",
        'tmp_name' => $tmp,
    ];
    add_filter( 'upload_mimes', function( $m ) { $m['png'] = 'image/png'; return $m; } );
    $attach_id = media_handle_sideload( $file, $post_id );
    if ( is_wp_error( $attach_id ) ) {
        @unlink( $tmp );
        wp_send_json_error( [ 'message' => 'Sideload failed.' ] );
    }

    set_post_thumbnail( $post_id, $attach_id );
    wp_send_json_success( [ 'url' => wp_get_attachment_url( $attach_id ) ] );
});

/**
 * Register the ACF block for Advert Image
 */
add_action( 'acf/init', function() {
    if ( function_exists( 'acf_register_block_type' ) ) {
        acf_register_block_type( [
            'name'            => 'advert-image',
            'title'           => __( 'Advert Image' ),
            'render_template' => BCAD_PLUGIN_PATH . 'src/blocks/advert-image.php',
            'category'        => 'formatting',
            'icon'            => 'format-image',
            'keywords'        => [ 'advert','image','background' ],
            'post_types'      => [ 'advert' ],
            'mode'            => 'preview',
            'supports'        => [
                'align' => true,
                'mode' => false
            ],
        ] );
    }
} );

