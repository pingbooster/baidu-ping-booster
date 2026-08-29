<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Submission {

    const API_ENDPOINT = 'http://data.zz.baidu.com/urls';
    const XMLRPC_ENDPOINT = 'http://ping.baidu.com/ping/RPC2';

    public function __construct() {
        add_action( 'wp_after_insert_post', array( $this, 'maybe_queue_post' ), 20, 4 );
        add_action( 'baidu_booster_process_queue', array( $this, 'process_queue' ) );
        add_action( 'init', array( $this, 'ensure_cleanup_schedule' ) );
        add_action( 'baidu_booster_daily_cleanup', array( 'Baidu_Booster_Logger', 'cleanup_old' ) );
    }

    public function ensure_cleanup_schedule() {
        if ( ! wp_next_scheduled( 'baidu_booster_daily_cleanup' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'baidu_booster_daily_cleanup' );
        }
    }

    public function maybe_queue_post( $post_id, $post, $update, $post_before ) {
        if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        if ( empty( $options['auto_submit'] ) ) {
            return;
        }

        $allowed = isset( $options['submission_post_types'] ) && is_array( $options['submission_post_types'] ) ? $options['submission_post_types'] : array( 'post', 'page' );
        if ( ! in_array( $post->post_type, $allowed, true ) ) {
            return;
        }

        $cooldown = max( 0, absint( $options['resubmit_cooldown'] ) );
        $last     = (int) get_post_meta( $post_id, '_baidu_booster_last_queued', true );
        if ( $update && $last && ( time() - $last ) < $cooldown ) {
            return;
        }

        $url = get_permalink( $post_id );
        if ( ! $url ) {
            return;
        }

        $this->queue_url( $url, $post_id, $post->post_type );
        update_post_meta( $post_id, '_baidu_booster_last_queued', time() );
    }

    public function queue_url( $url, $post_id = 0, $post_type = '' ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) {
            return 0;
        }

        $id = Baidu_Booster_Logger::add(
            array(
                'url'       => $url,
                'post_id'   => $post_id,
                'post_type' => $post_type,
                'method'    => $this->api_ready() ? 'api' : 'xmlrpc',
                'status'    => 'pending',
                'message'   => 'Queued for Baidu submission.',
            )
        );

        if ( $id && ! wp_next_scheduled( 'baidu_booster_process_queue' ) ) {
            wp_schedule_single_event( time() + 60, 'baidu_booster_process_queue' );
        }

        return $id;
    }

    public function process_queue() {
        $items = Baidu_Booster_Logger::pending( 10 );
        foreach ( $items as $item ) {
            $this->submit_log_item( $item );
        }

        if ( Baidu_Booster_Logger::pending( 1 ) ) {
            wp_schedule_single_event( time() + 60, 'baidu_booster_process_queue' );
        }
    }

    public function submit_log_item( $item ) {
        $attempts = (int) $item->attempts + 1;
        $result   = $this->submit_url( $item->url );

        Baidu_Booster_Logger::update(
            $item->id,
            array(
                'status'    => ! empty( $result['success'] ) ? 'success' : 'failed',
                'method'    => $result['method'],
                'http_code' => $result['http_code'],
                'message'   => $result['message'],
                'response'  => $result['response'],
                'attempts'  => $attempts,
            )
        );

        return $result;
    }

    public function submit_now( $url, $post_id = 0, $post_type = '' ) {
        $log_id = Baidu_Booster_Logger::add(
            array(
                'url'       => $url,
                'post_id'   => $post_id,
                'post_type' => $post_type,
                'method'    => $this->api_ready() ? 'api' : 'xmlrpc',
                'status'    => 'pending',
                'message'   => 'Manual submission started.',
            )
        );

        $item = (object) array(
            'id'       => $log_id,
            'url'      => $url,
            'attempts' => 0,
        );
        return $this->submit_log_item( $item );
    }

    public function submit_url( $url ) {
        if ( $this->api_ready() ) {
            return $this->send_api( array( $url ) );
        }

        $options = get_option( 'baidu_booster_options', array() );
        if ( ! empty( $options['legacy_xmlrpc'] ) ) {
            return $this->send_xmlrpc( $url );
        }

        return array(
            'success'   => false,
            'method'    => 'none',
            'http_code' => 0,
            'message'   => 'Baidu API credentials are not configured and legacy XML-RPC fallback is disabled.',
            'response'  => '',
        );
    }

    public function api_ready() {
        $options = get_option( 'baidu_booster_options', array() );
        return ! empty( $options['baidu_site'] ) && ! empty( $options['baidu_token'] );
    }

    public function send_api( $urls ) {
        $options = get_option( 'baidu_booster_options', array() );
        $site    = isset( $options['baidu_site'] ) ? trim( $options['baidu_site'] ) : '';
        $token   = isset( $options['baidu_token'] ) ? trim( $options['baidu_token'] ) : '';

        if ( ! $site || ! $token ) {
            return array( 'success' => false, 'method' => 'api', 'http_code' => 0, 'message' => 'Baidu site URL or API token is missing.', 'response' => '' );
        }

        $clean_urls = array();
        foreach ( (array) $urls as $url ) {
            $url = esc_url_raw( trim( $url ) );
            if ( $url ) {
                $clean_urls[] = $url;
            }
        }
        $clean_urls = array_values( array_unique( $clean_urls ) );

        if ( empty( $clean_urls ) ) {
            return array( 'success' => false, 'method' => 'api', 'http_code' => 0, 'message' => 'No valid URL was supplied.', 'response' => '' );
        }

        $endpoint = add_query_arg(
            array(
                'site'  => $site,
                'token' => $token,
            ),
            self::API_ENDPOINT
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'headers' => array( 'Content-Type' => 'text/plain; charset=UTF-8' ),
                'body'    => implode( "\n", $clean_urls ),
                'timeout' => 20,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'method' => 'api', 'http_code' => 0, 'message' => $response->get_error_message(), 'response' => '' );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );
        $ok   = 200 === $code && is_array( $json ) && ! isset( $json['error'] );

        if ( $ok ) {
            $success_count = isset( $json['success'] ) ? absint( $json['success'] ) : count( $clean_urls );
            $remain        = isset( $json['remain'] ) ? absint( $json['remain'] ) : null;
            $message       = 'Baidu API accepted ' . $success_count . ' URL(s).';
            if ( null !== $remain ) {
                $message .= ' Remaining quota reported: ' . $remain . '.';
            }
        } else {
            $message = is_array( $json ) && ! empty( $json['message'] ) ? sanitize_text_field( $json['message'] ) : 'Baidu API submission failed.';
        }

        return array(
            'success'   => $ok,
            'method'    => 'api',
            'http_code' => $code,
            'message'   => $message,
            'response'  => $body,
        );
    }

    public function send_xmlrpc( $update_url ) {
        $blog_name = get_bloginfo( 'name' );
        $blog_url  = home_url( '/' );

        $payload  = '<?xml version="1.0" encoding="UTF-8"?>';
        $payload .= '<methodCall><methodName>weblogUpdates.ping</methodName><params>';
        $payload .= '<param><value><string>' . esc_html( $blog_name ) . '</string></value></param>';
        $payload .= '<param><value><string>' . esc_url( $blog_url ) . '</string></value></param>';
        $payload .= '<param><value><string>' . esc_url( $update_url ) . '</string></value></param>';
        $payload .= '</params></methodCall>';

        $response = wp_remote_post(
            self::XMLRPC_ENDPOINT,
            array(
                'headers' => array( 'Content-Type' => 'text/xml; charset=UTF-8' ),
                'body'    => $payload,
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'method' => 'xmlrpc', 'http_code' => 0, 'message' => $response->get_error_message(), 'response' => '' );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $ok   = 200 === $code && false === strpos( $body, '<fault>' );

        return array(
            'success'   => $ok,
            'method'    => 'xmlrpc',
            'http_code' => $code,
            'message'   => $ok ? 'Legacy Baidu XML-RPC ping completed.' : 'Legacy Baidu XML-RPC ping failed.',
            'response'  => $body,
        );
    }
}
