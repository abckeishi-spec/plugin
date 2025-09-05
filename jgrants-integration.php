<?php
/**
 * Plugin Name: J-Grants Integration
 * Plugin URI: https://example.com/jgrants-integration
 * Description: Jグランツ公開APIから補助金データを自動取得し、Gemini AIを用いて高品質なコンテンツを生成するWordPressプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: jgrants-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// プラグインが直接アクセスされることを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// PHPバージョンチェック
if (version_compare(PHP_VERSION, '7.2', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('J-Grants Integration requires PHP 7.2 or higher. Your current PHP version is ', 'jgrants-integration') . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

// WordPressバージョンチェック
global $wp_version;
if (version_compare($wp_version, '5.0', '<')) {
    add_action('admin_notices', function() {
        global $wp_version;
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('J-Grants Integration requires WordPress 5.0 or higher. Your current WordPress version is ', 'jgrants-integration') . $wp_version;
        echo '</p></div>';
    });
    return;
}

// プラグインの定数を定義
if (!defined('JGRANTS_PLUGIN_URL')) {
    define('JGRANTS_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('JGRANTS_PLUGIN_PATH')) {
    define('JGRANTS_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('JGRANTS_PLUGIN_VERSION')) {
    define('JGRANTS_PLUGIN_VERSION', '1.0.0');
}
if (!defined('JGRANTS_PLUGIN_BASENAME')) {
    define('JGRANTS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('JGRANTS_DEBUG')) {
    define('JGRANTS_DEBUG', WP_DEBUG);
}

/**
 * メインプラグインクラス
 */
class JGrantsIntegration {
    
    /**
     * プラグインのインスタンス
     */
    private static $instance = null;
    
    /**
     * シングルトンインスタンスを取得
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * プラグインの初期化
     */
    public function init() {
        // 言語ファイルの読み込み
        $this->load_plugin_textdomain();
        
        try {
            // 必要なファイルを読み込み
            $this->load_dependencies();
            
            // フックを設定
            $this->setup_hooks();
            
            // 管理画面の初期化
            if (is_admin()) {
                if (class_exists('JGrants_Admin')) {
                    new JGrants_Admin();
                } else {
                    $this->log_error('JGrants_Admin class not found');
                }
            }
            
            // 投稿タイプとタクソノミーの初期化
            if (class_exists('JGrants_Post_Type')) {
                new JGrants_Post_Type();
            } else {
                $this->log_error('JGrants_Post_Type class not found');
            }
            
            if (class_exists('JGrants_Taxonomies')) {
                new JGrants_Taxonomies();
            } else {
                $this->log_error('JGrants_Taxonomies class not found');
            }
            
            // ショートコードの登録
            add_shortcode('jgrants_list', array($this, 'shortcode_grants_list'));
            
            // Gutenbergブロックの登録
            add_action('init', array($this, 'register_gutenberg_block'));
            
        } catch (Exception $e) {
            $this->log_error('Plugin initialization failed: ' . $e->getMessage());
            add_action('admin_notices', array($this, 'show_error_notice'));
        }
    }
    
    /**
     * 言語ファイルの読み込み
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain(
            'jgrants-integration',
            false,
            dirname(JGRANTS_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * エラーログ出力
     */
    private function log_error($message) {
        if (JGRANTS_DEBUG) {
            error_log('[JGrants Integration] ' . $message);
        }
    }
    
    /**
     * エラー通知の表示
     */
    public function show_error_notice() {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo esc_html__('J-Grants Integration: プラグインの初期化中にエラーが発生しました。デバッグログを確認してください。', 'jgrants-integration');
        echo '</p></div>';
    }
    
    /**
     * 必要なファイルを読み込み
     */
    private function load_dependencies() {
        // デバッグとヘルスチェッククラスを最初に読み込み
        $debug_files = array(
            'includes/class-jgrants-debug.php',
            'includes/class-jgrants-health-check.php'
        );
        
        foreach ($debug_files as $file) {
            $file_path = JGRANTS_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        $required_files = array(
            'includes/class-jgrants-post-type.php',
            'includes/class-jgrants-taxonomies.php',
            'includes/class-jgrants-api-client.php',
            'includes/class-jgrants-ai-generator.php',
            'includes/class-jgrants-sync-manager.php',
            'includes/class-jgrants-settings.php',
            'includes/class-jgrants-ajax-handler.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = JGRANTS_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception('Required file not found: ' . $file);
            }
        }
        
        if (is_admin()) {
            $admin_file = JGRANTS_PLUGIN_PATH . 'admin/class-jgrants-admin.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            } else {
                throw new Exception('Admin file not found');
            }
        }
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_head', array($this, 'output_dynamic_css'));
    }
    
    /**
     * フロントエンドスクリプトの読み込み
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('jgrants-frontend', JGRANTS_PLUGIN_URL . 'assets/frontend.css', array(), JGRANTS_PLUGIN_VERSION);
        wp_enqueue_script('jgrants-frontend', JGRANTS_PLUGIN_URL . 'assets/frontend.js', array('jquery'), JGRANTS_PLUGIN_VERSION, true);
    }
    
    /**
     * 動的CSSの出力
     */
    public function output_dynamic_css() {
        $settings = get_option('jgrants_design_settings', array());
        $accent_color = isset($settings['accent_color']) ? $settings['accent_color'] : '#007cba';
        $status_badge_color = isset($settings['status_badge_color']) ? $settings['status_badge_color'] : '#28a745';
        
        echo '<style type="text/css">';
        echo '.jgrants-accent { color: ' . esc_attr($accent_color) . ' !important; }';
        echo '.jgrants-accent-bg { background-color: ' . esc_attr($accent_color) . ' !important; }';
        echo '.jgrants-status-badge { background-color: ' . esc_attr($status_badge_color) . ' !important; }';
        echo '</style>';
    }
    
    /**
     * ショートコードの処理
     */
    public function shortcode_grants_list($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => '',
            'prefecture' => '',
            'tag' => '',
            'order' => 'DESC',
            'orderby' => 'date'
        ), $atts, 'jgrants_list');
        
        return $this->render_grants_list($atts);
    }
    
    /**
     * Gutenbergブロックの登録
     */
    public function register_gutenberg_block() {
        if (function_exists('register_block_type')) {
            register_block_type('jgrants/grants-list', array(
                'render_callback' => array($this, 'render_gutenberg_block'),
                'attributes' => array(
                    'count' => array(
                        'type' => 'number',
                        'default' => 5
                    ),
                    'category' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                    'prefecture' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                    'tag' => array(
                        'type' => 'string',
                        'default' => ''
                    ),
                    'order' => array(
                        'type' => 'string',
                        'default' => 'DESC'
                    ),
                    'orderby' => array(
                        'type' => 'string',
                        'default' => 'date'
                    )
                )
            ));
        }
    }
    
    /**
     * Gutenbergブロックのレンダリング
     */
    public function render_gutenberg_block($attributes) {
        return $this->render_grants_list($attributes);
    }
    
    /**
     * 助成金一覧のレンダリング
     */
    private function render_grants_list($args) {
        // WP_Queryの引数を構築
        $query_args = array(
            'post_type' => 'grant',
            'posts_per_page' => intval($args['count']),
            'post_status' => 'publish',
            'order' => $args['order'],
            'orderby' => $args['orderby']
        );
        
        // タクソノミーフィルターの追加
        $tax_query = array();
        
        if (!empty($args['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $args['category']
            );
        }
        
        if (!empty($args['prefecture'])) {
            $tax_query[] = array(
                'taxonomy' => 'grant_prefecture',
                'field' => 'slug',
                'terms' => $args['prefecture']
            );
        }
        
        if (!empty($args['tag'])) {
            $tax_query[] = array(
                'taxonomy' => 'grant_tag',
                'field' => 'slug',
                'terms' => $args['tag']
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // テンプレートファイルの読み込み
        ob_start();
        
        // テーマ側のテンプレートファイルが存在するかチェック
        $theme_template = get_template_directory() . '/jgrants-integration/grants-list.php';
        $child_theme_template = get_stylesheet_directory() . '/jgrants-integration/grants-list.php';
        
        if (file_exists($child_theme_template)) {
            $template_path = $child_theme_template;
        } elseif (file_exists($theme_template)) {
            $template_path = $theme_template;
        } else {
            $template_path = JGRANTS_PLUGIN_PATH . 'templates/grants-list.php';
        }
        
        // クエリを実行してテンプレートに渡す
        $grants_query = new WP_Query($query_args);
        
        include $template_path;
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * プラグインの有効化
     */
    public function activate() {
        // 最小要件のチェック
        if (!$this->check_requirements()) {
            deactivate_plugins(JGRANTS_PLUGIN_BASENAME);
            wp_die(__('このプラグインを実行するための最小要件を満たしていません。', 'jgrants-integration'));
        }
        
        // 必要なディレクトリの作成
        $this->create_plugin_directories();
        
        // 投稿タイプとタクソノミーの登録（リライトルールのため）
        if (class_exists('JGrants_Post_Type')) {
            $post_type = new JGrants_Post_Type();
            $post_type->register_post_type();
        }
        
        if (class_exists('JGrants_Taxonomies')) {
            $taxonomies = new JGrants_Taxonomies();
            $taxonomies->register_taxonomies();
        }
        
        // フラッシュルールの更新
        flush_rewrite_rules();
        
        // デフォルト設定の作成
        $this->create_default_options();
        
        // アクティベーション時刻を記録
        update_option('jgrants_activated', time());
    }
    
    /**
     * 最小要件のチェック
     */
    private function check_requirements() {
        global $wp_version;
        
        // PHPバージョンチェック
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            return false;
        }
        
        // WordPressバージョンチェック
        if (version_compare($wp_version, '5.0', '<')) {
            return false;
        }
        
        // 必要な関数のチェック
        $required_functions = array('curl_init', 'json_decode');
        foreach ($required_functions as $func) {
            if (!function_exists($func)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 必要なディレクトリの作成
     */
    private function create_plugin_directories() {
        $directories = array(
            JGRANTS_PLUGIN_PATH . 'cache',
            JGRANTS_PLUGIN_PATH . 'logs'
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // .htaccessファイルでアクセス制限
                file_put_contents($dir . '/.htaccess', 'Deny from all');
            }
        }
    }
    
    /**
     * デフォルトオプションの作成
     */
    private function create_default_options() {
        // デフォルト設定の作成
        $default_settings = array(
            'accent_color' => '#007cba',
            'status_badge_color' => '#28a745'
        );
        add_option('jgrants_design_settings', $default_settings);
        
        // デフォルトのフィールドマッピング設定
        $default_mapping = array(
            'title' => 'post_title',
            'content' => 'post_content',
            'excerpt' => 'post_excerpt'
        );
        add_option('jgrants_field_mapping', $default_mapping);
        
        // AI設定のデフォルト値
        $default_ai_settings = array(
            'enable_ai_tags' => false,
            'gemini_api_key' => ''
        );
        add_option('jgrants_ai_settings', $default_ai_settings);
        
        // デバッグ設定
        add_option('jgrants_debug_mode', false);
    }
    
    /**
     * プラグインの無効化
     */
    public function deactivate() {
        // スケジュールされたイベントのクリア
        wp_clear_scheduled_hook('jgrants_sync_event');
        
        // フラッシュルールの更新
        flush_rewrite_rules();
        
        // 無効化時刻を記録
        update_option('jgrants_deactivated', time());
    }
}

// プラグインの初期化
function jgrants_integration_init() {
    return JGrantsIntegration::get_instance();
}
add_action('plugins_loaded', 'jgrants_integration_init', 10);

// プラグイン設定リンクの追加
add_filter('plugin_action_links_' . JGRANTS_PLUGIN_BASENAME, function($links) {
    $settings_link = '<a href="admin.php?page=jgrants-integration">' . __('設定', 'jgrants-integration') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// アンインストールフックの登録
register_uninstall_hook(__FILE__, 'jgrants_integration_uninstall');

/**
 * プラグインのアンインストール処理
 */
function jgrants_integration_uninstall() {
    // オプションの削除（必要に応じて）
    if (get_option('jgrants_delete_data_on_uninstall')) {
        delete_option('jgrants_design_settings');
        delete_option('jgrants_field_mapping');
        delete_option('jgrants_ai_settings');
        delete_option('jgrants_debug_mode');
        delete_option('jgrants_activated');
        delete_option('jgrants_deactivated');
        
        // 投稿タイプのデータ削除（オプション）
        // $posts = get_posts(array('post_type' => 'grant', 'numberposts' => -1));
        // foreach ($posts as $post) {
        //     wp_delete_post($post->ID, true);
        // }
    }
}

