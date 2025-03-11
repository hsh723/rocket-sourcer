<?php
/**
 * 제품 분석 페이지 템플릿
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rocket-sourcer-product-filters">
        <input type="text" id="rocket-sourcer-search" placeholder="제품명 검색...">
        
        <select id="rocket-sourcer-filter-category">
            <option value="">모든 카테고리</option>
            <option value="electronics">전자제품</option>
            <option value="fashion">패션</option>
            <option value="home">홈/리빙</option>
            <option value="beauty">뷰티</option>
            <option value="food">식품</option>
            <option value="sports">스포츠/레저</option>
        </select>
        
        <select id="rocket-sourcer-filter-sort">
            <option value="date">날짜 순</option>
            <option value="price_low">가격 낮은 순</option>
            <option value="price_high">가격 높은 순</option>
            <option value="rating">평점 순</option>
            <option value="reviews">리뷰 순</option>
        </select>
        
        <button id="rocket-sourcer-filter-apply" class="button">필터 적용</button>
    </div>
    
    <div class="rocket-sourcer-product-list">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>제품명</th>
                    <th>가격</th>
                    <th>평점</th>
                    <th>리뷰 수</th>
                    <th>판매자</th>
                    <th>카테고리</th>
                    <th>작업</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7">분석된 제품이 없습니다.</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="rocket-sourcer-product-detail" style="display: none;">
        <h2>제품 상세 정보</h2>
        
        <div class="rocket-sourcer-product-info">
            <div class="rocket-sourcer-product-image">
                <img src="" alt="제품 이미지">
            </div>
            
            <div class="rocket-sourcer-product-data">
                <h3 id="rocket-sourcer-product-title"></h3>
                <p id="rocket-sourcer-product-price"></p>
                <p id="rocket-sourcer-product-rating"></p>
                <p id="rocket-sourcer-product-reviews"></p>
                <p id="rocket-sourcer-product-seller"></p>
                <p id="rocket-sourcer-product-category"></p>
                <p id="rocket-sourcer-product-date"></p>
                
                <div class="rocket-sourcer-product-actions">
                    <a href="#" id="rocket-sourcer-product-url" class="button button-primary" target="_blank">제품 페이지 방문</a>
                    <button id="rocket-sourcer-product-save" class="button">저장하기</button>
                </div>
            </div>
        </div>
        
        <div class="rocket-sourcer-product-analysis">
            <h3>제품 분석</h3>
            
            <div class="rocket-sourcer-analysis-item">
                <h4>가격 분석</h4>
                <div id="rocket-sourcer-price-analysis"></div>
            </div>
            
            <div class="rocket-sourcer-analysis-item">
                <h4>경쟁 분석</h4>
                <div id="rocket-sourcer-competition-analysis"></div>
            </div>
            
            <div class="rocket-sourcer-analysis-item">
                <h4>리뷰 분석</h4>
                <div id="rocket-sourcer-review-analysis"></div>
            </div>
        </div>
    </div>
</div> 