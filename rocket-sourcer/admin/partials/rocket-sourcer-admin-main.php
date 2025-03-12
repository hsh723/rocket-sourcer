<?php
/**
 * 관리자 메인 대시보드 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-dashboard">
    <div class="rocket-sourcer-header">
        <h2>로켓소서 대시보드</h2>
        <p>쿠팡 파트너스를 위한 키워드 분석 및 제품 소싱 도우미입니다.</p>
    </div>

    <div class="rocket-sourcer-stats">
        <div class="rocket-sourcer-stat-card">
            <i class="dashicons dashicons-search"></i>
            <h3>저장된 키워드</h3>
            <p class="stat-number" id="saved-keywords-count">0</p>
            <p class="stat-description">분석된 키워드 수</p>
        </div>

        <div class="rocket-sourcer-stat-card">
            <i class="dashicons dashicons-products"></i>
            <h3>분석된 제품</h3>
            <p class="stat-number" id="analyzed-products-count">0</p>
            <p class="stat-description">검토된 제품 수</p>
        </div>

        <div class="rocket-sourcer-stat-card">
            <i class="dashicons dashicons-chart-line"></i>
            <h3>평균 마진</h3>
            <p class="stat-number" id="average-margin">0%</p>
            <p class="stat-description">전체 제품 평균</p>
        </div>

        <div class="rocket-sourcer-stat-card">
            <i class="dashicons dashicons-performance"></i>
            <h3>성공 지수</h3>
            <p class="stat-number" id="success-score">0</p>
            <p class="stat-description">전반적인 성과 지표</p>
        </div>
    </div>

    <div class="rocket-sourcer-features">
        <div class="rocket-sourcer-feature-card">
            <div class="feature-header">
                <i class="dashicons dashicons-search"></i>
                <h3>키워드 분석</h3>
            </div>
            <p>쿠팡에서 인기 있는 키워드를 분석하고 트렌드를 파악하세요.</p>
            <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-keywords'); ?>" class="rocket-sourcer-button">
                키워드 분석하기
            </a>
        </div>

        <div class="rocket-sourcer-feature-card">
            <div class="feature-header">
                <i class="dashicons dashicons-products"></i>
                <h3>제품 분석</h3>
            </div>
            <p>경쟁력 있는 제품을 찾고 상세한 시장 분석을 확인하세요.</p>
            <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-products'); ?>" class="rocket-sourcer-button">
                제품 분석하기
            </a>
        </div>

        <div class="rocket-sourcer-feature-card">
            <div class="feature-header">
                <i class="dashicons dashicons-calculator"></i>
                <h3>마진 계산기</h3>
            </div>
            <p>제품의 실제 수익성을 계산하고 최적의 가격을 찾으세요.</p>
            <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-calculator'); ?>" class="rocket-sourcer-button">
                마진 계산하기
            </a>
        </div>

        <div class="rocket-sourcer-feature-card">
            <div class="feature-header">
                <i class="dashicons dashicons-admin-settings"></i>
                <h3>설정</h3>
            </div>
            <p>API 키 설정, 크롤링 옵션, 알림 설정을 관리하세요.</p>
            <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-settings'); ?>" class="rocket-sourcer-button">
                설정 관리하기
            </a>
        </div>
    </div>

    <div class="rocket-sourcer-recent">
        <div class="rocket-sourcer-recent-keywords">
            <h3>최근 분석된 키워드</h3>
            <div class="rocket-sourcer-table-container">
                <table class="rocket-sourcer-table">
                    <thead>
                        <tr>
                            <th>키워드</th>
                            <th>검색량</th>
                            <th>경쟁강도</th>
                            <th>분석일</th>
                        </tr>
                    </thead>
                    <tbody id="recent-keywords-list">
                        <!-- JavaScript로 동적 추가 -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rocket-sourcer-recent-products">
            <h3>최근 분석된 제품</h3>
            <div class="rocket-sourcer-table-container">
                <table class="rocket-sourcer-table">
                    <thead>
                        <tr>
                            <th>제품명</th>
                            <th>가격</th>
                            <th>마진율</th>
                            <th>분석일</th>
                        </tr>
                    </thead>
                    <tbody id="recent-products-list">
                        <!-- JavaScript로 동적 추가 -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 