/**
 * Rocket Sourcer 관리자 JavaScript
 */

// 모듈 네임스페이스
const RocketSourcer = {
    // 초기화
    init() {
        this.initDashboard();
        this.initKeywordAnalysis();
        this.initProductAnalysis();
        this.initMarginCalculator();
        this.initEventListeners();
    },

    // 대시보드 초기화
    initDashboard() {
        this.loadDashboardStats();
        this.initDashboardCharts();
        
        // 5분마다 데이터 자동 갱신
        setInterval(() => this.loadDashboardStats(), 300000);
    },

    // 대시보드 통계 로드
    async loadDashboardStats() {
        try {
            const response = await this.makeRequest('get_dashboard_stats', {
                nonce: rocketSourcerAdmin.nonce
            });

            if (response.success) {
                this.updateDashboardStats(response.data);
                this.updateDashboardCharts(response.data);
            }
        } catch (error) {
            this.showNotification('통계 데이터 로드 실패: ' + error.message, 'error');
        }
    },

    // 대시보드 차트 초기화
    initDashboardCharts() {
        // 키워드 분석 추이 차트
        this.keywordTrendChart = new Chart(
            document.getElementById('keywordTrendChart'),
            {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: '분석된 키워드',
                        data: [],
                        borderColor: '#4CAF50',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        );

        // 제품 분석 분포 차트
        this.productDistributionChart = new Chart(
            document.getElementById('productDistributionChart'),
            {
                type: 'pie',
                data: {
                    labels: ['높은 수익', '중간 수익', '낮은 수익'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#4CAF50', '#FFC107', '#F44336']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        );
    },

    // 키워드 분석 초기화
    initKeywordAnalysis() {
        this.keywordTable = new DataTable('#keywordTable', {
            columns: [
                { data: 'keyword', title: '키워드' },
                { data: 'volume_score', title: '검색량 점수' },
                { data: 'competition_score', title: '경쟁강도' },
                { data: 'trend_score', title: '트렌드 점수' },
                { data: 'total_score', title: '종합 점수' },
                { data: 'actions', title: '작업' }
            ],
            pageLength: 10,
            order: [[4, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Korean.json'
            }
        });

        // 키워드 분석 폼 이벤트 리스너
        document.getElementById('keywordAnalysisForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.analyzeKeyword();
        });
    },

    // 키워드 분석 실행
    async analyzeKeyword() {
        const form = document.getElementById('keywordAnalysisForm');
        const keyword = form.querySelector('[name="keyword"]').value;
        const category = form.querySelector('[name="category"]').value;

        this.showLoading('키워드 분석 중...');

        try {
            const response = await this.makeRequest('analyze_keyword', {
                nonce: rocketSourcerAdmin.nonce,
                keyword: keyword,
                category: category
            });

            if (response.success) {
                this.updateKeywordTable(response.data);
                this.showNotification('키워드 분석이 완료되었습니다.', 'success');
            }
        } catch (error) {
            this.showNotification('키워드 분석 실패: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    },

    // 제품 분석 초기화
    initProductAnalysis() {
        // 제품 그리드 초기화
        this.productGrid = new Grid('#productGrid', {
            columns: [
                { field: 'image', title: '이미지', formatter: 'image' },
                { field: 'title', title: '제품명' },
                { field: 'price', title: '가격', formatter: 'currency' },
                { field: 'rating', title: '평점', formatter: 'rating' },
                { field: 'profit_margin', title: '마진율', formatter: 'percent' },
                { field: 'actions', title: '작업', formatter: 'actions' }
            ],
            pagination: true,
            search: true,
            sort: true
        });

        // 제품 분석 폼 이벤트 리스너
        document.getElementById('productAnalysisForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.analyzeProduct();
        });
    },

    // 제품 분석 실행
    async analyzeProduct() {
        const form = document.getElementById('productAnalysisForm');
        const productUrl = form.querySelector('[name="product_url"]').value;

        this.showLoading('제품 분석 중...');

        try {
            const response = await this.makeRequest('analyze_product', {
                nonce: rocketSourcerAdmin.nonce,
                product_url: productUrl
            });

            if (response.success) {
                this.updateProductGrid(response.data);
                this.showProductDetails(response.data);
                this.showNotification('제품 분석이 완료되었습니다.', 'success');
            }
        } catch (error) {
            this.showNotification('제품 분석 실패: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    },

    // 마진 계산기 초기화
    initMarginCalculator() {
        const calculator = document.getElementById('marginCalculator');
        const inputs = calculator.querySelectorAll('input[type="number"]');

        // 입력값 변경시 실시간 계산
        inputs.forEach(input => {
            input.addEventListener('input', () => this.calculateMargin());
        });

        // 계산기 폼 제출
        calculator.addEventListener('submit', (e) => {
            e.preventDefault();
            this.calculateMargin();
        });
    },

    // 마진 계산
    calculateMargin() {
        const calculator = document.getElementById('marginCalculator');
        const data = {
            product_cost: parseFloat(calculator.querySelector('[name="product_cost"]').value) || 0,
            selling_price: parseFloat(calculator.querySelector('[name="selling_price"]').value) || 0,
            shipping_cost: parseFloat(calculator.querySelector('[name="shipping_cost"]').value) || 0,
            coupang_fee_rate: parseFloat(calculator.querySelector('[name="coupang_fee_rate"]').value) || 0,
            expected_return_rate: parseFloat(calculator.querySelector('[name="expected_return_rate"]').value) || 0
        };

        // 입력값 검증
        if (!this.validateMarginInput(data)) {
            return;
        }

        // 계산 결과 표시
        const result = this.calculateMarginResult(data);
        this.displayMarginResult(result);
    },

    // 마진 입력값 검증
    validateMarginInput(data) {
        if (data.product_cost <= 0) {
            this.showNotification('상품 원가는 0보다 커야 합니다.', 'error');
            return false;
        }

        if (data.selling_price <= 0) {
            this.showNotification('판매가는 0보다 커야 합니다.', 'error');
            return false;
        }

        if (data.shipping_cost < 0) {
            this.showNotification('배송비는 0 이상이어야 합니다.', 'error');
            return false;
        }

        if (data.coupang_fee_rate < 0 || data.coupang_fee_rate > 100) {
            this.showNotification('쿠팡 수수료율은 0에서 100 사이여야 합니다.', 'error');
            return false;
        }

        if (data.expected_return_rate < 0 || data.expected_return_rate > 100) {
            this.showNotification('예상 반품률은 0에서 100 사이여야 합니다.', 'error');
            return false;
        }

        return true;
    },

    // AJAX 요청 함수
    async makeRequest(action, data) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: action,
                    ...data
                })
            });

            if (!response.ok) {
                throw new Error('네트워크 오류가 발생했습니다.');
            }

            return await response.json();
        } catch (error) {
            throw new Error('요청 처리 중 오류가 발생했습니다: ' + error.message);
        }
    },

    // 알림 표시
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // 3초 후 자동 제거
        setTimeout(() => {
            notification.remove();
        }, 3000);
    },

    // 로딩 표시
    showLoading(message = '로딩 중...') {
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.innerHTML = `
            <div class="loading-spinner"></div>
            <div class="loading-message">${message}</div>
        `;

        document.body.appendChild(loading);
    },

    // 로딩 숨기기
    hideLoading() {
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            loading.remove();
        }
    },

    // 이벤트 리스너 초기화
    initEventListeners() {
        // 탭 전환
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const tabId = e.target.dataset.tab;
                this.switchTab(tabId);
            });
        });

        // 모달 닫기
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', () => {
                this.closeModal();
            });
        });
    },

    // 탭 전환
    switchTab(tabId) {
        // 모든 탭 컨텐츠 숨기기
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });

        // 모든 탭 버튼 비활성화
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // 선택한 탭 표시 및 활성화
        document.getElementById(tabId).style.display = 'block';
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    },

    // 모달 표시
    showModal(content) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <button class="modal-close">&times;</button>
                ${content}
            </div>
        `;

        document.body.appendChild(modal);
    },

    // 모달 닫기
    closeModal() {
        const modal = document.querySelector('.modal');
        if (modal) {
            modal.remove();
        }
    }
};

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', () => {
    RocketSourcer.init();
}); 