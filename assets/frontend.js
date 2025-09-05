/**
 * JGrants Integration Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // フロントエンドの初期化
    $(document).ready(function() {
        initializeJGrantsFrontend();
    });
    
    /**
     * フロントエンド機能の初期化
     */
    function initializeJGrantsFrontend() {
        initializeGrantsList();
        initializeFilters();
        initializeSearch();
        initializeLazyLoading();
        initializeAnimations();
    }
    
    /**
     * 助成金一覧の初期化
     */
    function initializeGrantsList() {
        $('.jgrants-list').each(function() {
            var $list = $(this);
            
            // アイテムの表示アニメーション
            animateItemsOnScroll($list);
            
            // 外部リンクの処理
            $list.find('.jgrants-external-link').on('click', function(e) {
                // 外部リンクのクリック追跡（必要に応じて）
                trackExternalLinkClick($(this).attr('href'));
            });
            
            // 詳細リンクの処理
            $list.find('.jgrants-read-more').on('click', function(e) {
                // 詳細リンクのクリック追跡（必要に応じて）
                trackDetailLinkClick($(this).attr('href'));
            });
        });
    }
    
    /**
     * フィルター機能の初期化
     */
    function initializeFilters() {
        // カテゴリーフィルター
        $('.jgrants-category-filter').on('change', function() {
            var category = $(this).val();
            filterGrantsByCategory(category);
        });
        
        // 都道府県フィルター
        $('.jgrants-prefecture-filter').on('change', function() {
            var prefecture = $(this).val();
            filterGrantsByPrefecture(prefecture);
        });
        
        // ステータスフィルター
        $('.jgrants-status-filter').on('change', function() {
            var status = $(this).val();
            filterGrantsByStatus(status);
        });
        
        // フィルターリセット
        $('.jgrants-filter-reset').on('click', function(e) {
            e.preventDefault();
            resetAllFilters();
        });
    }
    
    /**
     * 検索機能の初期化
     */
    function initializeSearch() {
        var $searchInput = $('.jgrants-search-input');
        var searchTimeout;
        
        $searchInput.on('input', function() {
            var query = $(this).val();
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchGrants(query);
            }, 300);
        });
        
        // 検索フォームの送信
        $('.jgrants-search-form').on('submit', function(e) {
            e.preventDefault();
            var query = $searchInput.val();
            searchGrants(query);
        });
    }
    
    /**
     * 遅延読み込みの初期化
     */
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            var lazyImageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.remove('lazy');
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });
            
            $('.jgrants-item img.lazy').each(function() {
                lazyImageObserver.observe(this);
            });
        }
    }
    
    /**
     * アニメーションの初期化
     */
    function initializeAnimations() {
        // スクロール時のアニメーション
        if ('IntersectionObserver' in window) {
            var animationObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        animationObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            $('.jgrants-item').each(function() {
                animationObserver.observe(this);
            });
        }
    }
    
    /**
     * アイテムのスクロールアニメーション
     */
    function animateItemsOnScroll($list) {
        var $items = $list.find('.jgrants-item');
        
        $items.each(function(index) {
            var $item = $(this);
            $item.css('animation-delay', (index * 0.1) + 's');
        });
    }
    
    /**
     * カテゴリーによるフィルタリング
     */
    function filterGrantsByCategory(category) {
        var $items = $('.jgrants-item');
        
        if (!category) {
            $items.show();
            return;
        }
        
        $items.each(function() {
            var $item = $(this);
            var itemCategories = $item.find('.jgrants-category-link').map(function() {
                return $(this).text().trim();
            }).get();
            
            if (itemCategories.includes(category)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        updateResultsCount();
    }
    
    /**
     * 都道府県によるフィルタリング
     */
    function filterGrantsByPrefecture(prefecture) {
        var $items = $('.jgrants-item');
        
        if (!prefecture) {
            $items.show();
            return;
        }
        
        $items.each(function() {
            var $item = $(this);
            var itemPrefectures = $item.find('.jgrants-prefecture-link').map(function() {
                return $(this).text().trim();
            }).get();
            
            if (itemPrefectures.includes(prefecture)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        updateResultsCount();
    }
    
    /**
     * ステータスによるフィルタリング
     */
    function filterGrantsByStatus(status) {
        var $items = $('.jgrants-item');
        
        if (!status) {
            $items.show();
            return;
        }
        
        $items.each(function() {
            var $item = $(this);
            var itemStatus = $item.find('.jgrants-status-badge').attr('class');
            
            if (itemStatus && itemStatus.includes('status-' + status)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        updateResultsCount();
    }
    
    /**
     * 助成金の検索
     */
    function searchGrants(query) {
        var $items = $('.jgrants-item');
        
        if (!query) {
            $items.show();
            updateResultsCount();
            return;
        }
        
        query = query.toLowerCase();
        
        $items.each(function() {
            var $item = $(this);
            var title = $item.find('.jgrants-title').text().toLowerCase();
            var excerpt = $item.find('.jgrants-excerpt').text().toLowerCase();
            var organization = $item.find('.jgrants-organization').text().toLowerCase();
            
            if (title.includes(query) || excerpt.includes(query) || organization.includes(query)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        updateResultsCount();
    }
    
    /**
     * すべてのフィルターをリセット
     */
    function resetAllFilters() {
        $('.jgrants-category-filter').val('');
        $('.jgrants-prefecture-filter').val('');
        $('.jgrants-status-filter').val('');
        $('.jgrants-search-input').val('');
        
        $('.jgrants-item').show();
        updateResultsCount();
    }
    
    /**
     * 結果件数の更新
     */
    function updateResultsCount() {
        var visibleCount = $('.jgrants-item:visible').length;
        var totalCount = $('.jgrants-item').length;
        
        $('.jgrants-results-count').text(visibleCount + ' / ' + totalCount + ' 件');
        
        // 結果がない場合のメッセージ表示
        if (visibleCount === 0) {
            showNoResultsMessage();
        } else {
            hideNoResultsMessage();
        }
    }
    
    /**
     * 結果なしメッセージの表示
     */
    function showNoResultsMessage() {
        if ($('.jgrants-no-results-message').length === 0) {
            var message = '<div class="jgrants-no-results-message">' +
                         '<p>条件に一致する助成金が見つかりませんでした。</p>' +
                         '<button class="jgrants-filter-reset">フィルターをリセット</button>' +
                         '</div>';
            $('.jgrants-items').after(message);
        }
    }
    
    /**
     * 結果なしメッセージの非表示
     */
    function hideNoResultsMessage() {
        $('.jgrants-no-results-message').remove();
    }
    
    /**
     * 外部リンククリックの追跡
     */
    function trackExternalLinkClick(url) {
        // Google Analytics等での追跡（必要に応じて実装）
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                'event_category': 'external_link',
                'event_label': url
            });
        }
    }
    
    /**
     * 詳細リンククリックの追跡
     */
    function trackDetailLinkClick(url) {
        // Google Analytics等での追跡（必要に応じて実装）
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                'event_category': 'detail_link',
                'event_label': url
            });
        }
    }
    
    /**
     * AJAX でのページネーション
     */
    function initializeAjaxPagination() {
        $(document).on('click', '.jgrants-pagination a', function(e) {
            e.preventDefault();
            
            var url = $(this).attr('href');
            var $container = $(this).closest('.jgrants-list');
            
            $container.addClass('jgrants-loading');
            
            $.get(url, function(data) {
                var $newContent = $(data).find('.jgrants-items');
                var $newPagination = $(data).find('.jgrants-pagination');
                
                $container.find('.jgrants-items').replaceWith($newContent);
                $container.find('.jgrants-pagination').replaceWith($newPagination);
                
                // 新しいアイテムのアニメーション
                animateItemsOnScroll($container);
                
                // ページトップにスクロール
                $('html, body').animate({
                    scrollTop: $container.offset().top - 100
                }, 500);
                
            }).always(function() {
                $container.removeClass('jgrants-loading');
            });
        });
    }
    
    /**
     * お気に入り機能
     */
    function initializeFavorites() {
        $('.jgrants-favorite-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var isFavorite = $btn.hasClass('is-favorite');
            
            $.post(jgrants_ajax.ajax_url, {
                action: 'jgrants_toggle_favorite',
                post_id: postId,
                nonce: jgrants_ajax.nonce
            }, function(response) {
                if (response.success) {
                    if (isFavorite) {
                        $btn.removeClass('is-favorite').text('お気に入りに追加');
                    } else {
                        $btn.addClass('is-favorite').text('お気に入りから削除');
                    }
                }
            });
        });
    }
    
    /**
     * ソート機能
     */
    function initializeSorting() {
        $('.jgrants-sort-select').on('change', function() {
            var sortBy = $(this).val();
            var $items = $('.jgrants-item');
            var $container = $('.jgrants-items');
            
            var sortedItems = $items.sort(function(a, b) {
                var aValue, bValue;
                
                switch (sortBy) {
                    case 'date':
                        aValue = new Date($(a).find('.jgrants-updated time').attr('datetime'));
                        bValue = new Date($(b).find('.jgrants-updated time').attr('datetime'));
                        return bValue - aValue;
                    
                    case 'title':
                        aValue = $(a).find('.jgrants-title').text().toLowerCase();
                        bValue = $(b).find('.jgrants-title').text().toLowerCase();
                        return aValue.localeCompare(bValue);
                    
                    case 'amount':
                        aValue = parseInt($(a).find('.jgrants-amount').text().replace(/[^\d]/g, '')) || 0;
                        bValue = parseInt($(b).find('.jgrants-amount').text().replace(/[^\d]/g, '')) || 0;
                        return bValue - aValue;
                    
                    default:
                        return 0;
                }
            });
            
            $container.empty().append(sortedItems);
            animateItemsOnScroll($container.parent());
        });
    }
    
    // 公開関数
    window.JGrantsFrontend = {
        filterByCategory: filterGrantsByCategory,
        filterByPrefecture: filterGrantsByPrefecture,
        filterByStatus: filterGrantsByStatus,
        search: searchGrants,
        resetFilters: resetAllFilters
    };
    
})(jQuery);

