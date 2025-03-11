<?php
/**
 * 수익성 계산기 페이지 템플릿
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-calculator">
    <div class="rocket-sourcer-calculator-header">
        <h1>수익성 계산기</h1>
        <p>제품의 예상 수익과 ROI를 계산해보세요.</p>
    </div>
    
    <div class="rocket-sourcer-calculator-form">
        <form id="rocket-sourcer-profit-calculator">
            <div class="rocket-sourcer-form-section">
                <h2>제품 정보</h2>
                
                <div class="rocket-sourcer-form-row">
                    <label for="product_name">제품명</label>
                    <input type="text" id="product_name" name="product_name" placeholder="제품명을 입력하세요">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="purchase_price">구매 가격 (원)</label>
                    <input type="number" id="purchase_price" name="purchase_price" min="0" step="100" placeholder="0">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="selling_price">판매 가격 (원)</label>
                    <input type="number" id="selling_price" name="selling_price" min="0" step="100" placeholder="0">
                </div>
            </div>
            
            <div class="rocket-sourcer-form-section">
                <h2>비용 정보</h2>
                
                <div class="rocket-sourcer-form-row">
                    <label for="shipping_cost">배송비 (원)</label>
                    <input type="number" id="shipping_cost" name="shipping_cost" min="0" step="100" placeholder="0">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="packaging_cost">포장비 (원)</label>
                    <input type="number" id="packaging_cost" name="packaging_cost" min="0" step="100" placeholder="0">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="marketing_cost">마케팅 비용 (원)</label>
                    <input type="number" id="marketing_cost" name="marketing_cost" min="0" step="100" placeholder="0">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="commission_rate">수수료율 (%)</label>
                    <input type="number" id="commission_rate" name="commission_rate" min="0" max="100" step="0.1" value="10">
                </div>
            </div>
            
            <div class="rocket-sourcer-form-section">
                <h2>판매 정보</h2>
                
                <div class="rocket-sourcer-form-row">
                    <label for="monthly_sales">예상 월 판매량 (개)</label>
                    <input type="number" id="monthly_sales" name="monthly_sales" min="0" step="1" placeholder="0">
                </div>
                
                <div class="rocket-sourcer-form-row">
                    <label for="return_rate">예상 반품률 (%)</label>
                    <input type="number" id="return_rate" name="return_rate" min="0" max="100" step="0.1" value="2">
                </div>
            </div>
            
            <div class="rocket-sourcer-form-actions">
                <button type="submit" class="button button-primary">계산하기</button>
                <button type="reset" class="button">초기화</button>
            </div>
        </form>
    </div>
    
    <div class="rocket-sourcer-calculator-results" style="display: none;">
        <h2>계산 결과</h2>
        
        <div class="rocket-sourcer-results-grid">
            <div class="rocket-sourcer-result-box">
                <h3>단위당 수익</h3>
                <div class="rocket-sourcer-result-value" id="profit_per_unit">0원</div>
                <div class="rocket-sourcer-result-detail">
                    <p>판매가: <span id="result_selling_price">0원</span></p>
                    <p>총 비용: <span id="result_total_cost">0원</span></p>
                </div>
            </div>
            
            <div class="rocket-sourcer-result-box">
                <h3>월 예상 수익</h3>
                <div class="rocket-sourcer-result-value" id="monthly_profit">0원</div>
                <div class="rocket-sourcer-result-detail">
                    <p>총 매출: <span id="result_monthly_revenue">0원</span></p>
                    <p>총 비용: <span id="result_monthly_cost">0원</span></p>
                </div>
            </div>
            
            <div class="rocket-sourcer-result-box">
                <h3>수익률</h3>
                <div class="rocket-sourcer-result-value" id="profit_margin">0%</div>
                <div class="rocket-sourcer-result-detail">
                    <p>ROI: <span id="result_roi">0%</span></p>
                    <p>마진율: <span id="result_margin">0%</span></p>
                </div>
            </div>
        </div>
        
        <div class="rocket-sourcer-results-chart">
            <canvas id="profitChart"></canvas>
        </div>
        
        <div class="rocket-sourcer-results-breakdown">
            <h3>비용 분석</h3>
            <table class="rocket-sourcer-breakdown-table">
                <thead>
                    <tr>
                        <th>항목</th>
                        <th>금액</th>
                        <th>비율</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>구매 비용</td>
                        <td id="breakdown_purchase">0원</td>
                        <td id="breakdown_purchase_ratio">0%</td>
                    </tr>
                    <tr>
                        <td>배송비</td>
                        <td id="breakdown_shipping">0원</td>
                        <td id="breakdown_shipping_ratio">0%</td>
                    </tr>
                    <tr>
                        <td>포장비</td>
                        <td id="breakdown_packaging">0원</td>
                        <td id="breakdown_packaging_ratio">0%</td>
                    </tr>
                    <tr>
                        <td>마케팅 비용</td>
                        <td id="breakdown_marketing">0원</td>
                        <td id="breakdown_marketing_ratio">0%</td>
                    </tr>
                    <tr>
                        <td>수수료</td>
                        <td id="breakdown_commission">0원</td>
                        <td id="breakdown_commission_ratio">0%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div> 