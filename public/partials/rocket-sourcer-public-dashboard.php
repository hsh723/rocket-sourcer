<?php
/**
 * 로켓소서 대시보드 템플릿
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/public/partials
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rocket-sourcer-container">
    <h2>쿠팡 소싱 도우미</h2>
    <div class="rocket-sourcer-dashboard">
        <div class="rocket-sourcer-section">
            <h3>키워드 분석</h3>
            <p>인기 키워드와 트렌드를 분석하여 최적의 상품을 찾아보세요.</p>
            <button class="rocket-sourcer-button" data-action="analyze-keywords">키워드 분석 시작</button>
        </div>

        <div class="rocket-sourcer-section">
            <h3>제품 분석</h3>
            <p>경쟁사 제품을 분석하고 수익성을 계산해보세요.</p>
            <button class="rocket-sourcer-button" data-action="analyze-products">제품 분석 시작</button>
        </div>

        <div class="rocket-sourcer-section">
            <h3>마진 계산기</h3>
            <p>판매가, 원가, 수수료를 고려한 예상 수익을 계산해보세요.</p>
            <button class="rocket-sourcer-button" data-action="calculate-margin">마진 계산하기</button>
        </div>
    </div>

    <div id="rocket-sourcer-results" class="rocket-sourcer-results">
        <p class="rocket-sourcer-notice">분석을 시작하면 이곳에 결과가 표시됩니다.</p>
    </div>
</div> 