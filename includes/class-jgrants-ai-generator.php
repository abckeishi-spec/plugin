<?php
/**
 * AI コンテンツ生成クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_AI_Generator {
    
    /**
     * Gemini API エンドポイント
     */
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    /**
     * APIキー
     */
    private $api_key;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $ai_settings = get_option('jgrants_ai_settings', array());
        $this->api_key = isset($ai_settings['gemini_api_key']) ? $ai_settings['gemini_api_key'] : '';
    }
    
    /**
     * APIキーが設定されているかチェック
     */
    public function is_api_key_set() {
        return !empty($this->api_key);
    }
    
    /**
     * APIキーの有効性をテスト
     */
    public function test_api_key() {
        if (!$this->is_api_key_set()) {
            return array(
                'success' => false,
                'message' => 'APIキーが設定されていません。'
            );
        }
        
        $test_prompt = 'こんにちは';
        $response = $this->make_gemini_request($test_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API接続エラー: ' . $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'APIキーは有効です。Gemini AIに正常に接続できました。'
        );
    }
    
    /**
     * 助成金データから要約を生成
     */
    public function generate_summary($subsidy_data) {
        if (!$this->is_api_key_set()) {
            return new WP_Error('no_api_key', 'Gemini APIキーが設定されていません。');
        }
        
        $prompt = $this->build_summary_prompt($subsidy_data);
        
        return $this->make_gemini_request($prompt);
    }
    
    /**
     * 助成金データから詳細説明を生成
     */
    public function generate_detailed_description($subsidy_data) {
        if (!$this->is_api_key_set()) {
            return new WP_Error('no_api_key', 'Gemini APIキーが設定されていません。');
        }
        
        $prompt = $this->build_detailed_description_prompt($subsidy_data);
        
        return $this->make_gemini_request($prompt);
    }
    
    /**
     * 助成金データからタグを生成
     */
    public function generate_tags_for_post($subsidy_data) {
        if (!$this->is_api_key_set()) {
            return new WP_Error('no_api_key', 'Gemini APIキーが設定されていません。');
        }
        
        $ai_settings = get_option('jgrants_ai_settings', array());
        if (empty($ai_settings['enable_ai_tags'])) {
            return array(); // AIタグ生成が無効の場合は空配列を返す
        }
        
        $prompt = $this->build_tags_prompt($subsidy_data);
        $response = $this->make_gemini_request($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_tags_response($response);
    }
    
    /**
     * 要約生成用プロンプトの構築
     */
    private function build_summary_prompt($data) {
        $prompt = "以下の助成金情報を基に、わかりやすく簡潔な要約を200文字以内で作成してください。\n\n";
        $prompt .= "助成金名: " . $data['title'] . "\n";
        $prompt .= "実施機関: " . $data['organization'] . "\n";
        $prompt .= "概要: " . $data['description'] . "\n";
        $prompt .= "対象業種: " . implode(', ', $data['industry']) . "\n";
        $prompt .= "対象地域: " . implode(', ', $data['target_area']) . "\n";
        $prompt .= "補助金額: " . number_format($data['amount_min']) . "円 〜 " . number_format($data['amount_max']) . "円\n";
        $prompt .= "補助率: " . $data['rate'] . "\n";
        $prompt .= "申請期間: " . $data['application_start'] . " 〜 " . $data['application_end'] . "\n\n";
        $prompt .= "要約は以下の点を含めてください：\n";
        $prompt .= "- 助成金の目的と対象\n";
        $prompt .= "- 主な支援内容\n";
        $prompt .= "- 申請のポイント\n\n";
        $prompt .= "読みやすく、事業者にとって有益な情報として整理してください。";
        
        return $prompt;
    }
    
    /**
     * 詳細説明生成用プロンプトの構築
     */
    private function build_detailed_description_prompt($data) {
        $prompt = "以下の助成金情報を基に、事業者向けの詳細な説明記事を800文字程度で作成してください。\n\n";
        $prompt .= "助成金名: " . $data['title'] . "\n";
        $prompt .= "実施機関: " . $data['organization'] . "\n";
        $prompt .= "概要: " . $data['description'] . "\n";
        $prompt .= "利用目的: " . $data['purpose'] . "\n";
        $prompt .= "対象業種: " . implode(', ', $data['industry']) . "\n";
        $prompt .= "対象地域: " . implode(', ', $data['target_area']) . "\n";
        $prompt .= "対象従業員数: " . $data['target_employees'] . "\n";
        $prompt .= "補助金額: " . number_format($data['amount_min']) . "円 〜 " . number_format($data['amount_max']) . "円\n";
        $prompt .= "補助率: " . $data['rate'] . "\n";
        $prompt .= "申請期間: " . $data['application_start'] . " 〜 " . $data['application_end'] . "\n";
        $prompt .= "実施期間: " . $data['implementation_start'] . " 〜 " . $data['implementation_end'] . "\n\n";
        $prompt .= "記事は以下の構成で作成してください：\n";
        $prompt .= "1. 助成金の概要と目的\n";
        $prompt .= "2. 対象となる事業者・条件\n";
        $prompt .= "3. 支援内容と補助金額\n";
        $prompt .= "4. 申請時の注意点\n";
        $prompt .= "5. 活用のメリット\n\n";
        $prompt .= "事業者が理解しやすく、実用的な内容として整理してください。";
        
        return $prompt;
    }
    
    /**
     * タグ生成用プロンプトの構築
     */
    private function build_tags_prompt($data) {
        $prompt = "以下の助成金情報から、検索やカテゴリ分類に適したタグを5〜10個抽出してください。\n\n";
        $prompt .= "助成金名: " . $data['title'] . "\n";
        $prompt .= "概要: " . $data['description'] . "\n";
        $prompt .= "利用目的: " . $data['purpose'] . "\n";
        $prompt .= "対象業種: " . implode(', ', $data['industry']) . "\n\n";
        $prompt .= "タグの条件：\n";
        $prompt .= "- 2〜4文字程度の短いキーワード\n";
        $prompt .= "- 業種、技術分野、支援内容を表すもの\n";
        $prompt .= "- 検索されやすい一般的な用語\n";
        $prompt .= "- カンマ区切りで出力\n\n";
        $prompt .= "例: IT, デジタル化, 製造業, 研究開発, スタートアップ\n\n";
        $prompt .= "タグのみを出力してください（説明文は不要）。";
        
        return $prompt;
    }
    
    /**
     * Gemini APIリクエストの実行
     */
    private function make_gemini_request($prompt) {
        $url = self::GEMINI_API_URL . '?key=' . $this->api_key;
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024
            )
        );
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('connection_error', 'Gemini API接続エラー: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_message = $this->get_gemini_error_message($response_code, $response_body);
            return new WP_Error('gemini_api_error', $error_message, array('status' => $response_code));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSONの解析に失敗しました: ' . json_last_error_msg());
        }
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            // エラーレスポンスの詳細を確認
            if (isset($data['error'])) {
                return new WP_Error('gemini_response_error', 'Gemini APIエラー: ' . $data['error']['message']);
            }
            return new WP_Error('invalid_response', '無効なレスポンス形式です。レスポンス: ' . substr($response_body, 0, 200));
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Gemini APIエラーメッセージを取得
     */
    private function get_gemini_error_message($status_code, $response_body = '') {
        $error_messages = array(
            400 => 'リクエストの形式が正しくありません。プロンプトやパラメータを確認してください。',
            401 => 'APIキーが無効です。Google AI Studioで正しいAPIキーを取得してください。',
            403 => 'APIキーの権限が不足しています。Google AI Studioで設定を確認してください。',
            404 => 'リクエストしたモデルまたはエンドポイントが見つかりません。',
            429 => 'API使用量の制限に達しました。無料プランの場合は制限があります。しばらく待ってから再試行してください。',
            500 => 'Gemini APIサーバー内部エラーが発生しました。',
            503 => 'Gemini APIサービスが一時的に利用できません。'
        );
        
        $base_message = isset($error_messages[$status_code]) ? $error_messages[$status_code] : 'Gemini APIエラーが発生しました。';
        
        // レスポンスボディからエラー詳細を取得
        if (!empty($response_body)) {
            $error_data = json_decode($response_body, true);
            if (isset($error_data['error']['message'])) {
                $base_message .= ' 詳細: ' . $error_data['error']['message'];
            }
        }
        
        return $base_message . ' (ステータスコード: ' . $status_code . ')';
    }
    
    /**
     * タグレスポンスの解析
     */
    private function parse_tags_response($response) {
        // カンマ区切りのタグを配列に変換
        $tags = array_map('trim', explode(',', $response));
        
        // 空のタグを除去
        $tags = array_filter($tags, function($tag) {
            return !empty($tag);
        });
        
        // 重複を除去
        $tags = array_unique($tags);
        
        return array_values($tags);
    }
    
    /**
     * 投稿にAIタグを設定
     */
    public function set_ai_tags_to_post($post_id, $subsidy_data) {
        $tags = $this->generate_tags_for_post($subsidy_data);
        
        if (is_wp_error($tags)) {
            return $tags;
        }
        
        if (empty($tags)) {
            return true; // タグ生成が無効の場合は成功として扱う
        }
        
        // タグをgrant_tagタクソノミーに設定
        return JGrants_Taxonomies::set_post_terms($post_id, $tags, 'grant_tag');
    }
    
    /**
     * バッチでAIコンテンツを生成
     */
    public function generate_batch_content($subsidies_data) {
        $results = array();
        
        foreach ($subsidies_data as $index => $subsidy) {
            $result = array(
                'index' => $index,
                'subsidy_id' => $subsidy['id'],
                'summary' => '',
                'detailed_description' => '',
                'tags' => array(),
                'errors' => array()
            );
            
            // 要約の生成
            $summary = $this->generate_summary($subsidy);
            if (is_wp_error($summary)) {
                $result['errors'][] = 'Summary: ' . $summary->get_error_message();
            } else {
                $result['summary'] = $summary;
            }
            
            // 詳細説明の生成
            $description = $this->generate_detailed_description($subsidy);
            if (is_wp_error($description)) {
                $result['errors'][] = 'Description: ' . $description->get_error_message();
            } else {
                $result['detailed_description'] = $description;
            }
            
            // タグの生成
            $tags = $this->generate_tags_for_post($subsidy);
            if (is_wp_error($tags)) {
                $result['errors'][] = 'Tags: ' . $tags->get_error_message();
            } else {
                $result['tags'] = $tags;
            }
            
            $results[] = $result;
            
            // API制限を考慮して少し待機
            sleep(1);
        }
        
        return $results;
    }
    
    /**
     * API接続テスト
     */
    public function test_api_connection() {
        if (!$this->is_api_key_set()) {
            return array(
                'success' => false,
                'message' => 'Gemini APIキーが設定されていません。'
            );
        }
        
        $test_prompt = "こんにちは。APIの接続テストです。「接続成功」と返答してください。";
        $response = $this->make_gemini_request($test_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Gemini API接続に成功しました。',
            'response' => $response
        );
    }
}

