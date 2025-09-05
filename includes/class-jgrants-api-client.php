<?php
/**
 * JグランツAPI クライアントクラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_API_Client {
    
    /**
     * APIの基本URL（正しいJグランツAPIエンドポイント）
     */
    const API_BASE_URL = 'https://api.jgrants-portal.go.jp/exp/v1';
    
    /**
     * APIエンドポイント
     */
    const ENDPOINT_SUBSIDIES = '/subsidies';
    const ENDPOINT_SUBSIDY_DETAIL = '/subsidies/id/';
    
    /**
     * HTTPリクエストのタイムアウト（秒）
     */
    const REQUEST_TIMEOUT = 30;
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        // 必要に応じて初期化処理
    }
    
    /**
     * 助成金一覧を取得
     */
    public function get_subsidies($params = array()) {
        // 必須パラメータのチェックと設定
        if (empty($params['keyword']) || strlen($params['keyword']) < 2) {
            return new WP_Error('invalid_keyword', 'キーワードは2文字以上で入力してください。');
        }
        
        // JグランツAPIの必須パラメータを設定
        $required_params = array(
            'keyword' => $params['keyword'],
            'sort' => isset($params['sort']) ? $params['sort'] : 'created_date',
            'order' => isset($params['order']) ? $params['order'] : 'DESC',
            'acceptance' => isset($params['acceptance']) ? $params['acceptance'] : '0'
        );
        
        // オプションパラメータを追加
        $optional_params = array();
        if (!empty($params['use_purpose'])) {
            $optional_params['use_purpose'] = $params['use_purpose'];
        }
        if (!empty($params['industry'])) {
            $optional_params['industry'] = is_array($params['industry']) ? implode(' / ', $params['industry']) : $params['industry'];
        }
        if (!empty($params['target_number_of_employees'])) {
            $optional_params['target_number_of_employees'] = $params['target_number_of_employees'];
        }
        if (!empty($params['target_area_search'])) {
            $optional_params['target_area_search'] = is_array($params['target_area_search']) ? implode(' / ', $params['target_area_search']) : $params['target_area_search'];
        }
        
        $final_params = array_merge($required_params, $optional_params);
        
        // APIリクエストの実行
        $url = self::API_BASE_URL . self::ENDPOINT_SUBSIDIES;
        $response = $this->make_request($url, $final_params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_subsidies_response($response);
    }
    
    /**
     * 助成金詳細を取得
     */
    public function get_subsidy_detail($subsidy_id) {
        if (empty($subsidy_id)) {
            return new WP_Error('invalid_id', '助成金IDが指定されていません。');
        }
        
        $url = self::API_BASE_URL . self::ENDPOINT_SUBSIDY_DETAIL . $subsidy_id;
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_subsidy_detail_response($response);
    }
    
    /**
     * HTTPリクエストを実行
     */
    private function make_request($url, $params = array()) {
        // URLにパラメータを追加
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // リクエストの実行
        $response = wp_remote_get($url, array(
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'JGrants-Integration-Plugin/' . JGRANTS_PLUGIN_VERSION
            )
        ));
        
        // エラーチェック
        if (is_wp_error($response)) {
            return new WP_Error('connection_error', 'API接続エラー: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // HTTPステータスコードによるエラーハンドリング
        if ($response_code !== 200) {
            $error_message = $this->get_error_message($response_code, $body);
            return new WP_Error('api_error', $error_message, array('status' => $response_code));
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSONの解析に失敗しました: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * エラーメッセージを取得
     */
    private function get_error_message($status_code, $response_body = '') {
        $error_messages = array(
            400 => 'リクエストの形式が正しくありません。パラメータを確認してください。',
            401 => '認証に失敗しました。APIキーを確認してください。',
            403 => 'アクセスが拒否されました。権限を確認してください。',
            404 => 'リクエストしたリソースが見つかりません。',
            429 => 'API使用量の制限に達しました。しばらく待ってから再試行してください。',
            500 => 'サーバー内部エラーが発生しました。',
            502 => 'ゲートウェイエラーが発生しました。',
            503 => 'サービスが一時的に利用できません。'
        );
        
        $base_message = isset($error_messages[$status_code]) ? $error_messages[$status_code] : 'APIエラーが発生しました。';
        
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
     * 助成金一覧レスポンスの解析
     */
    private function parse_subsidies_response($response) {
        if (!isset($response['result']) || !is_array($response['result'])) {
            return new WP_Error('invalid_response', '無効なレスポンス形式です。');
        }
        
        $subsidies = array();
        
        foreach ($response['result'] as $item) {
            $subsidies[] = $this->normalize_subsidy_data($item);
        }
        
        return array(
            'metadata' => isset($response['metadata']) ? $response['metadata'] : array(),
            'subsidies' => $subsidies
        );
    }
    
    /**
     * 助成金詳細レスポンスの解析
     */
    private function parse_subsidy_detail_response($response) {
        if (!isset($response['result'])) {
            return new WP_Error('invalid_response', '無効なレスポンス形式です。');
        }
        
        return $this->normalize_subsidy_data($response['result']);
    }
    
    /**
     * 助成金データの正規化
     */
    private function normalize_subsidy_data($data) {
        $normalized = array(
            'id' => isset($data['subsidy_id']) ? $data['subsidy_id'] : '',
            'title' => isset($data['subsidy_name']) ? $data['subsidy_name'] : '',
            'organization' => isset($data['organization_name']) ? $data['organization_name'] : '',
            'description' => isset($data['subsidy_outline']) ? $data['subsidy_outline'] : '',
            'purpose' => isset($data['use_purpose']) ? $data['use_purpose'] : '',
            'industry' => isset($data['industry']) ? $this->parse_multiple_values($data['industry']) : array(),
            'target_area' => isset($data['target_area']) ? $this->parse_multiple_values($data['target_area']) : array(),
            'target_employees' => isset($data['target_number_of_employees']) ? $data['target_number_of_employees'] : '',
            'amount_min' => isset($data['subsidy_amount_min']) ? intval($data['subsidy_amount_min']) : 0,
            'amount_max' => isset($data['subsidy_amount_max']) ? intval($data['subsidy_amount_max']) : 0,
            'rate' => isset($data['subsidy_rate']) ? $data['subsidy_rate'] : '',
            'application_start' => isset($data['application_start_date']) ? $this->parse_date($data['application_start_date']) : '',
            'application_end' => isset($data['application_end_date']) ? $this->parse_date($data['application_end_date']) : '',
            'implementation_start' => isset($data['implementation_start_date']) ? $this->parse_date($data['implementation_start_date']) : '',
            'implementation_end' => isset($data['implementation_end_date']) ? $this->parse_date($data['implementation_end_date']) : '',
            'url' => isset($data['subsidy_url']) ? $data['subsidy_url'] : '',
            'contact' => isset($data['contact_information']) ? $data['contact_information'] : '',
            'updated_date' => isset($data['updated_date']) ? $this->parse_date($data['updated_date']) : '',
            'status' => $this->determine_status($data)
        );
        
        return $normalized;
    }
    
    /**
     * 複数値の解析（' / ' 区切り）
     */
    private function parse_multiple_values($value) {
        if (empty($value)) {
            return array();
        }
        
        return array_map('trim', explode(' / ', $value));
    }
    
    /**
     * 日付の解析（ISO 8601形式）
     */
    private function parse_date($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_string);
        if ($date === false) {
            // フォールバック：他の形式も試す
            $date = DateTime::createFromFormat('Y-m-d', $date_string);
        }
        
        return $date ? $date->format('Y-m-d H:i:s') : $date_string;
    }
    
    /**
     * ステータスの判定
     */
    private function determine_status($data) {
        $now = current_time('timestamp');
        
        // 申請開始日と終了日をチェック
        if (isset($data['application_start_date']) && isset($data['application_end_date'])) {
            $start_date = strtotime($data['application_start_date']);
            $end_date = strtotime($data['application_end_date']);
            
            if ($start_date && $end_date) {
                if ($now < $start_date) {
                    return 'upcoming';
                } elseif ($now > $end_date) {
                    return 'closed';
                } else {
                    return 'open';
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * 検索パラメータの構築
     */
    public static function build_search_params($args = array()) {
        $params = array();
        
        // 必須パラメータ
        if (isset($args['keyword'])) {
            $params['keyword'] = $args['keyword'];
        }
        
        if (isset($args['sort'])) {
            $params['sort'] = $args['sort'];
        }
        
        if (isset($args['order'])) {
            $params['order'] = $args['order'];
        }
        
        if (isset($args['acceptance'])) {
            $params['acceptance'] = $args['acceptance'];
        }
        
        // オプションパラメータ
        if (isset($args['use_purpose'])) {
            $params['use_purpose'] = $args['use_purpose'];
        }
        
        if (isset($args['industry']) && is_array($args['industry'])) {
            $params['industry'] = implode(' / ', $args['industry']);
        }
        
        if (isset($args['target_number_of_employees'])) {
            $params['target_number_of_employees'] = $args['target_number_of_employees'];
        }
        
        if (isset($args['target_area_search']) && is_array($args['target_area_search'])) {
            $params['target_area_search'] = implode(' / ', $args['target_area_search']);
        }
        
        return $params;
    }
    
    /**
     * APIの接続テスト
     */
    public function test_connection() {
        $test_params = array(
            'keyword' => 'テスト',
            'sort' => 'updated_date',
            'order' => 'desc',
            'acceptance' => 'all'
        );
        
        $response = $this->get_subsidies($test_params);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'API接続に成功しました。',
            'data_count' => count($response['subsidies'])
        );
    }
}

