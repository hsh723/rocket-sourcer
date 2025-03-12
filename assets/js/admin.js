/**
 * Rocket Sourcer 관리자 스크립트
 */

jQuery(document).ready(function($) {
    'use strict';

    // 차트 설정
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };

    // 마진 차트 초기화
    function initMarginChart(data) {
        const ctx = document.getElementById('margin-chart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['매출원가', '판매수수료', '배송비', '기타비용', '순이익'],
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#60a5fa',
                        '#34d399',
                        '#fbbf24',
                        '#f87171',
                        '#a78bfa'
                    ]
                }]
            },
            options: chartOptions
        });
    }

    // 가격 비교 차트 초기화
    function initPriceComparisonChart(data) {
        const ctx = document.getElementById('price-comparison-chart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['최저가', '평균가', '최고가', '분석가'],
                datasets: [{
                    label: '가격 비교',
                    data: data,
                    backgroundColor: '#60a5fa'
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 판매 예측 차트 초기화
    function initSalesForecastChart(data) {
        const ctx = document.getElementById('sales-forecast-chart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: '예상 판매량',
                    data: data.values,
                    borderColor: '#60a5fa',
                    tension: 0.1
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 경쟁 분석 차트 초기화
    function initCompetitionChart(data) {
        const ctx = document.getElementById('competition-chart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['가격 경쟁력', '리뷰 점수', '판매자 신뢰도', '상품 완성도', '시장 점유율'],
                datasets: [{
                    label: '분석 제품',
                    data: data.product,
                    borderColor: '#60a5fa',
                    backgroundColor: 'rgba(96, 165, 250, 0.2)'
                }, {
                    label: '경쟁사 평균',
                    data: data.average,
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52, 211, 153, 0.2)'
                }]
            },
            options: chartOptions
        });
    }

    // 제품 분석 폼 제출
    $('#product-analysis-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $loading = $('#analysis-loading');
        const $results = $('#analysis-results');
        const $error = $('#error-message');

        // 로딩 표시
        $loading.show();
        $results.hide();
        $error.hide();

        // 진행률 업데이트
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += 10;
                $('.progress').css('width', progress + '%');
                updateProgressStatus(progress);
            }
        }, 1000);

        // API 요청
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_analyze_product',
                nonce: $form.find('#rocket_sourcer_nonce').val(),
                url: $form.find('#product-url').val(),
                options: {
                    competitors: $form.find('#include_competitors').is(':checked'),
                    forecast: $form.find('#include_forecast').is(':checked'),
                    overseas: $form.find('#include_overseas').is(':checked')
                }
            },
            success: function(response) {
                clearInterval(progressInterval);
                $('.progress').css('width', '100%');
                
                if (response.success) {
                    updateAnalysisResults(response.data);
                    $results.show();
                } else {
                    $error.find('p').text(response.data.message);
                    $error.show();
                }
            },
            error: function() {
                clearInterval(progressInterval);
                $error.show();
            },
            complete: function() {
                $loading.hide();
            }
        });
    });

    // URL 붙여넣기 버튼
    $('#paste-url').on('click', function() {
        navigator.clipboard.readText().then(text => {
            $('#product-url').val(text);
        });
    });

    // 분석 결과 업데이트
    function updateAnalysisResults(data) {
        // 기본 정보 업데이트
        $('#product-image').attr('src', data.image);
        $('#product-name').text(data.name);
        $('#product-price').text(formatPrice(data.price));
        $('#product-category').text(data.category);

        // 최적화 점수 업데이트
        $('#title-score').css('width', data.scores.title + '%');
        $('#image-score').css('width', data.scores.image + '%');
        $('#description-score').css('width', data.scores.description + '%');

        // 차트 초기화
        initMarginChart(data.margin);
        initPriceComparisonChart(data.prices);
        initSalesForecastChart(data.forecast);
        initCompetitionChart(data.competition);

        // 요약 정보 업데이트
        $('#expected-margin').text(data.margin.total + '%');
        $('#break-even').text(formatPrice(data.margin.breakeven));
        $('#market-average').text(formatPrice(data.prices.average));
        $('#price-competitiveness').text(data.prices.competitiveness);
        $('#monthly-sales').text(formatNumber(data.forecast.monthly));
        $('#market-growth').text(data.forecast.growth + '%');
        $('#competition-level').text(data.competition.level);
        $('#market-share').text(data.competition.share + '%');

        // 해외 소싱 결과 업데이트
        updateSourcingResults(data.sourcing);
    }

    // 해외 소싱 결과 업데이트
    function updateSourcingResults(data) {
        const $container = $('#sourcing-results');
        const template = document.getElementById('sourcing-item-template').innerHTML;

        $container.empty();

        data.forEach(item => {
            const html = template
                .replace(/{image}/g, item.image)
                .replace(/{title}/g, item.title)
                .replace(/{price}/g, formatPrice(item.price))
                .replace(/{orders}/g, formatNumber(item.orders))
                .replace(/{rating}/g, item.rating)
                .replace(/{supplier_logo}/g, item.supplier.logo)
                .replace(/{supplier_name}/g, item.supplier.name)
                .replace(/{url}/g, item.url);

            $container.append(html);
        });
    }

    // 진행 상태 업데이트
    function updateProgressStatus(progress) {
        let status = '기본 정보 수집 중...';
        if (progress > 20) status = '가격 정보 분석 중...';
        if (progress > 40) status = '경쟁사 정보 수집 중...';
        if (progress > 60) status = '시장 데이터 분석 중...';
        if (progress > 80) status = '해외 소싱 검색 중...';
        $('.progress-status').text(status);
    }

    // 마진 계산기 모달
    $('#open-margin-calculator').on('click', function() {
        $('#margin-calculator-modal').show();
        loadMarginCalculator();
    });

    // 모달 닫기
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').hide();
    });

    // 마진 계산기 로드
    function loadMarginCalculator() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_load_calculator'
            },
            success: function(response) {
                if (response.success) {
                    $('#margin-calculator-modal .modal-body').html(response.data);
                }
            }
        });
    }

    // 비디오 튜토리얼
    $('.help-card a[id^="tutorial-"]').on('click', function(e) {
        e.preventDefault();
        const type = this.id.replace('tutorial-', '');
        const titles = {
            keywords: '키워드 분석 방법',
            products: '제품 분석 방법',
            margin: '마진 계산 방법'
        };
        
        $('#tutorial-title').text(titles[type]);
        $('#tutorial-modal').show();
        loadTutorialVideo(type);
    });

    // 튜토리얼 비디오 로드
    function loadTutorialVideo(type) {
        const videoIds = {
            keywords: 'xxxxxx',
            products: 'yyyyyy',
            margin: 'zzzzzz'
        };

        const iframe = `<iframe width="100%" 
                               height="450" 
                               src="https://www.youtube.com/embed/${videoIds[type]}" 
                               frameborder="0" 
                               allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                               allowfullscreen></iframe>`;

        $('#tutorial-video').html(iframe);
    }

    // 데이터 내보내기
    $('#export-data').on('click', function() {
        window.location.href = ajaxurl + '?action=rocket_sourcer_export_data';
    });

    // 데이터베이스 정리
    $('#clear-old-data').on('click', function() {
        if (confirm('만료된 분석 결과를 삭제하시겠습니까?')) {
            clearData('old');
        }
    });

    $('#clear-all-data').on('click', function() {
        if (confirm('모든 분석 결과를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
            clearData('all');
        }
    });

    function clearData(type) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_clear_data',
                type: type
            },
            success: function(response) {
                if (response.success) {
                    alert('데이터가 성공적으로 삭제되었습니다.');
                } else {
                    alert('데이터 삭제 중 오류가 발생했습니다.');
                }
            }
        });
    }

    // API 상태 확인
    function checkApiStatus() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rocket_sourcer_check_api'
            },
            success: function(response) {
                if (response.success) {
                    updateApiStatus(response.data);
                }
            }
        });
    }

    function updateApiStatus(data) {
        $('#api-connection-status').html(`
            <div class="status-indicator ${data.status}">
                ${data.message}
            </div>
        `);

        $('#api-usage-info').html(`
            <div>일일 사용량: ${data.usage.daily}/${data.usage.limit}</div>
            <div>월간 사용량: ${data.usage.monthly}/${data.usage.monthly_limit}</div>
        `);

        $('#subscription-info').html(`
            <div>플랜: ${data.subscription.plan}</div>
            <div>만료일: ${data.subscription.expires}</div>
        `);
    }

    // 초기 API 상태 확인
    if ($('.api-status').length) {
        checkApiStatus();
    }

    $('#check-api-status').on('click', checkApiStatus);

    // 유틸리티 함수
    function formatPrice(price) {
        return new Intl.NumberFormat('ko-KR', {
            style: 'currency',
            currency: 'KRW'
        }).format(price);
    }

    function formatNumber(number) {
        return new Intl.NumberFormat('ko-KR').format(number);
    }
}); 