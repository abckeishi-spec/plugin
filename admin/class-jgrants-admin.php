<?php
/**
 * 管理画面クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Admin {
    
    /**
     * 同期マネージャー
     */
    private $sync_manager;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->sync_manager = new JGrants_Sync_Manager();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_jgrants_batch_create_drafts', array($this, 'ajax_batch_create_drafts'));
        add_action('wp_ajax_jgrants_batch_publish_posts', array($this, 'ajax_batch_publish_posts'));
        add_action('wp_ajax_jgrants_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_jgrants_test_ai_connection', array($this, 'ajax_test_ai_connection'));
    }
    
    /**
     * 管理メニューの追加
     */
    public function add_admin_menu() {
        add_menu_page(
            'JGrants連携',
            'JGrants連携',
            'manage_options',
            'jgrants-integration',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'jgrants-integration',
            'ダッシュボード',
            'ダッシュボード',
            'manage_options',
            'jgrants-integration',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'jgrants-integration',
            'フィールドマッピング',
            'フィールドマッピング',
            'manage_options',
            'jgrants-field-mapping',
            array($this, 'field_mapping_page')
        );
        
        add_submenu_page(
            'jgrants-integration',
            '下書き一括生成',
            '下書き一括生成',
            'manage_options',
            'jgrants-batch-generate',
            array($this, 'batch_generate_page')
        );
        
        add_submenu_page(
            'jgrants-integration',
            'AI設定',
            'AI設定',
            'manage_options',
            'jgrants-ai-settings',
            array($this, 'ai_settings_page')
        );
        
        add_submenu_page(
            'jgrants-integration',
            'デザイン設定',
            'デザイン設定',
            'manage_options',
            'jgrants-design-settings',
            array($this, 'design_settings_page')
        );
        
        add_submenu_page(
            'jgrants-integration',
            'システムステータス',
            'システムステータス',
            'manage_options',
            'jgrants-status',
            array($this, 'status_page')
        );
    }
    
    /**
     * 管理画面スクリプトの読み込み
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'jgrants') === false) {
            return;
        }
        
        wp_enqueue_style('jgrants-admin', JGRANTS_PLUGIN_URL . 'admin/css/admin.css', array(), JGRANTS_PLUGIN_VERSION);
        wp_enqueue_script('jgrants-admin', JGRANTS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), JGRANTS_PLUGIN_VERSION, true);
        
        wp_localize_script('jgrants-admin', 'jgrants_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jgrants_ajax_nonce'),
            'messages' => array(
                'confirm_publish' => '選択した投稿を公開しますか？',
                'confirm_generate' => '下書きを生成しますか？',
                'processing' => '処理中...',
                'success' => '処理が完了しました。',
                'error' => 'エラーが発生しました。'
            )
        ));
        
        // カラーピッカーの読み込み
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    
    /**
     * ダッシュボードページ
     */
    public function dashboard_page() {
        $stats = $this->sync_manager->get_sync_statistics();
        $sync_history = get_option('jgrants_sync_history', array());
        
        include JGRANTS_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * フィールドマッピングページ
     */
    public function field_mapping_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'jgrants_field_mapping')) {
            $field_mapping = array();
            
            if (isset($_POST['field_mapping']) && is_array($_POST['field_mapping'])) {
                foreach ($_POST['field_mapping'] as $api_field => $acf_field) {
                    if (!empty($acf_field)) {
                        $field_mapping[sanitize_key($api_field)] = sanitize_text_field($acf_field);
                    }
                }
            }
            
            update_option('jgrants_field_mapping', $field_mapping);
            add_settings_error('jgrants_field_mapping', 'settings_updated', 'フィールドマッピングを保存しました。', 'updated');
        }
        
        $field_mapping = get_option('jgrants_field_mapping', array());
        $api_fields = JGrants_Settings::get_api_fields();
        $acf_fields = JGrants_Settings::get_acf_field_groups();
        
        include JGRANTS_PLUGIN_PATH . 'admin/views/field-mapping.php';
    }
    
    /**
     * 下書き一括生成ページ
     */
    public function batch_generate_page() {
        $draft_posts = get_posts(array(
            'post_type' => 'grant',
            'post_status' => 'draft',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        include JGRANTS_PLUGIN_PATH . 'admin/views/batch-generate.php';
    }
    
    /**
     * AI設定ページ
     */
    public function ai_settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'jgrants_ai_settings')) {
            $ai_settings = array(
                'enable_ai_tags' => isset($_POST['enable_ai_tags']) ? true : false,
                'gemini_api_key' => sanitize_text_field($_POST['gemini_api_key']),
                'ai_temperature' => floatval($_POST['ai_temperature']),
                'max_tokens' => intval($_POST['max_tokens'])
            );
            
            update_option('jgrants_ai_settings', $ai_settings);
            add_settings_error('jgrants_ai_settings', 'settings_updated', 'AI設定を保存しました。', 'updated');
        }
        
        $ai_settings = get_option('jgrants_ai_settings', array(
            'enable_ai_tags' => false,
            'gemini_api_key' => '',
            'ai_temperature' => 0.7,
            'max_tokens' => 1024
        ));
        
        include JGRANTS_PLUGIN_PATH . 'admin/views/ai-settings.php';
    }
    
    /**
     * デザイン設定ページ
     */
    public function design_settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'jgrants_design_settings')) {
            $design_settings = array(
                'accent_color' => sanitize_hex_color($_POST['accent_color']),
                'status_badge_color' => sanitize_hex_color($_POST['status_badge_color']),
                'custom_css' => wp_strip_all_tags($_POST['custom_css'])
            );
            
            update_option('jgrants_design_settings', $design_settings);
            add_settings_error('jgrants_design_settings', 'settings_updated', 'デザイン設定を保存しました。', 'updated');
        }
        
        $design_settings = get_option('jgrants_design_settings', array(
            'accent_color' => '#007cba',
            'status_badge_color' => '#28a745',
            'custom_css' => ''
        ));
        
        include JGRANTS_PLUGIN_PATH . 'admin/views/design-settings.php';
    }
    
    /**
     * AJAX: 下書き一括生成
     */
    public function ajax_batch_create_drafts() {
        check_ajax_referer('jgrants_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $count = intval($_POST['count']);
        $keyword = sanitize_text_field($_POST['keyword']);
        
        if ($count < 1 || $count > 50) {
            wp_send_json_error('生成件数は1〜50件の範囲で指定してください。');
        }
        
        if (strlen($keyword) < 2) {
            wp_send_json_error('キーワードは2文字以上で入力してください。');
        }
        
        $search_params = array(
            'keyword' => $keyword,
            'sort' => 'updated_date',
            'order' => 'desc',
            'acceptance' => 'all'
        );
        
        $results = $this->sync_manager->batch_create_drafts($count, $search_params);
        
        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }
        
        // 同期履歴の記録
        $this->sync_manager->record_sync_history('batch_create_drafts', $results);
        
        wp_send_json_success(array(
            'message' => count($results) . '件の下書きを生成しました。',
            'results' => $results
        ));
    }
    
    /**
     * AJAX: 投稿一括公開
     */
    public function ajax_batch_publish_posts() {
        check_ajax_referer('jgrants_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        
        if (empty($post_ids)) {
            wp_send_json_error('公開する投稿が選択されていません。');
        }
        
        $results = $this->sync_manager->batch_publish_posts($post_ids);
        
        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }
        
        // 同期履歴の記録
        $this->sync_manager->record_sync_history('batch_publish_posts', $results);
        
        $success_count = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => $success_count . '件の投稿を公開しました。',
            'results' => $results
        ));
    }
    
    /**
     * AJAX: API接続テスト
     */
    public function ajax_test_api_connection() {
        check_ajax_referer('jgrants_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $api_client = new JGrants_API_Client();
        $result = $api_client->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * システムステータスページ
     */
    public function status_page() {
        include JGRANTS_PLUGIN_PATH . 'admin/views/status-page.php';
    }
    
    /**
     * AJAX: AI接続テスト
     */
    public function ajax_test_ai_connection() {
        check_ajax_referer('jgrants_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $ai_generator = new JGrants_AI_Generator();
        $result = $ai_generator->test_api_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}

