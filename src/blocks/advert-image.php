<?php
$post_id = get_the_ID();

$background_image = get_field('background_image', $post_id);
$image_placement = get_field('image_placement', $post_id) ?: 'full_overlay';
$words_on_image = get_field('words_on_image', $post_id);
$title = get_field('title', $post_id);
$type_of_ad = get_field('type_of_ad', $post_id);
$subtitle = get_field('sub_title', $post_id);
$date_time_text = get_field('date_time_text', $post_id);
$info_long = get_field('info_long', $post_id, false);
$social_share_buttons = get_field('social_share_buttons', $post_id);
$sign_up_form = get_field('sign_up_form', $post_id);

// Resolve image URL
if (is_array($background_image) && isset($background_image['url'])) {
    $background_image_url = $background_image['url'];
} elseif (is_numeric($background_image)) {
    $background_image_url = wp_get_attachment_image_url($background_image, 'full');
} else {
    $background_image_url = '';
}

// Generate utility class
$image_placement_class = "image-placement--" . sanitize_html_class($image_placement);
?>

<div class="bc-advert-wrapper">
    <div class="advert">
        <div class="advert-image-section <?php echo esc_attr($image_placement_class); ?>"
             style="background-image: url('<?php echo esc_url($background_image_url); ?>');">
        </div>
        <div class="advert-content-section">
            <?php if ($title): ?>
                <h3><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php if ($subtitle): ?>
                <h4><?php echo esc_html($subtitle); ?></h4>
            <?php endif; ?>
            <?php if ($date_time_text): ?>
                <p><?php echo esc_html($date_time_text); ?></p>
            <?php endif; ?>
            <?php if ($info_long): ?>
                <div class="info-long"><?php echo wp_kses_post($info_long); ?></div>
            <?php endif; ?>
            <?php if ($sign_up_form): ?>
                <div class="sign-up-form"><?php echo $sign_up_form; ?></div>
            <?php endif; ?>
            <?php if (!empty($social_share_buttons)): ?>
                <div class="share-buttons"><?php echo $social_share_buttons; ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

