<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Ping_Booster_SEO_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
        add_action( 'save_post', array( $this, 'save_metabox' ) );
        add_filter( 'document_title_parts', array( $this, 'filter_title' ) );
        add_action( 'wp_head', array( $this, 'output_meta' ), 2 );
    }

    private function enabled() {
        $options = get_option( 'baidu_booster_options', array() );
        return ! isset( $options['enable_seo_fields'] ) || ! empty( $options['enable_seo_fields'] );
    }

    public function add_metabox() {
        if ( ! $this->enabled() ) {
            return;
        }
        foreach ( array( 'post', 'page', 'product' ) as $screen ) {
            if ( post_type_exists( $screen ) ) {
                add_meta_box( 'bpb_seo_metabox', __( 'Baidu SEO Fields', 'baidu-ping-booster' ), array( $this, 'render_metabox' ), $screen, 'normal', 'default' );
            }
        }
    }

    public function render_metabox( $post ) {
        wp_nonce_field( 'baidu_seo_meta_save', 'baidu_seo_meta_nonce' );
        $title = get_post_meta( $post->ID, '_baidu_seo_title', true );
        $desc  = get_post_meta( $post->ID, '_baidu_seo_desc', true );
        $canon = get_post_meta( $post->ID, '_baidu_seo_canonical', true );
        ?>
        <p><label for="baidu_seo_title"><strong><?php esc_html_e( 'SEO Title', 'baidu-ping-booster' ); ?></strong></label></p>
        <input class="widefat" id="baidu_seo_title" name="baidu_seo_title" type="text" value="<?php echo esc_attr( $title ); ?>" maxlength="180">
        <p class="description"><?php esc_html_e( 'Optional custom title. Leave blank to keep the normal WordPress/SEO-plugin title.', 'baidu-ping-booster' ); ?></p>

        <p><label for="baidu_seo_desc"><strong><?php esc_html_e( 'Meta Description', 'baidu-ping-booster' ); ?></strong></label></p>
        <textarea class="widefat" id="baidu_seo_desc" name="baidu_seo_desc" rows="3"><?php echo esc_textarea( $desc ); ?></textarea>

        <p><label for="baidu_seo_canonical"><strong><?php esc_html_e( 'Canonical URL', 'baidu-ping-booster' ); ?></strong></label></p>
        <input class="widefat" id="baidu_seo_canonical" name="baidu_seo_canonical" type="url" value="<?php echo esc_attr( $canon ); ?>" placeholder="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>">
        <?php
    }

    public function save_metabox( $post_id ) {
        if ( ! isset( $_POST['baidu_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['baidu_seo_meta_nonce'] ) ), 'baidu_seo_meta_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        update_post_meta( $post_id, '_baidu_seo_title', isset( $_POST['baidu_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['baidu_seo_title'] ) ) : '' );
        update_post_meta( $post_id, '_baidu_seo_desc', isset( $_POST['baidu_seo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['baidu_seo_desc'] ) ) : '' );
        update_post_meta( $post_id, '_baidu_seo_canonical', isset( $_POST['baidu_seo_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['baidu_seo_canonical'] ) ) : '' );
    }

    public function filter_title( $parts ) {
        if ( ! $this->enabled() || ! is_singular() ) {
            return $parts;
        }
        $title = get_post_meta( get_queried_object_id(), '_baidu_seo_title', true );
        if ( $title ) {
            $parts['title'] = $title;
        }
        return $parts;
    }

    public function output_meta() {
        if ( ! $this->enabled() || ! is_singular() ) {
            return;
        }
        $id    = get_queried_object_id();
        $desc  = get_post_meta( $id, '_baidu_seo_desc', true );
        $canon = get_post_meta( $id, '_baidu_seo_canonical', true );

        if ( $desc ) {
            echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
        }
        if ( $canon ) {
            echo '<link rel="canonical" href="' . esc_url( $canon ) . '">' . "\n";
        }
    }
}
