<?php
/**
 * 助成金一覧表示テンプレート
 * 
 * このファイルは、ショートコードやGutenbergブロックで助成金一覧を表示する際に使用されます。
 * テーマ側で /jgrants-integration/grants-list.php を作成することで、このテンプレートを上書きできます。
 */

if (!defined('ABSPATH')) {
    exit;
}

// $grants_query が設定されていることを確認
if (!isset($grants_query) || !$grants_query instanceof WP_Query) {
    return;
}
?>

<div class="jgrants-list">
    <?php if ($grants_query->have_posts()) : ?>
        <div class="jgrants-items">
            <?php while ($grants_query->have_posts()) : $grants_query->the_post(); ?>
                <article class="jgrants-item" id="grant-<?php the_ID(); ?>">
                    <header class="jgrants-header">
                        <h3 class="jgrants-title">
                            <a href="<?php the_permalink(); ?>" class="jgrants-accent">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        
                        <div class="jgrants-meta">
                            <?php
                            // ステータスの表示
                            $status = get_post_meta(get_the_ID(), 'jgrants_status', true);
                            if ($status) {
                                $status_labels = array(
                                    'open' => '募集中',
                                    'upcoming' => '募集予定',
                                    'closed' => '募集終了',
                                    'unknown' => '詳細確認'
                                );
                                $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                $status_class = 'status-' . $status;
                                ?>
                                <span class="jgrants-status-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            <?php } ?>
                            
                            <?php
                            // 実施機関の表示
                            $organization = get_post_meta(get_the_ID(), 'jgrants_organization', true);
                            if ($organization) : ?>
                                <span class="jgrants-organization">
                                    <?php echo esc_html($organization); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            // 補助金額の表示
                            $amount_min = get_post_meta(get_the_ID(), 'jgrants_amount_min', true);
                            $amount_max = get_post_meta(get_the_ID(), 'jgrants_amount_max', true);
                            if ($amount_min || $amount_max) : ?>
                                <span class="jgrants-amount">
                                    <?php
                                    if ($amount_min && $amount_max) {
                                        echo number_format($amount_min) . '円〜' . number_format($amount_max) . '円';
                                    } elseif ($amount_max) {
                                        echo '最大' . number_format($amount_max) . '円';
                                    } elseif ($amount_min) {
                                        echo number_format($amount_min) . '円〜';
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            // 補助率の表示
                            $rate = get_post_meta(get_the_ID(), 'jgrants_rate', true);
                            if ($rate) : ?>
                                <span class="jgrants-rate">
                                    補助率: <?php echo esc_html($rate); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </header>
                    
                    <div class="jgrants-content">
                        <div class="jgrants-excerpt">
                            <?php
                            // 抜粋の表示（AIが生成した要約を優先）
                            $excerpt = get_the_excerpt();
                            if ($excerpt) {
                                echo wp_kses_post($excerpt);
                            }
                            ?>
                        </div>
                        
                        <?php
                        // 申請期間の表示
                        $app_start = get_post_meta(get_the_ID(), 'jgrants_application_start', true);
                        $app_end = get_post_meta(get_the_ID(), 'jgrants_application_end', true);
                        if ($app_start || $app_end) : ?>
                            <div class="jgrants-dates">
                                <span class="jgrants-dates-label">申請期間:</span>
                                <?php
                                if ($app_start && $app_end) {
                                    echo esc_html(date('Y/m/d', strtotime($app_start))) . ' 〜 ' . esc_html(date('Y/m/d', strtotime($app_end)));
                                } elseif ($app_start) {
                                    echo esc_html(date('Y/m/d', strtotime($app_start))) . ' 〜';
                                } elseif ($app_end) {
                                    echo '〜 ' . esc_html(date('Y/m/d', strtotime($app_end)));
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // タクソノミーの表示
                        $categories = get_the_terms(get_the_ID(), 'grant_category');
                        $prefectures = get_the_terms(get_the_ID(), 'grant_prefecture');
                        $tags = get_the_terms(get_the_ID(), 'grant_tag');
                        
                        if ($categories || $prefectures || $tags) : ?>
                            <div class="jgrants-taxonomies">
                                <?php if ($categories && !is_wp_error($categories)) : ?>
                                    <div class="jgrants-categories">
                                        <span class="taxonomy-label">カテゴリー:</span>
                                        <?php foreach ($categories as $category) : ?>
                                            <a href="<?php echo get_term_link($category); ?>" class="jgrants-category-link">
                                                <?php echo esc_html($category->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($prefectures && !is_wp_error($prefectures)) : ?>
                                    <div class="jgrants-prefectures">
                                        <span class="taxonomy-label">対象地域:</span>
                                        <?php foreach ($prefectures as $prefecture) : ?>
                                            <a href="<?php echo get_term_link($prefecture); ?>" class="jgrants-prefecture-link">
                                                <?php echo esc_html($prefecture->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($tags && !is_wp_error($tags)) : ?>
                                    <div class="jgrants-tags">
                                        <span class="taxonomy-label">タグ:</span>
                                        <?php foreach ($tags as $tag) : ?>
                                            <a href="<?php echo get_term_link($tag); ?>" class="jgrants-tag-link">
                                                <?php echo esc_html($tag->name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <footer class="jgrants-footer">
                        <div class="jgrants-actions">
                            <a href="<?php the_permalink(); ?>" class="jgrants-read-more jgrants-accent-bg">
                                詳細を見る
                            </a>
                            
                            <?php
                            // 外部リンクの表示
                            $external_url = get_post_meta(get_the_ID(), 'jgrants_url', true);
                            if ($external_url) : ?>
                                <a href="<?php echo esc_url($external_url); ?>" target="_blank" rel="noopener noreferrer" class="jgrants-external-link">
                                    公式サイト
                                    <span class="external-icon">↗</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="jgrants-updated">
                            <span class="updated-label">更新日:</span>
                            <time datetime="<?php echo get_the_modified_date('c'); ?>">
                                <?php echo get_the_modified_date('Y/m/d'); ?>
                            </time>
                        </div>
                    </footer>
                </article>
            <?php endwhile; ?>
        </div>
        
        <?php
        // ページネーション
        if ($grants_query->max_num_pages > 1) : ?>
            <nav class="jgrants-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $grants_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'end_size' => 1,
                    'mid_size' => 2,
                    'prev_next' => true,
                    'prev_text' => '« 前へ',
                    'next_text' => '次へ »',
                    'type' => 'plain'
                ));
                ?>
            </nav>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="jgrants-no-results">
            <p>条件に一致する助成金が見つかりませんでした。</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* 助成金一覧のスタイル */
.jgrants-list {
    margin: 20px 0;
}

.jgrants-items {
    display: grid;
    gap: 20px;
}

.jgrants-item {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.2s ease;
}

.jgrants-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.jgrants-header {
    margin-bottom: 15px;
}

.jgrants-title {
    margin: 0 0 10px 0;
    font-size: 20px;
    line-height: 1.4;
}

.jgrants-title a {
    text-decoration: none;
    color: inherit;
}

.jgrants-title a:hover {
    text-decoration: underline;
}

.jgrants-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    font-size: 14px;
    color: #6c757d;
}

.jgrants-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
}

.status-open {
    background-color: #28a745;
}

.status-upcoming {
    background-color: #17a2b8;
}

.status-closed {
    background-color: #6c757d;
}

.status-unknown {
    background-color: #ffc107;
    color: #212529;
}

.jgrants-organization,
.jgrants-amount,
.jgrants-rate {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.jgrants-content {
    margin-bottom: 15px;
}

.jgrants-excerpt {
    margin-bottom: 10px;
    line-height: 1.6;
    color: #495057;
}

.jgrants-dates {
    margin-bottom: 10px;
    font-size: 14px;
    color: #6c757d;
}

.jgrants-dates-label {
    font-weight: bold;
    margin-right: 5px;
}

.jgrants-taxonomies {
    margin-top: 15px;
}

.jgrants-taxonomies > div {
    margin-bottom: 8px;
}

.taxonomy-label {
    font-weight: bold;
    margin-right: 8px;
    font-size: 12px;
    color: #6c757d;
}

.jgrants-category-link,
.jgrants-prefecture-link,
.jgrants-tag-link {
    display: inline-block;
    padding: 2px 6px;
    margin-right: 5px;
    background: #e9ecef;
    color: #495057;
    text-decoration: none;
    border-radius: 3px;
    font-size: 11px;
    transition: background-color 0.2s ease;
}

.jgrants-category-link:hover,
.jgrants-prefecture-link:hover,
.jgrants-tag-link:hover {
    background: #dee2e6;
}

.jgrants-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.jgrants-actions {
    display: flex;
    gap: 10px;
}

.jgrants-read-more {
    display: inline-block;
    padding: 8px 16px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.jgrants-read-more:hover {
    background: #005a87;
    color: white;
}

.jgrants-external-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border: 1px solid #007cba;
    color: #007cba;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.jgrants-external-link:hover {
    background: #007cba;
    color: white;
}

.external-icon {
    font-size: 12px;
}

.jgrants-updated {
    font-size: 12px;
    color: #6c757d;
}

.updated-label {
    margin-right: 4px;
}

.jgrants-pagination {
    margin-top: 30px;
    text-align: center;
}

.jgrants-pagination a,
.jgrants-pagination span {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    color: #007cba;
    text-decoration: none;
    border-radius: 4px;
}

.jgrants-pagination a:hover {
    background: #e9ecef;
}

.jgrants-pagination .current {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.jgrants-no-results {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .jgrants-item {
        padding: 15px;
    }
    
    .jgrants-title {
        font-size: 18px;
    }
    
    .jgrants-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .jgrants-footer {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .jgrants-actions {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

