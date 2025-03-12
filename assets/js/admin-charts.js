/**
 * Rocket Sourcer 관리자 차트
 */

(function($) {
    'use strict';

    const RocketSourcerCharts = {
        /**
         * 차트 기본 옵션
         */
        defaultOptions: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        },

        /**
         * 키워드 트렌드 차트 초기화
         * 
         * @param {string} canvasId 캔버스 요소 ID
         * @param {Object} data 차트 데이터
         */
        initKeywordTrendChart: function(canvasId, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: '검색량',
                        data: data.volumes,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    ...this.defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '검색량'
                            }
                        }
                    }
                }
            });
        },

        /**
         * 가격 변동 차트 초기화
         * 
         * @param {string} canvasId 캔버스 요소 ID
         * @param {Object} data 차트 데이터
         */
        initPriceHistoryChart: function(canvasId, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: '판매가',
                        data: data.prices,
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    ...this.defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '가격 (원)'
                            }
                        }
                    }
                }
            });
        },

        /**
         * 리뷰 분석 차트 초기화
         * 
         * @param {string} canvasId 캔버스 요소 ID
         * @param {Object} data 차트 데이터
         */
        initReviewAnalysisChart: function(canvasId, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['긍정적', '중립적', '부정적'],
                    datasets: [{
                        data: [
                            data.positive,
                            data.neutral,
                            data.negative
                        ],
                        backgroundColor: [
                            '#4CAF50',
                            '#FFC107',
                            '#F44336'
                        ]
                    }]
                },
                options: {
                    ...this.defaultOptions,
                    cutout: '70%'
                }
            });
        },

        /**
         * 마진 분석 차트 초기화
         * 
         * @param {string} canvasId 캔버스 요소 ID
         * @param {Object} data 차트 데이터
         */
        initMarginAnalysisChart: function(canvasId, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['판매가', '원가', '수수료', '배송비', '기타비용', '순이익'],
                    datasets: [{
                        label: '금액',
                        data: [
                            data.selling_price,
                            data.cost_price,
                            data.commission,
                            data.shipping_cost,
                            data.additional_costs,
                            data.net_profit
                        ],
                        backgroundColor: [
                            '#2196F3',
                            '#F44336',
                            '#FFC107',
                            '#9C27B0',
                            '#FF5722',
                            '#4CAF50'
                        ]
                    }]
                },
                options: {
                    ...this.defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '금액 (원)'
                            }
                        }
                    }
                }
            });
        },

        /**
         * 손익분기점 차트 초기화
         * 
         * @param {string} canvasId 캔버스 요소 ID
         * @param {Object} data 차트 데이터
         */
        initBreakEvenChart: function(canvasId, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.quantities,
                    datasets: [
                        {
                            label: '총수입',
                            data: data.total_revenue,
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0
                        },
                        {
                            label: '총비용',
                            data: data.total_costs,
                            borderColor: '#F44336',
                            backgroundColor: 'rgba(244, 67, 54, 0.1)',
                            tension: 0
                        }
                    ]
                },
                options: {
                    ...this.defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '금액 (원)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '판매 수량'
                            }
                        }
                    },
                    plugins: {
                        ...this.defaultOptions.plugins,
                        annotation: {
                            annotations: {
                                breakEvenPoint: {
                                    type: 'point',
                                    xValue: data.break_even_point.quantity,
                                    yValue: data.break_even_point.amount,
                                    backgroundColor: '#9C27B0',
                                    radius: 5,
                                    label: {
                                        content: '손익분기점',
                                        enabled: true,
                                        position: 'top'
                                    }
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * 차트 데이터 처리
         * 
         * @param {Object} rawData 원본 데이터
         * @returns {Object} 처리된 차트 데이터
         */
        processChartData: function(rawData) {
            return {
                // 키워드 트렌드 데이터
                keywordTrend: {
                    labels: rawData.monthly_volumes.map(item => item.month),
                    volumes: rawData.monthly_volumes.map(item => item.volume)
                },

                // 가격 변동 데이터
                priceHistory: {
                    labels: rawData.price_history.map(item => item.date),
                    prices: rawData.price_history.map(item => item.price)
                },

                // 리뷰 분석 데이터
                reviewAnalysis: {
                    positive: rawData.review_analysis.positive,
                    neutral: rawData.review_analysis.neutral,
                    negative: rawData.review_analysis.negative
                },

                // 마진 분석 데이터
                marginAnalysis: {
                    selling_price: rawData.selling_price,
                    cost_price: rawData.cost_price,
                    commission: rawData.commission,
                    shipping_cost: rawData.shipping_cost,
                    additional_costs: rawData.additional_costs,
                    net_profit: rawData.net_profit
                },

                // 손익분기점 데이터
                breakEven: {
                    quantities: this.generateQuantities(rawData.break_even_point.quantity),
                    total_revenue: this.calculateRevenue(rawData),
                    total_costs: this.calculateCosts(rawData),
                    break_even_point: rawData.break_even_point
                }
            };
        },

        /**
         * 수량 배열 생성
         * 
         * @param {number} breakEvenQuantity 손익분기 수량
         * @returns {Array} 수량 배열
         */
        generateQuantities: function(breakEvenQuantity) {
            const quantities = [];
            const maxQuantity = Math.ceil(breakEvenQuantity * 2);
            const step = Math.ceil(maxQuantity / 10);

            for (let i = 0; i <= maxQuantity; i += step) {
                quantities.push(i);
            }

            return quantities;
        },

        /**
         * 수익 계산
         * 
         * @param {Object} data 원본 데이터
         * @returns {Array} 수익 배열
         */
        calculateRevenue: function(data) {
            return this.generateQuantities(data.break_even_point.quantity)
                .map(quantity => quantity * data.selling_price);
        },

        /**
         * 비용 계산
         * 
         * @param {Object} data 원본 데이터
         * @returns {Array} 비용 배열
         */
        calculateCosts: function(data) {
            const fixedCosts = data.fixed_costs;
            const variableCosts = data.variable_costs;

            return this.generateQuantities(data.break_even_point.quantity)
                .map(quantity => fixedCosts + (quantity * variableCosts));
        }
    };

    // 전역 객체에 추가
    window.RocketSourcerCharts = RocketSourcerCharts;

})(jQuery); 