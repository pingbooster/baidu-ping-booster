<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_News {

    public static function get_news_feed( $limit = 3 ) {
        if ( ! function_exists( 'fetch_feed' ) ) {
            require_once ABSPATH . WPINC . '/feed.php';
        }

        $items = array();
        $rss   = fetch_feed( 'https://blog.pingbooster.site/feed/' );
        if ( ! is_wp_error( $rss ) ) {
            foreach ( $rss->get_items( 0, min( 6, absint( $limit ) ) ) as $item ) {
                $items[] = array(
                    'title'       => wp_strip_all_tags( $item->get_title() ),
                    'description' => wp_strip_all_tags( $item->get_description() ),
                    'permalink'   => esc_url_raw( $item->get_permalink() ),
                );
            }
        }

        if ( ! $items ) {
            $items[] = array(
                'title'       => 'PingBooster Updates & SEO Guides',
                'description' => 'Read product updates, SEO guides, WordPress development notes, and company news.',
                'permalink'   => 'https://blog.pingbooster.site/',
            );
        }
        return $items;
    }

    public static function company_cards() {
        return array(
            array( 'title' => 'SEO Services', 'text' => 'Technical SEO, indexing, on-page improvements, audits, and ongoing SEO support.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'GEO Services', 'text' => 'Generative Engine Optimization and search visibility services for modern AI-assisted discovery.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'Web Development', 'text' => 'WordPress and custom website development, maintenance, performance, and technical fixes.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'Plugin Development', 'text' => 'Custom WordPress plugin planning, development, debugging, and integration services.', 'url' => 'https://pingbooster.site/' ),
        );
    }

    public static function plugin_cards() {
        return array(
            array( 'title' => 'Auto Ping Booster Pro', 'text' => 'Advanced WordPress indexing and search submission automation.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'Share Bee', 'text' => 'Social sharing tools for WordPress with a lightweight sharing workflow.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'Auto Figure Jin', 'text' => 'WordPress content and figure automation tools.', 'url' => 'https://pingbooster.site/' ),
            array( 'title' => 'PB Agency', 'text' => 'PingBooster Agency client portal and service package system.', 'url' => 'https://pingbooster.site/' ),
        );
    }
}
