<?php
/**
 * 공개 대시보드 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-public-dashboard">
    <div class="rocket-sourcer-header">
        <h2>쿠팡 로켓그로스 소싱 도우미</h2>
        <p>쿠팡 로켓그로스 셀러를 위한 제품 소싱 및 분석 도구입니다.</p>
    </div>
    
    <div class="rocket-sourcer-search">
        <h3>키워드 분석</h3>
        <p>분석하고 싶은 키워드나 카테고리를 입력하세요.</p>
        
        <div class="rocket-sourcer-search-form">
            <input type="text" id="public-keyword" placeholder="키워드 입력 (예: 전자렌지 선반, 주방 수납장)">
            <select id="public-category">
                <option value="">카테고리 선택 (선택사항)</option>
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
            <button type="button" id="public-analyze-btn" class="rocket-sourcer-button">분석하기</button>
        </div>
    </div>
    
    <div class="rocket-sourcer-results" style="display: none;">
        <div class="rocket-sourcer-tabs">
            <div class="rocket-sourcer-tab-buttons">
                <button type="button" class="rocket-sourcer-tab-button active" data-tab="keywords">키워드 분석</button>
                <button type="button" class="rocket-sourcer-tab-button" data-tab="products">제품 분석</button>
                <button type="button" class="rocket-sourcer-tab-button" data-tab="calculator">마진 계산기</button>
            </div>
            
            <div class="rocket-sourcer-tab-content active" id="public-tab-keywords">
                <h3>키워드 분석 결과</h3>
                <p class="rocket-sourcer-results-summary">총 <span id="public-keywords-count">0</span>개의 키워드를 찾았습니다.</p>
                
                <div class="rocket-sourcer-table-container">
                    <table class="rocket-sourcer-keywords-table">
                        <thead>
                            <tr>
                                <th>키워드</th>
                                <th>검색량</th>
                                <th>경쟁 강도</th>
                                <th>추천 점수</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="public-keywords-results">
                            <!-- 결과가 여기에 동적으로 추가됩니다 -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="rocket-sourcer-tab-content" id="public-tab-products">
                <h3>제품 분석 결과</h3>
                <p class="rocket-sourcer-results-summary">총 <span id="public-products-count">0</span>개의 제품을 찾았습니다.</p>
                
                <div class="rocket-sourcer-products-summary">
                    <div class="rocket-sourcer-summary-item">
                        <h4>평균 가격</h4>
                        <div id="public-avg-price">0원</div>
                    </div>
                    
                    <div class="rocket-sourcer-summary-item">
                        <h4>평균 평점</h4>
                        <div id="public-avg-rating">0.0</div>
                    </div>
                    
                    <div class="rocket-sourcer-summary-item">
                        <h4>평균 리뷰 수</h4>
                        <div id="public-avg-reviews">0</div>
                    </div>
                    
                    <div class="rocket-sourcer-summary-item">
                        <h4>로켓배송 비율</h4>
                        <div id="public-rocket-delivery-percent">0%</div>
                    </div>
                </div>
                
                <div id="public-products-grid" class="rocket-sourcer-products-grid">
                    <!-- 제품 결과가 여기에 동적으로 추가됩니다 -->
                </div>
            </div>
            
            <div class="rocket-sourcer-tab-content" id="public-tab-calculator">
                <h3>마진 계산기</h3>
                <p>제품 원가와 판매가를 입력하여 마진을 계산해보세요.</p>
                
                <div class="rocket-sourcer-calculator-form">
                    <div class="rocket-sourcer-form-group">
                        <label for="public-product-cost">제품 원가 (원)</label>
                        <input type="number" id="public-product-cost" min="0" step="100" placeholder="제품 원가 입력">
                    </div>
                    
                    <div class="rocket-sourcer-form-group">
                        <label for="public-selling-price">판매가 (원)</label>
                        <input type="number" id="public-selling-price" min="0" step="100" placeholder="판매가 입력">
                    </div>
                    
                    <div class="rocket-sourcer-form-group">
                        <label for="public-shipping-cost">배송비 (원)</label>
                        <input type="number" id="public-shipping-cost" min="0" step="100" placeholder="배송비 입력">
                    </div>
                    
                    <div class="rocket-sourcer-form-group">
                        <label for="public-coupang-fee">쿠팡 수수료 (%)</label>
                        <input type="number" id="public-coupang-fee" min="0" max="100" step="0.1" placeholder="쿠팡 수수료 입력" value="10">
                    </div>
                    
                    <button type="button" id="public-calculate-margin" class="rocket-sourcer-button">계산하기</button>
                </div>
                
                <div class="rocket-sourcer-calculator-results" style="display: none;">
                    <h4>계산 결과</h4>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>판매 수익:</span>
                        <span id="public-result-revenue">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>총 비용:</span>
                        <span id="public-result-total-cost">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>순 이익:</span>
                        <span id="public-result-net-profit">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>이익률:</span>
                        <span id="public-result-profit-margin">0%</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>손익분기점 판매량:</span>
                        <span id="public-result-break-even">0개</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 