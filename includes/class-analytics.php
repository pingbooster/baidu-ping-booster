<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Analytics {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tongji' ), 20 );
    }

    public function enqueue_tongji() {
        $options = get_option( 'baidu_booster_options', array() );
        $id      = isset( $options['tongji_id'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', $options['tongji_id'] ) : '';

        if ( ! $id ) {
            return;
        }

        wp_register_script(
            'baidu-booster-tongji',
            'https://hm.baidu.com/hm.js?' . rawurlencode( $id ),
            array(),
            null,
            false
        );
        wp_enqueue_script( 'baidu-booster-tongji' );
        wp_add_inline_script( 'baidu-booster-tongji', 'window._hmt = window._hmt || [];', 'before' );
    }
}
