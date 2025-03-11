<?php
/**
 * 공개 대시보드 페이지 템플릿
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-public-dashboard">
    <div class="rocket-sourcer-dashboard-header">
        <h1>로켓 소서 대시보드</h1>
        <p>쿠팡 로켓그로스 셀러를 위한 제품 소싱 및 분석 도구</p>
    </div>
    
    <div class="rocket-sourcer-dashboard-stats">
        <div class="rocket-sourcer-stat-box">
            <h3>분석된 키워드</h3>
            <div class="rocket-sourcer-stat-value">
                <?php echo esc_html(get_option('rocket_sourcer_analyzed_keywords_count', '0')); ?>
            </div>
        </div>
        
        <div class="rocket-sourcer-stat-box">
            <h3>분석된 제품</h3>
            <div class="rocket-sourcer-stat-value">
                <?php echo esc_html(get_option('rocket_sourcer_analyzed_products_count', '0')); ?>
            </div>
        </div>
        
        <div class="rocket-sourcer-stat-box">
            <h3>저장된 제품</h3>
            <div class="rocket-sourcer-stat-value">
                <?php echo esc_html(get_option('rocket_sourcer_saved_products_count', '0')); ?>
            </div>
        </div>
    </div>
    
    <div class="rocket-sourcer-dashboard-actions">
        <div class="rocket-sourcer-action-box">
            <h3>키워드 분석</h3>
            <p>새로운 키워드를 분석하고 관련 제품을 찾아보세요.</p>
            <a href="#" class="button button-primary rocket-sourcer-analyze-keywords">키워드 분석하기</a>
        </div>
        
        <div class="rocket-sourcer-action-box">
            <h3>제품 분석</h3>
            <p>제품의 가격, 리뷰, 판매자 정보 등을 분석해보세요.</p>
            <a href="#" class="button button-primary rocket-sourcer-analyze-products">제품 분석하기</a>
        </div>
        
        <div class="rocket-sourcer-action-box">
            <h3>수익성 계산기</h3>
            <p>제품의 예상 수익과 ROI를 계산해보세요.</p>
            <a href="#" class="button button-primary rocket-sourcer-calculator">계산기 열기</a>
        </div>
    </div>
    
    <div class="rocket-sourcer-dashboard-recent">
        <h2>최근 분석된 키워드</h2>
        <div class="rocket-sourcer-recent-keywords">
            <?php
            $recent_keywords = get_option('rocket_sourcer_recent_keywords', array());
            if (!empty($recent_keywords)) :
                foreach ($recent_keywords as $keyword) :
            ?>
                <div class="rocket-sourcer-keyword-item">
                    <h4><?php echo esc_html($keyword['keyword']); ?></h4>
                    <p>검색량: <?php echo esc_html($keyword['search_volume']); ?></p>
                    <p>경쟁도: <?php echo esc_html($keyword['competition']); ?></p>
                    <p>분석일: <?php echo esc_html($keyword['date']); ?></p>
                </div>
            <?php
                endforeach;
            else :
            ?>
                <p>최근 분석된 키워드가 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="rocket-sourcer-dashboard-recent">
        <h2>최근 분석된 제품</h2>
        <div class="rocket-sourcer-recent-products">
            <?php
            $recent_products = get_option('rocket_sourcer_recent_products', array());
            if (!empty($recent_products)) :
                foreach ($recent_products as $product) :
            ?>
                <div class="rocket-sourcer-product-item">
                    <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>">
                    <h4><?php echo esc_html($product['title']); ?></h4>
                    <p>가격: <?php echo esc_html($product['price']); ?></p>
                    <p>평점: <?php echo esc_html($product['rating']); ?></p>
                    <p>분석일: <?php echo esc_html($product['date']); ?></p>
                </div>
            <?php
                endforeach;
            else :
            ?>
                <p>최근 분석된 제품이 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>
</div> 