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
 */

// プラグインが直接アクセスされることを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数を定義
define('JGRANTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JGRANTS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JGRANTS_PLUGIN_VERSION', '1.0.0');

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
        // 必要なファイルを読み込み
        $this->load_dependencies();
        
        // フックを設定
        $this->setup_hooks();
        
        // 管理画面の初期化
        if (is_admin()) {
            new JGrants_Admin();
        }
        
        // 投稿タイプとタクソノミーの初期化
        new JGrants_Post_Type();
        new JGrants_Taxonomies();
        
        // ショートコードの登録
        add_shortcode('jgrants_list', array($this, 'shortcode_grants_list'));
        
        // Gutenbergブロックの登録
        add_action('init', array($this, 'register_gutenberg_block'));
    }
    
    /**
     * 必要なファイルを読み込み
     */
    private function load_dependencies() {
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-post-type.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-taxonomies.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-api-client.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-ai-generator.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-sync-manager.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-settings.php';
        require_once JGRANTS_PLUGIN_PATH . 'includes/class-jgrants-ajax-handler.php';
        
        if (is_admin()) {
            require_once JGRANTS_PLUGIN_PATH . 'admin/class-jgrants-admin.php';
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
        // フラッシュルールの更新
        flush_rewrite_rules();
        
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
    }
    
    /**
     * プラグインの無効化
     */
    public function deactivate() {
        // フラッシュルールの更新
        flush_rewrite_rules();
    }
}

// プラグインの初期化
JGrantsIntegration::get_instance();

