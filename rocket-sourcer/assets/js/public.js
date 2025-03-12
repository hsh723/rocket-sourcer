jQuery(document).ready(function($) {
    'use strict';

    // 탭 전환 기능
    function initTabs() {
        $('.rocket-sourcer-tab').on('click', function() {
            const tabId = $(this).data('tab');
            
            $('.rocket-sourcer-tab').removeClass('active');
            $('.rocket-sourcer-tab-content').removeClass('active');
            
            $(this).addClass('active');
            $(`#${tabId}`).addClass('active');
        });
    }

    // 키워드 분석 기능
    function initKeywordAnalysis() {
        $('#keyword-analysis-form').on('submit', function(e) {
            e.preventDefault();
            
            const keyword = $('#keyword-input').val();
            const category = $('#category-select').val();
            
            if (!keyword) {
                showNotice('키워드를 입력해주세요.', 'error');
                return;
            }
            
            analyzeKeyword(keyword, category);
        });
    }

    // 키워드 분석 AJAX 요청
    function analyzeKeyword(keyword, category) {
        showLoading('#keyword-analysis-results');
        
        $.ajax({
            url: rocketSourcer.ajaxurl,
            type: 'POST',
            data: {
                action: 'analyze_keyword',
                keyword: keyword,
                category: category,
                nonce: rocketSourcer.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayKeywordResults(response.data);
                } else {
                    showNotice('키워드 분석 중 오류가 발생했습니다.', 'error');
                }
            },
            error: function() {
                showNotice('서버 연결 중 오류가 발생했습니다.', 'error');
            },
            complete: function() {
                hideLoading('#keyword-analysis-results');
            }
        });
    }

    // 키워드 분석 결과 표시
    function displayKeywordResults(data) {
        const resultsHtml = `
            <div class="rocket-sourcer-result-section">
                <h4>검색 결과 요약</h4>
                <div class="rocket-sourcer-result-item">
                    <span>검색량:</span>
                    <span>${data.search_volume}</span>
                </div>
                <div class="rocket-sourcer-result-item">
                    <span>경쟁 강도:</span>
                    <span>${data.competition}</span>
                </div>
                <div class="rocket-sourcer-result-item highlight">
                    <span>추천 점수:</span>
                    <span>${data.recommendation_score}</span>
                </div>
            </div>
            
            <div class="rocket-sourcer-chart-container">
                <h4>트렌드 분석</h4>
                <canvas id="trend-chart"></canvas>
            </div>
            
            <div class="rocket-sourcer-recommendations">
                <h4>추천 사항</h4>
                <ul>
                    ${data.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                </ul>
            </div>
        `;
        
        $('#keyword-analysis-results').html(resultsHtml);
        
        if (data.trend_data) {
            initTrendChart(data.trend_data);
        }
    }

    // 마진 계산 기능
    function initMarginCalculator() {
        $('#margin-calculator-form').on('submit', function(e) {
            e.preventDefault();
            
            const data = {
                sellingPrice: $('#selling-price').val(),
                productCost: $('#product-cost').val(),
                shippingCost: $('#shipping-cost').val(),
                coupangFee: $('#coupang-fee').val()
            };
            
            if (!validateMarginData(data)) {
                return;
            }
            
            const results = calculateMargin(data);
            displayMarginResults(results);
        });
    }

    // 마진 데이터 유효성 검사
    function validateMarginData(data) {
        if (!data.sellingPrice || !data.productCost) {
            showNotice('판매가와 제품 원가를 입력해주세요.', 'error');
            return false;
        }
        
        if (parseFloat(data.sellingPrice) < parseFloat(data.productCost)) {
            showNotice('판매가는 제품 원가보다 높아야 합니다.', 'warning');
            return false;
        }
        
        return true;
    }

    // 마진 계산
    function calculateMargin(data) {
        const sellingPrice = parseFloat(data.sellingPrice);
        const productCost = parseFloat(data.productCost);
        const shippingCost = parseFloat(data.shippingCost) || 0;
        const coupangFee = parseFloat(data.coupangFee) || 10;
        
        const revenue = sellingPrice;
        const fees = (revenue * coupangFee) / 100;
        const totalCost = productCost + shippingCost + fees;
        const netProfit = revenue - totalCost;
        const profitMargin = (netProfit / revenue) * 100;
        const breakEven = Math.ceil(totalCost / (revenue - totalCost));
        
        return {
            revenue: revenue.toLocaleString(),
            totalCost: totalCost.toLocaleString(),
            netProfit: netProfit.toLocaleString(),
            profitMargin: profitMargin.toFixed(2),
            breakEven: breakEven.toLocaleString()
        };
    }

    // 마진 계산 결과 표시
    function displayMarginResults(results) {
        const resultsHtml = `
            <div class="rocket-sourcer-result-section">
                <h4>수익 분석</h4>
                <div class="rocket-sourcer-result-item">
                    <span>판매 수익:</span>
                    <span>${results.revenue}원</span>
                </div>
                <div class="rocket-sourcer-result-item">
                    <span>총 비용:</span>
                    <span>${results.totalCost}원</span>
                </div>
                <div class="rocket-sourcer-result-item highlight">
                    <span>순 이익:</span>
                    <span>${results.netProfit}원</span>
                </div>
                <div class="rocket-sourcer-result-item highlight">
                    <span>이익률:</span>
                    <span>${results.profitMargin}%</span>
                </div>
                <div class="rocket-sourcer-result-item">
                    <span>손익분기점 판매량:</span>
                    <span>${results.breakEven}개</span>
                </div>
            </div>
            
            <div class="rocket-sourcer-recommendations">
                <h4>추천 사항</h4>
                <ul>
                    ${generateMarginRecommendations(results)}
                </ul>
            </div>
        `;
        
        $('#margin-calculator-results').html(resultsHtml).show();
    }

    // 마진 추천 사항 생성
    function generateMarginRecommendations(results) {
        const profitMargin = parseFloat(results.profitMargin);
        const recommendations = [];
        
        if (profitMargin < 10) {
            recommendations.push('이익률이 낮습니다. 판매가 인상을 고려해보세요.');
        } else if (profitMargin > 50) {
            recommendations.push('높은 이익률입니다. 경쟁사 가격을 확인해보세요.');
        }
        
        if (parseInt(results.breakEven) > 100) {
            recommendations.push('손익분기점 판매량이 높습니다. 비용 절감을 검토해보세요.');
        }
        
        return recommendations.map(rec => `<li>${rec}</li>`).join('');
    }

    // 알림 표시
    function showNotice(message, type) {
        const notice = $('<div>')
            .addClass(`rocket-sourcer-notice ${type}`)
            .text(message);
        
        $('.rocket-sourcer-notices').html(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // 로딩 표시
    function showLoading(target) {
        const loading = $('<div>')
            .addClass('rocket-sourcer-loading')
            .html('<div class="rocket-sourcer-spinner"></div>');
        
        $(target).html(loading);
    }

    // 로딩 숨기기
    function hideLoading(target) {
        $(target).find('.rocket-sourcer-loading').remove();
    }

    // 트렌드 차트 초기화
    function initTrendChart(data) {
        const ctx = document.getElementById('trend-chart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: '검색량 추이',
                    data: data.values,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 초기화
    function init() {
        initTabs();
        initKeywordAnalysis();
        initMarginCalculator();
    }

    // 페이지 로드 시 초기화
    init();
}); 