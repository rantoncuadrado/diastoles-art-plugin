<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?> class="diastoles-privacy-document">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#edf0e8">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'diastoles-privacy-page' ); ?>>
<?php wp_body_open(); ?>
<div class="diastoles-privacy-shell">
	<header class="diastoles-privacy-header">
		<a class="diastoles-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>">Diástoles</a>
	</header>
	<main class="diastoles-privacy-main" id="main-content">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<h1><?php the_title(); ?></h1>
			<article class="diastoles-privacy-content">
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
