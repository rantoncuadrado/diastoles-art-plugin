<?php
/**
 * Plugin Name: Diástoles
 * Description: A multilingual, anonymous experience that connects human memories through smells.
 * Version: 0.15.46
 * Author: Diastoles
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: diastoles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIASTOLES_VERSION', '0.15.46' );
define( 'DIASTOLES_FILE', __FILE__ );
define( 'DIASTOLES_DIR', plugin_dir_path( __FILE__ ) );
define( 'DIASTOLES_URL', plugin_dir_url( __FILE__ ) );

require_once DIASTOLES_DIR . 'includes/class-diastoles-db.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-session.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-texts.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-i18n.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-anthropic.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-matcher.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-rest.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-admin.php';
require_once DIASTOLES_DIR . 'includes/class-diastoles-plugin.php';

register_activation_hook( __FILE__, array( 'Diastoles_DB', 'activate' ) );

Diastoles_Plugin::instance();
