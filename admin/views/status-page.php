<?php
/**
 * プラグインステータスページのテンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

// ヘルスチェックの実行
$health_check = JGrants_Health_Check::run_checks();
$system_status = JGrants_Debug::check_system_status();
$debug_info = JGrants_Debug::get_debug_info();

// 修復アクションの実行
$repair_results = array();
if (isset($_POST['run_repair']) && wp_verify_nonce($_POST['jgrants_repair_nonce'], 'jgrants_repair_action')) {
    $repair_results = JGrants_Health_Check::attempt_repair();
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('J-Grants Integration - システムステータス', 'jgrants-integration'); ?></h1>
    
    <?php if (!empty($repair_results)): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php echo esc_html__('修復が完了しました:', 'jgrants-integration'); ?></strong></p>
        <ul>
            <?php foreach ($repair_results as $repair): ?>
            <li><?php echo esc_html($repair); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- ヘルスチェック結果 -->
    <div class="card">
        <h2 class="title">
            <?php echo esc_html__('ヘルスチェック', 'jgrants-integration'); ?>
            <?php if ($health_check['status'] === 'healthy'): ?>
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            <?php elseif ($health_check['status'] === 'warning'): ?>
                <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
            <?php else: ?>
                <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
            <?php endif; ?>
        </h2>
        
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('チェック項目', 'jgrants-integration'); ?></th>
                    <th><?php echo esc_html__('ステータス', 'jgrants-integration'); ?></th>
                    <th><?php echo esc_html__('メッセージ', 'jgrants-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health_check['checks'] as $check_name => $check): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $check_name))); ?></strong></td>
                    <td>
                        <?php if ($check['status'] === 'ok'): ?>
                            <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                            <?php echo esc_html__('正常', 'jgrants-integration'); ?>
                        <?php elseif ($check['status'] === 'warning'): ?>
                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                            <?php echo esc_html__('警告', 'jgrants-integration'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                            <?php echo esc_html__('エラー', 'jgrants-integration'); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($check['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($health_check['status'] !== 'healthy'): ?>
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('jgrants_repair_action', 'jgrants_repair_nonce'); ?>
            <input type="submit" name="run_repair" class="button button-primary" 
                   value="<?php echo esc_attr__('自動修復を実行', 'jgrants-integration'); ?>">
        </form>
        <?php endif; ?>
    </div>
    
    <!-- システムステータス -->
    <div class="card" style="margin-top: 20px;">
        <h2 class="title"><?php echo esc_html__('システム要件', 'jgrants-integration'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('項目', 'jgrants-integration'); ?></th>
                    <th><?php echo esc_html__('現在の値', 'jgrants-integration'); ?></th>
                    <th><?php echo esc_html__('ステータス', 'jgrants-integration'); ?></th>
                    <th><?php echo esc_html__('メッセージ', 'jgrants-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($system_status as $item_key => $item): ?>
                <tr>
                    <td><strong><?php echo esc_html($item['label']); ?></strong></td>
                    <td><?php echo esc_html($item['value']); ?></td>
                    <td>
                        <?php if ($item['status'] === 'ok'): ?>
                            <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                        <?php elseif ($item['status'] === 'warning'): ?>
                            <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($item['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- デバッグ情報 -->
    <div class="card" style="margin-top: 20px;">
        <h2 class="title"><?php echo esc_html__('システム情報', 'jgrants-integration'); ?></h2>
        <table class="widefat striped">
            <tbody>
                <?php foreach ($debug_info as $key => $value): ?>
                <tr>
                    <th style="width: 30%;"><?php echo esc_html($key); ?></th>
                    <td>
                        <?php 
                        if (is_array($value)) {
                            echo '<ul style="margin: 0;">';
                            foreach ($value as $item) {
                                echo '<li>' . esc_html($item) . '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo esc_html($value);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- エクスポートボタン -->
    <div style="margin-top: 20px;">
        <button class="button button-secondary" onclick="exportDebugInfo()">
            <?php echo esc_html__('デバッグ情報をエクスポート', 'jgrants-integration'); ?>
        </button>
    </div>
</div>

<script>
function exportDebugInfo() {
    var debugInfo = <?php echo json_encode(JGrants_Health_Check::generate_report()); ?>;
    var dataStr = JSON.stringify(debugInfo, null, 2);
    var dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    var exportFileDefaultName = 'jgrants-debug-' + new Date().toISOString().slice(0,10) + '.json';
    
    var linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}
</script>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
}
.card .title {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4;
}
</style>