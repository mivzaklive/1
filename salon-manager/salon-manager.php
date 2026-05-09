<?php
/**
 * Plugin Name: Salon Manager - ניהול סלון יופי
 * Plugin URI:  https://github.com/mivzaklive/1
 * Description: מערכת מקיפה לניהול תורים, לקוחות ותשלומים לסלון יופי
 * Version:     1.0.0
 * Author:      Salon Manager
 * Text Domain: salon-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SALON_MANAGER_VERSION', '1.0.0' );
define( 'SALON_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'SALON_MANAGER_URL', plugin_dir_url( __FILE__ ) );

require_once SALON_MANAGER_PATH . 'includes/class-db.php';
require_once SALON_MANAGER_PATH . 'includes/class-customers.php';
require_once SALON_MANAGER_PATH . 'includes/class-appointments.php';
require_once SALON_MANAGER_PATH . 'includes/class-payments.php';
require_once SALON_MANAGER_PATH . 'admin/class-admin.php';

register_activation_hook( __FILE__, [ 'Salon_Manager_DB', 'install' ] );
register_deactivation_hook( __FILE__, [ 'Salon_Manager_DB', 'deactivate' ] );

add_action( 'plugins_loaded', function() {
    new Salon_Manager_Admin();
} );
