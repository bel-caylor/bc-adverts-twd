<?php
/**
 * ACF Block: Advert Image
 *
 * @package YourThemeOrPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Start output buffering
ob_start();

// Allow Gutenberg block classes & alignments
$block_class = isset( $block['className'] ) ? $block['className'] : '';

// Get current post ID
$post_id = get_the_ID();

// Fetch all ACF fields (raw for the image so we can pull alt & ID)
$bg_field             = get_field( 'background_image', $post_id, false );
$image_placement      = get_field( 'image_placement', $post_id ) ?: 'full_overlay';
$title                = get_field( 'title',           $post_id );
$subtitle             = get_field( 'sub_title',       $post_id );
$date_time_text       = get_field( 'date_time_text',  $post_id );
$info_long            = get_field( 'info_long',       $post_id );
$social_share_buttons = get_field( 'social_share_buttons', $post_id );
$sign_up_form         = get_field( 'sign_up_form',    $post_id );
$words_on_image       = get_field( 'words_on_image',  $post_id );
$word_placement       = get_field( 'word_placement',  $post_id ) ?: 'middle';
$type_of_ad           = get_field( 'type_of_ad',      $post_id );
$title_show           = (bool) get_field( 'title_show',    $post_id );
$sub_title_show       = (bool) get_field( 'sub_title_show',$post_id );
$date_show            = (bool) get_field( 'date_show',     $post_id );

// Normalize background image URL, ID & alt text
$bg_url = '';
$bg_alt = '';
$bg_id  = '';

if ( is_array( $bg_field ) && ! empty( $bg_field['url'] ) ) {
    $bg_url = esc_url( $bg_field['url'] );
    $bg_alt = $bg_field['alt'] ?: get_the_title( $post_id );
    $bg_id  = $bg_field['ID'] ?? '';
} elseif ( is_numeric( $bg_field ) ) {
    $bg_id  = intval( $bg_field );
    $url    = wp_get_attachment_image_url( $bg_id, 'full' );
    if ( $url ) {
        $bg_url = esc_url( $url );
        $alt    = get_post_meta( $bg_id, '_wp_attachment_image_alt', true );
        $bg_alt = $alt ?: get_the_title( $post_id );
    }
} elseif ( is_string( $bg_field ) ) {
    $bg_url = esc_url( $bg_field );
    $bg_alt = get_the_title( $post_id );
}

// Map word placement to Tailwind flex classes
switch ( $word_placement ) {
    case 'top':
        $placement_class = 'justify-start';
        break;
    case 'bottom':
        $placement_class = 'justify-end';
        break;
    case 'middle':
    default:
        $placement_class = 'justify-center';
        break;
}

// Choose whether overlay or static text block
$ad_fields_class = $image_placement === 'full_overlay' ? 'overlay-text' : 'ad-text';
?>
<div class="advert <?php echo esc_attr( $block_class ); ?>">
  <div class="wrapper <?php echo esc_attr( $image_placement === 'full_overlay' ? 'flex-col' : 'md:flex-row' ); ?>">
    
    <?php if ( $bg_url ) : ?>
      <?php if ( $words_on_image ) : ?>
        <div class="advert-image-section ad-image <?php echo ($image_placement === 'bottom_contain') ? '!flex-col-reverse' : '' ?>">
          <?php
            // Responsive <img> with lazy-loading & alt text
            $img_class = sprintf(
                'w-full %s object-cover',
                $image_placement === 'full_overlay' ? 'h-full' : 'h-1/2'
              );
            echo wp_get_attachment_image(
              $bg_id,
              'full',
              false,
              [
                'src'     => $bg_url,
                'alt'     => esc_attr( $bg_alt ),
                'loading' => 'lazy',
                'class'   => esc_attr( $img_class ),
              ]
            );
          ?>
          <div class="<?php echo esc_attr( "flex flex-col $ad_fields_class $placement_class items-center text-center p-4" ); ?>">
            <?php if ( $type_of_ad ) : ?>
              <h2 class="ad-type uppercase font-bold"><?php echo esc_html( $type_of_ad ); ?></h2>
            <?php endif; ?>
            <?php if ( $title_show && $title ) : ?>
              <h2 class="ad-title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>
            <?php if ( $sub_title_show && $subtitle ) : ?>
              <p class="ad-sub-title"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
            <?php if ( $date_show && $date_time_text ) : ?>
              <p class="ad-date"><?php echo esc_html( $date_time_text ); ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php else : ?>
        <div
          class="advert-background bg-cover bg-center"
          style="<?php echo esc_attr( 'background-image:url(' . $bg_url . ');' ); ?>">
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="advert-content-section">
        <?php if ( !$words_on_image ) : ?>
            <div class="<?php echo esc_attr( "flex flex-col $ad_fields_class $placement_class items-center text-center p-4" ); ?>">
                <?php if ( $type_of_ad ) : ?>
                <h2 class="ad-type uppercase font-bold"><?php echo esc_html( $type_of_ad ); ?></h2>
                <?php endif; ?>
                <?php if ( $title_show && $title ) : ?>
                <h2 class="ad-title"><?php echo esc_html( $title ); ?></h2>
                <?php endif; ?>
                <?php if ( $sub_title_show && $subtitle ) : ?>
                <p class="ad-sub-title"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
                <?php if ( $date_show && $date_time_text ) : ?>
                <p class="ad-date"><?php echo esc_html( $date_time_text ); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ( $info_long ) : ?>
            <div class="advert-content advert-info">
            <?php echo apply_filters( 'the_content', $info_long ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $social_share_buttons ) : ?>
            <div class="ad-social-share py-4">
            <p class="!mb-0"><strong>Help Us Spread the Word</strong><br>and Share on Your Favorite Social Media</p>
            <?php echo do_shortcode( '[Sassy_Social_Share]' ); ?>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>
<?php
// Output the buffer
echo ob_get_clean();
