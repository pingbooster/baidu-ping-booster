<?php
/**
 * Plugin Name: Baidu Ping Booster
 * Plugin URI:  https://pingbooster.site
 * Description: Free Baidu URL submission, XML-RPC pinging, Baidu sitemap, Tongji integration, crawler diagnostics, activity logs, and Baidu-focused SEO tools for WordPress.
 * Version:     2.1.0
 * Author:      Samee Ullah Feroz
 * Author URI:  https://pingbooster.site
 * Text Domain: baidu-ping-booster
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================================
// Plugin constants
// =========================================================
define( 'BAIDU_BOOSTER_VERSION', '2.1.0' );
define( 'BAIDU_BOOSTER_FILE', __FILE__ );
define( 'BAIDU_BOOSTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'BAIDU_BOOSTER_URL', plugin_dir_url( __FILE__ ) );

require_once BAIDU_BOOSTER_PATH . 'includes/class-loader.php';

// =========================================================
// Activation / deactivation
// =========================================================
function baidu_booster_activate() {
    require_once BAIDU_BOOSTER_PATH . 'includes/class-robots.php';
    require_once BAIDU_BOOSTER_PATH . 'includes/class-logger.php';
    require_once BAIDU_BOOSTER_PATH . 'includes/class-sitemap.php';

    Baidu_Booster_Logger::create_table();

    $sitemap = new Baidu_Booster_Sitemap();
    $sitemap->add_sitemap_endpoint();
    flush_rewrite_rules();

    $defaults = Baidu_Booster_Loader::default_options();
    $current  = get_option( 'baidu_booster_options', array() );
    update_option( 'baidu_booster_options', wp_parse_args( $current, $defaults ) );

    update_option( 'baidu_booster_version', BAIDU_BOOSTER_VERSION );
}
register_activation_hook( __FILE__, 'baidu_booster_activate' );

function baidu_booster_deactivate() {
    wp_clear_scheduled_hook( 'baidu_booster_process_queue' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'baidu_booster_deactivate' );

// =========================================================
// Boot
// =========================================================
function baidu_booster_run() {
    $plugin = new Baidu_Booster_Loader();
    $plugin->run();
}
baidu_booster_run();
