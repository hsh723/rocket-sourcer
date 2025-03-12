<?php
/**
 * Rocket Sourcer 키워드 분석 페이지
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/admin/partials
 */

// 직접 접근 방지
if (!defined('WPINC')) {
    die;
}

// 카테고리 목록 가져오기
$categories = array(
    'fashion' => '패션의류',
    'beauty' => '뷰티',
    'food' => '식품',
    'living' => '생활용품',
    'digital' => '디지털/가전',
    'sports' => '스포츠/레저',
    'baby' => '유아동',
    'pets' => '반려동물'
);
?>

<div class="wrap rocket-sourcer-keywords">
    <h1 class="wp-heading-inline">키워드 분석</h1>
    
    <!-- 검색 폼 -->
    <div class="keyword-search-form">
        <form id="keyword-analysis-form" method="post">
            <?php wp_nonce_field('rocket_sourcer_keyword_analysis', 'rocket_sourcer_nonce'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category">카테고리</label>
                    <select name="category" id="category" required>
                        <option value="">카테고리 선택</option>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="keywords">키워드</label>
                    <textarea name="keywords" 
                              id="keywords" 
                              rows="3" 
                              placeholder="분석할 키워드를 입력하세요 (여러 개인 경우 줄바꿈으로 구분)"
                              required></textarea>
                </div>

                <div class="form-group">
                    <label for="analysis-type">분석 유형</label>
                    <select name="analysis_type" id="analysis-type">
                        <option value="basic">기본 분석</option>
                        <option value="detailed">상세 분석</option>
                        <option value="competitive">경쟁 분석</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary" id="analyze-keywords">
                    <i class="dashicons dashicons-search"></i>
                    키워드 분석
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
        <p>키워드를 분석하고 있습니다...</p>
        <div class="progress-bar">
            <div class="progress"></div>
        </div>
        <p class="progress-status">검색량 데이터 수집 중...</p>
    </div>

    <!-- 분석 결과 -->
    <div id="analysis-results" class="analysis-results" style="display: none;">
        <div class="results-header">
            <h2>분석 결과</h2>
            <div class="results-actions">
                <button class="button" id="export-csv">
                    <i class="dashicons dashicons-download"></i>
                    CSV 내보내기
                </button>
                <button class="button" id="save-results">
                    <i class="dashicons dashicons-saved"></i>
                    결과 저장
                </button>
            </div>
        </div>

        <div class="results-filters">
            <select id="sort-by">
                <option value="score">추천 점수순</option>
                <option value="volume">검색량순</option>
                <option value="competition">경쟁 강도순</option>
                <option value="trend">트렌드순</option>
            </select>

            <div class="filter-buttons">
                <button class="button active" data-filter="all">전체</button>
                <button class="button" data-filter="recommended">추천</button>
                <button class="button" data-filter="competitive">경쟁 낮음</button>
                <button class="button" data-filter="trending">상승 트렌드</button>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="sortable" data-sort="keyword">키워드</th>
                    <th class="sortable" data-sort="volume">월간 검색량</th>
                    <th class="sortable" data-sort="competition">경쟁도</th>
                    <th class="sortable" data-sort="trend">트렌드</th>
                    <th class="sortable" data-sort="cpc">예상 CPC</th>
                    <th class="sortable" data-sort="score">추천 점수</th>
                    <th>차트</th>
                    <th>작업</th>
                </tr>
            </thead>
            <tbody id="results-body">
                <!-- JavaScript로 결과가 여기에 삽입됩니다 -->
            </tbody>
        </table>

        <div class="pagination">
            <!-- JavaScript로 페이지네이션이 여기에 삽입됩니다 -->
        </div>
    </div>

    <!-- 차트 모달 -->
    <div id="chart-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>키워드 트렌드 분석</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <canvas id="trend-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- 결과 없음 메시지 -->
    <div id="no-results" class="notice notice-info" style="display: none;">
        <p>검색 결과가 없습니다. 다른 키워드로 시도해보세요.</p>
    </div>

    <!-- 에러 메시지 -->
    <div id="error-message" class="notice notice-error" style="display: none;">
        <p>분석 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</p>
    </div>

    <!-- 도움말 -->
    <div class="keyword-analysis-help">
        <h3>도움말</h3>
        <div class="help-content">
            <h4>분석 지표 설명</h4>
            <ul>
                <li><strong>월간 검색량</strong>: 최근 30일 동안의 검색 횟수입니다.</li>
                <li><strong>경쟁도</strong>: 키워드의 경쟁 강도를 나타냅니다. (낮음/중간/높음)</li>
                <li><strong>트렌드</strong>: 검색량의 증감 추세를 나타냅니다. (상승/하락/유지)</li>
                <li><strong>예상 CPC</strong>: 키워드 광고 시 예상되는 클릭당 비용입니다.</li>
                <li><strong>추천 점수</strong>: 검색량, 경쟁도, 트렌드를 종합적으로 평가한 점수입니다.</li>
            </ul>

            <h4>분석 유형</h4>
            <ul>
                <li><strong>기본 분석</strong>: 검색량, 경쟁도, 트렌드 정보를 제공합니다.</li>
                <li><strong>상세 분석</strong>: 기본 분석에 구매 의도와 연관 키워드를 추가로 제공합니다.</li>
                <li><strong>경쟁 분석</strong>: 상세 분석에 경쟁사 정보와 시장 분석을 추가로 제공합니다.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 결과 템플릿 -->
<script type="text/template" id="result-row-template">
    <tr>
        <td>{keyword}</td>
        <td>{volume}</td>
        <td>
            <div class="competition-level {competition_class}">
                {competition}
            </div>
        </td>
        <td>
            <div class="trend-indicator {trend_class}">
                {trend}
            </div>
        </td>
        <td>{cpc}</td>
        <td>
            <div class="score-bar">
                <div class="bar" style="width: {score}%"></div>
                <span>{score}</span>
            </div>
        </td>
        <td>
            <button class="button view-chart" data-keyword="{keyword}">
                <i class="dashicons dashicons-chart-line"></i>
            </button>
        </td>
        <td>
            <div class="row-actions">
                <button class="button analyze-product" data-keyword="{keyword}">
                    <i class="dashicons dashicons-products"></i>
                    제품 분석
                </button>
                <button class="button save-keyword" data-keyword="{keyword}">
                    <i class="dashicons dashicons-star-filled"></i>
                    저장
                </button>
            </div>
        </td>
    </tr>
</script> 