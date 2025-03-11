<?php
/**
 * 키워드 분석 페이지 템플릿
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
    
    <div class="rocket-sourcer-keyword-search">
        <h2>키워드 분석</h2>
        <p>분석할 키워드를 입력하세요. 여러 키워드는 쉼표로 구분하여 입력할 수 있습니다.</p>
        
        <form method="post" action="">
            <div class="rocket-sourcer-form-row">
                <label for="rocket-sourcer-keywords">키워드</label>
                <textarea id="rocket-sourcer-keywords" name="keywords" rows="4" placeholder="예: 블루투스 이어폰, 스마트워치, 노트북 파우치"></textarea>
            </div>
            
            <div class="rocket-sourcer-form-row">
                <label for="rocket-sourcer-category">카테고리</label>
                <select id="rocket-sourcer-category" name="category">
                    <option value="">카테고리 선택</option>
                    <option value="electronics">전자제품</option>
                    <option value="fashion">패션</option>
                    <option value="home">홈/리빙</option>
                    <option value="beauty">뷰티</option>
                    <option value="food">식품</option>
                    <option value="sports">스포츠/레저</option>
                </select>
            </div>
            
            <div class="rocket-sourcer-form-actions">
                <button type="submit" class="button button-primary">키워드 분석하기</button>
            </div>
        </form>
    </div>
    
    <div class="rocket-sourcer-keyword-results">
        <h2>분석 결과</h2>
        
        <div class="rocket-sourcer-filters">
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
                <option value="search_volume">검색량 순</option>
                <option value="competition">경쟁도 순</option>
                <option value="date">날짜 순</option>
            </select>
            
            <button id="rocket-sourcer-filter-apply" class="button">필터 적용</button>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>키워드</th>
                    <th>검색량</th>
                    <th>경쟁도</th>
                    <th>카테고리</th>
                    <th>분석 날짜</th>
                    <th>작업</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6">분석된 키워드가 없습니다.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div> 