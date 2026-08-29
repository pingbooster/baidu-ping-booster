<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Diagnostics {

    public static function checks() {
        $options    = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        $home       = home_url( '/' );
        $host       = wp_parse_url( $home, PHP_URL_HOST );
        $is_private = self::is_private_host( $host );
        $public     = '1' === (string) get_option( 'blog_public' );
        $cron_off   = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

        return array(
            array( 'label' => 'WordPress search visibility', 'ok' => $public, 'detail' => $public ? 'Search engines are allowed to discover this site.' : 'WordPress is configured to discourage search engines.' ),
            array( 'label' => 'Public website address', 'ok' => ! $is_private, 'detail' => $is_private ? 'Localhost/private sites cannot be reached by Baidu.' : $home ),
            array( 'label' => 'Baidu API credentials', 'ok' => ! empty( $options['baidu_site'] ) && ! empty( $options['baidu_token'] ), 'detail' => ! empty( $options['baidu_token'] ) ? 'Site and token are configured.' : 'Add your verified Baidu site and API token for API Push.' ),
            array( 'label' => 'Baidu sitemap', 'ok' => ! empty( $options['enable_sitemap'] ), 'detail' => ! empty( $options['enable_sitemap'] ) ? home_url( '/baidu-sitemap.xml' ) : 'Baidu sitemap is disabled.' ),
            array( 'label' => 'WP-Cron queue', 'ok' => ! $cron_off, 'detail' => $cron_off ? 'DISABLE_WP_CRON is enabled. Configure a real server cron for queue processing.' : 'WP-Cron can process queued submissions.' ),
            array( 'label' => 'HTTPS', 'ok' => 'https' === wp_parse_url( $home, PHP_URL_SCHEME ), 'detail' => 'https' === wp_parse_url( $home, PHP_URL_SCHEME ) ? 'The site uses HTTPS.' : 'HTTPS is recommended for production websites.' ),
        );
    }

    private static function is_private_host( $host ) {
        if ( ! $host ) {
            return true;
        }
        $host = strtolower( $host );
        if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || '.local' === substr( $host, -6 ) || '.test' === substr( $host, -5 ) ) {
            return true;
        }
        return false;
    }
}
