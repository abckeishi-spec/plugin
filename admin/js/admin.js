/**
 * JGrants Integration Admin JavaScript
 */

(function($) {
    'use strict';
    
    // 管理画面の初期化
    $(document).ready(function() {
        initializeAdminFeatures();
    });
    
    /**
     * 管理画面機能の初期化
     */
    function initializeAdminFeatures() {
        // 共通機能
        initializeTooltips();
        initializeConfirmDialogs();
        initializeAjaxLoading();
        
        // ページ固有の機能
        if ($('.jgrants-dashboard').length) {
            initializeDashboard();
        }
        
        if ($('.jgrants-field-mapping').length) {
            initializeFieldMapping();
        }
        
        if ($('.jgrants-batch-generate').length) {
            initializeBatchGenerate();
        }
        
        if ($('.jgrants-ai-settings').length) {
            initializeAISettings();
        }
        
        if ($('.jgrants-design-settings').length) {
            initializeDesignSettings();
        }
    }
    
    /**
     * ツールチップの初期化
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });
    }
    
    /**
     * 確認ダイアログの初期化
     */
    function initializeConfirmDialogs() {
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * AJAX読み込み表示の初期化
     */
    function initializeAjaxLoading() {
        $(document).ajaxStart(function() {
            $('body').addClass('jgrants-loading');
        }).ajaxStop(function() {
            $('body').removeClass('jgrants-loading');
        });
    }
    
    /**
     * ダッシュボードの初期化
     */
    function initializeDashboard() {
        // 統計情報の自動更新
        setInterval(function() {
            updateDashboardStats();
        }, 300000); // 5分ごと
        
        // 詳細表示の切り替え
        $('.toggle-details').on('click', function() {
            var targetId = $(this).data('target');
            var $target = $('#' + targetId);
            
            $target.slideToggle();
            $(this).text($target.is(':visible') ? '詳細非表示' : '詳細表示');
        });
    }
    
    /**
     * ダッシュボード統計の更新
     */
    function updateDashboardStats() {
        $.post(ajaxurl, {
            action: 'jgrants_get_dashboard_stats',
            nonce: jgrants_ajax.nonce
        }, function(response) {
            if (response.success) {
                updateStatsDisplay(response.data);
            }
        });
    }
    
    /**
     * 統計表示の更新
     */
    function updateStatsDisplay(stats) {
        $('.stat-number').each(function() {
            var $this = $(this);
            var key = $this.data('stat-key');
            if (stats[key] !== undefined) {
                animateNumber($this, parseInt($this.text()), stats[key]);
            }
        });
    }
    
    /**
     * 数値のアニメーション
     */
    function animateNumber($element, from, to) {
        $({ value: from }).animate({ value: to }, {
            duration: 1000,
            step: function() {
                $element.text(Math.floor(this.value));
            },
            complete: function() {
                $element.text(to);
            }
        });
    }
    
    /**
     * フィールドマッピングの初期化
     */
    function initializeFieldMapping() {
        // マッピング状況の更新
        updateMappingStatus();
        
        // マッピング変更時の処理
        $('select[name^="field_mapping"]').on('change', function() {
            updateMappingStatus();
            showMappingPreview();
        });
        
        // 自動マッピング
        $('#auto-mapping').on('click', function() {
            if (!confirm('自動マッピングを実行しますか？現在の設定は上書きされます。')) {
                return;
            }
            
            applyAutoMapping();
        });
        
        // マッピングクリア
        $('#clear-mapping').on('click', function() {
            if (!confirm('すべてのマッピング設定をクリアしますか？')) {
                return;
            }
            
            clearAllMappings();
        });
    }
    
    /**
     * マッピング状況の更新
     */
    function updateMappingStatus() {
        var totalFields = $('select[name^="field_mapping"]').length;
        var mappedFields = $('select[name^="field_mapping"]').filter(function() {
            return $(this).val() !== '';
        }).length;
        
        var percentage = totalFields > 0 ? Math.round((mappedFields / totalFields) * 100) : 0;
        
        $('.summary-stats .stat-number').first().text(mappedFields);
        $('.summary-stats .stat-number').last().text(percentage + '%');
        
        // プログレスバーの更新
        updateProgressBar(percentage);
    }
    
    /**
     * プログレスバーの更新
     */
    function updateProgressBar(percentage) {
        var $progressBar = $('.mapping-progress-bar');
        if ($progressBar.length === 0) {
            $progressBar = $('<div class="mapping-progress-bar"><div class="progress-fill"></div></div>');
            $('.summary-stats').after($progressBar);
        }
        
        $progressBar.find('.progress-fill').css('width', percentage + '%');
    }
    
    /**
     * 自動マッピングの適用
     */
    function applyAutoMapping() {
        var autoMappings = {
            'title': 'post_title',
            'ai_description': 'post_content',
            'ai_summary': 'post_excerpt'
        };
        
        $.each(autoMappings, function(apiField, acfField) {
            $('select[name="field_mapping[' + apiField + ']"]').val(acfField);
        });
        
        updateMappingStatus();
        showNotice('自動マッピングを適用しました。設定を保存してください。', 'success');
    }
    
    /**
     * すべてのマッピングをクリア
     */
    function clearAllMappings() {
        $('select[name^="field_mapping"]').val('');
        updateMappingStatus();
        showNotice('マッピング設定をクリアしました。', 'info');
    }
    
    /**
     * マッピングプレビューの表示
     */
    function showMappingPreview() {
        // プレビュー機能の実装（必要に応じて）
    }
    
    /**
     * 下書き一括生成の初期化
     */
    function initializeBatchGenerate() {
        // フォームバリデーション
        $('#batch-generate-form').on('submit', function(e) {
            if (!validateGenerateForm()) {
                e.preventDefault();
                return false;
            }
        });
        
        // 全選択/選択解除
        $('#select-all-drafts').on('click', function() {
            $('.draft-checkbox').prop('checked', true);
            updatePublishButtonState();
        });
        
        $('#deselect-all-drafts').on('click', function() {
            $('.draft-checkbox').prop('checked', false);
            updatePublishButtonState();
        });
        
        // チェックボックス変更時の処理
        $('.draft-checkbox').on('change', function() {
            updatePublishButtonState();
        });
        
        // 公開ボタンの状態更新
        updatePublishButtonState();
    }
    
    /**
     * 生成フォームのバリデーション
     */
    function validateGenerateForm() {
        var count = parseInt($('#generate-count').val());
        var keyword = $('#search-keyword').val().trim();
        
        if (isNaN(count) || count < 1 || count > 50) {
            showNotice('生成件数は1〜50件の範囲で指定してください。', 'error');
            return false;
        }
        
        if (keyword.length < 2) {
            showNotice('キーワードは2文字以上で入力してください。', 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * 公開ボタンの状態更新
     */
    function updatePublishButtonState() {
        var checkedCount = $('.draft-checkbox:checked').length;
        var $publishBtn = $('#publish-selected-btn');
        
        if (checkedCount > 0) {
            $publishBtn.prop('disabled', false).text('選択した' + checkedCount + '件を公開');
        } else {
            $publishBtn.prop('disabled', true).text('選択した投稿を公開');
        }
    }
    
    /**
     * AI設定の初期化
     */
    function initializeAISettings() {
        // APIキーの表示/非表示切り替え
        $('#toggle-api-key').on('click', function() {
            var $input = $('#gemini_api_key');
            var type = $input.attr('type');
            $input.attr('type', type === 'password' ? 'text' : 'password');
            $(this).text(type === 'password' ? '非表示' : '表示');
        });
        
        // AI接続テスト
        $('#test-ai-key').on('click', function() {
            var $button = $(this);
            var $result = $('#ai-test-result');
            
            $button.prop('disabled', true).text('テスト中...');
            $result.show().html('<div class="notice notice-info"><p>接続をテストしています...</p></div>');
            
            $.post(ajaxurl, {
                action: 'jgrants_test_ai_connection',
                nonce: jgrants_admin.nonce
            }, function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            }).fail(function() {
                $result.html('<div class="notice notice-error"><p>接続テストに失敗しました。ネットワーク接続を確認してください。</p></div>');
            }).always(function() {
                $button.prop('disabled', false).text('接続テスト');
            });
        });
        
        // Temperature値の表示更新
        $('#ai_temperature').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
        
        // 設定保存時の検証
        $('form').on('submit', function(e) {
            var apiKey = $('#gemini_api_key').val().trim();
            
            if (apiKey && !isValidApiKey(apiKey)) {
                e.preventDefault();
                alert('APIキーの形式が正しくありません。Google AI Studioで取得した正しいAPIキーを入力してください。');
                return false;
            }
        });
        
        // AI機能テスト
        $('.ai-test-btn').on('click', function() {
            var testType = $(this).data('test-type');
            runAITest(testType, $(this));
        });
    }
    
    /**
     * APIキーの形式チェック
     */
    function isValidApiKey(apiKey) {
        // Gemini APIキーの基本的な形式チェック
        return /^AIza[0-9A-Za-z_-]{35}$/.test(apiKey);
    }
    
    /**
     * AI機能テストの実行
     */
    function runAITest(testType, $button) {
        var originalText = $button.text();
        $button.prop('disabled', true).text('テスト中...');
        
        // テスト用のサンプルデータ
        var sampleData = {
            title: 'IT導入補助金2024',
            organization: '中小企業庁',
            description: '中小企業・小規模事業者等のITツール導入による生産性向上を支援する補助金です。',
            purpose: 'ITツールの導入による業務効率化・売上向上',
            industry: ['情報通信業', '製造業', 'サービス業']
        };
        
        $.post(ajaxurl, {
            action: 'jgrants_test_ai_function',
            nonce: jgrants_ajax.nonce,
            test_type: testType,
            sample_data: sampleData
        }, function(response) {
            if (response.success) {
                showAITestResult(testType, response.data);
            } else {
                showNotice('AIテストに失敗しました: ' + response.data, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }
    
    /**
     * AIテスト結果の表示
     */
    function showAITestResult(testType, result) {
        var $results = $('#test-results');
        var $output = $('#test-output');
        
        $output.html(
            '<div class="test-result-item">' +
            '<h5>' + testType.toUpperCase() + '生成結果:</h5>' +
            '<div class="test-content">' + escapeHtml(result) + '</div>' +
            '</div>'
        );
        
        $results.show();
    }
    
    /**
     * デザイン設定の初期化
     */
    function initializeDesignSettings() {
        // カラーピッカーの変更時にプレビューを更新
        $('.wp-color-picker').on('change', function() {
            updateDesignPreview();
        });
        
        // テンプレートの適用
        $('.template-item').on('click', function() {
            applyDesignTemplate($(this));
        });
        
        // 初期プレビューの更新
        updateDesignPreview();
    }
    
    /**
     * デザインプレビューの更新
     */
    function updateDesignPreview() {
        var accentColor = $('#accent_color').val();
        var badgeColor = $('#status_badge_color').val();
        
        $('.preview-container .jgrants-accent').css('color', accentColor);
        $('.preview-container .jgrants-status-badge').css('background-color', badgeColor);
    }
    
    /**
     * デザインテンプレートの適用
     */
    function applyDesignTemplate($template) {
        var accentColor = $template.data('accent');
        var badgeColor = $template.data('badge');
        
        $('#accent_color').val(accentColor).wpColorPicker('color', accentColor);
        $('#status_badge_color').val(badgeColor).wpColorPicker('color', badgeColor);
        
        updateDesignPreview();
        
        $('.template-item').removeClass('selected');
        $template.addClass('selected');
        
        showNotice('デザインテンプレートを適用しました。', 'success');
    }
    
    /**
     * 通知メッセージの表示
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // 自動削除
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // 削除ボタンの処理
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * HTMLエスケープ
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * ローディング表示の制御
     */
    function showLoading($element) {
        $element.addClass('jgrants-loading');
    }
    
    function hideLoading($element) {
        $element.removeClass('jgrants-loading');
    }
    
    /**
     * データのエクスポート
     */
    function exportData(data, filename) {
        var dataStr = JSON.stringify(data, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        
        URL.revokeObjectURL(url);
    }
    
    /**
     * ファイルのインポート
     */
    function importFile(file, callback) {
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                callback(null, data);
            } catch (error) {
                callback('ファイルの読み込みに失敗しました。');
            }
        };
        reader.readAsText(file);
    }
    
    // グローバルに公開する関数
    window.JGrantsAdmin = {
        showNotice: showNotice,
        exportData: exportData,
        importFile: importFile,
        updateDesignPreview: updateDesignPreview
    };
    
})(jQuery);

