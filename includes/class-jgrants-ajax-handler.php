<?php
/**
 * AJAX処理ハンドラークラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Ajax_Handler {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('wp_ajax_jgrants_test_ai_connection', array($this, 'test_ai_connection'));
        add_action('wp_ajax_jgrants_test_jgrants_api', array($this, 'test_jgrants_api'));
        add_action('wp_ajax_jgrants_sync_data', array($this, 'sync_data'));
        add_action('wp_ajax_jgrants_generate_batch_content', array($this, 'generate_batch_content'));
    }
    
    /**
     * AI接続テスト
     */
    public function test_ai_connection() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'], 'jgrants_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません。');
        }
        
        $ai_generator = new JGrants_AI_Generator();
        $result = $ai_generator->test_api_key();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * JグランツAPI接続テスト
     */
    public function test_jgrants_api() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'], 'jgrants_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません。');
        }
        
        $api_client = new JGrants_API_Client();
        $result = $api_client->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * データ同期
     */
    public function sync_data() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'], 'jgrants_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません。');
        }
        
        $sync_manager = new JGrants_Sync_Manager();
        
        // 同期パラメータの取得
        $params = array(
            'keyword' => sanitize_text_field($_POST['keyword'] ?? 'IT'),
            'limit' => intval($_POST['limit'] ?? 10)
        );
        
        $result = $sync_manager->sync_subsidies($params);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => '同期が完了しました。',
                'synced_count' => $result['synced_count'],
                'total_count' => $result['total_count']
            ));
        }
    }
    
    /**
     * バッチコンテンツ生成
     */
    public function generate_batch_content() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'], 'jgrants_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません。');
        }
        
        // パラメータの取得
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $count = intval($_POST['count'] ?? 5);
        $generate_ai_content = isset($_POST['generate_ai_content']) && $_POST['generate_ai_content'] === '1';
        
        if (empty($keyword) || strlen($keyword) < 2) {
            wp_send_json_error('キーワードは2文字以上で入力してください。');
        }
        
        if ($count < 1 || $count > 50) {
            wp_send_json_error('生成件数は1〜50件の範囲で指定してください。');
        }
        
        // API経由でデータを取得
        $api_client = new JGrants_API_Client();
        $api_params = array(
            'keyword' => $keyword,
            'sort' => 'created_date',
            'order' => 'DESC',
            'acceptance' => '1'
        );
        
        $api_response = $api_client->get_subsidies($api_params);
        
        if (is_wp_error($api_response)) {
            wp_send_json_error('データの取得に失敗しました: ' . $api_response->get_error_message());
        }
        
        $subsidies = array_slice($api_response['subsidies'], 0, $count);
        
        if (empty($subsidies)) {
            wp_send_json_error('指定されたキーワードでデータが見つかりませんでした。');
        }
        
        $results = array();
        $ai_generator = $generate_ai_content ? new JGrants_AI_Generator() : null;
        
        foreach ($subsidies as $subsidy) {
            // 既存の投稿をチェック
            $existing_post = get_posts(array(
                'post_type' => 'jgrants_subsidy',
                'meta_query' => array(
                    array(
                        'key' => 'jgrants_subsidy_id',
                        'value' => $subsidy['id'],
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if (!empty($existing_post)) {
                $results[] = array(
                    'status' => 'skipped',
                    'title' => $subsidy['title'],
                    'message' => '既に存在します'
                );
                continue;
            }
            
            // 投稿データの準備
            $post_data = array(
                'post_title' => $subsidy['title'],
                'post_content' => $subsidy['description'],
                'post_excerpt' => '',
                'post_status' => 'draft',
                'post_type' => 'jgrants_subsidy'
            );
            
            // AI生成コンテンツの追加
            if ($ai_generator && $ai_generator->is_api_key_set()) {
                $summary = $ai_generator->generate_summary($subsidy);
                if (!is_wp_error($summary)) {
                    $post_data['post_excerpt'] = $summary;
                }
                
                $detailed_description = $ai_generator->generate_detailed_description($subsidy);
                if (!is_wp_error($detailed_description)) {
                    $post_data['post_content'] = $detailed_description;
                }
            }
            
            // 投稿を作成
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                $results[] = array(
                    'status' => 'error',
                    'title' => $subsidy['title'],
                    'message' => $post_id->get_error_message()
                );
                continue;
            }
            
            // カスタムフィールドの設定
            $this->set_subsidy_meta($post_id, $subsidy);
            
            // タクソノミーの設定
            $this->set_subsidy_taxonomies($post_id, $subsidy);
            
            // AIタグの設定
            if ($ai_generator && $ai_generator->is_api_key_set()) {
                $ai_generator->set_ai_tags_to_post($post_id, $subsidy);
            }
            
            $results[] = array(
                'status' => 'success',
                'title' => $subsidy['title'],
                'post_id' => $post_id,
                'message' => '下書きを作成しました'
            );
            
            // API制限を考慮して少し待機
            if ($generate_ai_content) {
                sleep(1);
            }
        }
        
        wp_send_json_success(array(
            'message' => '下書きの一括生成が完了しました。',
            'results' => $results,
            'total_processed' => count($results)
        ));
    }
    
    /**
     * 助成金メタデータの設定
     */
    private function set_subsidy_meta($post_id, $subsidy_data) {
        $meta_fields = array(
            'jgrants_subsidy_id' => $subsidy_data['id'],
            'jgrants_organization' => $subsidy_data['organization'],
            'jgrants_purpose' => $subsidy_data['purpose'],
            'jgrants_amount_min' => $subsidy_data['amount_min'],
            'jgrants_amount_max' => $subsidy_data['amount_max'],
            'jgrants_rate' => $subsidy_data['rate'],
            'jgrants_application_start' => $subsidy_data['application_start'],
            'jgrants_application_end' => $subsidy_data['application_end'],
            'jgrants_status' => $subsidy_data['status'],
            'jgrants_url' => $subsidy_data['url']
        );
        
        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
    }
    
    /**
     * 助成金タクソノミーの設定
     */
    private function set_subsidy_taxonomies($post_id, $subsidy_data) {
        // カテゴリーの設定
        if (!empty($subsidy_data['industry'])) {
            wp_set_post_terms($post_id, $subsidy_data['industry'], 'grant_category');
        }
        
        // 都道府県の設定
        if (!empty($subsidy_data['target_area'])) {
            wp_set_post_terms($post_id, $subsidy_data['target_area'], 'grant_prefecture');
        }
    }
}

// インスタンス化
new JGrants_Ajax_Handler();

