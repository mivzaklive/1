<?php
/**
 * Plugin Name: מנוע כתבות AI – מבזק לייב
 * Plugin URI: https://www.mivzaklive.co.il/
 * Description: מנוע עריכה ויצירת כתבות אוטומטי לפי קטגוריות, כולל מחקר רשת, כתיבה עיתונאית, תמונה ראשית ובקרת איכות.
 * Version: 1.1.10
 * Author: MivzakLive
 * Author URI: https://www.mivzaklive.co.il/
 * Text Domain: mivzak-ai-editorial-engine
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MAIE_VERSION', '1.1.10');
define('MAIE_PLUGIN_FILE', __FILE__);
define('MAIE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAIE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAIE_DB_VERSION', '1.1.0');

require_once MAIE_PLUGIN_DIR . 'includes/class-maie-db.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-options.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-prompts.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-install.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-openai.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-generator.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-cron.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-admin.php';
require_once MAIE_PLUGIN_DIR . 'includes/class-maie-tracking.php';

register_activation_hook(MAIE_PLUGIN_FILE, ['MAIE_Install', 'activate']);
register_deactivation_hook(MAIE_PLUGIN_FILE, ['MAIE_Install', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    MAIE_Install::maybe_upgrade();
    MAIE_Cron::init();
    MAIE_Tracking::init();
    if (is_admin()) {
        MAIE_Admin::init();
    }
});
