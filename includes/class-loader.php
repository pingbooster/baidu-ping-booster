<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Loader {

    public function run() {
        $this->load_dependencies();
        $this->maybe_upgrade();
        $this->define_hooks();
    }

    public static function default_options() {
        return array(
            'auto_submit'                => 1,
            'legacy_xmlrpc'              => 1,
            'baidu_site'                 => home_url( '/' ),
            'baidu_token'                => '',
            'submission_post_types'      => array( 'post', 'page', 'product' ),
            'resubmit_cooldown'          => 3600,
            'enable_sitemap'             => 1,
            'sitemap_include_posts'      => 1,
            'sitemap_include_pages'      => 1,
            'sitemap_include_products'   => 1,
            'sitemap_include_categories' => 1,
            'sitemap_include_tags'       => 1,
            'enable_chinese_meta'        => 1,
            'force_zh_cn'                => 0,
            'enable_seo_fields'          => 1,
            'tongji_id'                  => '',
            'webmaster_analytics_id'     => '',
            'robots_rules'               => Baidu_Booster_Robots::default_rules(),
            'log_retention_days'         => 30,
        );
    }

    private function load_dependencies() {
        require_once BAIDU_BOOSTER_PATH . 'includes/class-robots.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-logger.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-submission.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-diagnostics.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-sitemap.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-meta.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-seo-meta.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-news.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-analytics.php';
        require_once BAIDU_BOOSTER_PATH . 'includes/class-admin.php';
    }

    private function maybe_upgrade() {
        $stored = get_option( 'baidu_booster_version', '0' );
        if ( version_compare( $stored, BAIDU_BOOSTER_VERSION, '<' ) ) {
            Baidu_Booster_Logger::create_table();
            $current = get_option( 'baidu_booster_options', array() );
            update_option( 'baidu_booster_options', wp_parse_args( $current, self::default_options() ) );
            update_option( 'baidu_booster_version', BAIDU_BOOSTER_VERSION );
        }
    }

    private function define_hooks() {
        new Baidu_Booster_Submission();
        new Baidu_Booster_Robots();
        new Baidu_Booster_Sitemap();
        new Baidu_Ping_Booster_Meta();
        new Baidu_Ping_Booster_SEO_Meta();
        new Baidu_Booster_Analytics();

        if ( is_admin() ) {
            new Baidu_Booster_Admin();
        }
    }
}
