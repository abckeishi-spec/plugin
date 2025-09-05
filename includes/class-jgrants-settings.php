<?php
/**
 * 設定管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Settings {
    
    /**
     * 設定オプション名
     */
    const FIELD_MAPPING_OPTION = 'jgrants_field_mapping';
    const AI_SETTINGS_OPTION = 'jgrants_ai_settings';
    const DESIGN_SETTINGS_OPTION = 'jgrants_design_settings';
    const SYNC_SETTINGS_OPTION = 'jgrants_sync_settings';
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * 設定の登録
     */
    public function register_settings() {
        // フィールドマッピング設定
        register_setting('jgrants_field_mapping', self::FIELD_MAPPING_OPTION, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_field_mapping')
        ));
        
        // AI設定
        register_setting('jgrants_ai_settings', self::AI_SETTINGS_OPTION, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_ai_settings')
        ));
        
        // デザイン設定
        register_setting('jgrants_design_settings', self::DESIGN_SETTINGS_OPTION, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_design_settings')
        ));
        
        // 同期設定
        register_setting('jgrants_sync_settings', self::SYNC_SETTINGS_OPTION, array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_sync_settings')
        ));
    }
    
    /**
     * フィールドマッピング設定のサニタイズ
     */
    public function sanitize_field_mapping($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $api_field => $acf_field) {
                $sanitized[sanitize_key($api_field)] = sanitize_text_field($acf_field);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * AI設定のサニタイズ
     */
    public function sanitize_ai_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            $sanitized['enable_ai_tags'] = isset($input['enable_ai_tags']) ? (bool) $input['enable_ai_tags'] : false;
            $sanitized['gemini_api_key'] = isset($input['gemini_api_key']) ? sanitize_text_field($input['gemini_api_key']) : '';
            $sanitized['ai_temperature'] = isset($input['ai_temperature']) ? floatval($input['ai_temperature']) : 0.7;
            $sanitized['max_tokens'] = isset($input['max_tokens']) ? intval($input['max_tokens']) : 1024;
        }
        
        return $sanitized;
    }
    
    /**
     * デザイン設定のサニタイズ
     */
    public function sanitize_design_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            $sanitized['accent_color'] = isset($input['accent_color']) ? sanitize_hex_color($input['accent_color']) : '#007cba';
            $sanitized['status_badge_color'] = isset($input['status_badge_color']) ? sanitize_hex_color($input['status_badge_color']) : '#28a745';
            $sanitized['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';
        }
        
        return $sanitized;
    }
    
    /**
     * 同期設定のサニタイズ
     */
    public function sanitize_sync_settings($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            $sanitized['auto_sync'] = isset($input['auto_sync']) ? (bool) $input['auto_sync'] : false;
            $sanitized['sync_interval'] = isset($input['sync_interval']) ? intval($input['sync_interval']) : 24;
            $sanitized['default_status'] = isset($input['default_status']) ? sanitize_key($input['default_status']) : 'draft';
            $sanitized['default_keyword'] = isset($input['default_keyword']) ? sanitize_text_field($input['default_keyword']) : 'デジタル';
        }
        
        return $sanitized;
    }
    
    /**
     * フィールドマッピング設定の取得
     */
    public static function get_field_mapping() {
        return get_option(self::FIELD_MAPPING_OPTION, array(
            'title' => 'post_title',
            'content' => 'post_content',
            'excerpt' => 'post_excerpt'
        ));
    }
    
    /**
     * AI設定の取得
     */
    public static function get_ai_settings() {
        return get_option(self::AI_SETTINGS_OPTION, array(
            'enable_ai_tags' => false,
            'gemini_api_key' => '',
            'ai_temperature' => 0.7,
            'max_tokens' => 1024
        ));
    }
    
    /**
     * デザイン設定の取得
     */
    public static function get_design_settings() {
        return get_option(self::DESIGN_SETTINGS_OPTION, array(
            'accent_color' => '#007cba',
            'status_badge_color' => '#28a745',
            'custom_css' => ''
        ));
    }
    
    /**
     * 同期設定の取得
     */
    public static function get_sync_settings() {
        return get_option(self::SYNC_SETTINGS_OPTION, array(
            'auto_sync' => false,
            'sync_interval' => 24,
            'default_status' => 'draft',
            'default_keyword' => 'デジタル'
        ));
    }
    
    /**
     * ACFフィールドグループの取得
     */
    public static function get_acf_field_groups() {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }
        
        $field_groups = acf_get_field_groups(array(
            'post_type' => 'grant'
        ));
        
        $fields = array();
        
        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            
            if ($group_fields) {
                foreach ($group_fields as $field) {
                    $fields[$field['name']] = array(
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'group' => $group['title']
                    );
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * APIフィールドの定義を取得
     */
    public static function get_api_fields() {
        return array(
            'title' => array(
                'label' => '助成金名',
                'type' => 'text',
                'description' => 'APIから取得される助成金の名称'
            ),
            'organization' => array(
                'label' => '実施機関',
                'type' => 'text',
                'description' => '助成金を実施する機関名'
            ),
            'description' => array(
                'label' => '概要',
                'type' => 'textarea',
                'description' => '助成金の概要説明'
            ),
            'purpose' => array(
                'label' => '利用目的',
                'type' => 'textarea',
                'description' => '助成金の利用目的'
            ),
            'amount_min' => array(
                'label' => '最小補助金額',
                'type' => 'number',
                'description' => '補助金の最小金額'
            ),
            'amount_max' => array(
                'label' => '最大補助金額',
                'type' => 'number',
                'description' => '補助金の最大金額'
            ),
            'rate' => array(
                'label' => '補助率',
                'type' => 'text',
                'description' => '補助金の補助率'
            ),
            'application_start' => array(
                'label' => '申請開始日',
                'type' => 'date',
                'description' => '申請受付開始日'
            ),
            'application_end' => array(
                'label' => '申請終了日',
                'type' => 'date',
                'description' => '申請受付終了日'
            ),
            'implementation_start' => array(
                'label' => '実施開始日',
                'type' => 'date',
                'description' => '事業実施開始日'
            ),
            'implementation_end' => array(
                'label' => '実施終了日',
                'type' => 'date',
                'description' => '事業実施終了日'
            ),
            'url' => array(
                'label' => '詳細URL',
                'type' => 'url',
                'description' => '助成金の詳細ページURL'
            ),
            'contact' => array(
                'label' => '問い合わせ先',
                'type' => 'textarea',
                'description' => '問い合わせ先情報'
            ),
            'industry' => array(
                'label' => '対象業種',
                'type' => 'text',
                'description' => '対象となる業種（複数の場合はカンマ区切り）'
            ),
            'target_area' => array(
                'label' => '対象地域',
                'type' => 'text',
                'description' => '対象となる地域（複数の場合はカンマ区切り）'
            ),
            'target_employees' => array(
                'label' => '対象従業員数',
                'type' => 'text',
                'description' => '対象となる従業員数の条件'
            ),
            'status' => array(
                'label' => 'ステータス',
                'type' => 'select',
                'description' => '助成金の現在のステータス'
            ),
            'ai_summary' => array(
                'label' => 'AI要約',
                'type' => 'textarea',
                'description' => 'AIが生成した要約文'
            ),
            'ai_description' => array(
                'label' => 'AI詳細説明',
                'type' => 'textarea',
                'description' => 'AIが生成した詳細説明'
            )
        );
    }
    
    /**
     * デフォルト設定の作成
     */
    public static function create_default_settings() {
        // フィールドマッピングのデフォルト
        if (!get_option(self::FIELD_MAPPING_OPTION)) {
            add_option(self::FIELD_MAPPING_OPTION, array(
                'title' => 'post_title',
                'content' => 'post_content',
                'excerpt' => 'post_excerpt'
            ));
        }
        
        // AI設定のデフォルト
        if (!get_option(self::AI_SETTINGS_OPTION)) {
            add_option(self::AI_SETTINGS_OPTION, array(
                'enable_ai_tags' => false,
                'gemini_api_key' => '',
                'ai_temperature' => 0.7,
                'max_tokens' => 1024
            ));
        }
        
        // デザイン設定のデフォルト
        if (!get_option(self::DESIGN_SETTINGS_OPTION)) {
            add_option(self::DESIGN_SETTINGS_OPTION, array(
                'accent_color' => '#007cba',
                'status_badge_color' => '#28a745',
                'custom_css' => ''
            ));
        }
        
        // 同期設定のデフォルト
        if (!get_option(self::SYNC_SETTINGS_OPTION)) {
            add_option(self::SYNC_SETTINGS_OPTION, array(
                'auto_sync' => false,
                'sync_interval' => 24,
                'default_status' => 'draft',
                'default_keyword' => 'デジタル'
            ));
        }
    }
    
    /**
     * 設定の削除
     */
    public static function delete_settings() {
        delete_option(self::FIELD_MAPPING_OPTION);
        delete_option(self::AI_SETTINGS_OPTION);
        delete_option(self::DESIGN_SETTINGS_OPTION);
        delete_option(self::SYNC_SETTINGS_OPTION);
    }
    
    /**
     * 設定のエクスポート
     */
    public static function export_settings() {
        $settings = array(
            'field_mapping' => self::get_field_mapping(),
            'ai_settings' => self::get_ai_settings(),
            'design_settings' => self::get_design_settings(),
            'sync_settings' => self::get_sync_settings(),
            'export_date' => current_time('mysql'),
            'plugin_version' => JGRANTS_PLUGIN_VERSION
        );
        
        return json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 設定のインポート
     */
    public static function import_settings($json_data) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSONの解析に失敗しました。');
        }
        
        $imported = array();
        
        if (isset($data['field_mapping'])) {
            update_option(self::FIELD_MAPPING_OPTION, $data['field_mapping']);
            $imported[] = 'フィールドマッピング';
        }
        
        if (isset($data['ai_settings'])) {
            update_option(self::AI_SETTINGS_OPTION, $data['ai_settings']);
            $imported[] = 'AI設定';
        }
        
        if (isset($data['design_settings'])) {
            update_option(self::DESIGN_SETTINGS_OPTION, $data['design_settings']);
            $imported[] = 'デザイン設定';
        }
        
        if (isset($data['sync_settings'])) {
            update_option(self::SYNC_SETTINGS_OPTION, $data['sync_settings']);
            $imported[] = '同期設定';
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'message' => '設定をインポートしました: ' . implode(', ', $imported)
        );
    }
}

