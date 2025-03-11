/**
 * 로켓 소서 관리자 페이지 JavaScript
 *
 * @package RocketSourcer
 */

(function($) {
    'use strict';

    // 관리자 페이지 초기화
    $(document).ready(function() {
        initializeAdminPage();
    });

    /**
     * 관리자 페이지 초기화 함수
     */
    function initializeAdminPage() {
        initializeDataTables();
        initializeCharts();
        initializeEventHandlers();
    }

    /**
     * DataTables 초기화
     */
    function initializeDataTables() {
        if ($.fn.DataTable) {
            $('.wp-list-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Korean.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[4, 'desc']], // 날짜 기준 내림차순 정렬
                columnDefs: [
                    {
                        targets: -1, // 마지막 열 (작업)
                        orderable: false
                    }
                ]
            });
        }
    }

    /**
     * 차트 초기화
     */
    function initializeCharts() {
        if (typeof Chart !== 'undefined') {
            // 대시보드 통계 차트
            const statsCtx = document.getElementById('statsChart');
            if (statsCtx) {
                new Chart(statsCtx, {
                    type: 'line',
                    data: {
                        labels: [], // AJAX로 데이터 로드
                        datasets: [{
                            label: '분석된 키워드',
                            data: [],
                            borderColor: '#007cba',
                            fill: false
                        }, {
                            label: '분석된 제품',
                            data: [],
                            borderColor: '#46b450',
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }
    }

    /**
     * 이벤트 핸들러 초기화
     */
    function initializeEventHandlers() {
        // 키워드 분석 폼 제출
        $('#rocket-sourcer-keyword-form').on('submit', function(e) {
            e.preventDefault();
            analyzeKeywords($(this));
        });

        // 제품 분석 폼 제출
        $('#rocket-sourcer-product-form').on('submit', function(e) {
            e.preventDefault();
            analyzeProducts($(this));
        });

        // 필터 적용
        $('#rocket-sourcer-filter-apply').on('click', function() {
            applyFilters();
        });

        // 제품 상세 정보 표시
        $('.rocket-sourcer-view-product').on('click', function(e) {
            e.preventDefault();
            showProductDetails($(this).data('product-id'));
        });

        // 설정 저장
        $('#rocket-sourcer-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings($(this));
        });
    }

    /**
     * 키워드 분석 실행
     * @param {jQuery} form 폼 엘리먼트
     */
    function analyzeKeywords(form) {
        const formData = form.serialize();
        const submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true).text('분석 중...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_analyze_keywords',
                nonce: rocketSourcerAdmin.nonce,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    updateKeywordResults(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('키워드 분석 중 오류가 발생했습니다.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('키워드 분석하기');
            }
        });
    }

    /**
     * 제품 분석 실행
     * @param {jQuery} form 폼 엘리먼트
     */
    function analyzeProducts(form) {
        const formData = form.serialize();
        const submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true).text('분석 중...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_analyze_products',
                nonce: rocketSourcerAdmin.nonce,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    updateProductResults(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('제품 분석 중 오류가 발생했습니다.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text('제품 분석하기');
            }
        });
    }

    /**
     * 필터 적용
     */
    function applyFilters() {
        const category = $('#rocket-sourcer-filter-category').val();
        const sort = $('#rocket-sourcer-filter-sort').val();
        const search = $('#rocket-sourcer-search').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_filter_results',
                nonce: rocketSourcerAdmin.nonce,
                category: category,
                sort: sort,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    updateResults(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('필터 적용 중 오류가 발생했습니다.');
            }
        });
    }

    /**
     * 제품 상세 정보 표시
     * @param {number} productId 제품 ID
     */
    function showProductDetails(productId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_get_product_details',
                nonce: rocketSourcerAdmin.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    updateProductDetails(response.data);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('제품 정보를 불러오는 중 오류가 발생했습니다.');
            }
        });
    }

    /**
     * 설정 저장
     * @param {jQuery} form 폼 엘리먼트
     */
    function saveSettings(form) {
        const formData = form.serialize();
        const submitButton = form.find('button[type="submit"]');

        submitButton.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_save_settings',
                nonce: rocketSourcerAdmin.nonce,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('설정이 저장되었습니다.');
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError('설정 저장 중 오류가 발생했습니다.');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    }

    /**
     * 키워드 분석 결과 업데이트
     * @param {Object} data 분석 결과 데이터
     */
    function updateKeywordResults(data) {
        const resultsContainer = $('.rocket-sourcer-keyword-results');
        const table = resultsContainer.find('table tbody');
        
        table.empty();

        if (data.keywords && data.keywords.length > 0) {
            data.keywords.forEach(function(keyword) {
                table.append(`
                    <tr>
                        <td>${keyword.keyword}</td>
                        <td>${keyword.search_volume}</td>
                        <td>${keyword.competition}</td>
                        <td>${keyword.category}</td>
                        <td>${keyword.date}</td>
                        <td>
                            <button class="button rocket-sourcer-analyze-keyword" 
                                    data-keyword="${keyword.keyword}">
                                제품 찾기
                            </button>
                        </td>
                    </tr>
                `);
            });
        } else {
            table.append('<tr><td colspan="6">분석된 키워드가 없습니다.</td></tr>');
        }

        resultsContainer.show();
    }

    /**
     * 제품 분석 결과 업데이트
     * @param {Object} data 분석 결과 데이터
     */
    function updateProductResults(data) {
        const resultsContainer = $('.rocket-sourcer-product-results');
        const table = resultsContainer.find('table tbody');
        
        table.empty();

        if (data.products && data.products.length > 0) {
            data.products.forEach(function(product) {
                table.append(`
                    <tr>
                        <td>${product.title}</td>
                        <td>${product.price}</td>
                        <td>${product.rating}</td>
                        <td>${product.reviews}</td>
                        <td>${product.seller}</td>
                        <td>${product.category}</td>
                        <td>
                            <button class="button rocket-sourcer-view-product" 
                                    data-product-id="${product.id}">
                                상세보기
                            </button>
                        </td>
                    </tr>
                `);
            });
        } else {
            table.append('<tr><td colspan="7">분석된 제품이 없습니다.</td></tr>');
        }

        resultsContainer.show();
    }

    /**
     * 제품 상세 정보 업데이트
     * @param {Object} data 제품 상세 정보
     */
    function updateProductDetails(data) {
        const detailContainer = $('.rocket-sourcer-product-detail');
        
        $('#rocket-sourcer-product-title').text(data.title);
        $('#rocket-sourcer-product-price').text(data.price);
        $('#rocket-sourcer-product-rating').text(`평점: ${data.rating}`);
        $('#rocket-sourcer-product-reviews').text(`리뷰: ${data.reviews}`);
        $('#rocket-sourcer-product-seller').text(`판매자: ${data.seller}`);
        $('#rocket-sourcer-product-category').text(`카테고리: ${data.category}`);
        $('#rocket-sourcer-product-date').text(`분석일: ${data.date}`);
        
        detailContainer.show();
    }

    /**
     * 성공 메시지 표시
     * @param {string} message 메시지
     */
    function showSuccess(message) {
        const notice = $('<div class="notice notice-success"><p></p></div>');
        notice.find('p').text(message);
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * 에러 메시지 표시
     * @param {string} message 메시지
     */
    function showError(message) {
        const notice = $('<div class="notice notice-error"><p></p></div>');
        notice.find('p').text(message);
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);