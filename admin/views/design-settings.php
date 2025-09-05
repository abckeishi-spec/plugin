<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>デザイン設定</h1>
    
    <?php settings_errors(); ?>
    
    <div class="jgrants-design-settings">
        <p>助成金一覧の表示デザインをカスタマイズできます。</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('jgrants_design_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="accent_color">アクセントカラー</label>
                    </th>
                    <td>
                        <input type="text" id="accent_color" name="accent_color" 
                               value="<?php echo esc_attr($design_settings['accent_color']); ?>" 
                               class="color-picker" data-default-color="#007cba">
                        <p class="description">
                            リンクやボタンなどのアクセント要素に使用される色です。
                        </p>
                        <div class="color-preview">
                            <div class="preview-item">
                                <a href="#" class="jgrants-accent" style="color: <?php echo esc_attr($design_settings['accent_color']); ?>;">
                                    サンプルリンク
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="status_badge_color">ステータスバッジカラー</label>
                    </th>
                    <td>
                        <input type="text" id="status_badge_color" name="status_badge_color" 
                               value="<?php echo esc_attr($design_settings['status_badge_color']); ?>" 
                               class="color-picker" data-default-color="#28a745">
                        <p class="description">
                            助成金のステータス（募集中、終了など）を表示するバッジの色です。
                        </p>
                        <div class="color-preview">
                            <div class="preview-item">
                                <span class="jgrants-status-badge" style="background-color: <?php echo esc_attr($design_settings['status_badge_color']); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">
                                    募集中
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="custom_css">カスタムCSS</label>
                    </th>
                    <td>
                        <textarea id="custom_css" name="custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($design_settings['custom_css']); ?></textarea>
                        <p class="description">
                            助成金一覧の表示をさらにカスタマイズするためのCSSを記述できます。<br>
                            利用可能なクラス: <code>.jgrants-list</code>, <code>.jgrants-item</code>, <code>.jgrants-title</code>, <code>.jgrants-meta</code>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="design-preview">
                <h3>プレビュー</h3>
                <div class="preview-container">
                    <div class="jgrants-list preview-list">
                        <div class="jgrants-item preview-item">
                            <h4 class="jgrants-title">
                                <a href="#" class="jgrants-accent" style="color: <?php echo esc_attr($design_settings['accent_color']); ?>;">
                                    IT導入補助金2024
                                </a>
                            </h4>
                            <div class="jgrants-meta">
                                <span class="jgrants-status-badge" style="background-color: <?php echo esc_attr($design_settings['status_badge_color']); ?>;">募集中</span>
                                <span class="jgrants-organization">中小企業庁</span>
                                <span class="jgrants-amount">50万円〜450万円</span>
                            </div>
                            <div class="jgrants-excerpt">
                                中小企業・小規模事業者等のITツール導入による生産性向上を支援する補助金です。
                            </div>
                        </div>
                        
                        <div class="jgrants-item preview-item">
                            <h4 class="jgrants-title">
                                <a href="#" class="jgrants-accent" style="color: <?php echo esc_attr($design_settings['accent_color']); ?>;">
                                    ものづくり補助金
                                </a>
                            </h4>
                            <div class="jgrants-meta">
                                <span class="jgrants-status-badge" style="background-color: <?php echo esc_attr($design_settings['status_badge_color']); ?>;">募集中</span>
                                <span class="jgrants-organization">中小企業庁</span>
                                <span class="jgrants-amount">100万円〜1000万円</span>
                            </div>
                            <div class="jgrants-excerpt">
                                中小企業・小規模事業者等の革新的サービス開発・試作品開発・生産プロセスの改善を行うための設備投資等を支援します。
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="design-templates">
                <h3>デザインテンプレート</h3>
                <p>よく使われる配色パターンから選択できます。</p>
                
                <div class="template-grid">
                    <div class="template-item" data-accent="#007cba" data-badge="#28a745">
                        <div class="template-preview">
                            <div class="template-accent" style="background-color: #007cba;"></div>
                            <div class="template-badge" style="background-color: #28a745;"></div>
                        </div>
                        <div class="template-name">デフォルト（青・緑）</div>
                    </div>
                    
                    <div class="template-item" data-accent="#dc3545" data-badge="#fd7e14">
                        <div class="template-preview">
                            <div class="template-accent" style="background-color: #dc3545;"></div>
                            <div class="template-badge" style="background-color: #fd7e14;"></div>
                        </div>
                        <div class="template-name">レッド・オレンジ</div>
                    </div>
                    
                    <div class="template-item" data-accent="#6f42c1" data-badge="#e83e8c">
                        <div class="template-preview">
                            <div class="template-accent" style="background-color: #6f42c1;"></div>
                            <div class="template-badge" style="background-color: #e83e8c;"></div>
                        </div>
                        <div class="template-name">パープル・ピンク</div>
                    </div>
                    
                    <div class="template-item" data-accent="#20c997" data-badge="#17a2b8">
                        <div class="template-preview">
                            <div class="template-accent" style="background-color: #20c997;"></div>
                            <div class="template-badge" style="background-color: #17a2b8;"></div>
                        </div>
                        <div class="template-name">ティール・シアン</div>
                    </div>
                </div>
            </div>
            
            <?php submit_button('デザイン設定を保存'); ?>
        </form>
        
        <div class="design-tools">
            <h3>デザインツール</h3>
            <div class="tool-buttons">
                <button type="button" class="button" id="reset-colors">色をリセット</button>
                <button type="button" class="button" id="export-design">設定をエクスポート</button>
                <input type="file" id="import-design" accept=".json" style="display: none;">
                <button type="button" class="button" id="import-design-btn">設定をインポート</button>
            </div>
        </div>
    </div>
</div>

<style>
.jgrants-design-settings .color-preview {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.preview-container {
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
}

.preview-list .preview-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 4px;
    margin-bottom: 15px;
}

.preview-item .jgrants-title {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.preview-item .jgrants-meta {
    margin-bottom: 10px;
}

.preview-item .jgrants-meta > * {
    margin-right: 10px;
}

.jgrants-status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    color: white;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.template-item {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s;
}

.template-item:hover {
    border-color: #007cba;
}

.template-preview {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
}

.template-accent,
.template-badge {
    width: 30px;
    height: 30px;
    border-radius: 50%;
}

.template-name {
    font-weight: bold;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // カラーピッカーの初期化
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview();
        }
    });
    
    // プレビューの更新
    function updatePreview() {
        var accentColor = $('#accent_color').val();
        var badgeColor = $('#status_badge_color').val();
        
        $('.jgrants-accent').css('color', accentColor);
        $('.jgrants-status-badge').css('background-color', badgeColor);
    }
    
    // テンプレートの適用
    $('.template-item').on('click', function() {
        var accentColor = $(this).data('accent');
        var badgeColor = $(this).data('badge');
        
        $('#accent_color').val(accentColor).wpColorPicker('color', accentColor);
        $('#status_badge_color').val(badgeColor).wpColorPicker('color', badgeColor);
        
        updatePreview();
        
        $('.template-item').removeClass('selected');
        $(this).addClass('selected');
    });
    
    // 色のリセット
    $('#reset-colors').on('click', function() {
        if (!confirm('色設定をデフォルトに戻しますか？')) {
            return;
        }
        
        $('#accent_color').val('#007cba').wpColorPicker('color', '#007cba');
        $('#status_badge_color').val('#28a745').wpColorPicker('color', '#28a745');
        $('#custom_css').val('');
        
        updatePreview();
    });
    
    // 設定エクスポート
    $('#export-design').on('click', function() {
        var settings = {
            accent_color: $('#accent_color').val(),
            status_badge_color: $('#status_badge_color').val(),
            custom_css: $('#custom_css').val(),
            export_date: new Date().toISOString(),
            plugin_version: '<?php echo JGRANTS_PLUGIN_VERSION; ?>'
        };
        
        var dataStr = JSON.stringify(settings, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = 'jgrants-design-settings-' + new Date().toISOString().split('T')[0] + '.json';
        link.click();
        
        URL.revokeObjectURL(url);
    });
    
    // 設定インポート
    $('#import-design-btn').on('click', function() {
        $('#import-design').click();
    });
    
    $('#import-design').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                
                if (data.accent_color) {
                    $('#accent_color').val(data.accent_color).wpColorPicker('color', data.accent_color);
                }
                if (data.status_badge_color) {
                    $('#status_badge_color').val(data.status_badge_color).wpColorPicker('color', data.status_badge_color);
                }
                if (data.custom_css) {
                    $('#custom_css').val(data.custom_css);
                }
                
                updatePreview();
                alert('設定をインポートしました。設定を保存してください。');
            } catch (error) {
                alert('ファイルの読み込みに失敗しました。');
            }
        };
        reader.readAsText(file);
    });
    
    // 初期プレビュー更新
    updatePreview();
});
</script>

