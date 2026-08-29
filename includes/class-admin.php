<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Admin {

    private $page_slug = 'baidu-booster';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Baidu Ping Booster', 'baidu-ping-booster' ),
            __( 'Baidu Booster', 'baidu-ping-booster' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render' ),
            'dashicons-performance',
            80
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_' . $this->page_slug !== $hook ) {
            return;
        }
        wp_register_style( 'baidu-booster-admin', false, array(), BAIDU_BOOSTER_VERSION );
        wp_enqueue_style( 'baidu-booster-admin' );
        wp_add_inline_style( 'baidu-booster-admin', $this->admin_css() );
    }

    private function admin_css() {
        return '
        .bpb-wrap{max-width:1280px}.bpb-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin:18px 0}.bpb-version{background:#e7f5ff;color:#0a58ca;padding:5px 10px;border-radius:999px;font-weight:600}.bpb-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin:20px 0}.bpb-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.03)}.bpb-card h2,.bpb-card h3{margin-top:0}.bpb-stat strong{display:block;font-size:30px;line-height:1.1;margin-top:8px}.bpb-success{border-left:4px solid #00a32a}.bpb-failed{border-left:4px solid #d63638}.bpb-pending{border-left:4px solid #dba617}.bpb-info{border-left:4px solid #2271b1}.bpb-status{display:inline-flex;align-items:center;gap:6px;font-weight:600}.bpb-status.ok{color:#008a20}.bpb-status.bad{color:#b32d2e}.bpb-table{width:100%;border-collapse:collapse;background:#fff}.bpb-table th,.bpb-table td{padding:10px 12px;border-bottom:1px solid #f0f0f1;text-align:left;vertical-align:top}.bpb-table th{background:#f6f7f7}.bpb-code{font-family:monospace;background:#f6f7f7;padding:3px 6px;border-radius:4px}.bpb-hero{background:linear-gradient(135deg,#073b7a,#1267ba);color:#fff;border-radius:12px;padding:28px;margin:22px 0}.bpb-hero h2{color:#fff;font-size:26px}.bpb-hero p{color:#eaf4ff;max-width:850px}.bpb-card .button{margin-right:6px}.bpb-muted{color:#646970}.bpb-log-message{max-width:340px;word-break:break-word}.bpb-section{margin-top:28px}.bpb-two{display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:18px}.bpb-service{min-height:150px}.bpb-pill{display:inline-block;background:#f0f0f1;border-radius:999px;padding:4px 9px;font-size:12px}.bpb-token{font-family:monospace;letter-spacing:1px}.bpb-wrap textarea.large-text{min-height:150px}
        ';
    }

    public function handle_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_POST['bpb_action'] ) ) {
            return;
        }

        check_admin_referer( 'bpb_admin_action', 'bpb_nonce' );
        $action = sanitize_key( wp_unslash( $_POST['bpb_action'] ) );
        $tab    = isset( $_POST['bpb_tab'] ) ? sanitize_key( wp_unslash( $_POST['bpb_tab'] ) ) : 'dashboard';

        switch ( $action ) {
            case 'save_submission':
                $this->save_submission();
                $this->redirect( 'submission', 'saved' );
                break;

            case 'manual_submit':
                $url = isset( $_POST['manual_url'] ) ? esc_url_raw( wp_unslash( $_POST['manual_url'] ) ) : '';
                if ( ! $url ) {
                    $this->redirect( 'submission', 'invalid_url' );
                }
                $engine = new Baidu_Booster_Submission();
                $result = $engine->submit_now( $url );
                $this->redirect( 'submission', ! empty( $result['success'] ) ? 'submitted' : 'submit_failed', $result['message'] );
                break;

            case 'bulk_queue':
                $raw  = isset( $_POST['bulk_urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bulk_urls'] ) ) : '';
                $urls = preg_split( '/\r\n|\r|\n/', $raw );
                $engine = new Baidu_Booster_Submission();
                $count  = 0;
                foreach ( array_slice( (array) $urls, 0, 500 ) as $url ) {
                    $url = esc_url_raw( trim( $url ) );
                    if ( $url && $engine->queue_url( $url ) ) {
                        $count++;
                    }
                }
                $this->redirect( 'submission', 'queued', sprintf( '%d URL(s) queued.', $count ) );
                break;

            case 'save_seo':
                $this->save_seo();
                flush_rewrite_rules( false );
                $this->redirect( 'seo', 'saved' );
                break;

            case 'save_analytics':
                $this->save_analytics();
                $this->redirect( 'analytics', 'saved' );
                break;

            case 'clear_logs':
                Baidu_Booster_Logger::clear();
                $this->redirect( 'logs', 'logs_cleared' );
                break;

            case 'retry_failed':
                $count = (int) Baidu_Booster_Logger::retry_failed();
                if ( $count && ! wp_next_scheduled( 'baidu_booster_process_queue' ) ) {
                    wp_schedule_single_event( time() + 60, 'baidu_booster_process_queue' );
                }
                $this->redirect( 'logs', 'retry_queued', sprintf( '%d failed item(s) returned to the queue.', $count ) );
                break;

            case 'export_settings':
                $this->export_settings();
                break;

            case 'import_settings':
                $this->import_settings();
                $this->redirect( 'tools', 'imported' );
                break;

            case 'refresh_rewrites':
                flush_rewrite_rules( false );
                $this->redirect( 'tools', 'rewrites_refreshed' );
                break;
        }

        $this->redirect( $tab, 'saved' );
    }

    private function save_submission() {
        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        $options['auto_submit']       = isset( $_POST['auto_submit'] ) ? 1 : 0;
        $options['legacy_xmlrpc']     = isset( $_POST['legacy_xmlrpc'] ) ? 1 : 0;
        $options['baidu_site']        = isset( $_POST['baidu_site'] ) ? esc_url_raw( wp_unslash( $_POST['baidu_site'] ) ) : '';
        $options['baidu_token']       = isset( $_POST['baidu_token'] ) ? sanitize_text_field( wp_unslash( $_POST['baidu_token'] ) ) : '';
        $options['resubmit_cooldown'] = isset( $_POST['resubmit_cooldown'] ) ? max( 0, absint( $_POST['resubmit_cooldown'] ) ) : 3600;

        $types = isset( $_POST['submission_post_types'] ) ? (array) wp_unslash( $_POST['submission_post_types'] ) : array();
        $options['submission_post_types'] = array_values( array_filter( array_map( 'sanitize_key', $types ), 'post_type_exists' ) );
        update_option( 'baidu_booster_options', $options );
    }

    private function save_seo() {
        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        foreach ( array( 'enable_sitemap', 'sitemap_include_posts', 'sitemap_include_pages', 'sitemap_include_products', 'sitemap_include_categories', 'sitemap_include_tags', 'enable_chinese_meta', 'force_zh_cn', 'enable_seo_fields' ) as $key ) {
            $options[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
        }
        $options['robots_rules'] = isset( $_POST['robots_rules'] ) ? sanitize_textarea_field( wp_unslash( $_POST['robots_rules'] ) ) : '';
        update_option( 'baidu_booster_options', $options );
    }

    private function save_analytics() {
        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        $options['tongji_id']              = isset( $_POST['tongji_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tongji_id'] ) ) : '';
        $options['webmaster_analytics_id'] = isset( $_POST['webmaster_analytics_id'] ) ? sanitize_text_field( wp_unslash( $_POST['webmaster_analytics_id'] ) ) : '';
        update_option( 'baidu_booster_options', $options );
    }

    private function export_settings() {
        $data = array(
            'plugin'      => 'Baidu Ping Booster',
            'version'     => BAIDU_BOOSTER_VERSION,
            'exported_at' => current_time( 'mysql' ),
            'options'     => get_option( 'baidu_booster_options', array() ),
        );
        $json = wp_json_encode( $data, JSON_PRETTY_PRINT );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="baidu-ping-booster-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function import_settings() {
        if ( empty( $_FILES['settings_file']['tmp_name'] ) ) {
            $this->redirect( 'tools', 'import_failed', 'Please select a JSON settings file.' );
        }
        $name = isset( $_FILES['settings_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['settings_file']['name'] ) ) : '';
        if ( 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
            $this->redirect( 'tools', 'import_failed', 'Only JSON files are accepted.' );
        }
        $raw = file_get_contents( $_FILES['settings_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) ) {
            $this->redirect( 'tools', 'import_failed', 'Invalid settings file.' );
        }

        $defaults = Baidu_Booster_Loader::default_options();
        $clean    = wp_parse_args( $data['options'], $defaults );
        $clean['baidu_site']             = esc_url_raw( $clean['baidu_site'] );
        $clean['baidu_token']            = sanitize_text_field( $clean['baidu_token'] );
        $clean['tongji_id']              = sanitize_text_field( $clean['tongji_id'] );
        $clean['webmaster_analytics_id'] = sanitize_text_field( $clean['webmaster_analytics_id'] );
        $clean['robots_rules']           = sanitize_textarea_field( $clean['robots_rules'] );
        $clean['submission_post_types']  = array_map( 'sanitize_key', (array) $clean['submission_post_types'] );
        update_option( 'baidu_booster_options', $clean );
    }

    private function redirect( $tab, $notice, $message = '' ) {
        $args = array( 'page' => $this->page_slug, 'tab' => sanitize_key( $tab ), 'bpb_notice' => sanitize_key( $notice ) );
        if ( $message ) {
            $args['bpb_message'] = rawurlencode( wp_strip_all_tags( $message ) );
        }
        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
        $options = wp_parse_args( get_option( 'baidu_booster_options', array() ), Baidu_Booster_Loader::default_options() );
        ?>
        <div class="wrap bpb-wrap">
            <div class="bpb-header">
                <div><h1><?php esc_html_e( 'Baidu Ping Booster', 'baidu-ping-booster' ); ?></h1><p class="bpb-muted">Free Baidu indexing, crawler and SEO toolkit by PingBooster.</p></div>
                <span class="bpb-version">v<?php echo esc_html( BAIDU_BOOSTER_VERSION ); ?> • Free</span>
            </div>

            <?php $this->notice(); ?>
            <?php $this->tabs( $tab ); ?>

            <?php
            switch ( $tab ) {
                case 'submission': $this->render_submission( $options ); break;
                case 'seo':        $this->render_seo( $options ); break;
                case 'analytics':  $this->render_analytics( $options ); break;
                case 'logs':       $this->render_logs(); break;
                case 'tools':      $this->render_tools(); break;
                case 'pingbooster':$this->render_pingbooster(); break;
                default:           $this->render_dashboard( $options ); break;
            }
            ?>
        </div>
        <?php
    }

    private function tabs( $active ) {
        $tabs = array(
            'dashboard'   => 'Dashboard',
            'submission'  => 'Submission',
            'seo'         => 'Sitemap & SEO',
            'analytics'   => 'Baidu Tongji',
            'logs'        => 'Activity Log',
            'tools'       => 'Tools',
            'pingbooster' => 'PingBooster',
        );
        echo '<nav class="nav-tab-wrapper">';
        foreach ( $tabs as $key => $label ) {
            $url = add_query_arg( array( 'page' => $this->page_slug, 'tab' => $key ), admin_url( 'admin.php' ) );
            echo '<a class="nav-tab ' . ( $active === $key ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
    }

    private function notice() {
        if ( empty( $_GET['bpb_notice'] ) ) {
            return;
        }
        $notice  = sanitize_key( wp_unslash( $_GET['bpb_notice'] ) );
        $message = isset( $_GET['bpb_message'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['bpb_message'] ) ) ) : '';
        $errors  = array( 'invalid_url', 'submit_failed', 'import_failed' );
        $class   = in_array( $notice, $errors, true ) ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';
        $labels  = array(
            'saved'              => 'Settings saved.',
            'submitted'          => 'URL submitted to Baidu.',
            'submit_failed'      => 'Submission failed.',
            'invalid_url'        => 'Please enter a valid URL.',
            'queued'             => 'Bulk URLs added to the queue.',
            'logs_cleared'       => 'Activity log cleared.',
            'retry_queued'       => 'Failed submissions queued for retry.',
            'imported'           => 'Settings imported.',
            'import_failed'      => 'Settings import failed.',
            'rewrites_refreshed' => 'Rewrite rules refreshed.',
        );
        echo '<div class="' . esc_attr( $class ) . '"><p><strong>' . esc_html( $labels[ $notice ] ?? 'Done.' ) . '</strong>' . ( $message ? ' ' . esc_html( $message ) : '' ) . '</p></div>';
    }

    private function form_open( $tab ) {
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field( 'bpb_admin_action', 'bpb_nonce' );
        echo '<input type="hidden" name="bpb_tab" value="' . esc_attr( $tab ) . '">';
    }

    private function render_dashboard( $options ) {
        $stats  = Baidu_Booster_Logger::stats();
        $recent = Baidu_Booster_Logger::get( 5 );
        $checks = Baidu_Booster_Diagnostics::checks();
        ?>
        <div class="bpb-grid">
            <div class="bpb-card bpb-stat bpb-info"><span>Submitted Today</span><strong><?php echo esc_html( $stats['today'] ); ?></strong></div>
            <div class="bpb-card bpb-stat bpb-success"><span>Total Successful</span><strong><?php echo esc_html( $stats['success'] ); ?></strong></div>
            <div class="bpb-card bpb-stat bpb-failed"><span>Total Failed</span><strong><?php echo esc_html( $stats['failed'] ); ?></strong></div>
            <div class="bpb-card bpb-stat bpb-pending"><span>Pending Queue</span><strong><?php echo esc_html( $stats['pending'] ); ?></strong></div>
        </div>

        <div class="bpb-two">
            <div class="bpb-card">
                <h2>Submission Engine</h2>
                <p><strong>Preferred mode:</strong> <?php echo ! empty( $options['baidu_token'] ) ? '<span class="bpb-status ok">● Baidu API Push</span>' : '<span class="bpb-status bad">● API token not configured</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                <p><strong>Automatic submission:</strong> <?php echo ! empty( $options['auto_submit'] ) ? 'Enabled' : 'Disabled'; ?></p>
                <p><strong>Legacy XML-RPC fallback:</strong> <?php echo ! empty( $options['legacy_xmlrpc'] ) ? 'Enabled' : 'Disabled'; ?></p>
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=baidu-booster&tab=submission' ) ); ?>">Configure Submission</a>
            </div>
            <div class="bpb-card">
                <h2>Site Health Snapshot</h2>
                <?php foreach ( array_slice( $checks, 0, 4 ) as $check ) : ?>
                    <p><span class="bpb-status <?php echo $check['ok'] ? 'ok' : 'bad'; ?>"><?php echo $check['ok'] ? '●' : '●'; ?> <?php echo esc_html( $check['label'] ); ?></span><br><span class="bpb-muted"><?php echo esc_html( $check['detail'] ); ?></span></p>
                <?php endforeach; ?>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=baidu-booster&tab=tools' ) ); ?>">Open Diagnostics</a>
            </div>
        </div>

        <div class="bpb-card bpb-section">
            <h2>Recent Activity</h2>
            <?php $this->log_table( $recent ); ?>
        </div>
        <?php
    }

    private function render_submission( $options ) {
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        unset( $post_types['attachment'] );
        ?>
        <div class="bpb-two bpb-section">
            <div class="bpb-card">
                <h2>Baidu API Push</h2>
                <p>Enter the verified site and token from Baidu Search Resource Platform. When configured, API Push becomes the primary submission method.</p>
                <?php $this->form_open( 'submission' ); ?>
                <input type="hidden" name="bpb_action" value="save_submission">
                <table class="form-table">
                    <tr><th><label for="baidu_site">Verified Baidu Site</label></th><td><input class="regular-text" type="url" id="baidu_site" name="baidu_site" value="<?php echo esc_attr( $options['baidu_site'] ); ?>" placeholder="https://example.com/"><p class="description">Use the same site address/protocol verified in Baidu.</p></td></tr>
                    <tr><th><label for="baidu_token">API Token</label></th><td><input class="regular-text bpb-token" type="password" id="baidu_token" name="baidu_token" value="<?php echo esc_attr( $options['baidu_token'] ); ?>" autocomplete="off"><p class="description">Stored in your WordPress database. The token is only sent to Baidu when submitting URLs.</p></td></tr>
                    <tr><th>Automatic Submission</th><td><label><input type="checkbox" name="auto_submit" value="1" <?php checked( ! empty( $options['auto_submit'] ) ); ?>> Queue published and meaningfully updated content automatically.</label></td></tr>
                    <tr><th>Content Types</th><td><?php foreach ( $post_types as $type ) : ?><label style="display:block;margin-bottom:5px"><input type="checkbox" name="submission_post_types[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, (array) $options['submission_post_types'], true ) ); ?>> <?php echo esc_html( $type->labels->singular_name ); ?> <code><?php echo esc_html( $type->name ); ?></code></label><?php endforeach; ?></td></tr>
                    <tr><th><label for="resubmit_cooldown">Update Cooldown</label></th><td><input type="number" min="0" step="60" id="resubmit_cooldown" name="resubmit_cooldown" value="<?php echo esc_attr( $options['resubmit_cooldown'] ); ?>"> seconds<p class="description">Prevents repeated submissions while editing the same content. 3600 = one hour.</p></td></tr>
                    <tr><th>Legacy Fallback</th><td><label><input type="checkbox" name="legacy_xmlrpc" value="1" <?php checked( ! empty( $options['legacy_xmlrpc'] ) ); ?>> Use Baidu XML-RPC ping when no API token is configured.</label></td></tr>
                </table>
                <?php submit_button( 'Save Submission Settings' ); ?>
                </form>
            </div>

            <div class="bpb-card">
                <h2>Manual URL Submission</h2>
                <p>Submit one public URL immediately. The result is written to the Activity Log.</p>
                <?php $this->form_open( 'submission' ); ?>
                <input type="hidden" name="bpb_action" value="manual_submit">
                <p><input class="large-text" type="url" name="manual_url" value="<?php echo esc_attr( home_url( '/' ) ); ?>"></p>
                <?php submit_button( 'Submit URL Now', 'primary' ); ?>
                </form>

                <hr>
                <h3>Bulk Queue</h3>
                <p>Paste up to 500 URLs, one per line. They are queued and processed in small batches through WP-Cron.</p>
                <?php $this->form_open( 'submission' ); ?>
                <input type="hidden" name="bpb_action" value="bulk_queue">
                <textarea class="large-text code" name="bulk_urls" placeholder="https://example.com/page-1/&#10;https://example.com/page-2/"></textarea>
                <?php submit_button( 'Add URLs to Queue', 'secondary' ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function render_seo( $options ) {
        ?>
        <div class="bpb-two bpb-section">
            <div class="bpb-card">
                <h2>Baidu XML Sitemap</h2>
                <p><strong>URL:</strong> <a href="<?php echo esc_url( home_url( '/baidu-sitemap.xml' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/baidu-sitemap.xml' ) ); ?></a></p>
                <?php $this->form_open( 'seo' ); ?>
                <input type="hidden" name="bpb_action" value="save_seo">
                <p><label><input type="checkbox" name="enable_sitemap" value="1" <?php checked( ! empty( $options['enable_sitemap'] ) ); ?>> Enable dedicated Baidu sitemap</label></p>
                <p><strong>Include:</strong></p>
                <p><label><input type="checkbox" name="sitemap_include_posts" value="1" <?php checked( ! empty( $options['sitemap_include_posts'] ) ); ?>> Posts</label></p>
                <p><label><input type="checkbox" name="sitemap_include_pages" value="1" <?php checked( ! empty( $options['sitemap_include_pages'] ) ); ?>> Pages</label></p>
                <p><label><input type="checkbox" name="sitemap_include_products" value="1" <?php checked( ! empty( $options['sitemap_include_products'] ) ); ?>> WooCommerce Products</label></p>
                <p><label><input type="checkbox" name="sitemap_include_categories" value="1" <?php checked( ! empty( $options['sitemap_include_categories'] ) ); ?>> Categories</label></p>
                <p><label><input type="checkbox" name="sitemap_include_tags" value="1" <?php checked( ! empty( $options['sitemap_include_tags'] ) ); ?>> Tags</label></p>
            </div>

            <div class="bpb-card">
                <h2>Baidu SEO Output</h2>
                <p><label><input type="checkbox" name="enable_chinese_meta" value="1" <?php checked( ! empty( $options['enable_chinese_meta'] ) ); ?>> Output Baidu-friendly device/no-transform meta tags</label></p>
                <p><label><input type="checkbox" name="force_zh_cn" value="1" <?php checked( ! empty( $options['force_zh_cn'] ) ); ?>> Force <code>lang="zh-CN"</code> on the site</label></p>
                <p class="description"><strong>Only enable zh-CN if the website content is actually Simplified Chinese.</strong></p>
                <p><label><input type="checkbox" name="enable_seo_fields" value="1" <?php checked( ! empty( $options['enable_seo_fields'] ) ); ?>> Enable Baidu SEO title, description and canonical fields on content editor screens</label></p>
                <p class="description">If another SEO plugin already manages meta descriptions/canonicals, you may disable these fields to avoid duplicate output.</p>
            </div>
        </div>

        <div class="bpb-card bpb-section">
            <h2>Baiduspider robots.txt Rules</h2>
            <p>These rules are appended to WordPress virtual robots.txt output. Keep them simple and do not accidentally block public content.</p>
            <textarea class="large-text code" name="robots_rules"><?php echo esc_textarea( $options['robots_rules'] ); ?></textarea>
            <?php submit_button( 'Save Sitemap & SEO Settings' ); ?>
            </form>
        </div>
        <?php
    }

    private function render_analytics( $options ) {
        ?>
        <div class="bpb-card bpb-section">
            <h2>Baidu Tongji & Webmaster Tracking</h2>
            <p>Enter your Baidu Tongji tracking ID to load the official hm.baidu.com tracking script on the public site. You can also store an optional secondary webmaster/property reference ID.</p>
            <?php $this->form_open( 'analytics' ); ?>
            <input type="hidden" name="bpb_action" value="save_analytics">
            <table class="form-table">
                <tr><th><label for="tongji_id">Baidu Tongji Site ID</label></th><td><input class="regular-text" id="tongji_id" name="tongji_id" value="<?php echo esc_attr( $options['tongji_id'] ); ?>"><p class="description">Paste the tracking/site identifier used by your Baidu Tongji setup.</p></td></tr>
                <tr><th><label for="webmaster_analytics_id">Webmaster / Alternate ID</label></th><td><input class="regular-text" id="webmaster_analytics_id" name="webmaster_analytics_id" value="<?php echo esc_attr( $options['webmaster_analytics_id'] ); ?>"><p class="description">Optional field for a secondary Baidu property or reference identifier.</p></td></tr>
            </table>
            <?php submit_button( 'Save Analytics Settings' ); ?>
            </form>
        </div>
        <div class="notice notice-info inline"><p><strong>Privacy note:</strong> When a Tongji ID is configured, visitors' browsers will request Baidu's <code>hm.baidu.com</code> analytics script. Site owners should disclose analytics usage as required by their privacy policy and local laws.</p></div>
        <?php
    }

    private function render_logs() {
        $logs = Baidu_Booster_Logger::get( 150 );
        ?>
        <div class="bpb-card bpb-section">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap">
                <div><h2>Submission Activity</h2><p class="bpb-muted">Latest 150 API, XML-RPC and queue records.</p></div>
                <div style="display:flex;gap:8px">
                    <?php $this->form_open( 'logs' ); ?><input type="hidden" name="bpb_action" value="retry_failed"><button class="button" type="submit">Retry Failed</button></form>
                    <?php $this->form_open( 'logs' ); ?><input type="hidden" name="bpb_action" value="clear_logs"><button class="button" type="submit" onclick="return confirm('Clear all Baidu Ping Booster logs?');">Clear Logs</button></form>
                </div>
            </div>
            <?php $this->log_table( $logs ); ?>
        </div>
        <?php
    }

    private function log_table( $logs ) {
        if ( empty( $logs ) ) {
            echo '<p>No submission activity has been recorded yet.</p>';
            return;
        }
        echo '<div style="overflow:auto"><table class="bpb-table"><thead><tr><th>Date</th><th>URL</th><th>Method</th><th>Status</th><th>HTTP</th><th>Message</th></tr></thead><tbody>';
        foreach ( $logs as $log ) {
            $status_class = 'success' === $log->status ? 'ok' : ( 'failed' === $log->status ? 'bad' : '' );
            echo '<tr><td>' . esc_html( $log->created_at ) . '</td><td><a href="' . esc_url( $log->url ) . '" target="_blank" rel="noopener">' . esc_html( wp_html_excerpt( $log->url, 55, '…' ) ) . '</a></td><td><span class="bpb-pill">' . esc_html( strtoupper( $log->method ) ) . '</span></td><td><span class="bpb-status ' . esc_attr( $status_class ) . '">' . esc_html( ucfirst( $log->status ) ) . '</span></td><td>' . esc_html( $log->http_code ?: '—' ) . '</td><td class="bpb-log-message">' . esc_html( $log->message ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function render_tools() {
        $checks = Baidu_Booster_Diagnostics::checks();
        ?>
        <div class="bpb-two bpb-section">
            <div class="bpb-card">
                <h2>Diagnostics</h2>
                <?php foreach ( $checks as $check ) : ?>
                    <p><span class="bpb-status <?php echo $check['ok'] ? 'ok' : 'bad'; ?>">● <?php echo esc_html( $check['label'] ); ?></span><br><span class="bpb-muted"><?php echo esc_html( $check['detail'] ); ?></span></p>
                <?php endforeach; ?>
                <?php $this->form_open( 'tools' ); ?><input type="hidden" name="bpb_action" value="refresh_rewrites"><?php submit_button( 'Refresh Sitemap Rewrite Rules', 'secondary', 'submit', false ); ?></form>
            </div>

            <div class="bpb-card">
                <h2>System Information</h2>
                <table class="bpb-table">
                    <tr><th>Plugin</th><td><?php echo esc_html( BAIDU_BOOSTER_VERSION ); ?></td></tr>
                    <tr><th>WordPress</th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
                    <tr><th>PHP</th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
                    <tr><th>Home URL</th><td><?php echo esc_html( home_url( '/' ) ); ?></td></tr>
                    <tr><th>Sitemap</th><td><?php echo esc_html( home_url( '/baidu-sitemap.xml' ) ); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="bpb-two bpb-section">
            <div class="bpb-card">
                <h2>Export Settings</h2>
                <p>Download a JSON backup of Baidu Ping Booster configuration. API credentials are included, so store the file securely.</p>
                <?php $this->form_open( 'tools' ); ?><input type="hidden" name="bpb_action" value="export_settings"><?php submit_button( 'Export Settings JSON', 'secondary' ); ?></form>
            </div>
            <div class="bpb-card">
                <h2>Import Settings</h2>
                <p>Restore a JSON file previously exported by Baidu Ping Booster.</p>
                <?php $this->form_open( 'tools' ); ?><input type="hidden" name="bpb_action" value="import_settings"><input type="file" name="settings_file" accept="application/json,.json" required><?php submit_button( 'Import Settings JSON', 'secondary' ); ?></form>
            </div>
        </div>
        <?php
    }

    private function render_pingbooster() {
        $news     = Baidu_Booster_News::get_news_feed( 3 );
        $services = Baidu_Booster_News::company_cards();
        $plugins  = Baidu_Booster_News::plugin_cards();
        ?>
        <div class="bpb-hero">
            <span class="bpb-pill" style="background:#fff;color:#0b57a3">PINGBOOSTER COMPANY</span>
            <h2>SEO, GEO, Web & WordPress Plugin Services</h2>
            <p>Baidu Ping Booster will remain free. This page introduces the wider PingBooster ecosystem, company services, service packages and other plugins. Links currently use the main PingBooster website and can be updated individually in a future release.</p>
            <a class="button button-primary" href="https://pingbooster.site/" target="_blank" rel="noopener">Visit PingBooster</a>
        </div>

        <h2>Company Services & Packages</h2>
        <div class="bpb-grid">
            <?php foreach ( $services as $card ) : ?>
                <div class="bpb-card bpb-service"><h3><?php echo esc_html( $card['title'] ); ?></h3><p><?php echo esc_html( $card['text'] ); ?></p><a class="button" href="<?php echo esc_url( $card['url'] ); ?>" target="_blank" rel="noopener">View Services</a></div>
            <?php endforeach; ?>
        </div>

        <h2>PingBooster Plugins</h2>
        <div class="bpb-grid">
            <?php foreach ( $plugins as $card ) : ?>
                <div class="bpb-card"><h3><?php echo esc_html( $card['title'] ); ?></h3><p><?php echo esc_html( $card['text'] ); ?></p><a class="button" href="<?php echo esc_url( $card['url'] ); ?>" target="_blank" rel="noopener">Learn More</a></div>
            <?php endforeach; ?>
        </div>

        <h2>Latest News & Guides</h2>
        <div class="bpb-grid">
            <?php foreach ( $news as $item ) : ?>
                <div class="bpb-card"><span class="bpb-pill">NEWS</span><h3><?php echo esc_html( $item['title'] ); ?></h3><p><?php echo esc_html( wp_trim_words( $item['description'], 28 ) ); ?></p><a class="button" href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" rel="noopener">Read More</a></div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
