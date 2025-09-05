<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>フィールドマッピング設定</h1>
    
    <?php settings_errors(); ?>
    
    <div class="jgrants-field-mapping">
        <p>JグランツAPIから取得したデータを、どのACFフィールドに保存するかを設定してください。</p>
        
        <?php if (empty($acf_fields)) : ?>
            <div class="notice notice-warning">
                <p><strong>注意:</strong> grant投稿タイプに関連付けられたACFフィールドグループが見つかりません。</p>
                <p>Advanced Custom Fieldsプラグインが有効で、grant投稿タイプ用のフィールドグループが作成されていることを確認してください。</p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('jgrants_field_mapping'); ?>
            
            <table class="form-table">
                <thead>
                    <tr>
                        <th>APIフィールド</th>
                        <th>説明</th>
                        <th>データ型</th>
                        <th>ACFフィールド</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_fields as $api_field => $field_info) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($field_info['label']); ?></strong>
                                <br>
                                <code><?php echo esc_html($api_field); ?></code>
                            </td>
                            <td><?php echo esc_html($field_info['description']); ?></td>
                            <td><span class="field-type"><?php echo esc_html($field_info['type']); ?></span></td>
                            <td>
                                <select name="field_mapping[<?php echo esc_attr($api_field); ?>]">
                                    <option value="">-- 選択してください --</option>
                                    
                                    <!-- 標準フィールド -->
                                    <optgroup label="標準フィールド">
                                        <option value="post_title" <?php selected(isset($field_mapping[$api_field]) ? $field_mapping[$api_field] : '', 'post_title'); ?>>
                                            投稿タイトル
                                        </option>
                                        <option value="post_content" <?php selected(isset($field_mapping[$api_field]) ? $field_mapping[$api_field] : '', 'post_content'); ?>>
                                            投稿内容
                                        </option>
                                        <option value="post_excerpt" <?php selected(isset($field_mapping[$api_field]) ? $field_mapping[$api_field] : '', 'post_excerpt'); ?>>
                                            投稿抜粋
                                        </option>
                                    </optgroup>
                                    
                                    <!-- ACFフィールド -->
                                    <?php if (!empty($acf_fields)) : ?>
                                        <?php
                                        $grouped_fields = array();
                                        foreach ($acf_fields as $field_name => $field_data) {
                                            $grouped_fields[$field_data['group']][$field_name] = $field_data;
                                        }
                                        ?>
                                        
                                        <?php foreach ($grouped_fields as $group_name => $group_fields) : ?>
                                            <optgroup label="<?php echo esc_attr($group_name); ?>">
                                                <?php foreach ($group_fields as $field_name => $field_data) : ?>
                                                    <option value="<?php echo esc_attr($field_name); ?>" <?php selected(isset($field_mapping[$api_field]) ? $field_mapping[$api_field] : '', $field_name); ?>>
                                                        <?php echo esc_html($field_data['label']); ?> (<?php echo esc_html($field_data['type']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                
                                <?php if (isset($field_mapping[$api_field]) && !empty($field_mapping[$api_field])) : ?>
                                    <div class="mapping-status">
                                        <span class="status-mapped">✓ マッピング済み</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mapping-summary">
                <h3>マッピング状況</h3>
                <div class="summary-stats">
                    <?php
                    $total_fields = count($api_fields);
                    $mapped_fields = count(array_filter($field_mapping));
                    $mapping_percentage = $total_fields > 0 ? round(($mapped_fields / $total_fields) * 100) : 0;
                    ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $mapped_fields; ?></span>
                        <span class="stat-label">/ <?php echo $total_fields; ?> フィールドがマッピング済み</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $mapping_percentage; ?>%</span>
                        <span class="stat-label">完了率</span>
                    </div>
                </div>
            </div>
            
            <div class="mapping-recommendations">
                <h3>推奨マッピング</h3>
                <p>効果的なデータ活用のために、以下のマッピングを推奨します：</p>
                <ul>
                    <li><strong>title</strong> → 投稿タイトル（必須）</li>
                    <li><strong>ai_description</strong> → 投稿内容（AIが生成した詳細説明）</li>
                    <li><strong>ai_summary</strong> → 投稿抜粋（AIが生成した要約）</li>
                    <li><strong>organization</strong> → 実施機関フィールド</li>
                    <li><strong>amount_min, amount_max</strong> → 補助金額フィールド</li>
                    <li><strong>application_start, application_end</strong> → 申請期間フィールド</li>
                </ul>
            </div>
            
            <?php submit_button('マッピング設定を保存'); ?>
        </form>
        
        <div class="mapping-tools">
            <h3>マッピングツール</h3>
            <div class="tool-buttons">
                <button type="button" class="button" id="auto-mapping">自動マッピング</button>
                <button type="button" class="button" id="clear-mapping">マッピングをクリア</button>
                <button type="button" class="button" id="export-mapping">設定をエクスポート</button>
                <input type="file" id="import-mapping" accept=".json" style="display: none;">
                <button type="button" class="button" id="import-mapping-btn">設定をインポート</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 自動マッピング
    $('#auto-mapping').on('click', function() {
        if (!confirm('自動マッピングを実行しますか？現在の設定は上書きされます。')) {
            return;
        }
        
        // 推奨マッピングを適用
        var autoMappings = {
            'title': 'post_title',
            'ai_description': 'post_content',
            'ai_summary': 'post_excerpt'
        };
        
        $.each(autoMappings, function(apiField, acfField) {
            $('select[name="field_mapping[' + apiField + ']"]').val(acfField);
        });
        
        alert('自動マッピングを適用しました。設定を保存してください。');
    });
    
    // マッピングクリア
    $('#clear-mapping').on('click', function() {
        if (!confirm('すべてのマッピング設定をクリアしますか？')) {
            return;
        }
        
        $('select[name^="field_mapping"]').val('');
        alert('マッピング設定をクリアしました。');
    });
    
    // 設定エクスポート
    $('#export-mapping').on('click', function() {
        var mappings = {};
        $('select[name^="field_mapping"]').each(function() {
            var name = $(this).attr('name').match(/\[(.*?)\]/)[1];
            var value = $(this).val();
            if (value) {
                mappings[name] = value;
            }
        });
        
        var exportData = {
            field_mapping: mappings,
            export_date: new Date().toISOString(),
            plugin_version: '<?php echo JGRANTS_PLUGIN_VERSION; ?>'
        };
        
        var dataStr = JSON.stringify(exportData, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = 'jgrants-field-mapping-' + new Date().toISOString().split('T')[0] + '.json';
        link.click();
        
        URL.revokeObjectURL(url);
    });
    
    // 設定インポート
    $('#import-mapping-btn').on('click', function() {
        $('#import-mapping').click();
    });
    
    $('#import-mapping').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                if (data.field_mapping) {
                    $.each(data.field_mapping, function(apiField, acfField) {
                        $('select[name="field_mapping[' + apiField + ']"]').val(acfField);
                    });
                    alert('設定をインポートしました。設定を保存してください。');
                } else {
                    alert('無効なファイル形式です。');
                }
            } catch (error) {
                alert('ファイルの読み込みに失敗しました。');
            }
        };
        reader.readAsText(file);
    });
});
</script>

