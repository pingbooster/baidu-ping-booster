<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Ping_Booster_Meta {

    public function __construct() {
        add_action( 'wp_head', array( $this, 'add_meta_tags' ), 1 );
        add_filter( 'language_attributes', array( $this, 'language_attributes' ) );
    }

    public function add_meta_tags() {
        $options = get_option( 'baidu_booster_options', array() );
        if ( empty( $options['enable_chinese_meta'] ) ) {
            return;
        }

        echo "\n<!-- Baidu Ping Booster -->\n";
        echo '<meta name="applicable-device" content="pc,mobile">' . "\n";
        echo '<meta http-equiv="Cache-Control" content="no-transform">' . "\n";
        echo '<meta http-equiv="Cache-Control" content="no-siteapp">' . "\n";
    }

    public function language_attributes( $output ) {
        $options = get_option( 'baidu_booster_options', array() );
        if ( empty( $options['force_zh_cn'] ) ) {
            return $output;
        }

        if ( preg_match( '/lang=("|\')[^"\']*("|\')/', $output ) ) {
            return preg_replace( '/lang=("|\')[^"\']*("|\')/', 'lang="zh-CN"', $output, 1 );
        }
        return trim( $output . ' lang="zh-CN"' );
    }
}
