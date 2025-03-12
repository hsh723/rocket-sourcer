<?php
/**
 * Rocket Sourcer 관리자 메인 대시보드
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/admin/partials
 */

// 직접 접근 방지
if (!defined('WPINC')) {
    die;
}

// 통계 데이터 가져오기
$stats = array(
    'keywords' => get_option('rocket_sourcer_analyzed_keywords', 0),
    'products' => get_option('rocket_sourcer_analyzed_products', 0),
    'avg_margin' => get_option('rocket_sourcer_average_margin', 0),
    'api_usage' => get_option('rocket_sourcer_api_usage', 0)
);

// 최근 분석 결과 가져오기
$recent_keywords = get_option('rocket_sourcer_recent_keywords', array());
$recent_products = get_option('rocket_sourcer_recent_products', array());
?>

<div class="wrap rocket-sourcer-dashboard">
    <h1 class="wp-heading-inline">
        <img src="<?php echo plugin_dir_url(__FILE__) . '../../assets/images/logo.png'; ?>" 
             alt="Rocket Sourcer" 
             class="rocket-sourcer-logo">
        Rocket Sourcer 대시보드
    </h1>

    <!-- 통계 요약 -->
    <div class="stats-summary">
        <div class="stat-card">
            <i class="dashicons dashicons-search"></i>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($stats['keywords']); ?></span>
                <span class="stat-label">분석된 키워드</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="dashicons dashicons-products"></i>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($stats['products']); ?></span>
                <span class="stat-label">분석된 제품</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="dashicons dashicons-chart-line"></i>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($stats['avg_margin'], 1); ?>%</span>
                <span class="stat-label">평균 마진율</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="dashicons dashicons-performance"></i>
            <div class="stat-content">
                <span class="stat-value"><?php echo number_format($stats['api_usage']); ?>/1000</span>
                <span class="stat-label">API 사용량</span>
            </div>
        </div>
    </div>

    <!-- 기능 카드 -->
    <div class="feature-cards">
        <div class="feature-card">
            <div class="card-header">
                <i class="dashicons dashicons-search"></i>
                <h2>키워드 분석</h2>
            </div>
            <p>인기 키워드를 분석하고 트렌드를 파악하세요.</p>
            <a href="?page=rocket-sourcer-keywords" class="button button-primary">
                키워드 분석하기
            </a>
        </div>
        <div class="feature-card">
            <div class="card-header">
                <i class="dashicons dashicons-products"></i>
                <h2>제품 분석</h2>
            </div>
            <p>제품의 수익성과 시장성을 분석하세요.</p>
            <a href="?page=rocket-sourcer-products" class="button button-primary">
                제품 분석하기
            </a>
        </div>
        <div class="feature-card">
            <div class="card-header">
                <i class="dashicons dashicons-calculator"></i>
                <h2>마진 계산기</h2>
            </div>
            <p>정확한 마진율과 수익을 계산하세요.</p>
            <button class="button button-primary" id="open-margin-calculator">
                마진 계산하기
            </button>
        </div>
        <div class="feature-card">
            <div class="card-header">
                <i class="dashicons dashicons-admin-settings"></i>
                <h2>설정</h2>
            </div>
            <p>API 키 설정 및 기본 설정을 관리하세요.</p>
            <a href="?page=rocket-sourcer-settings" class="button button-primary">
                설정 관리
            </a>
        </div>
    </div>

    <!-- 최근 분석 결과 -->
    <div class="recent-results">
        <!-- 최근 키워드 분석 -->
        <div class="results-section">
            <h2>최근 키워드 분석</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>키워드</th>
                        <th>검색량</th>
                        <th>경쟁도</th>
                        <th>트렌드</th>
                        <th>분석일</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_keywords)) : ?>
                        <tr>
                            <td colspan="5">최근 분석된 키워드가 없습니다.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($recent_keywords as $keyword) : ?>
                            <tr>
                                <td><?php echo esc_html($keyword['keyword']); ?></td>
                                <td><?php echo number_format($keyword['volume']); ?></td>
                                <td>
                                    <div class="competition-level <?php echo esc_attr($keyword['competition']); ?>">
                                        <?php echo esc_html($keyword['competition']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="trend-indicator <?php echo esc_attr($keyword['trend']); ?>">
                                        <?php echo esc_html($keyword['trend']); ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($keyword['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="?page=rocket-sourcer-keywords" class="button">
                모든 키워드 보기
            </a>
        </div>

        <!-- 최근 제품 분석 -->
        <div class="results-section">
            <h2>최근 제품 분석</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>제품명</th>
                        <th>가격</th>
                        <th>마진율</th>
                        <th>경쟁도</th>
                        <th>분석일</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_products)) : ?>
                        <tr>
                            <td colspan="5">최근 분석된 제품이 없습니다.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($recent_products as $product) : ?>
                            <tr>
                                <td><?php echo esc_html($product['name']); ?></td>
                                <td><?php echo number_format($product['price']); ?>원</td>
                                <td>
                                    <div class="margin-rate <?php echo get_margin_class($product['margin']); ?>">
                                        <?php echo number_format($product['margin'], 1); ?>%
                                    </div>
                                </td>
                                <td>
                                    <div class="competition-level <?php echo esc_attr($product['competition']); ?>">
                                        <?php echo esc_html($product['competition']); ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($product['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="?page=rocket-sourcer-products" class="button">
                모든 제품 보기
            </a>
        </div>
    </div>

    <!-- 도움말 -->
    <div class="dashboard-help">
        <h2>도움말</h2>
        <div class="help-grid">
            <div class="help-card">
                <h3>문서</h3>
                <ul>
                    <li><a href="https://rocketsourcer.com/docs" target="_blank">사용자 매뉴얼</a></li>
                    <li><a href="https://rocketsourcer.com/docs/api" target="_blank">API 문서</a></li>
                    <li><a href="https://rocketsourcer.com/docs/faq" target="_blank">자주 묻는 질문</a></li>
                </ul>
            </div>
            <div class="help-card">
                <h3>기술 지원</h3>
                <ul>
                    <li><a href="https://rocketsourcer.com/support" target="_blank">지원 티켓 열기</a></li>
                    <li><a href="https://rocketsourcer.com/community" target="_blank">커뮤니티 포럼</a></li>
                    <li><a href="mailto:support@rocketsourcer.com">이메일 문의</a></li>
                </ul>
            </div>
            <div class="help-card">
                <h3>비디오 튜토리얼</h3>
                <ul>
                    <li><a href="#" id="tutorial-keywords">키워드 분석 방법</a></li>
                    <li><a href="#" id="tutorial-products">제품 분석 방법</a></li>
                    <li><a href="#" id="tutorial-margin">마진 계산 방법</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 마진 계산기 모달 -->
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

    <!-- 비디오 튜토리얼 모달 -->
    <div id="tutorial-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="tutorial-title"></h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="tutorial-video"></div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * 마진율에 따른 CSS 클래스를 반환합니다.
 *
 * @param float $margin 마진율
 * @return string CSS 클래스
 */
function get_margin_class($margin) {
    if ($margin >= 40) {
        return 'high';
    } elseif ($margin >= 20) {
        return 'medium';
    } else {
        return 'low';
    }
}
?> 