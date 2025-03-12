<?php
/**
 * 키워드 분석 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rocket-sourcer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rocket-sourcer-keywords">
        <div class="rocket-sourcer-keywords-search">
            <h2>키워드 분석</h2>
            <p>분석하고 싶은 카테고리나 키워드를 입력하세요. 인기 키워드와 경쟁 강도 정보를 확인할 수 있습니다.</p>
            
            <div class="rocket-sourcer-search-form">
                <div class="rocket-sourcer-form-group">
                    <label for="search-type">검색 유형</label>
                    <select id="search-type">
                        <option value="category">카테고리</option>
                        <option value="keyword">키워드</option>
                    </select>
                </div>
                
                <div class="rocket-sourcer-form-group category-select" id="category-select-group">
                    <label for="category">카테고리 선택</label>
                    <select id="category">
                        <option value="">카테고리 선택</option>
                        <option value="fashion">패션의류/잡화</option>
                        <option value="beauty">뷰티</option>
                        <option value="baby">출산/유아동</option>
                        <option value="food">식품</option>
                        <option value="kitchen">주방용품</option>
                        <option value="household">생활용품</option>
                        <option value="interior">홈인테리어</option>
                        <option value="appliances">가전디지털</option>
                        <option value="sports">스포츠/레저</option>
                        <option value="car">자동차용품</option>
                        <option value="hobby">완구/취미</option>
                        <option value="book">도서/음반/DVD</option>
                        <option value="pet">반려동물용품</option>
                    </select>
                </div>
                
                <div class="rocket-sourcer-form-group keyword-input" id="keyword-input-group" style="display: none;">
                    <label for="keyword">키워드 입력</label>
                    <input type="text" id="keyword" placeholder="분석할 키워드 입력">
                </div>
                
                <div class="rocket-sourcer-form-group">
                    <label for="search-limit">검색 결과 수</label>
                    <select id="search-limit">
                        <option value="10">10개</option>
                        <option value="20">20개</option>
                        <option value="50">50개</option>
                        <option value="100">100개</option>
                    </select>
                </div>
                
                <button type="button" id="analyze-keywords" class="button button-primary">분석 시작</button>
            </div>
        </div>
        
        <div class="rocket-sourcer-keywords-results" style="display: none;">
            <h3>분석 결과</h3>
            <p class="rocket-sourcer-results-summary">총 <span id="results-count">0</span>개의 키워드를 찾았습니다.</p>
            
            <div class="rocket-sourcer-table-container">
                <table class="rocket-sourcer-keywords-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>키워드</th>
                            <th>검색량</th>
                            <th>경쟁 강도</th>
                            <th>추천 점수</th>
                            <th>추세</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody id="keywords-results">
                        <!-- 결과가 여기에 동적으로 추가됩니다 -->
                    </tbody>
                </table>
            </div>
            
            <div class="rocket-sourcer-pagination">
                <button type="button" id="prev-page" class="button" disabled>이전</button>
                <span id="page-info">페이지 1 / 1</span>
                <button type="button" id="next-page" class="button" disabled>다음</button>
            </div>
            
            <div class="rocket-sourcer-export-actions">
                <button type="button" id="save-selected" class="button">선택 항목 저장</button>
                <button type="button" id="export-csv" class="button">CSV로 내보내기</button>
            </div>
        </div>
        
        <div class="rocket-sourcer-saved-keywords">
            <h3>저장된 키워드</h3>
            
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'rocket_sourcer_keywords';
            $keywords = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10");
            
            if ($keywords && count($keywords) > 0) :
            ?>
            
            <div class="rocket-sourcer-table-container">
                <table class="rocket-sourcer-saved-keywords-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>키워드</th>
                            <th>검색량</th>
                            <th>경쟁 강도</th>
                            <th>카테고리</th>
                            <th>저장 날짜</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keywords as $keyword) : ?>
                        <tr>
                            <td><?php echo esc_html($keyword->keyword); ?></td>
                            <td><?php echo esc_html($keyword->search_volume); ?></td>
                            <td><?php echo esc_html($keyword->competition); ?></td>
                            <td><?php echo esc_html($keyword->category); ?></td>
                            <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($keyword->created_at))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-products&keyword_id=' . $keyword->id); ?>" class="button button-small">제품 분석</a>
                                <button type="button" class="button button-small delete-keyword" data-id="<?php echo $keyword->id; ?>">삭제</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php else : ?>
            
            <p>저장된 키워드가 없습니다. 키워드 분석 결과에서 키워드를 저장해보세요.</p>
            
            <?php endif; ?>
            
            <a href="<?php echo admin_url('admin.php?page=rocket-sourcer-keywords&view=all'); ?>" class="button">모든 키워드 보기</a>
        </div>
    </div>
</div> 