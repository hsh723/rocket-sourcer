<?php
/**
 * Rocket Sourcer 제품 분석 페이지
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/admin/partials
 */

// 직접 접근 방지
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rocket-sourcer-products">
    <h1 class="wp-heading-inline">제품 분석</h1>

    <!-- 검색 폼 -->
    <div class="product-search-form">
        <form id="product-analysis-form" method="post">
            <?php wp_nonce_field('rocket_sourcer_product_analysis', 'rocket_sourcer_nonce'); ?>

            <div class="form-row">
                <div class="form-group url-input">
                    <label for="product-url">제품 URL</label>
                    <div class="url-input-wrapper">
                        <input type="url" name="product-url" id="product-url" required
                               placeholder="분석할 제품의 URL을 입력하세요">
                        <button type="button" class="button" id="paste-url">
                            <i class="dashicons dashicons-clipboard"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="analysis-options">분석 옵션</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="include_competitors" id="include_competitors" checked>
                            경쟁사 분석 포함
                        </label>
                        <label>
                            <input type="checkbox" name="include_forecast" id="include_forecast" checked>
                            판매 예측 포함
                        </label>
                        <label>
                            <input type="checkbox" name="include_overseas" id="include_overseas" checked>
                            해외 소싱 검색
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary" id="analyze-product">
                    <i class="dashicons dashicons-search"></i>
                    제품 분석
                </button>
                <button type="button" class="button" id="clear-form">
                    <i class="dashicons dashicons-dismiss"></i>
                    초기화
                </button>
            </div>
        </form>
    </div>

    <!-- 로딩 표시 -->
    <div id="analysis-loading" class="analysis-loading" style="display: none;">
        <div class="spinner"></div>
        <p>제품을 분석하고 있습니다...</p>
        <div class="progress-bar">
            <div class="progress"></div>
        </div>
        <p class="progress-status">기본 정보 수집 중...</p>
    </div>

    <!-- 분석 결과 -->
    <div id="analysis-results" class="analysis-results" style="display: none;">
        <!-- 기본 정보 -->
        <div class="result-section basic-info">
            <h2>기본 정보</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3>제품 정보</h3>
                    <div class="product-info">
                        <img src="" alt="" id="product-image" class="product-image">
                        <div class="product-details">
                            <h4 id="product-name"></h4>
                            <p id="product-price"></p>
                            <p id="product-category"></p>
                            <div class="product-rating">
                                <span class="stars"></span>
                                <span class="review-count"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3>최적화 점수</h3>
                    <div class="optimization-scores">
                        <div class="score-item">
                            <label>제목 최적화</label>
                            <div class="score-bar">
                                <div class="bar" id="title-score"></div>
                                <span class="score-value"></span>
                            </div>
                        </div>
                        <div class="score-item">
                            <label>이미지 품질</label>
                            <div class="score-bar">
                                <div class="bar" id="image-score"></div>
                                <span class="score-value"></span>
                            </div>
                        </div>
                        <div class="score-item">
                            <label>설명 충실도</label>
                            <div class="score-bar">
                                <div class="bar" id="description-score"></div>
                                <span class="score-value"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 수익성 분석 -->
        <div class="result-section profitability">
            <h2>수익성 분석</h2>
            <div class="profitability-grid">
                <div class="chart-card">
                    <h3>마진 분석</h3>
                    <canvas id="margin-chart"></canvas>
                    <div class="margin-summary">
                        <div class="summary-item">
                            <label>예상 마진율</label>
                            <span id="expected-margin"></span>
                        </div>
                        <div class="summary-item">
                            <label>손익분기점</label>
                            <span id="break-even"></span>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>가격 경쟁력</h3>
                    <canvas id="price-comparison-chart"></canvas>
                    <div class="price-summary">
                        <div class="summary-item">
                            <label>시장 평균가</label>
                            <span id="market-average"></span>
                        </div>
                        <div class="summary-item">
                            <label>가격 경쟁력</label>
                            <span id="price-competitiveness"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 시장 분석 -->
        <div class="result-section market-analysis">
            <h2>시장 분석</h2>
            <div class="market-grid">
                <div class="chart-card">
                    <h3>판매 예측</h3>
                    <canvas id="sales-forecast-chart"></canvas>
                    <div class="forecast-summary">
                        <div class="summary-item">
                            <label>예상 월 판매량</label>
                            <span id="monthly-sales"></span>
                        </div>
                        <div class="summary-item">
                            <label>시장 성장률</label>
                            <span id="market-growth"></span>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>경쟁 분석</h3>
                    <canvas id="competition-chart"></canvas>
                    <div class="competition-summary">
                        <div class="summary-item">
                            <label>경쟁 강도</label>
                            <span id="competition-level"></span>
                        </div>
                        <div class="summary-item">
                            <label>시장 점유율</label>
                            <span id="market-share"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 해외 소싱 -->
        <div class="result-section overseas-sourcing">
            <h2>해외 소싱 검색 결과</h2>
            <div class="sourcing-filters">
                <select id="platform-filter">
                    <option value="all">전체 플랫폼</option>
                    <option value="1688">1688.com</option>
                    <option value="aliexpress">AliExpress</option>
                </select>
                <select id="sort-filter">
                    <option value="price">가격순</option>
                    <option value="orders">주문량순</option>
                    <option value="rating">평점순</option>
                </select>
            </div>

            <div class="sourcing-grid" id="sourcing-results">
                <!-- JavaScript로 결과가 여기에 삽입됩니다 -->
            </div>

            <template id="sourcing-item-template">
                <div class="sourcing-item">
                    <div class="item-image">
                        <img src="{image}" alt="{title}">
                    </div>
                    <div class="item-details">
                        <h4>{title}</h4>
                        <div class="price">{price}</div>
                        <div class="stats">
                            <span class="orders">{orders} 주문</span>
                            <span class="rating">{rating}점</span>
                        </div>
                        <div class="supplier">
                            <img src="{supplier_logo}" alt="{supplier_name}">
                            <span>{supplier_name}</span>
                        </div>
                        <div class="actions">
                            <a href="{url}" target="_blank" class="button">
                                <i class="dashicons dashicons-external"></i>
                                상품 보기
                            </a>
                            <button class="button calculate-margin" data-price="{price}">
                                <i class="dashicons dashicons-calculator"></i>
                                마진 계산
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- 결과 액션 -->
        <div class="result-actions">
            <button class="button" id="export-pdf">
                <i class="dashicons dashicons-pdf"></i>
                PDF 내보내기
            </button>
            <button class="button" id="save-analysis">
                <i class="dashicons dashicons-saved"></i>
                분석 저장
            </button>
            <button class="button" id="share-analysis">
                <i class="dashicons dashicons-share"></i>
                공유
            </button>
        </div>
    </div>

    <!-- 마진 계산 모달 -->
    <div id="margin-calculator-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>마진 계산기</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- 마진 계산기 내용은 JavaScript로 로드됩니다 -->
            </div>
        </div>
    </div>

    <!-- 에러 메시지 -->
    <div id="error-message" class="notice notice-error" style="display: none;">
        <p>분석 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</p>
    </div>

    <!-- 도움말 -->
    <div class="product-analysis-help">
        <h3>도움말</h3>
        <div class="help-content">
            <h4>분석 지표 설명</h4>
            <ul>
                <li><strong>최적화 점수</strong>: 제품 정보의 완성도를 평가합니다.</li>
                <li><strong>수익성 분석</strong>: 예상 마진율과 손익분기점을 계산합니다.</li>
                <li><strong>시장 분석</strong>: 시장 규모와 경쟁 상황을 분석합니다.</li>
                <li><strong>해외 소싱</strong>: 유사 제품의 해외 공급처를 검색합니다.</li>
            </ul>
        </div>
    </div>
</div> 