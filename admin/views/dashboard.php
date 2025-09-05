<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>JGrants連携 - ダッシュボード</h1>
    
    <?php settings_errors(); ?>
    
    <div class="jgrants-dashboard">
        <div class="jgrants-stats-grid">
            <!-- 統計情報 -->
            <div class="jgrants-stat-card">
                <h3>投稿統計</h3>
                <div class="stat-number"><?php echo esc_html($stats['total_grants']); ?></div>
                <div class="stat-label">総投稿数</div>
                <div class="stat-details">
                    公開: <?php echo esc_html($stats['published_grants']); ?>件 | 
                    下書き: <?php echo esc_html($stats['draft_grants']); ?>件
                </div>
            </div>
            
            <div class="jgrants-stat-card">
                <h3>API接続状況</h3>
                <div class="stat-status <?php echo $stats['api_status'] === 'connected' ? 'status-success' : 'status-error'; ?>">
                    <?php echo $stats['api_status'] === 'connected' ? '接続中' : 'エラー'; ?>
                </div>
                <button type="button" class="button" id="test-api-connection">接続テスト</button>
            </div>
            
            <div class="jgrants-stat-card">
                <h3>AI接続状況</h3>
                <div class="stat-status <?php echo $stats['ai_status'] === 'connected' ? 'status-success' : 'status-error'; ?>">
                    <?php echo $stats['ai_status'] === 'connected' ? '接続中' : 'エラー'; ?>
                </div>
                <button type="button" class="button" id="test-ai-connection">接続テスト</button>
            </div>
            
            <div class="jgrants-stat-card">
                <h3>最終同期</h3>
                <div class="stat-text"><?php echo esc_html($stats['last_sync']); ?></div>
            </div>
        </div>
        
        <!-- クイックアクション -->
        <div class="jgrants-quick-actions">
            <h2>クイックアクション</h2>
            <div class="action-buttons">
                <a href="<?php echo admin_url('admin.php?page=jgrants-batch-generate'); ?>" class="button button-primary">
                    下書きを一括生成
                </a>
                <a href="<?php echo admin_url('admin.php?page=jgrants-field-mapping'); ?>" class="button">
                    フィールドマッピング設定
                </a>
                <a href="<?php echo admin_url('admin.php?page=jgrants-ai-settings'); ?>" class="button">
                    AI設定
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=grant'); ?>" class="button">
                    助成金一覧を表示
                </a>
            </div>
        </div>
        
        <!-- 同期履歴 -->
        <div class="jgrants-sync-history">
            <h2>同期履歴</h2>
            <?php if (!empty($sync_history)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>日時</th>
                            <th>アクション</th>
                            <th>成功</th>
                            <th>失敗</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($sync_history, 0, 10) as $record) : ?>
                            <tr>
                                <td><?php echo esc_html($record['timestamp']); ?></td>
                                <td>
                                    <?php
                                    $action_labels = array(
                                        'batch_create_drafts' => '下書き一括生成',
                                        'batch_publish_posts' => '投稿一括公開'
                                    );
                                    echo esc_html($action_labels[$record['action']] ?? $record['action']);
                                    ?>
                                </td>
                                <td><span class="success-count"><?php echo esc_html($record['success_count']); ?></span></td>
                                <td><span class="error-count"><?php echo esc_html($record['error_count']); ?></span></td>
                                <td>
                                    <button type="button" class="button button-small toggle-details" data-target="details-<?php echo esc_attr($record['timestamp']); ?>">
                                        詳細表示
                                    </button>
                                    <div id="details-<?php echo esc_attr($record['timestamp']); ?>" class="sync-details" style="display: none;">
                                        <pre><?php echo esc_html(json_encode($record['results'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>同期履歴がありません。</p>
            <?php endif; ?>
        </div>
        
        <!-- システム情報 -->
        <div class="jgrants-system-info">
            <h2>システム情報</h2>
            <table class="form-table">
                <tr>
                    <th>プラグインバージョン</th>
                    <td><?php echo esc_html(JGRANTS_PLUGIN_VERSION); ?></td>
                </tr>
                <tr>
                    <th>WordPress バージョン</th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th>PHP バージョン</th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th>投稿タイプ登録状況</th>
                    <td>
                        <?php if (JGrants_Post_Type::is_registered()) : ?>
                            <span class="status-success">✓ grant投稿タイプが登録されています</span>
                        <?php else : ?>
                            <span class="status-error">✗ grant投稿タイプが登録されていません</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>タクソノミー登録状況</th>
                    <td>
                        <?php if (JGrants_Taxonomies::are_registered()) : ?>
                            <span class="status-success">✓ すべてのタクソノミーが登録されています</span>
                        <?php else : ?>
                            <span class="status-error">✗ 一部のタクソノミーが登録されていません</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>ACF プラグイン</th>
                    <td>
                        <?php if (function_exists('acf_get_field_groups')) : ?>
                            <span class="status-success">✓ Advanced Custom Fields が有効です</span>
                        <?php else : ?>
                            <span class="status-warning">⚠ Advanced Custom Fields が無効です</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 詳細表示の切り替え
    $('.toggle-details').on('click', function() {
        var target = $(this).data('target');
        $('#' + target).toggle();
        $(this).text($(this).text() === '詳細表示' ? '詳細非表示' : '詳細表示');
    });
    
    // API接続テスト
    $('#test-api-connection').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('テスト中...');
        
        $.post(jgrants_ajax.ajax_url, {
            action: 'jgrants_test_api_connection',
            nonce: jgrants_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('API接続テストに成功しました。\n' + response.data.message);
            } else {
                alert('API接続テストに失敗しました。\n' + response.data);
            }
        }).always(function() {
            button.prop('disabled', false).text('接続テスト');
        });
    });
    
    // AI接続テスト
    $('#test-ai-connection').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('テスト中...');
        
        $.post(jgrants_ajax.ajax_url, {
            action: 'jgrants_test_ai_connection',
            nonce: jgrants_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert('AI接続テストに成功しました。\n' + response.data.message);
            } else {
                alert('AI接続テストに失敗しました。\n' + response.data);
            }
        }).always(function() {
            button.prop('disabled', false).text('接続テスト');
        });
    });
});
</script>

