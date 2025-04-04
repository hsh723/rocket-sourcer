<?php // 공개 영역 표시 템플릿

/**
 * 공개 영역 표시 템플릿
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 뷰 타입 확인 (grid 또는 list)
$view_type = isset($atts['view']) ? $atts['view'] : 'grid';
?>

<div class="rocket-sourcer-container">
    <div class="rocket-sourcer-header">
        <h2>쿠팡 로켓그로스 추천 제품</h2>
        
        <div class="rocket-sourcer-search-form">
            <form id="rocket-sourcer-search-form">
                <input type="text" id="rocket-sourcer-search-term" placeholder="검색어를 입력하세요...">
                
                <div class="rocket-sourcer-filters">
                    <select id="rocket-sourcer-category">
                        <option value="">모든 카테고리</option>
                        <option value="fashion">패션의류/잡화</option>
                        <option value="beauty">뷰티</option>
                        <option value="baby">출산/유아동</option>
                        <option value="food">식품</option>
                        <option value="kitchen">주방용품</option>
                        <option value="home">생활용품</option>
                        <option value="interior">홈인테리어</option>
                        <option value="appliances">가전디지털</option>
                        <option value="sports">스포츠/레저</option>
                        <option value="car">자동차용품</option>
                        <option value="hobby">취미/문구/오피스</option>
                        <option value="book">도서/음반/DVD</option>
                        <option value="pet">반려동물용품</option>
                    </select>
                    
                    <select id="rocket-sourcer-source">
                        <option value="">모든 소스</option>
                        <option value="coupang">쿠팡</option>
                        <option value="amazon">아마존</option>
                        <option value="aliexpress">알리익스프레스</option>
                    </select>
                    
                    <div class="rocket-sourcer-price-filter">
                        <input type="number" id="rocket-sourcer-min-price" placeholder="최소 가격">
                        <span>~</span>
                        <input type="number" id="rocket-sourcer-max-price" placeholder="최대 가격">
                    </div>
                    
                    <div class="rocket-sourcer-roi-filter">
                        <label for="rocket-sourcer-min-roi">최소 ROI (%)</label>
                        <input type="number" id="rocket-sourcer-min-roi" min="0" max="1000" value="<?php echo esc_attr($atts['min_roi']); ?>">
                    </div>
                </div>
                
                <button type="submit" class="rocket-sourcer-search-button">검색</button>
            </form>
            
            <div class="rocket-sourcer-view-toggle">
                <button class="rocket-sourcer-view-grid <?php echo $view_type === 'grid' ? 'active' : ''; ?>" data-view="grid">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="rocket-sourcer-view-list <?php echo $view_type === 'list' ? 'active' : ''; ?>" data-view="list">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="rocket-sourcer-products <?php echo 'rocket-sourcer-view-' . esc_attr($view_type); ?>">
        <?php if (empty($products)) : ?>
            <div class="rocket-sourcer-no-products">
                <p>검색 조건에 맞는 제품이 없습니다.</p>
            </div>
        <?php else : ?>
            <?php foreach ($products as $product) : ?>
                <div class="rocket-sourcer-product" data-id="<?php echo esc_attr($product->id); ?>">
                    <div class="rocket-sourcer-product-image">
                        <a href="<?php echo esc_url($product->product_url); ?>" target="_blank">
                            <img src="<?php echo esc_url($product->product_image); ?>" alt="<?php echo esc_attr($product->product_name); ?>">
                        </a>
                    </div>
                    
                    <div class="rocket-sourcer-product-info">
                        <h3 class="rocket-sourcer-product-title">
                            <a href="<?php echo esc_url($product->product_url); ?>" target="_blank">
                                <?php echo esc_html($product->product_name); ?>
                            </a>
                        </h3>
                        
                        <div class="rocket-sourcer-product-meta">
                            <div class="rocket-sourcer-product-price">
                                <?php echo esc_html(number_format($product->product_price)); ?>원
                            </div>
                            
                            <div class="rocket-sourcer-product-category">
                                <?php echo esc_html($product->product_category); ?>
                            </div>
                            
                            <div class="rocket-sourcer-product-source">
                                <span class="rocket-sourcer-source-icon rocket-sourcer-source-<?php echo esc_attr($product->product_source); ?>"></span>
                                <?php echo esc_html($product->product_source); ?>
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-product-stats">
                            <div class="rocket-sourcer-product-rating">
                                <span class="rocket-sourcer-rating-stars" data-rating="<?php echo esc_attr($product->product_rating); ?>"></span>
                                <span class="rocket-sourcer-rating-value"><?php echo esc_html($product->product_rating); ?></span>
                                <span class="rocket-sourcer-reviews-count">(<?php echo esc_html($product->product_reviews); ?>)</span>
                            </div>
                            
                            <div class="rocket-sourcer-product-profit">
                                <span class="rocket-sourcer-profit-label">예상 수익:</span>
                                <span class="rocket-sourcer-profit-value"><?php echo esc_html(number_format($product->product_profit)); ?>원</span>
                            </div>
                            
                            <div class="rocket-sourcer-product-roi">
                                <span class="rocket-sourcer-roi-label">ROI:</span>
                                <span class="rocket-sourcer-roi-value <?php echo $product->product_roi >= 50 ? 'high' : ($product->product_roi >= 30 ? 'medium' : 'low'); ?>">
                                    <?php echo esc_html($product->product_roi); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rocket-sourcer-product-actions">
                        <?php if (is_user_logged_in()) : ?>
                            <?php
                            // 즐겨찾기 상태 확인
                            $is_favorite = false;
                            if (isset($product->is_favorite)) {
                                $is_favorite = $product->is_favorite;
                            } else {
                                $db = new Rocket_Sourcer_DB();
                                $is_favorite = $db->is_product_in_favorites(get_current_user_id(), $product->id);
                            }
                            ?>
                            <button class="rocket-sourcer-favorite-button <?php echo $is_favorite ? 'active' : ''; ?>" 
                                    data-product-id="<?php echo esc_attr($product->id); ?>"
                                    data-action="<?php echo $is_favorite ? 'remove' : 'add'; ?>">
                                <span class="dashicons <?php echo $is_favorite ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></span>
                                <span class="rocket-sourcer-favorite-text">
                                    <?php echo $is_favorite ? '즐겨찾기 해제' : '즐겨찾기 추가'; ?>
                                </span>
                            </button>
                        <?php else : ?>
                            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="rocket-sourcer-login-to-favorite">
                                <span class="dashicons dashicons-star-empty"></span>
                                <span class="rocket-sourcer-favorite-text">로그인하여 즐겨찾기 추가</span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($product->product_url); ?>" target="_blank" class="rocket-sourcer-view-button">
                            <span class="dashicons dashicons-visibility"></span>
                            <span class="rocket-sourcer-view-text">제품 보기</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="rocket-sourcer-pagination">
        <button id="rocket-sourcer-load-more" class="rocket-sourcer-load-more-button" style="display: none;">
            더 보기
        </button>
    </div>
</div>

<script>
    (function($) {
        'use strict';
        
        $(document).ready(function() {
            // 별점 표시
            $('.rocket-sourcer-rating-stars').each(function() {
                var rating = parseFloat($(this).data('rating'));
                var starsHtml = '';
                
                for (var i = 1; i <= 5; i++) {
                    if (i <= rating) {
                        starsHtml += '<span class="dashicons dashicons-star-filled"></span>';
                    } else if (i - 0.5 <= rating) {
                        starsHtml += '<span class="dashicons dashicons-star-half"></span>';
                    } else {
                        starsHtml += '<span class="dashicons dashicons-star-empty"></span>';
                    }
                }
                
                $(this).html(starsHtml);
            });
            
            // 뷰 토글
            $('.rocket-sourcer-view-toggle button').on('click', function() {
                var view = $(this).data('view');
                $('.rocket-sourcer-view-toggle button').removeClass('active');
                $(this).addClass('active');
                $('.rocket-sourcer-products').removeClass('rocket-sourcer-view-grid rocket-sourcer-view-list').addClass('rocket-sourcer-view-' + view);
            });
            
            // 즐겨찾기 버튼 클릭
            $('.rocket-sourcer-favorite-button').on('click', function() {
                var button = $(this);
                var productId = button.data('product-id');
                var action = button.data('action');
                
                $.ajax({
                    url: rocket_sourcer_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rocket_sourcer_' + action + '_favorite',
                        nonce: rocket_sourcer_public.nonce,
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            if (action === 'add') {
                                button.data('action', 'remove');
                                button.addClass('active');
                                button.find('.dashicons').removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                                button.find('.rocket-sourcer-favorite-text').text('즐겨찾기 해제');
                            } else {
                                button.data('action', 'add');
                                button.removeClass('active');
                                button.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                                button.find('.rocket-sourcer-favorite-text').text('즐겨찾기 추가');
                            }
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('요청 처리 중 오류가 발생했습니다.');
                    }
                });
            });
            
            // 검색 폼 제출
            $('#rocket-sourcer-search-form').on('submit', function(e) {
                e.preventDefault();
                
                var searchTerm = $('#rocket-sourcer-search-term').val();
                var category = $('#rocket-sourcer-category').val();
                var source = $('#rocket-sourcer-source').val();
                var minPrice = $('#rocket-sourcer-min-price').val();
                var maxPrice = $('#rocket-sourcer-max-price').val();
                var minRoi = $('#rocket-sourcer-min-roi').val();
                
                // AJAX 검색 요청
                $.ajax({
                    url: rocket_sourcer_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rocket_sourcer_search_products',
                        nonce: rocket_sourcer_public.nonce,
                        search_term: searchTerm,
                        category: category,
                        source: source,
                        min_price: minPrice,
                        max_price: maxPrice,
                        min_roi: minRoi,
                        limit: 20,
                        offset: 0
                    },
                    beforeSend: function() {
                        $('.rocket-sourcer-products').html('<div class="rocket-sourcer-loading"><span class="dashicons dashicons-update-alt"></span> 검색 중...</div>');
                    },
                    success: function(response) {
                        if (response.success) {
                            // 결과 표시 로직 구현
                            displaySearchResults(response.data);
                        } else {
                            $('.rocket-sourcer-products').html('<div class="rocket-sourcer-no-products"><p>검색 중 오류가 발생했습니다.</p></div>');
                        }
                    },
                    error: function() {
                        $('.rocket-sourcer-products').html('<div class="rocket-sourcer-no-products"><p>요청 처리 중 오류가 발생했습니다.</p></div>');
                    }
                });
            });
            
            // 검색 결과 표시 함수
            function displaySearchResults(data) {
                var products = data.products;
                var html = '';
                
                if (products.length === 0) {
                    html = '<div class="rocket-sourcer-no-products"><p>검색 조건에 맞는 제품이 없습니다.</p></div>';
                } else {
                    for (var i = 0; i < products.length; i++) {
                        var product = products[i];
                        var isFavorite = product.is_favorite || false;
                        
                        html += '<div class="rocket-sourcer-product" data-id="' + product.id + '">';
                        html += '<div class="rocket-sourcer-product-image">';
                        html += '<a href="' + product.product_url + '" target="_blank">';
                        html += '<img src="' + product.product_image + '" alt="' + product.product_name + '">';
                        html += '</a>';
                        html += '</div>';
                        
                        html += '<div class="rocket-sourcer-product-info">';
                        html += '<h3 class="rocket-sourcer-product-title">';
                        html += '<a href="' + product.product_url + '" target="_blank">' + product.product_name + '</a>';
                        html += '</h3>';
                        
                        html += '<div class="rocket-sourcer-product-meta">';
                        html += '<div class="rocket-sourcer-product-price">' + numberWithCommas(product.product_price) + '원</div>';
                        html += '<div class="rocket-sourcer-product-category">' + product.product_category + '</div>';
                        html += '<div class="rocket-sourcer-product-source">';
                        html += '<span class="rocket-sourcer-source-icon rocket-sourcer-source-' + product.product_source + '"></span>';
                        html += product.product_source;
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="rocket-sourcer-product-stats">';
                        html += '<div class="rocket-sourcer-product-rating">';
                        html += '<span class="rocket-sourcer-rating-stars" data-rating="' + product.product_rating + '"></span>';
                        html += '<span class="rocket-sourcer-rating-value">' + product.product_rating + '</span>';
                        html += '<span class="rocket-sourcer-reviews-count">(' + product.product_reviews + ')</span>';
                        html += '</div>';
                        
                        html += '<div class="rocket-sourcer-product-profit">';
                        html += '<span class="rocket-sourcer-profit-label">예상 수익:</span>';
                        html += '<span class="rocket-sourcer-profit-value">' + numberWithCommas(product.product_profit) + '원</span>';
                        html += '</div>';
                        
                        html += '<div class="rocket-sourcer-product-roi">';
                        html += '<span class="rocket-sourcer-roi-label">ROI:</span>';
                        var roiClass = product.product_roi >= 50 ? 'high' : (product.product_roi >= 30 ? 'medium' : 'low');
                        html += '<span class="rocket-sourcer-roi-value ' + roiClass + '">' + product.product_roi + '%</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="rocket-sourcer-product-actions">';
                        
                        if (isFavorite) {
                            html += '<button class="rocket-sourcer-favorite-button active" data-product-id="' + product.id + '" data-action="remove">';
                            html += '<span class="dashicons dashicons-star-filled"></span>';
                            html += '<span class="rocket-sourcer-favorite-text">즐겨찾기 해제</span>';
                            html += '</button>';
                        } else {
                            html += '<button class="rocket-sourcer-favorite-button" data-product-id="' + product.id + '" data-action="add">';
                            html += '<span class="dashicons dashicons-star-empty"></span>';
                            html += '<span class="rocket-sourcer-favorite-text">즐겨찾기 추가</span>';
                            html += '</button>';
                        }
                        
                        html += '<a href="' + product.product_url + '" target="_blank" class="rocket-sourcer-view-button">';
                        html += '<span class="dashicons dashicons-visibility"></span>';
                        html += '<span class="rocket-sourcer-view-text">제품 보기</span>';
                        html += '</a>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    // 더 보기 버튼 표시 여부
                    if (data.has_more) {
                        $('#rocket-sourcer-load-more').show().data('offset', data.offset + data.limit);
                    } else {
                        $('#rocket-sourcer-load-more').hide();
                    }
                }
                
                $('.rocket-sourcer-products').html(html);
                
                // 별점 표시 업데이트
                $('.rocket-sourcer-rating-stars').each(function() {
                    var rating = parseFloat($(this).data('rating'));
                    var starsHtml = '';
                    
                    for (var i = 1; i <= 5; i++) {
                        if (i <= rating) {
                            starsHtml += '<span class="dashicons dashicons-star-filled"></span>';
                        } else if (i - 0.5 <= rating) {
                            starsHtml += '<span class="dashicons dashicons-star-half"></span>';
                        } else {
                            starsHtml += '<span class="dashicons dashicons-star-empty"></span>';
                        }
                    }
                    
                    $(this).html(starsHtml);
                });
                
                // 즐겨찾기 버튼 이벤트 다시 바인딩
                $('.rocket-sourcer-favorite-button').on('click', function() {
                    var button = $(this);
                    var productId = button.data('product-id');
                    var action = button.data('action');
                    
                    $.ajax({
                        url: rocket_sourcer_public.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'rocket_sourcer_' + action + '_favorite',
                            nonce: rocket_sourcer_public.nonce,
                            product_id: productId
                        },
                        success: function(response) {
                            if (response.success) {
                                if (action === 'add') {
                                    button.data('action', 'remove');
                                    button.addClass('active');
                                    button.find('.dashicons').removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                                    button.find('.rocket-sourcer-favorite-text').text('즐겨찾기 해제');
                                } else {
                                    button.data('action', 'add');
                                    button.removeClass('active');
                                    button.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                                    button.find('.rocket-sourcer-favorite-text').text('즐겨찾기 추가');
                                }
                            } else {
                                alert(response.data.message);
                            }
                        },
                        error: function() {
                            alert('요청 처리 중 오류가 발생했습니다.');
                        }
                    });
                });
            }
            
            // 숫자 포맷팅 함수
            function numberWithCommas(x) {
                return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
        });
    })(jQuery);
</script>
