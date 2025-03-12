<?php
/**
 * 공개 마진 계산기 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-calculator">
    <div class="rocket-sourcer-header">
        <h2>쿠팡 마진 계산기</h2>
        <p>제품 원가와 판매가를 입력하여 쿠팡에서의 실제 마진을 계산해보세요.</p>
    </div>
    
    <div class="rocket-sourcer-calculator-container">
        <div class="rocket-sourcer-calculator-form">
            <div class="rocket-sourcer-form-group">
                <label for="calculator-product-cost">제품 원가 (원)</label>
                <input type="number" id="calculator-product-cost" min="0" step="100" placeholder="제품 원가 입력">
            </div>
            
            <div class="rocket-sourcer-form-group">
                <label for="calculator-selling-price">판매가 (원)</label>
                <input type="number" id="calculator-selling-price" min="0" step="100" placeholder="판매가 입력">
            </div>
            
            <div class="rocket-sourcer-form-group">
                <label for="calculator-shipping-cost">배송비 (원)</label>
                <input type="number" id="calculator-shipping-cost" min="0" step="100" placeholder="배송비 입력">
            </div>
            
            <div class="rocket-sourcer-form-group">
                <label for="calculator-coupang-fee">쿠팡 수수료 (%)</label>
                <input type="number" id="calculator-coupang-fee" min="0" max="100" step="0.1" placeholder="쿠팡 수수료 입력" value="10">
            </div>
            
            <div class="rocket-sourcer-form-group">
                <label for="calculator-return-rate">예상 반품률 (%)</label>
                <input type="number" id="calculator-return-rate" min="0" max="100" step="0.1" placeholder="예상 반품률 입력" value="3">
            </div>
            
            <button type="button" id="calculator-calculate-margin" class="rocket-sourcer-button">계산하기</button>
        </div>
        
        <div class="rocket-sourcer-calculator-results" style="display: none;">
            <h3>계산 결과</h3>
            
            <div class="rocket-sourcer-results-container">
                <div class="rocket-sourcer-result-section">
                    <h4>기본 정보</h4>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>판매가:</span>
                        <span id="calculator-display-selling-price">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>제품 원가:</span>
                        <span id="calculator-display-product-cost">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>배송비:</span>
                        <span id="calculator-display-shipping-cost">0원</span>
                    </div>
                </div>
                
                <div class="rocket-sourcer-result-section">
                    <h4>수익 분석</h4>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>판매 수익:</span>
                        <span id="calculator-result-revenue">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>쿠팡 수수료:</span>
                        <span id="calculator-result-coupang-fee">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>반품 처리 비용:</span>
                        <span id="calculator-result-return-cost">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>총 비용:</span>
                        <span id="calculator-result-total-cost">0원</span>
                    </div>
                </div>
                
                <div class="rocket-sourcer-result-section">
                    <h4>이익 분석</h4>
                    
                    <div class="rocket-sourcer-result-item highlight">
                        <span>순 이익:</span>
                        <span id="calculator-result-net-profit">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item highlight">
                        <span>이익률:</span>
                        <span id="calculator-result-profit-margin">0%</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>손익분기점 판매량:</span>
                        <span id="calculator-result-break-even">0개</span>
                    </div>
                </div>
                
                <div class="rocket-sourcer-result-section">
                    <h4>월간 예상 실적</h4>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>예상 월 판매량:</span>
                        <span id="calculator-result-monthly-sales">0개</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item">
                        <span>예상 월 매출:</span>
                        <span id="calculator-result-monthly-revenue">0원</span>
                    </div>
                    
                    <div class="rocket-sourcer-result-item highlight">
                        <span>예상 월 순이익:</span>
                        <span id="calculator-result-monthly-profit">0원</span>
                    </div>
                </div>
            </div>
            
            <div class="rocket-sourcer-chart-container">
                <h4>수익 구조 분석</h4>
                <div id="calculator-profit-chart">
                    <!-- 차트가 여기에 렌더링됩니다 -->
                </div>
            </div>
            
            <div class="rocket-sourcer-recommendations">
                <h4>추천 사항</h4>
                <ul id="calculator-recommendations">
                    <!-- 추천 사항이 여기에 동적으로 추가됩니다 -->
                </ul>
            </div>
        </div>
    </div>
</div> 