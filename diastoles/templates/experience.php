<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?> class="diastoles-experience-document">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#edf0e8">
	<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( DIASTOLES_URL . 'public/images/steps-togetherness.webp?ver=' . DIASTOLES_VERSION ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'diastoles-experience-page' ); ?>>
<?php wp_body_open(); ?>
<main id="main-content">
	<?php echo do_shortcode( '[diastoles_experience]' ); ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
