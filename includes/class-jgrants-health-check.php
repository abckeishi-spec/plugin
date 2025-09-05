<?php
/**
 * プラグインヘルスチェッククラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Health_Check {
    
    /**
     * ヘルスチェックの実行
     */
    public static function run_checks() {
        $results = array(
            'status' => 'healthy',
            'checks' => array(),
            'errors' => array(),
            'warnings' => array()
        );
        
        // 必要なクラスの存在チェック
        $results['checks']['required_classes'] = self::check_required_classes();
        
        // 必要なファイルの存在チェック
        $results['checks']['required_files'] = self::check_required_files();
        
        // データベーステーブルのチェック
        $results['checks']['database'] = self::check_database();
        
        // 投稿タイプの登録チェック
        $results['checks']['post_types'] = self::check_post_types();
        
        // タクソノミーの登録チェック
        $results['checks']['taxonomies'] = self::check_taxonomies();
        
        // API接続チェック
        $results['checks']['api_connection'] = self::check_api_connection();
        
        // プラグイン設定チェック
        $results['checks']['plugin_settings'] = self::check_plugin_settings();
        
        // 結果の集計
        foreach ($results['checks'] as $check_name => $check) {
            if ($check['status'] === 'error') {
                $results['status'] = 'critical';
                $results['errors'][] = $check_name . ': ' . $check['message'];
            } elseif ($check['status'] === 'warning') {
                if ($results['status'] !== 'critical') {
                    $results['status'] = 'warning';
                }
                $results['warnings'][] = $check_name . ': ' . $check['message'];
            }
        }
        
        return $results;
    }
    
    /**
     * 必要なクラスの存在チェック
     */
    private static function check_required_classes() {
        $required_classes = array(
            'JGrants_Post_Type',
            'JGrants_Taxonomies',
            'JGrants_API_Client',
            'JGrants_AI_Generator',
            'JGrants_Sync_Manager',
            'JGrants_Settings',
            'JGrants_Ajax_Handler'
        );
        
        $missing = array();
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $missing[] = $class;
            }
        }
        
        if (empty($missing)) {
            return array(
                'status' => 'ok',
                'message' => 'All required classes are loaded'
            );
        } else {
            return array(
                'status' => 'error',
                'message' => 'Missing classes: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * 必要なファイルの存在チェック
     */
    private static function check_required_files() {
        $required_files = array(
            'admin/class-jgrants-admin.php',
            'includes/class-jgrants-post-type.php',
            'includes/class-jgrants-taxonomies.php',
            'includes/class-jgrants-api-client.php',
            'includes/class-jgrants-ai-generator.php',
            'includes/class-jgrants-sync-manager.php',
            'includes/class-jgrants-settings.php',
            'includes/class-jgrants-ajax-handler.php',
            'assets/frontend.css',
            'assets/frontend.js',
            'admin/css/admin.css',
            'admin/js/admin.js',
            'templates/grants-list.php'
        );
        
        $missing = array();
        foreach ($required_files as $file) {
            if (!file_exists(JGRANTS_PLUGIN_PATH . $file)) {
                $missing[] = $file;
            }
        }
        
        if (empty($missing)) {
            return array(
                'status' => 'ok',
                'message' => 'All required files exist'
            );
        } else {
            return array(
                'status' => 'error',
                'message' => 'Missing files: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * データベーステーブルのチェック
     */
    private static function check_database() {
        global $wpdb;
        
        // オプションテーブルに必要な設定が存在するかチェック
        $required_options = array(
            'jgrants_design_settings',
            'jgrants_field_mapping',
            'jgrants_ai_settings'
        );
        
        $missing = array();
        foreach ($required_options as $option) {
            if (get_option($option) === false) {
                $missing[] = $option;
            }
        }
        
        if (empty($missing)) {
            return array(
                'status' => 'ok',
                'message' => 'All database options are set'
            );
        } else {
            return array(
                'status' => 'warning',
                'message' => 'Missing options will be created: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * 投稿タイプの登録チェック
     */
    private static function check_post_types() {
        if (post_type_exists('grant')) {
            return array(
                'status' => 'ok',
                'message' => 'Grant post type is registered'
            );
        } else {
            return array(
                'status' => 'error',
                'message' => 'Grant post type is not registered'
            );
        }
    }
    
    /**
     * タクソノミーの登録チェック
     */
    private static function check_taxonomies() {
        $required_taxonomies = array(
            'grant_category',
            'grant_prefecture',
            'grant_tag'
        );
        
        $missing = array();
        foreach ($required_taxonomies as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                $missing[] = $taxonomy;
            }
        }
        
        if (empty($missing)) {
            return array(
                'status' => 'ok',
                'message' => 'All taxonomies are registered'
            );
        } else {
            return array(
                'status' => 'error',
                'message' => 'Missing taxonomies: ' . implode(', ', $missing)
            );
        }
    }
    
    /**
     * API接続チェック
     */
    private static function check_api_connection() {
        // J-Grants APIのテスト接続
        $api_url = 'https://api.jgrants-portal.go.jp/v1/subsidies';
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'status' => 'warning',
                'message' => 'Could not connect to J-Grants API: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return array(
                'status' => 'ok',
                'message' => 'J-Grants API connection successful'
            );
        } else {
            return array(
                'status' => 'warning',
                'message' => 'J-Grants API returned status code: ' . $status_code
            );
        }
    }
    
    /**
     * プラグイン設定チェック
     */
    private static function check_plugin_settings() {
        $warnings = array();
        
        // Gemini API キーのチェック
        $ai_settings = get_option('jgrants_ai_settings', array());
        if (empty($ai_settings['gemini_api_key'])) {
            $warnings[] = 'Gemini API key is not configured';
        }
        
        // 同期設定のチェック
        $sync_settings = get_option('jgrants_sync_settings', array());
        if (empty($sync_settings)) {
            $warnings[] = 'Sync settings are not configured';
        }
        
        if (empty($warnings)) {
            return array(
                'status' => 'ok',
                'message' => 'Plugin settings are properly configured'
            );
        } else {
            return array(
                'status' => 'warning',
                'message' => implode(', ', $warnings)
            );
        }
    }
    
    /**
     * 修復アクション
     */
    public static function attempt_repair() {
        $repairs = array();
        
        // 不足しているオプションの作成
        $default_settings = array(
            'jgrants_design_settings' => array(
                'accent_color' => '#007cba',
                'status_badge_color' => '#28a745'
            ),
            'jgrants_field_mapping' => array(
                'title' => 'post_title',
                'content' => 'post_content',
                'excerpt' => 'post_excerpt'
            ),
            'jgrants_ai_settings' => array(
                'enable_ai_tags' => false,
                'gemini_api_key' => ''
            )
        );
        
        foreach ($default_settings as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
                $repairs[] = 'Created missing option: ' . $option_name;
            }
        }
        
        // ディレクトリの作成
        $directories = array(
            JGRANTS_PLUGIN_PATH . 'cache',
            JGRANTS_PLUGIN_PATH . 'logs'
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                file_put_contents($dir . '/.htaccess', 'Deny from all');
                $repairs[] = 'Created missing directory: ' . basename($dir);
            }
        }
        
        // リライトルールのフラッシュ
        flush_rewrite_rules();
        $repairs[] = 'Flushed rewrite rules';
        
        return $repairs;
    }
    
    /**
     * 診断レポートの生成
     */
    public static function generate_report() {
        $report = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'health_check' => self::run_checks(),
            'system_info' => JGrants_Debug::get_debug_info(),
            'system_status' => JGrants_Debug::check_system_status()
        );
        
        return $report;
    }
}