<?php
/**
 * 助成金タクソノミーの管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Taxonomies {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    /**
     * タクソノミーの登録
     */
    public function register_taxonomies() {
        $this->register_grant_category();
        $this->register_grant_prefecture();
        $this->register_grant_tag();
    }
    
    /**
     * 助成金カテゴリーの登録
     */
    private function register_grant_category() {
        // 防御的プログラミング：テーマ側で既に定義されていないかチェック
        if (taxonomy_exists('grant_category')) {
            return;
        }
        
        $labels = array(
            'name' => '助成金カテゴリー',
            'singular_name' => '助成金カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名',
            'menu_name' => 'カテゴリー',
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'grant-category'),
            'show_in_rest' => true,
        );
        
        register_taxonomy('grant_category', array('grant'), $args);
    }
    
    /**
     * 助成金都道府県の登録
     */
    private function register_grant_prefecture() {
        // 防御的プログラミング：テーマ側で既に定義されていないかチェック
        if (taxonomy_exists('grant_prefecture')) {
            return;
        }
        
        $labels = array(
            'name' => '都道府県',
            'singular_name' => '都道府県',
            'search_items' => '都道府県を検索',
            'all_items' => 'すべての都道府県',
            'parent_item' => '親都道府県',
            'parent_item_colon' => '親都道府県:',
            'edit_item' => '都道府県を編集',
            'update_item' => '都道府県を更新',
            'add_new_item' => '新しい都道府県を追加',
            'new_item_name' => '新しい都道府県名',
            'menu_name' => '都道府県',
        );
        
        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'grant-prefecture'),
            'show_in_rest' => true,
        );
        
        register_taxonomy('grant_prefecture', array('grant'), $args);
    }
    
    /**
     * 助成金タグの登録
     */
    private function register_grant_tag() {
        // 防御的プログラミング：テーマ側で既に定義されていないかチェック
        if (taxonomy_exists('grant_tag')) {
            return;
        }
        
        $labels = array(
            'name' => '助成金タグ',
            'singular_name' => '助成金タグ',
            'search_items' => 'タグを検索',
            'popular_items' => '人気のタグ',
            'all_items' => 'すべてのタグ',
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => 'タグを編集',
            'update_item' => 'タグを更新',
            'add_new_item' => '新しいタグを追加',
            'new_item_name' => '新しいタグ名',
            'separate_items_with_commas' => 'タグをカンマで区切ってください',
            'add_or_remove_items' => 'タグを追加または削除',
            'choose_from_most_used' => 'よく使われるタグから選択',
            'not_found' => 'タグが見つかりませんでした。',
            'menu_name' => 'タグ',
        );
        
        $args = array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'grant-tag'),
            'show_in_rest' => true,
        );
        
        register_taxonomy('grant_tag', array('grant'), $args);
    }
    
    /**
     * デフォルトのタクソノミー項目を作成
     */
    public static function create_default_terms() {
        // デフォルトカテゴリー
        $default_categories = array(
            'it-digital' => 'IT・デジタル',
            'manufacturing' => '製造業',
            'service' => 'サービス業',
            'agriculture' => '農業',
            'tourism' => '観光業',
            'healthcare' => '医療・介護',
            'education' => '教育',
            'environment' => '環境・エネルギー',
            'startup' => 'スタートアップ',
            'research' => '研究開発'
        );
        
        foreach ($default_categories as $slug => $name) {
            if (!term_exists($name, 'grant_category')) {
                wp_insert_term($name, 'grant_category', array('slug' => $slug));
            }
        }
        
        // デフォルト都道府県
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
            if (!term_exists($prefecture, 'grant_prefecture')) {
                wp_insert_term($prefecture, 'grant_prefecture');
            }
        }
    }
    
    /**
     * タクソノミーが登録されているかチェック
     */
    public static function are_registered() {
        return taxonomy_exists('grant_category') && 
               taxonomy_exists('grant_prefecture') && 
               taxonomy_exists('grant_tag');
    }
    
    /**
     * タームの作成または取得
     */
    public static function get_or_create_term($term_name, $taxonomy) {
        $term = term_exists($term_name, $taxonomy);
        
        if (!$term) {
            $term = wp_insert_term($term_name, $taxonomy);
            if (is_wp_error($term)) {
                return false;
            }
        }
        
        return is_array($term) ? $term['term_id'] : $term;
    }
    
    /**
     * 投稿にタクソノミーを設定
     */
    public static function set_post_terms($post_id, $terms, $taxonomy, $append = false) {
        if (!taxonomy_exists($taxonomy)) {
            return false;
        }
        
        // タームIDの配列を作成
        $term_ids = array();
        
        if (is_array($terms)) {
            foreach ($terms as $term) {
                if (is_numeric($term)) {
                    $term_ids[] = intval($term);
                } else {
                    $term_id = self::get_or_create_term($term, $taxonomy);
                    if ($term_id) {
                        $term_ids[] = $term_id;
                    }
                }
            }
        } else {
            if (is_numeric($terms)) {
                $term_ids[] = intval($terms);
            } else {
                $term_id = self::get_or_create_term($terms, $taxonomy);
                if ($term_id) {
                    $term_ids[] = $term_id;
                }
            }
        }
        
        return wp_set_object_terms($post_id, $term_ids, $taxonomy, $append);
    }
    
    /**
     * タクソノミーの統計情報を取得
     */
    public static function get_taxonomy_stats($taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            return false;
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        
        if (is_wp_error($terms)) {
            return false;
        }
        
        $stats = array(
            'total_terms' => count($terms),
            'used_terms' => 0,
            'total_posts' => 0
        );
        
        foreach ($terms as $term) {
            if ($term->count > 0) {
                $stats['used_terms']++;
                $stats['total_posts'] += $term->count;
            }
        }
        
        return $stats;
    }
}

