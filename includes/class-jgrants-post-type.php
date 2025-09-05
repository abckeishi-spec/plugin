<?php
/**
 * 助成金投稿タイプの管理クラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Post_Type {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
    }
    
    /**
     * 投稿タイプの登録
     */
    public function register_post_type() {
        // 防御的プログラミング：テーマ側で既に定義されていないかチェック
        if (post_type_exists('grant')) {
            return;
        }
        
        $labels = array(
            'name' => '助成金',
            'singular_name' => '助成金',
            'menu_name' => '助成金',
            'name_admin_bar' => '助成金',
            'add_new' => '新規追加',
            'add_new_item' => '新しい助成金を追加',
            'new_item' => '新しい助成金',
            'edit_item' => '助成金を編集',
            'view_item' => '助成金を表示',
            'all_items' => 'すべての助成金',
            'search_items' => '助成金を検索',
            'parent_item_colon' => '親助成金:',
            'not_found' => '助成金が見つかりませんでした。',
            'not_found_in_trash' => 'ゴミ箱に助成金が見つかりませんでした。',
            'featured_image' => 'アイキャッチ画像',
            'set_featured_image' => 'アイキャッチ画像を設定',
            'remove_featured_image' => 'アイキャッチ画像を削除',
            'use_featured_image' => 'アイキャッチ画像として使用',
            'archives' => '助成金アーカイブ',
            'insert_into_item' => '助成金に挿入',
            'uploaded_to_this_item' => 'この助成金にアップロード',
            'filter_items_list' => '助成金リストをフィルター',
            'items_list_navigation' => '助成金リストナビゲーション',
            'items_list' => '助成金リスト',
        );
        
        $args = array(
            'labels' => $labels,
            'description' => 'Jグランツから取得した助成金情報',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'grants'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true, // Gutenbergエディタのサポート
            'taxonomies' => array('grant_category', 'grant_prefecture', 'grant_tag'),
        );
        
        register_post_type('grant', $args);
    }
    
    /**
     * 投稿タイプが登録されているかチェック
     */
    public static function is_registered() {
        return post_type_exists('grant');
    }
    
    /**
     * 助成金投稿の作成
     */
    public static function create_grant_post($data, $status = 'draft') {
        $post_data = array(
            'post_type' => 'grant',
            'post_status' => $status,
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_excerpt' => sanitize_textarea_field($data['excerpt']),
            'meta_input' => array()
        );
        
        // カスタムフィールドの追加
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                $post_data['meta_input'][$key] = sanitize_text_field($value);
            }
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // タクソノミーの設定
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxonomy => $terms) {
                if (taxonomy_exists($taxonomy)) {
                    wp_set_object_terms($post_id, $terms, $taxonomy);
                }
            }
        }
        
        return $post_id;
    }
    
    /**
     * 助成金投稿の更新
     */
    public static function update_grant_post($post_id, $data) {
        $post_data = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_excerpt' => sanitize_textarea_field($data['excerpt'])
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // カスタムフィールドの更新
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }
        
        // タクソノミーの更新
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxonomy => $terms) {
                if (taxonomy_exists($taxonomy)) {
                    wp_set_object_terms($post_id, $terms, $taxonomy);
                }
            }
        }
        
        return true;
    }
    
    /**
     * 助成金投稿の削除
     */
    public static function delete_grant_post($post_id) {
        return wp_delete_post($post_id, true);
    }
    
    /**
     * 助成金投稿の一括公開
     */
    public static function bulk_publish_posts($post_ids) {
        $results = array();
        
        foreach ($post_ids as $post_id) {
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
            
            $results[$post_id] = !is_wp_error($result);
        }
        
        return $results;
    }
    
    /**
     * 助成金投稿の検索
     */
    public static function search_grants($args = array()) {
        $default_args = array(
            'post_type' => 'grant',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return new WP_Query($args);
    }
}

