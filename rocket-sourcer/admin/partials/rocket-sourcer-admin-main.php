<?php
/**
 * 관리자 메인 페이지 템플릿
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rocket-sourcer-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rocket-sourcer-dashboard-header">
        <div class="rocket-sourcer-dashboard-welcome">
            <h2>환영합니다, <?php echo esc_html(wp_get_current_user()->display_name); ?>님!</h2>
            <p>로켓 소서는 쿠팡 로켓그로스 셀러를 위한 소싱 추천 및 분석 도구입니다.</p>
        </div>
    </div>
    
    <div class="rocket-sourcer-dashboard-stats">
        <div class="rocket-sourcer-stat-box">
            <h3>키워드 분석</h3>
            <div class="rocket-sourcer-stat-value">0</div>
            <p>분석된 키워드 수</p>
        </div>
        
        <div class="rocket-sourcer-stat-box">
            <h3>제품 분석</h3>
            <div class="rocket-sourcer-stat-value">0</div>
            <p>분석된 제품 수</p>
        </div>
        
        <div class="rocket-sourcer-stat-box">
            <h3>저장된 제품</h3>
            <div class="rocket-sourcer-stat-value">0</div>
            <p>저장된 제품 수</p>
        </div>
    </div>
    
    <div class="rocket-sourcer-dashboard-recent">
        <h2>최근 분석된 키워드</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>키워드</th>
                    <th>검색량</th>
                    <th>경쟁도</th>
                    <th>카테고리</th>
                    <th>분석 날짜</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5">분석된 키워드가 없습니다.</td>
                </tr>
            </tbody>
        </table>
        <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-keywords')); ?>" class="rocket-sourcer-view-all">모든 키워드 보기</a>
    </div>
    
    <div class="rocket-sourcer-dashboard-actions">
        <div class="rocket-sourcer-action-box">
            <h3>키워드 분석</h3>
            <p>새로운 키워드를 분석하고 관련 제품을 찾아보세요.</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-keywords')); ?>" class="button button-primary">키워드 분석하기</a>
        </div>
        
        <div class="rocket-sourcer-action-box">
            <h3>제품 분석</h3>
            <p>제품의 가격, 리뷰, 판매자 정보 등을 분석해보세요.</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-products')); ?>" class="button button-primary">제품 분석하기</a>
        </div>
        
        <div class="rocket-sourcer-action-box">
            <h3>설정</h3>
            <p>플러그인 설정을 변경하고 API 키를 관리하세요.</p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-settings')); ?>" class="button button-primary">설정 관리하기</a>
        </div>
    </div>
</div> 