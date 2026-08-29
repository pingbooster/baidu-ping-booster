<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Robots {

    public function __construct() {
        add_filter( 'robots_txt', array( $this, 'append_baidu_rules' ), 20, 2 );
    }

    public static function default_rules() {
        return "User-agent: Baiduspider\nAllow: /\nDisallow: /wp-admin/\n\nUser-agent: Baiduspider-mobile\nAllow: /\nDisallow: /wp-admin/";
    }

    public function append_baidu_rules( $output, $public ) {
        $options = get_option( 'baidu_booster_options', array() );
        $rules   = ! empty( $options['robots_rules'] ) ? trim( $options['robots_rules'] ) : self::default_rules();

        if ( $rules && false === stripos( $output, 'User-agent: Baiduspider' ) ) {
            $output .= "\n# Baidu Ping Booster\n" . $rules . "\n";
        }

        if ( ! empty( $options['enable_sitemap'] ) ) {
            $sitemap = home_url( '/baidu-sitemap.xml' );
            if ( false === stripos( $output, $sitemap ) ) {
                $output .= "\nSitemap: " . esc_url_raw( $sitemap ) . "\n";
            }
        }

        return $output;
    }
}
