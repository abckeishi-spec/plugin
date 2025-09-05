<?php
/**
 * データ同期管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Sync_Manager {
    
    /**
     * APIクライアント
     */
    private $api_client;
    
    /**
     * AI生成器
     */
    private $ai_generator;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->api_client = new JGrants_API_Client();
        $this->ai_generator = new JGrants_AI_Generator();
    }
    
    /**
     * 下書きを一括生成
     */
    public function batch_create_drafts($count = 10, $search_params = array()) {
        // デフォルトの検索パラメータ
        $default_params = array(
            'keyword' => 'デジタル',
            'sort' => 'updated_date',
            'order' => 'desc',
            'acceptance' => 'all'
        );
        
        $search_params = wp_parse_args($search_params, $default_params);
        
        // APIから助成金データを取得
        $api_response = $this->api_client->get_subsidies($search_params);
        
        if (is_wp_error($api_response)) {
            return $api_response;
        }
        
        $subsidies = array_slice($api_response['subsidies'], 0, $count);
        
        if (empty($subsidies)) {
            return new WP_Error('no_data', '取得できる助成金データがありません。');
        }
        
        $results = array();
        $field_mapping = get_option('jgrants_field_mapping', array());
        
        foreach ($subsidies as $subsidy) {
            // 既存の投稿をチェック
            $existing_post = $this->find_existing_post($subsidy['id']);
            if ($existing_post) {
                $results[] = array(
                    'success' => false,
                    'subsidy_id' => $subsidy['id'],
                    'post_id' => $existing_post->ID,
                    'message' => '既に投稿が存在します。'
                );
                continue;
            }
            
            // AIコンテンツの生成
            $ai_content = $this->generate_ai_content($subsidy);
            
            // 投稿データの準備
            $post_data = $this->prepare_post_data($subsidy, $ai_content, $field_mapping);
            
            // 投稿の作成
            $post_id = JGrants_Post_Type::create_grant_post($post_data, 'draft');
            
            if ($post_id) {
                // ACFフィールドの保存
                $this->save_acf_fields($post_id, $subsidy, $ai_content, $field_mapping);
                
                // タクソノミーの設定
                $this->set_taxonomies($post_id, $subsidy);
                
                // AIタグの設定
                $this->ai_generator->set_ai_tags_to_post($post_id, $subsidy);
                
                $results[] = array(
                    'success' => true,
                    'subsidy_id' => $subsidy['id'],
                    'post_id' => $post_id,
                    'message' => '下書きを作成しました。'
                );
            } else {
                $results[] = array(
                    'success' => false,
                    'subsidy_id' => $subsidy['id'],
                    'post_id' => null,
                    'message' => '投稿の作成に失敗しました。'
                );
            }
        }
        
        return $results;
    }
    
    /**
     * 投稿を一括公開
     */
    public function batch_publish_posts($post_ids) {
        if (empty($post_ids) || !is_array($post_ids)) {
            return new WP_Error('invalid_post_ids', '無効な投稿IDです。');
        }
        
        $results = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = intval($post_id);
            
            // 投稿の存在確認
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'grant') {
                $results[$post_id] = array(
                    'success' => false,
                    'message' => '投稿が見つかりません。'
                );
                continue;
            }
            
            // 投稿を公開
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            
            if (is_wp_error($result)) {
                $results[$post_id] = array(
                    'success' => false,
                    'message' => $result->get_error_message()
                );
            } else {
                $results[$post_id] = array(
                    'success' => true,
                    'message' => '投稿を公開しました。'
                );
            }
        }
        
        return $results;
    }
    
    /**
     * 既存投稿の検索
     */
    private function find_existing_post($subsidy_id) {
        $posts = get_posts(array(
            'post_type' => 'grant',
            'meta_key' => 'jgrants_subsidy_id',
            'meta_value' => $subsidy_id,
            'post_status' => array('publish', 'draft', 'private'),
            'numberposts' => 1
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * AIコンテンツの生成
     */
    private function generate_ai_content($subsidy) {
        $content = array(
            'summary' => '',
            'detailed_description' => '',
            'tags' => array()
        );
        
        // 要約の生成
        $summary = $this->ai_generator->generate_summary($subsidy);
        if (!is_wp_error($summary)) {
            $content['summary'] = $summary;
        }
        
        // 詳細説明の生成
        $description = $this->ai_generator->generate_detailed_description($subsidy);
        if (!is_wp_error($description)) {
            $content['detailed_description'] = $description;
        }
        
        // タグの生成
        $tags = $this->ai_generator->generate_tags_for_post($subsidy);
        if (!is_wp_error($tags)) {
            $content['tags'] = $tags;
        }
        
        return $content;
    }
    
    /**
     * 投稿データの準備
     */
    private function prepare_post_data($subsidy, $ai_content, $field_mapping) {
        $post_data = array(
            'title' => $subsidy['title'],
            'content' => !empty($ai_content['detailed_description']) ? $ai_content['detailed_description'] : $subsidy['description'],
            'excerpt' => !empty($ai_content['summary']) ? $ai_content['summary'] : wp_trim_words($subsidy['description'], 50),
            'meta' => array(
                'jgrants_subsidy_id' => $subsidy['id'],
                'jgrants_organization' => $subsidy['organization'],
                'jgrants_amount_min' => $subsidy['amount_min'],
                'jgrants_amount_max' => $subsidy['amount_max'],
                'jgrants_rate' => $subsidy['rate'],
                'jgrants_application_start' => $subsidy['application_start'],
                'jgrants_application_end' => $subsidy['application_end'],
                'jgrants_implementation_start' => $subsidy['implementation_start'],
                'jgrants_implementation_end' => $subsidy['implementation_end'],
                'jgrants_url' => $subsidy['url'],
                'jgrants_contact' => $subsidy['contact'],
                'jgrants_status' => $subsidy['status'],
                'jgrants_updated_date' => $subsidy['updated_date']
            ),
            'taxonomies' => array()
        );
        
        return $post_data;
    }
    
    /**
     * ACFフィールドの保存
     */
    private function save_acf_fields($post_id, $subsidy, $ai_content, $field_mapping) {
        if (!function_exists('update_field')) {
            return; // ACFが有効でない場合は何もしない
        }
        
        // フィールドマッピングに基づいてACFフィールドを保存
        foreach ($field_mapping as $api_field => $acf_field) {
            if (empty($acf_field)) {
                continue;
            }
            
            $value = '';
            
            switch ($api_field) {
                case 'title':
                    $value = $subsidy['title'];
                    break;
                case 'organization':
                    $value = $subsidy['organization'];
                    break;
                case 'description':
                    $value = $subsidy['description'];
                    break;
                case 'purpose':
                    $value = $subsidy['purpose'];
                    break;
                case 'amount_min':
                    $value = $subsidy['amount_min'];
                    break;
                case 'amount_max':
                    $value = $subsidy['amount_max'];
                    break;
                case 'rate':
                    $value = $subsidy['rate'];
                    break;
                case 'application_start':
                    $value = $subsidy['application_start'];
                    break;
                case 'application_end':
                    $value = $subsidy['application_end'];
                    break;
                case 'implementation_start':
                    $value = $subsidy['implementation_start'];
                    break;
                case 'implementation_end':
                    $value = $subsidy['implementation_end'];
                    break;
                case 'url':
                    $value = $subsidy['url'];
                    break;
                case 'contact':
                    $value = $subsidy['contact'];
                    break;
                case 'ai_summary':
                    $value = $ai_content['summary'];
                    break;
                case 'ai_description':
                    $value = $ai_content['detailed_description'];
                    break;
                case 'industry':
                    $value = implode(', ', $subsidy['industry']);
                    break;
                case 'target_area':
                    $value = implode(', ', $subsidy['target_area']);
                    break;
                case 'target_employees':
                    $value = $subsidy['target_employees'];
                    break;
                case 'status':
                    $value = $subsidy['status'];
                    break;
            }
            
            if (!empty($value)) {
                update_field($acf_field, $value, $post_id);
            }
        }
    }
    
    /**
     * タクソノミーの設定
     */
    private function set_taxonomies($post_id, $subsidy) {
        // 業種をカテゴリーに設定
        if (!empty($subsidy['industry'])) {
            $categories = array();
            foreach ($subsidy['industry'] as $industry) {
                $category_map = $this->get_industry_category_mapping();
                if (isset($category_map[$industry])) {
                    $categories[] = $category_map[$industry];
                }
            }
            if (!empty($categories)) {
                JGrants_Taxonomies::set_post_terms($post_id, $categories, 'grant_category');
            }
        }
        
        // 対象地域を都道府県に設定
        if (!empty($subsidy['target_area'])) {
            $prefectures = array();
            foreach ($subsidy['target_area'] as $area) {
                $prefecture = $this->extract_prefecture_from_area($area);
                if ($prefecture) {
                    $prefectures[] = $prefecture;
                }
            }
            if (!empty($prefectures)) {
                JGrants_Taxonomies::set_post_terms($post_id, $prefectures, 'grant_prefecture');
            }
        }
    }
    
    /**
     * 業種とカテゴリーのマッピング
     */
    private function get_industry_category_mapping() {
        return array(
            '情報通信業' => 'it-digital',
            'IT' => 'it-digital',
            'デジタル' => 'it-digital',
            '製造業' => 'manufacturing',
            'サービス業' => 'service',
            '農業' => 'agriculture',
            '観光業' => 'tourism',
            '医療' => 'healthcare',
            '介護' => 'healthcare',
            '教育' => 'education',
            '環境' => 'environment',
            'エネルギー' => 'environment',
            'スタートアップ' => 'startup',
            '研究開発' => 'research'
        );
    }
    
    /**
     * 地域名から都道府県を抽出
     */
    private function extract_prefecture_from_area($area) {
        $prefectures = array(
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        );
        
        foreach ($prefectures as $prefecture) {
            if (strpos($area, $prefecture) !== false) {
                return $prefecture;
            }
        }
        
        return null;
    }
    
    /**
     * 同期統計の取得
     */
    public function get_sync_statistics() {
        $stats = array(
            'total_grants' => 0,
            'published_grants' => 0,
            'draft_grants' => 0,
            'last_sync' => '',
            'api_status' => 'unknown',
            'ai_status' => 'unknown'
        );
        
        // 投稿数の取得
        $grant_counts = wp_count_posts('grant');
        $stats['total_grants'] = $grant_counts->publish + $grant_counts->draft + $grant_counts->private;
        $stats['published_grants'] = $grant_counts->publish;
        $stats['draft_grants'] = $grant_counts->draft;
        
        // 最後の同期日時
        $stats['last_sync'] = get_option('jgrants_last_sync', '未実行');
        
        // API接続状況
        $api_test = $this->api_client->test_connection();
        $stats['api_status'] = $api_test['success'] ? 'connected' : 'error';
        
        // AI接続状況
        $ai_test = $this->ai_generator->test_api_connection();
        $stats['ai_status'] = $ai_test['success'] ? 'connected' : 'error';
        
        return $stats;
    }
    
    /**
     * 同期履歴の記録
     */
    public function record_sync_history($action, $results) {
        $history = get_option('jgrants_sync_history', array());
        
        $record = array(
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'results' => $results,
            'success_count' => 0,
            'error_count' => 0
        );
        
        // 成功・失敗数をカウント
        if (is_array($results)) {
            foreach ($results as $result) {
                if (isset($result['success']) && $result['success']) {
                    $record['success_count']++;
                } else {
                    $record['error_count']++;
                }
            }
        }
        
        // 履歴に追加（最新50件まで保持）
        array_unshift($history, $record);
        $history = array_slice($history, 0, 50);
        
        update_option('jgrants_sync_history', $history);
        update_option('jgrants_last_sync', $record['timestamp']);
    }
}

