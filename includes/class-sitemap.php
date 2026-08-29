<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Sitemap {

    public function __construct() {
        add_action( 'init', array( $this, 'add_sitemap_endpoint' ) );
        add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
    }

    public function add_sitemap_endpoint() {
        add_rewrite_rule( '^baidu-sitemap\.xml$', 'index.php?baidu_sitemap=1', 'top' );
        add_rewrite_tag( '%baidu_sitemap%', '1' );
    }

    public function render_sitemap() {
        if ( '1' !== (string) get_query_var( 'baidu_sitemap' ) ) {
            return;
        }

        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        if ( empty( $options['enable_sitemap'] ) ) {
            status_header( 404 );
            exit;
        }

        $urls = $this->get_urls( $options );

        nocache_headers();
        header( 'Content-Type: application/xml; charset=UTF-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $urls as $row ) {
            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url( $row['loc'] ) . "</loc>\n";
            if ( ! empty( $row['lastmod'] ) ) {
                echo "\t\t<lastmod>" . esc_html( $row['lastmod'] ) . "</lastmod>\n";
            }
            echo "\t</url>\n";
        }

        echo '</urlset>';
        exit;
    }

    public function get_urls( $options = null ) {
        if ( null === $options ) {
            $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        }

        $post_types = array();
        if ( ! empty( $options['sitemap_include_posts'] ) ) {
            $post_types[] = 'post';
        }
        if ( ! empty( $options['sitemap_include_pages'] ) ) {
            $post_types[] = 'page';
        }
        if ( ! empty( $options['sitemap_include_products'] ) && post_type_exists( 'product' ) ) {
            $post_types[] = 'product';
        }

        $urls = array();
        if ( $post_types ) {
            $query = new WP_Query(
                array(
                    'post_type'              => $post_types,
                    'post_status'            => 'publish',
                    'posts_per_page'         => 2000,
                    'orderby'                => 'modified',
                    'order'                  => 'DESC',
                    'no_found_rows'          => true,
                    'fields'                 => 'ids',
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                )
            );

            foreach ( $query->posts as $post_id ) {
                $urls[] = array(
                    'loc'     => get_permalink( $post_id ),
                    'lastmod' => get_post_modified_time( 'c', true, $post_id ),
                );
            }
        }

        $taxonomies = array();
        if ( ! empty( $options['sitemap_include_categories'] ) ) {
            $taxonomies[] = 'category';
            if ( taxonomy_exists( 'product_cat' ) ) {
                $taxonomies[] = 'product_cat';
            }
        }
        if ( ! empty( $options['sitemap_include_tags'] ) ) {
            $taxonomies[] = 'post_tag';
            if ( taxonomy_exists( 'product_tag' ) ) {
                $taxonomies[] = 'product_tag';
            }
        }

        if ( $taxonomies ) {
            $terms = get_terms( array( 'taxonomy' => $taxonomies, 'hide_empty' => true, 'number' => 1000 ) );
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $link = get_term_link( $term );
                    if ( ! is_wp_error( $link ) ) {
                        $urls[] = array( 'loc' => $link, 'lastmod' => '' );
                    }
                }
            }
        }

        return $urls;
    }
}
