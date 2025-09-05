<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>下書き一括生成</h1>
    
    <?php settings_errors(); ?>
    
    <div class="jgrants-batch-generate">
        <!-- 生成設定 -->
        <div class="generate-settings">
            <h2>生成設定</h2>
            <form id="batch-generate-form">
                <table class="form-table">
                    <tr>
                        <th><label for="generate-count">生成件数</label></th>
                        <td>
                            <input type="number" id="generate-count" name="count" value="10" min="1" max="50" class="small-text">
                            <p class="description">一度に生成する下書きの件数（1〜50件）</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="search-keyword">検索キーワード</label></th>
                        <td>
                            <input type="text" id="search-keyword" name="keyword" value="デジタル" class="regular-text">
                            <p class="description">JグランツAPIで検索するキーワード（2文字以上）</p>
                        </td>
                    </tr>
                    <tr>
                        <th>生成オプション</th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable-ai-content" checked>
                                AIコンテンツを生成する
                            </label>
                            <p class="description">AIによる要約と詳細説明を生成します</p>
                            
                            <label>
                                <input type="checkbox" id="enable-ai-tags" <?php echo get_option('jgrants_ai_settings')['enable_ai_tags'] ? 'checked' : ''; ?>>
                                AIタグを自動生成する
                            </label>
                            <p class="description">AIによる関連タグの自動生成（AI設定で有効にする必要があります）</p>
                        </td>
                    </tr>
                </table>
                
                <div class="generate-actions">
                    <button type="submit" class="button button-primary" id="generate-drafts-btn">
                        下書きを一括生成
                    </button>
                    <div id="generate-progress" class="progress-indicator" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text">生成中...</div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- 生成結果 -->
        <div id="generate-results" class="generate-results" style="display: none;">
            <h2>生成結果</h2>
            <div id="results-summary" class="results-summary"></div>
            <div id="results-list" class="results-list"></div>
        </div>
        
        <!-- 既存の下書き一覧 -->
        <div class="existing-drafts">
            <h2>既存の下書き一覧</h2>
            
            <?php if (!empty($draft_posts)) : ?>
                <form id="publish-form">
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <button type="button" class="button" id="select-all-drafts">すべて選択</button>
                            <button type="button" class="button" id="deselect-all-drafts">選択解除</button>
                            <button type="submit" class="button button-primary" id="publish-selected-btn">
                                選択した投稿を公開
                            </button>
                        </div>
                        <div class="alignright">
                            <span class="displaying-num"><?php echo count($draft_posts); ?> 件の下書き</span>
                        </div>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all">
                                </td>
                                <th class="manage-column">タイトル</th>
                                <th class="manage-column">作成日</th>
                                <th class="manage-column">助成金ID</th>
                                <th class="manage-column">アクション</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($draft_posts as $post) : ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post->ID); ?>" class="draft-checkbox">
                                    </th>
                                    <td>
                                        <strong>
                                            <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                                <?php echo esc_html($post->post_title); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo get_edit_post_link($post->ID); ?>">編集</a> |
                                            </span>
                                            <span class="view">
                                                <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">プレビュー</a> |
                                            </span>
                                            <span class="trash">
                                                <a href="<?php echo get_delete_post_link($post->ID); ?>" class="submitdelete">削除</a>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html(get_the_date('Y/m/d H:i', $post->ID)); ?></td>
                                    <td>
                                        <?php
                                        $subsidy_id = get_post_meta($post->ID, 'jgrants_subsidy_id', true);
                                        echo $subsidy_id ? esc_html($subsidy_id) : '—';
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small publish-single" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                            公開
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php else : ?>
                <div class="no-drafts">
                    <p>下書きの投稿がありません。</p>
                    <p>上記のフォームから新しい下書きを生成してください。</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 下書き一括生成
    $('#batch-generate-form').on('submit', function(e) {
        e.preventDefault();
        
        var count = parseInt($('#generate-count').val());
        var keyword = $('#search-keyword').val().trim();
        
        if (count < 1 || count > 50) {
            alert('生成件数は1〜50件の範囲で指定してください。');
            return;
        }
        
        if (keyword.length < 2) {
            alert('キーワードは2文字以上で入力してください。');
            return;
        }
        
        if (!confirm('下書きを' + count + '件生成しますか？')) {
            return;
        }
        
        var $btn = $('#generate-drafts-btn');
        var $progress = $('#generate-progress');
        
        $btn.prop('disabled', true);
        $progress.show();
        
        $.post(jgrants_ajax.ajax_url, {
            action: 'jgrants_batch_create_drafts',
            nonce: jgrants_ajax.nonce,
            count: count,
            keyword: keyword
        }, function(response) {
            if (response.success) {
                showGenerateResults(response.data);
                // ページをリロードして新しい下書きを表示
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                alert('エラー: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false);
            $progress.hide();
        });
    });
    
    // 生成結果の表示
    function showGenerateResults(data) {
        var $results = $('#generate-results');
        var $summary = $('#results-summary');
        var $list = $('#results-list');
        
        var successCount = 0;
        var errorCount = 0;
        
        data.results.forEach(function(result) {
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        });
        
        $summary.html(
            '<div class="results-stats">' +
            '<span class="success-count">成功: ' + successCount + '件</span> | ' +
            '<span class="error-count">失敗: ' + errorCount + '件</span>' +
            '</div>'
        );
        
        var listHtml = '<ul class="results-items">';
        data.results.forEach(function(result) {
            var statusClass = result.success ? 'success' : 'error';
            var statusIcon = result.success ? '✓' : '✗';
            
            listHtml += '<li class="result-item ' + statusClass + '">';
            listHtml += '<span class="status-icon">' + statusIcon + '</span>';
            listHtml += '<span class="result-message">' + result.message + '</span>';
            if (result.subsidy_id) {
                listHtml += '<span class="subsidy-id">ID: ' + result.subsidy_id + '</span>';
            }
            listHtml += '</li>';
        });
        listHtml += '</ul>';
        
        $list.html(listHtml);
        $results.show();
    }
    
    // 全選択/選択解除
    $('#select-all-drafts').on('click', function() {
        $('.draft-checkbox').prop('checked', true);
    });
    
    $('#deselect-all-drafts').on('click', function() {
        $('.draft-checkbox').prop('checked', false);
    });
    
    $('#cb-select-all').on('change', function() {
        $('.draft-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // 選択した投稿を一括公開
    $('#publish-form').on('submit', function(e) {
        e.preventDefault();
        
        var selectedIds = [];
        $('.draft-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('公開する投稿を選択してください。');
            return;
        }
        
        if (!confirm('選択した' + selectedIds.length + '件の投稿を公開しますか？')) {
            return;
        }
        
        var $btn = $('#publish-selected-btn');
        $btn.prop('disabled', true).text('公開中...');
        
        $.post(jgrants_ajax.ajax_url, {
            action: 'jgrants_batch_publish_posts',
            nonce: jgrants_ajax.nonce,
            post_ids: selectedIds
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('エラー: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text('選択した投稿を公開');
        });
    });
    
    // 個別投稿の公開
    $('.publish-single').on('click', function() {
        var postId = $(this).data('post-id');
        var $btn = $(this);
        
        if (!confirm('この投稿を公開しますか？')) {
            return;
        }
        
        $btn.prop('disabled', true).text('公開中...');
        
        $.post(jgrants_ajax.ajax_url, {
            action: 'jgrants_batch_publish_posts',
            nonce: jgrants_ajax.nonce,
            post_ids: [postId]
        }, function(response) {
            if (response.success) {
                alert('投稿を公開しました。');
                location.reload();
            } else {
                alert('エラー: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text('公開');
        });
    });
});
</script>

