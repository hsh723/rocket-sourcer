/**
 * 로켓 소서 공개 자바스크립트
 *
 * 이 파일은 플러그인의 공개 영역에 대한 모든 자바스크립트 기능을 포함합니다.
 *
 * @link       https://www.yourwebsite.com
 * @since      1.0.0
 *
 * @package    Rocket_Sourcer
 */

(function( $ ) {
	'use strict';

	/**
	 * 모든 공개 JavaScript 기능은 이 파일 내에서 정의됩니다.
	 * 
	 * 다음 함수들이 포함됩니다:
	 * - 제품 검색 및 필터링
	 * - 그리드/리스트 뷰 전환
	 * - 페이지네이션 처리
	 * - 제품 저장 기능
	 */

	// 전역 변수
	let currentPage = 1;
	let totalPages = 1;
	let currentView = 'grid'; // 'grid' 또는 'list'
	let isLoading = false;
	let searchParams = {
		keyword: '',
		category: '',
		minPrice: '',
		maxPrice: '',
		sort: 'newest'
	};

	/**
	 * DOM이 완전히 로드된 후 실행
	 */
	$(document).ready(function() {
		// 초기 제품 로드
		loadProducts();

		// 검색 폼 제출 이벤트
		$('.rocket-sourcer-search-form').on('submit', function(e) {
			e.preventDefault();
			searchParams.keyword = $('.rocket-sourcer-search-input').val();
			currentPage = 1;
			loadProducts();
		});

		// 필터 적용 버튼 클릭 이벤트
		$('.rocket-sourcer-filter-apply').on('click', function() {
			updateFilters();
			currentPage = 1;
			loadProducts();
		});

		// 필터 초기화 버튼 클릭 이벤트
		$('.rocket-sourcer-filter-reset').on('click', function() {
			resetFilters();
			currentPage = 1;
			loadProducts();
		});

		// 뷰 전환 버튼 클릭 이벤트
		$('.rocket-sourcer-view-toggle button').on('click', function() {
			const view = $(this).data('view');
			switchView(view);
		});

		// 페이지네이션 클릭 이벤트
		$(document).on('click', '.rocket-sourcer-pagination a', function(e) {
			e.preventDefault();
			if ($(this).hasClass('active') || isLoading) return;
			
			const page = $(this).data('page');
			if (page) {
				currentPage = page;
				loadProducts();
				// 페이지 상단으로 스크롤
				$('html, body').animate({
					scrollTop: $('.rocket-sourcer-container').offset().top - 50
				}, 500);
			}
		});

		// 제품 저장 버튼 클릭 이벤트
		$(document).on('click', '.rocket-sourcer-product-save', function() {
			const productId = $(this).closest('[data-product-id]').data('product-id');
			saveProduct(productId);
		});

		// 제품 상세 보기 버튼 클릭 이벤트
		$(document).on('click', '.rocket-sourcer-product-view', function() {
			const productId = $(this).closest('[data-product-id]').data('product-id');
			viewProduct(productId);
		});

		// 공개 페이지 초기화
		initializePublicPage();
	});

	/**
	 * 제품 목록을 로드하는 함수
	 */
	function loadProducts() {
		showLoading();

		// AJAX 요청 파라미터
		const data = {
			action: 'rocket_sourcer_search_products',
			security: rocket_sourcer_public.nonce,
			page: currentPage,
			keyword: searchParams.keyword,
			category: searchParams.category,
			min_price: searchParams.minPrice,
			max_price: searchParams.maxPrice,
			sort: searchParams.sort
		};

		// AJAX 요청
		$.ajax({
			url: rocket_sourcer_public.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					renderProducts(response.data.products);
					renderPagination(response.data.current_page, response.data.total_pages);
					totalPages = response.data.total_pages;
				} else {
					showError(response.data.message || '제품을 로드하는 중 오류가 발생했습니다.');
				}
				hideLoading();
			},
			error: function() {
				showError('서버 연결 중 오류가 발생했습니다. 나중에 다시 시도해주세요.');
				hideLoading();
			}
		});
	}

	/**
	 * 제품 목록을 렌더링하는 함수
	 * 
	 * @param {Array} products 제품 데이터 배열
	 */
	function renderProducts(products) {
		const container = $('.rocket-sourcer-products-container');
		
		// 컨테이너 비우기
		container.empty();
		
		if (products.length === 0) {
			container.html('<div class="rocket-sourcer-no-results">검색 결과가 없습니다.</div>');
			return;
		}

		// 그리드 또는 리스트 컨테이너 생성
		const viewClass = currentView === 'grid' ? 'rocket-sourcer-products-grid' : 'rocket-sourcer-products-list';
		const productsEl = $('<div>').addClass(viewClass);
		
		// 각 제품 렌더링
		$.each(products, function(index, product) {
			if (currentView === 'grid') {
				productsEl.append(renderProductCard(product));
			} else {
				productsEl.append(renderProductRow(product));
			}
		});
		
		container.append(productsEl);
	}

	/**
	 * 그리드 뷰용 제품 카드를 렌더링하는 함수
	 * 
	 * @param {Object} product 제품 데이터
	 * @return {jQuery} 제품 카드 요소
	 */
	function renderProductCard(product) {
		return $(`
			<div class="rocket-sourcer-product-card" data-product-id="${product.id}">
				<div class="rocket-sourcer-product-image">
					<img src="${product.image}" alt="${product.title}">
				</div>
				<div class="rocket-sourcer-product-details">
					<h3 class="rocket-sourcer-product-title">${product.title}</h3>
					<div class="rocket-sourcer-product-price">${formatPrice(product.price)}</div>
					<div class="rocket-sourcer-product-meta">
						<span>${product.category}</span>
						<span>${product.date}</span>
					</div>
					<div class="rocket-sourcer-product-buttons">
						<a href="javascript:void(0);" class="rocket-sourcer-product-button rocket-sourcer-product-view">상세 보기</a>
						<a href="javascript:void(0);" class="rocket-sourcer-product-button rocket-sourcer-product-save">저장</a>
					</div>
				</div>
			</div>
		`);
	}

	/**
	 * 리스트 뷰용 제품 행을 렌더링하는 함수
	 * 
	 * @param {Object} product 제품 데이터
	 * @return {jQuery} 제품 행 요소
	 */
	function renderProductRow(product) {
		return $(`
			<div class="rocket-sourcer-product-row" data-product-id="${product.id}">
				<div class="rocket-sourcer-product-image">
					<img src="${product.image}" alt="${product.title}">
				</div>
				<div class="rocket-sourcer-product-details">
					<h3 class="rocket-sourcer-product-title">${product.title}</h3>
					<div class="rocket-sourcer-product-description">${product.description}</div>
					<div class="rocket-sourcer-product-price">${formatPrice(product.price)}</div>
					<div class="rocket-sourcer-product-meta">
						<span>${product.category}</span>
						<span>${product.date}</span>
					</div>
				</div>
				<div class="rocket-sourcer-product-buttons">
					<a href="javascript:void(0);" class="rocket-sourcer-product-button rocket-sourcer-product-view">상세 보기</a>
					<a href="javascript:void(0);" class="rocket-sourcer-product-button rocket-sourcer-product-save">저장</a>
				</div>
			</div>
		`);
	}

	/**
	 * 페이지네이션을 렌더링하는 함수
	 * 
	 * @param {number} currentPage 현재 페이지
	 * @param {number} totalPages 전체 페이지 수
	 */
	function renderPagination(currentPage, totalPages) {
		const paginationEl = $('.rocket-sourcer-pagination');
		paginationEl.empty();
		
		if (totalPages <= 1) return;
		
		// 이전 페이지 링크
		if (currentPage > 1) {
			paginationEl.append(`<a href="#" data-page="${currentPage - 1}">이전</a>`);
		}
		
		// 페이지 번호 링크
		const startPage = Math.max(1, currentPage - 2);
		const endPage = Math.min(totalPages, startPage + 4);
		
		for (let i = startPage; i <= endPage; i++) {
			const activeClass = i === currentPage ? 'active' : '';
			paginationEl.append(`<a href="#" class="${activeClass}" data-page="${i}">${i}</a>`);
		}
		
		// 다음 페이지 링크
		if (currentPage < totalPages) {
			paginationEl.append(`<a href="#" data-page="${currentPage + 1}">다음</a>`);
		}
	}

	/**
	 * 뷰 모드를 전환하는 함수
	 * 
	 * @param {string} view 뷰 모드 ('grid' 또는 'list')
	 */
	function switchView(view) {
		if (view === currentView) return;
		
		currentView = view;
		
		// 버튼 활성화 상태 업데이트
		$('.rocket-sourcer-view-toggle button').removeClass('active');
		$(`.rocket-sourcer-view-toggle button[data-view="${view}"]`).addClass('active');
		
		// 현재 제품 목록 다시 렌더링
		loadProducts();
	}

	/**
	 * 필터 값을 업데이트하는 함수
	 */
	function updateFilters() {
		searchParams.category = $('#rocket-sourcer-category-filter').val();
		searchParams.minPrice = $('#rocket-sourcer-min-price-filter').val();
		searchParams.maxPrice = $('#rocket-sourcer-max-price-filter').val();
		searchParams.sort = $('#rocket-sourcer-sort-filter').val();
	}

	/**
	 * 필터를 초기화하는 함수
	 */
	function resetFilters() {
		$('#rocket-sourcer-category-filter').val('');
		$('#rocket-sourcer-min-price-filter').val('');
		$('#rocket-sourcer-max-price-filter').val('');
		$('#rocket-sourcer-sort-filter').val('newest');
		
		searchParams = {
			keyword: searchParams.keyword, // 검색어는 유지
			category: '',
			minPrice: '',
			maxPrice: '',
			sort: 'newest'
		};
	}

	/**
	 * 제품을 저장하는 함수
	 * 
	 * @param {number} productId 제품 ID
	 */
	function saveProduct(productId) {
		if (!productId) return;
		
		$.ajax({
			url: rocket_sourcer_public.ajax_url,
			type: 'POST',
			data: {
				action: 'rocket_sourcer_save_product',
				security: rocket_sourcer_public.nonce,
				product_id: productId
			},
			success: function(response) {
				if (response.success) {
					showNotification('제품이 성공적으로 저장되었습니다.');
				} else {
					showError(response.data.message || '제품 저장 중 오류가 발생했습니다.');
				}
			},
			error: function() {
				showError('서버 연결 중 오류가 발생했습니다. 나중에 다시 시도해주세요.');
			}
		});
	}

	/**
	 * 제품 상세 페이지로 이동하는 함수
	 * 
	 * @param {number} productId 제품 ID
	 */
	function viewProduct(productId) {
		if (!productId) return;
		
		// 제품 상세 페이지 URL 생성
		const detailUrl = rocket_sourcer_public.product_detail_url.replace('%d', productId);
		
		// 새 창에서 열기
		window.open(detailUrl, '_blank');
	}

	/**
	 * 로딩 인디케이터를 표시하는 함수
	 */
	function showLoading() {
		isLoading = true;
		
		// 기존 로딩 인디케이터가 있으면 제거
		$('.rocket-sourcer-loading').remove();
		
		// 로딩 인디케이터 추가
		const loadingEl = $('<div class="rocket-sourcer-loading"><div class="rocket-sourcer-loading-spinner"></div></div>');
		$('.rocket-sourcer-products-container').append(loadingEl);
	}

	/**
	 * 로딩 인디케이터를 숨기는 함수
	 */
	function hideLoading() {
		isLoading = false;
		$('.rocket-sourcer-loading').remove();
	}

	/**
	 * 오류 메시지를 표시하는 함수
	 * 
	 * @param {string} message 오류 메시지
	 */
	function showError(message) {
		// 알림 표시
		showNotification(message, 'error');
	}

	/**
	 * 알림을 표시하는 함수
	 * 
	 * @param {string} message 알림 메시지
	 * @param {string} type 알림 유형 ('success' 또는 'error')
	 */
	function showNotification(message, type = 'success') {
		// 기존 알림 제거
		$('.rocket-sourcer-notification').remove();
		
		// 알림 요소 생성
		const notificationEl = $(`<div class="rocket-sourcer-notification rocket-sourcer-notification-${type}">${message}</div>`);
		$('body').append(notificationEl);
		
		// 알림 표시 후 자동으로 사라지게 함
		notificationEl.fadeIn(300).delay(3000).fadeOut(300, function() {
			$(this).remove();
		});
	}

	/**
	 * 가격을 포맷팅하는 함수
	 * 
	 * @param {number} price 가격
	 * @return {string} 포맷팅된 가격
	 */
	function formatPrice(price) {
		return '₩' + price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	/**
	 * 공개 페이지 초기화
	 */
	function initializePublicPage() {
		initDashboard();
		initCalculator();
		bindEvents();
	}

	/**
	 * 대시보드 초기화
	 */
	function initDashboard() {
		loadRecentKeywords();
		loadRecentProducts();
		updateStats();
	}

	/**
	 * 최근 키워드 로드
	 */
	function loadRecentKeywords() {
		$.ajax({
			url: rocket_sourcer_public.ajax_url,
			type: 'POST',
			data: {
				action: 'get_recent_keywords',
				nonce: rocket_sourcer_public.nonce
			},
			success: function(response) {
				if (response.success) {
					updateKeywordsList(response.data);
				}
			}
		});
	}

	/**
	 * 최근 제품 로드
	 */
	function loadRecentProducts() {
		$.ajax({
			url: rocket_sourcer_public.ajax_url,
			type: 'POST',
			data: {
				action: 'get_recent_products',
				nonce: rocket_sourcer_public.nonce
			},
			success: function(response) {
				if (response.success) {
					updateProductsList(response.data);
				}
			}
		});
	}

	/**
	 * 통계 업데이트
	 */
	function updateStats() {
		$.ajax({
			url: rocket_sourcer_public.ajax_url,
			type: 'POST',
			data: {
				action: 'get_dashboard_stats',
				nonce: rocket_sourcer_public.nonce
			},
			success: function(response) {
				if (response.success) {
					updateStatsDisplay(response.data);
				}
			}
		});
	}

	/**
	 * 키워드 목록 업데이트
	 */
	function updateKeywordsList(keywords) {
		const container = $('.rocket-sourcer-recent-keywords');
		container.empty();

		keywords.forEach(function(keyword) {
			const item = $('<div class="rocket-sourcer-keyword-item"></div>');
			item.append(`<h4>${keyword.keyword}</h4>`);
			item.append(`<p>검색량: ${keyword.search_volume}</p>`);
			item.append(`<p>경쟁도: ${keyword.competition}</p>`);
			container.append(item);
		});
	}

	/**
	 * 제품 목록 업데이트
	 */
	function updateProductsList(products) {
		const container = $('.rocket-sourcer-recent-products');
		container.empty();

		products.forEach(function(product) {
			const item = $('<div class="rocket-sourcer-product-item"></div>');
			if (product.image) {
				item.append(`<img src="${product.image}" alt="${product.title}">`);
			}
			item.append(`<h4>${product.title}</h4>`);
			item.append(`<p>가격: ${formatCurrency(product.price)}</p>`);
			item.append(`<p>예상 수익: ${formatCurrency(product.estimated_profit)}</p>`);
			container.append(item);
		});
	}

	/**
	 * 통계 표시 업데이트
	 */
	function updateStatsDisplay(stats) {
		$('.rocket-sourcer-stat-box.keywords .rocket-sourcer-stat-value').text(stats.total_keywords);
		$('.rocket-sourcer-stat-box.products .rocket-sourcer-stat-value').text(stats.total_products);
		$('.rocket-sourcer-stat-box.profit .rocket-sourcer-stat-value').text(formatCurrency(stats.total_profit));
	}

	/**
	 * 계산기 초기화
	 */
	function initCalculator() {
		bindCalculatorEvents();
		resetCalculator();
	}

	/**
	 * 계산기 이벤트 바인딩
	 */
	function bindCalculatorEvents() {
		$('#rocket-sourcer-calculator-form').on('submit', function(e) {
			e.preventDefault();
			calculateProfit();
		});

		$('#rocket-sourcer-calculator-reset').on('click', function(e) {
			e.preventDefault();
			resetCalculator();
		});

		// 입력값 변경 시 실시간 계산
		$('.rocket-sourcer-calculator input[type="number"]').on('input', debounce(function() {
			calculateProfit();
		}, 500));
	}

	/**
	 * 수익성 계산
	 */
	function calculateProfit() {
		const data = {
			product_price: parseFloat($('#product-price').val()) || 0,
			purchase_price: parseFloat($('#purchase-price').val()) || 0,
			shipping_cost: parseFloat($('#shipping-cost').val()) || 0,
			additional_cost: parseFloat($('#additional-cost').val()) || 0,
			expected_sales: parseInt($('#expected-sales').val()) || 0
		};

		// 단위 원가 계산
		const unitCost = data.purchase_price + data.shipping_cost + data.additional_cost;
		
		// 수수료 계산 (쿠팡 기본 수수료 10%)
		const commission = data.product_price * 0.1;
		
		// 단위당 수익 계산
		const profitPerUnit = data.product_price - unitCost - commission;
		
		// 월 예상 매출
		const monthlyRevenue = data.product_price * data.expected_sales;
		
		// 월 예상 수익
		const monthlyProfit = profitPerUnit * data.expected_sales;
		
		// 수익률
		const profitMargin = (profitPerUnit / data.product_price) * 100;

		updateCalculatorResults({
			unitCost: unitCost,
			commission: commission,
			profitPerUnit: profitPerUnit,
			monthlyRevenue: monthlyRevenue,
			monthlyProfit: monthlyProfit,
			profitMargin: profitMargin
		});
	}

	/**
	 * 계산기 결과 업데이트
	 */
	function updateCalculatorResults(results) {
		$('.rocket-sourcer-result-box.unit-cost .rocket-sourcer-result-value').text(formatCurrency(results.unitCost));
		$('.rocket-sourcer-result-box.commission .rocket-sourcer-result-value').text(formatCurrency(results.commission));
		$('.rocket-sourcer-result-box.profit-per-unit .rocket-sourcer-result-value').text(formatCurrency(results.profitPerUnit));
		$('.rocket-sourcer-result-box.monthly-revenue .rocket-sourcer-result-value').text(formatCurrency(results.monthlyRevenue));
		$('.rocket-sourcer-result-box.monthly-profit .rocket-sourcer-result-value').text(formatCurrency(results.monthlyProfit));
		$('.rocket-sourcer-result-box.profit-margin .rocket-sourcer-result-value').text(formatPercent(results.profitMargin));

		updateProfitChart(results);
	}

	/**
	 * 수익 차트 업데이트
	 */
	function updateProfitChart(results) {
		// Chart.js를 사용한 차트 업데이트 로직
		if (window.profitChart) {
			window.profitChart.destroy();
		}

		const ctx = document.getElementById('profit-breakdown-chart').getContext('2d');
		window.profitChart = new Chart(ctx, {
			type: 'pie',
			data: {
				labels: ['단위 원가', '수수료', '순수익'],
				datasets: [{
					data: [results.unitCost, results.commission, results.profitPerUnit],
					backgroundColor: ['#FF6384', '#36A2EB', '#4BC0C0']
				}]
			},
			options: {
				responsive: true,
				plugins: {
					legend: {
						position: 'bottom'
					}
				}
			}
		});
	}

	/**
	 * 계산기 초기화
	 */
	function resetCalculator() {
		$('#rocket-sourcer-calculator-form')[0].reset();
		$('.rocket-sourcer-result-value').text('0');
		if (window.profitChart) {
			window.profitChart.destroy();
		}
	}

	/**
	 * 이벤트 바인딩
	 */
	function bindEvents() {
		// 키워드 분석 버튼
		$('.rocket-sourcer-action-box.keywords button').on('click', function(e) {
			e.preventDefault();
			window.location.href = rocket_sourcer_public.keyword_analysis_url;
		});

		// 제품 분석 버튼
		$('.rocket-sourcer-action-box.products button').on('click', function(e) {
			e.preventDefault();
			window.location.href = rocket_sourcer_public.product_analysis_url;
		});

		// 수익성 계산기 버튼
		$('.rocket-sourcer-action-box.calculator button').on('click', function(e) {
			e.preventDefault();
			window.location.href = rocket_sourcer_public.calculator_url;
		});
	}

	/**
	 * 유틸리티 함수
	 */
	function formatCurrency(amount) {
		return new Intl.NumberFormat('ko-KR', {
			style: 'currency',
			currency: 'KRW'
		}).format(amount);
	}

	function formatPercent(value) {
		return new Intl.NumberFormat('ko-KR', {
			style: 'percent',
			minimumFractionDigits: 1,
			maximumFractionDigits: 1
		}).format(value / 100);
	}

	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	}

})( jQuery );
