<?php
$post_id = get_the_ID();

$background_image = get_field('background_image', $post_id);
$image_placement = get_field('image_placement', $post_id) ?: 'full_overlay';
$title = get_field('title', $post_id);
$subtitle = get_field('sub_title', $post_id);
$date_time_text = get_field('date_time_text', $post_id);
$info_long = get_field('info_long', $post_id, false);
$social_share_buttons = get_field('social_share_buttons', $post_id);
$sign_up_form = get_field('sign_up_form', $post_id);
$words_on_image = get_field('words_on_image', $post_id);
$word_placement = get_field('word_placement', $post_id);

// Debugging logs
error_log(print_r($background_image, true));
error_log(print_r($word_placement, true));

// Validate the background image
if (is_array($background_image)) {
    if (isset($background_image['url']) && is_string($background_image['url'])) {
        $background_image = $background_image['url'];
    } else {
        $background_image = '';
    }
} elseif (is_numeric($background_image)) {
    $background_image = wp_get_attachment_image_url($background_image, 'full');
} elseif (is_string($background_image)) {
    $background_image = esc_url($background_image);
} else {
    $background_image = '';
}
$inline_bg = $background_image
  ? '--bcad-bg-url: url(' . esc_url( $background_image ) . ');'
  : '';

// Map word_placement to Tailwind classes
$tailwind_placement = match ($word_placement) {
    'top' => 'justify-start',
    'middle' => 'justify-center',
    'bottom' => 'justify-end',
    default => 'justify-center',
};

// Start output buffering
ob_start();

// Main advert wrapper
// Display Editor Overlay
if (is_admin()) {
    echo '<div class="editor-overlay w-full bg-red-700 py-4 mb-2">';
    echo '<button id="bcad-generate-btn" class="button button-primary" style="margin-left:1em;">Generate Image</button>';
    echo '</div>';
    echo '<div id="bc-image-output"></div>';
}
echo '<div class="advert">';
    echo '<div class="wrapper">';

        // Advert Image Section
        echo '<div class="advert-image-section ad-image">';
        if ($words_on_image) {
            // Words on Image Logic
            if ($image_placement === 'full_overlay') {
                echo '<div class="advert-background" style="background-image: url(' . $background_image . ');">';
                echo '<div class="overlay-text ' . $tailwind_placement . '">';
                echo '<h1>' . esc_html($title) . '</h1>';
                echo '<p>' . esc_html($date_time_text) . '</p>';
                echo '</div></div>';
            } elseif ($image_placement === 'top_contain') {
                echo '<div class="image-top">';
                    echo '<img src="' . $background_image . '" alt="">';
                    echo '<h1>' . esc_html($title) . '</h1>';
                    echo '<p>' . esc_html($date_time_text) . '</p>';
                echo '</div>';
            } elseif ($image_placement === 'bottom_contain') {
                echo '<div class="image-bottom">';
                    echo '<h1>' . esc_html($title) . '</h1>';
                    echo '<p>' . esc_html($date_time_text) . '</p>';
                    echo '<img src="' . $background_image . '" alt="">';
                echo '</div>';
            }
        } else {
            // Image without words on image logic
            echo '<div class="advert-background" style="background-image: url(' . $background_image . ');"></div>';
        }

        echo '</div>'; // End of advert-image-section

        // Advert Content Section
        echo '<div class="advert-content-section">';
        if ($words_on_image) {
            echo '<div class="advert-content">' . $info_long . '</div>';
        } else {
            echo '<h1>' . esc_html($title) . '</h1>';
            echo '<h2>' . esc_html($subtitle) . '</h2>';
            echo '<p>' . esc_html($date_time_text) . '</p>';
            echo '<div class="advert-content">' . $info_long . '</div>';
        }
        echo '</div>'; // End of advert-content-section

    echo '</div>'; // End of wrapper
echo '</div>'; // End of advert

// Flush output
$output = ob_get_clean();
echo $output;

