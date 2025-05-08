<?php
$post_id = get_the_ID();
$background_image = get_field('background_image', $post_id);
$image_placement = get_field('image_placement', $post_id) ?: 'full_overlay';
$title = get_field('title', $post_id);
$sub_title = get_field('sub_title', $post_id);
$date_time_text = get_field('date_time_text', $post_id);
$info_long = get_field('info_long', $post_id, false);
$social_share_buttons = get_field('social_share_buttons', $post_id);
$sign_up_form = get_field('sign_up_form', $post_id);

// Handle image URL correctly
if (is_array($background_image) && isset($background_image['url'])) {
    // If ACF returns an array, use the 'url' key
    $background_image_url = $background_image['url'];
} elseif (is_numeric($background_image)) {
    // If ACF returns an ID, get the full image URL
    $background_image_url = wp_get_attachment_image_url($background_image, 'full');
} else {
    $background_image_url = '';
}


// Map image placement to class
$image_placement_class = "image-placement--" . $image_placement;
$block_id = 'advert-image-' . $post_id;

// Inject styles as CSS variables
echo "<style>
    :root {
        --advert-background-image: url('{$background_image_url}');
    }
</style>";
?>

<div class="advert">
    <div class="wrap">
        <div id="<?= esc_attr($block_id); ?>" class="advert-image-section ad-image <?php echo esc_attr($image_placement_class); ?>">
            <div class="advert-image-content-section">
                <h2><?= esc_html($sub_title); ?></h2>
                <h3><?= esc_html($title); ?></h3>
                <h4><?= esc_html($date_time_text); ?></h4>
            </div>
        </div>
        <div class="advert-content-section">
            <?php echo apply_filters('the_content', $info_long); ?>
        </div>
    </div>
</div>
