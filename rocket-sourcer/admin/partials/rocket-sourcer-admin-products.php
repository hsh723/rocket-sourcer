<?php
/**
 * 제품 분석 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 키워드 ID가 전달되었는지 확인
$keyword_id = isset($_GET['keyword_id']) ? intval($_GET['keyword_id']) : 0;
$keyword = '';

if ($keyword_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rocket_sourcer_keywords';
    $keyword_obj = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $keyword_id));
    
    if ($keyword_obj) {
        $keyword = $keyword_obj->keyword;
    }
}
?>

<div class="wrap rocket-sourcer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rocket-sourcer-products">
        <div class="rocket-sourcer-products-search">
            <h2>제품 분석</h2>
            <p>분석하고 싶은 키워드를 입력하거나 저장된 키워드를 선택하세요. 쿠팡의 인기 제품과 경쟁 정보를 확인할 수 있습니다.</p>
            
            <div class="rocket-sourcer-search-form">
                <div class="rocket-sourcer-form-group">
                    <label for="product-keyword">키워드 입력</label>
                    <input type="text" id="product-keyword" placeholder="제품 키워드 입력" value="<?php echo esc_attr($keyword); ?>">
                </div>
                
                <div class="rocket-sourcer-form-group">
                    <label for="saved-keywords">저장된 키워드</label>
                    <select id="saved-keywords">
                        <option value="">저장된 키워드 선택</option>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'rocket_sourcer_keywords';
                        $keywords = $wpdb->get_results("SELECT id, keyword FROM $table_name ORDER BY keyword ASC");
                        
                        if ($keywords) {
                            foreach ($keywords as $k) {
                                $selected = ($k->id == $keyword_id) ? 'selected' : '';
                                echo '<option value="' . esc_attr($k->id) . '" ' . $selected . '>' . esc_html($k->keyword) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="rocket-sourcer-form-group">
                    <label for="product-limit">검색 결과 수</label>
                    <select id="product-limit">
                        <option value="10">10개</option>
                        <option value="20">20개</option>
                        <option value="30">30개</option>
                        <option value="50">50개</option>
                    </select>
                </div>
                
                <button type="button" id="analyze-products" class="button button-primary">분석 시작</button>
            </div>
        </div>
        
        <div class="rocket-sourcer-products-results" style="display: none;">
            <h3>분석 결과</h3>
            <p class="rocket-sourcer-results-summary">
                <span id="product-keyword-display"></span> 키워드로 검색한 결과입니다. 
                총 <span id="products-count">0</span>개의 제품을 찾았습니다.
            </p>
            
            <div class="rocket-sourcer-products-summary">
                <div class="rocket-sourcer-summary-item">
                    <h4>평균 가격</h4>
                    <div id="avg-price">0원</div>
                </div>
                
                <div class="rocket-sourcer-summary-item">
                    <h4>평균 평점</h4>
                    <div id="avg-rating">0.0</div>
                </div>
                
                <div class="rocket-sourcer-summary-item">
                    <h4>평균 리뷰 수</h4>
                    <div id="avg-reviews">0</div>
                </div>
                
                <div class="rocket-sourcer-summary-item">
                    <h4>로켓배송 비율</h4>
                    <div id="rocket-delivery-percent">0%</div>
                </div>
            </div>
            
            <div class="rocket-sourcer-products-container">
                <div id="products-grid" class="rocket-sourcer-products-grid">
                    <!-- 제품 결과가 여기에 동적으로 추가됩니다 -->
                </div>
            </div>
            
            <div class="rocket-sourcer-pagination">
                <button type="button" id="products-prev-page" class="button" disabled>이전</button>
                <span id="products-page-info">페이지 1 / 1</span>
                <button type="button" id="products-next-page" class="button" disabled>다음</button>
            </div>
            
            <div class="rocket-sourcer-export-actions">
                <button type="button" id="save-products" class="button">선택 제품 저장</button>
                <button type="button" id="export-products-csv" class="button">CSV로 내보내기</button>
            </div>
        </div>
        
        <div class="rocket-sourcer-product-analysis" style="display: none;">
            <h3>제품 상세 분석</h3>
            
            <div class="rocket-sourcer-product-details">
                <div class="rocket-sourcer-product-image">
                    <img id="product-image" src="" alt="제품 이미지">
                </div>
                
                <div class="rocket-sourcer-product-info">
                    <h4 id="product-title"></h4>
                    <div class="rocket-sourcer-product-meta">
                        <div class="rocket-sourcer-meta-item">
                            <span>가격:</span>
                            <span id="product-price"></span>
                        </div>
                        
                        <div class="rocket-sourcer-meta-item">
                            <span>평점:</span>
                            <span id="product-rating"></span>
                        </div>
                        
                        <div class="rocket-sourcer-meta-item">
                            <span>리뷰 수:</span>
                            <span id="product-reviews"></span>
                        </div>
                        
                        <div class="rocket-sourcer-meta-item">
                            <span>판매자:</span>
                            <span id="product-seller"></span>
                        </div>
                    </div>
                    
                    <div class="rocket-sourcer-product-actions">
                        <a href="#" id="product-url" target="_blank" class="button">쿠팡에서 보기</a>
                        <button type="button" id="find-similar" class="button">유사 제품 찾기</button>
                        <button type="button" id="analyze-reviews" class="button">리뷰 분석</button>
                    </div>
                </div>
            </div>
            
            <div class="rocket-sourcer-tabs">
                <div class="rocket-sourcer-tab-buttons">
                    <button type="button" class="rocket-sourcer-tab-button active" data-tab="margin">마진 분석</button>
                    <button type="button" class="rocket-sourcer-tab-button" data-tab="competition">경쟁 분석</button>
                    <button type="button" class="rocket-sourcer-tab-button" data-tab="reviews">리뷰 분석</button>
                    <button type="button" class="rocket-sourcer-tab-button" data-tab="sourcing">소싱 옵션</button>
                </div>
                
                <div class="rocket-sourcer-tab-content active" id="tab-margin">
                    <h4>마진 분석</h4>
                    
                    <div class="rocket-sourcer-margin-calculator">
                        <div class="rocket-sourcer-form-group">
                            <label for="product-cost-analysis">예상 원가 (원)</label>
                            <input type="number" id="product-cost-analysis" min="0" step="100" placeholder="예상 원가 입력">
                        </div>
                        
                        <div class="rocket-sourcer-form-group">
                            <label for="shipping-cost-analysis">배송비 (원)</label>
                            <input type="number" id="shipping-cost-analysis" min="0" step="100" placeholder="배송비 입력">
                        </div>
                        
                        <div class="rocket-sourcer-form-group">
                            <label for="selling-price-analysis">판매가 (원)</label>
                            <input type="number" id="selling-price-analysis" min="0" step="100" readonly>
                        </div>
                        
                        <div class="rocket-sourcer-form-group">
                            <label for="coupang-fee-analysis">쿠팡 수수료 (%)</label>
                            <input type="number" id="coupang-fee-analysis" min="0" max="100" step="0.1" value="10" readonly>
                        </div>
                        
                        <button type="button" id="calculate-margin-analysis" class="button button-primary">계산하기</button>
                    </div>
                    
                    <div class="rocket-sourcer-margin-results" style="display: none;">
                        <h5>마진 계산 결과</h5>
                        
                        <div class="rocket-sourcer-margin-result-grid">
                            <div class="rocket-sourcer-margin-result-item">
                                <span>순 이익:</span>
                                <span id="result-net-profit-analysis">0원</span>
                            </div>
                            
                            <div class="rocket-sourcer-margin-result-item">
                                <span>이익률:</span>
                                <span id="result-profit-margin-analysis">0%</span>
                            </div>
                            
                            <div class="rocket-sourcer-margin-result-item">
                                <span>월 예상 판매량:</span>
                                <span id="estimated-monthly-sales">0개</span>
                            </div>
                            
                            <div class="rocket-sourcer-margin-result-item">
                                <span>월 예상 수익:</span>
                                <span id="estimated-monthly-profit">0원</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="rocket-sourcer-tab-content" id="tab-competition">
                    <h4>경쟁 분석</h4>
                    <p>현재 선택된 제품과 경쟁 제품들의 비교 분석입니다.</p>
                    
                    <div class="rocket-sourcer-competition-charts">
                        <div class="rocket-sourcer-chart-container">
                            <h5>가격 분포</h5>
                            <div class="rocket-sourcer-chart" id="price-distribution-chart">
                                <!-- 차트가 여기에 렌더링됩니다 -->
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-chart-container">
                            <h5>리뷰 수 비교</h5>
                            <div class="rocket-sourcer-chart" id="reviews-comparison-chart">
                                <!-- 차트가 여기에 렌더링됩니다 -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="rocket-sourcer-competition-analysis">
                        <h5>경쟁 분석 요약</h5>
                        
                        <ul class="rocket-sourcer-competition-points">
                            <li>선택한 제품의 가격은 경쟁 제품 대비 <span id="price-comparison">평균적</span>입니다.</li>
                            <li>선택한 제품의 평점은 경쟁 제품 대비 <span id="rating-comparison">평균적</span>입니다.</li>
                            <li>선택한 제품의 리뷰 수는 경쟁 제품 대비 <span id="review-comparison">평균적</span>입니다.</li>
                            <li>추정 시장 규모: <span id="market-size">알 수 없음</span></li>
                            <li>추정 진입 난이도: <span id="entry-difficulty">중간</span></li>
                        </ul>
                    </div>
                </div>
                
                <div class="rocket-sourcer-tab-content" id="tab-reviews">
                    <h4>리뷰 분석</h4>
                    <p>제품 리뷰 분석 결과입니다. 주요 장단점과 소비자 의견을 확인할 수 있습니다.</p>
                    
                    <div class="rocket-sourcer-reviews-summary">
                        <div class="rocket-sourcer-review-stats">
                            <div class="rocket-sourcer-rating-overview">
                                <div class="rocket-sourcer-big-rating">0.0</div>
                                <div class="rocket-sourcer-stars">★★★★★</div>
                                <div class="rocket-sourcer-total-reviews">총 0개 리뷰</div>
                            </div>
                            
                            <div class="rocket-sourcer-rating-bars">
                                <div class="rocket-sourcer-rating-bar">
                                    <span>5점</span>
                                    <div class="rocket-sourcer-progress-bar">
                                        <div class="rocket-sourcer-progress" style="width: 0%"></div>
                                    </div>
                                    <span>0%</span>
                                </div>
                                
                                <div class="rocket-sourcer-rating-bar">
                                    <span>4점</span>
                                    <div class="rocket-sourcer-progress-bar">
                                        <div class="rocket-sourcer-progress" style="width: 0%"></div>
                                    </div>
                                    <span>0%</span>
                                </div>
                                
                                <div class="rocket-sourcer-rating-bar">
                                    <span>3점</span>
                                    <div class="rocket-sourcer-progress-bar">
                                        <div class="rocket-sourcer-progress" style="width: 10%"></div>
                                    </div>
                                    <span>10%</span>
                                </div>
                                
                                <div class="rocket-sourcer-rating-bar">
                                    <span>2점</span>
                                    <div class="rocket-sourcer-progress-bar">
                                        <div class="rocket-sourcer-progress" style="width: 3%"></div>
                                    </div>
                                    <span>3%</span>
                                </div>
                                
                                <div class="rocket-sourcer-rating-bar">
                                    <span>1점</span>
                                    <div class="rocket-sourcer-progress-bar">
                                        <div class="rocket-sourcer-progress" style="width: 2%"></div>
                                    </div>
                                    <span>2%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-review-keywords">
                            <h5>주요 키워드</h5>
                            <div class="rocket-sourcer-keyword-cloud" id="review-keywords">
                                <!-- 키워드 클라우드가 여기에 렌더링됩니다 -->
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-review-sentiment">
                            <h5>감성 분석</h5>
                            <div class="rocket-sourcer-sentiment-chart" id="sentiment-chart">
                                <!-- 감성 분석 차트가 여기에 렌더링됩니다 -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="rocket-sourcer-review-analysis">
                        <div class="rocket-sourcer-strengths">
                            <h5>주요 장점</h5>
                            <ul id="product-strengths">
                                <li>가성비가 좋음 (32건의 언급)</li>
                                <li>배송이 빠름 (28건의 언급)</li>
                                <li>품질이 좋음 (24건의 언급)</li>
                                <li>사용이 편리함 (18건의 언급)</li>
                            </ul>
                        </div>
                        
                        <div class="rocket-sourcer-weaknesses">
                            <h5>주요 단점</h5>
                            <ul id="product-weaknesses">
                                <li>내구성이 약함 (7건의 언급)</li>
                                <li>크기가 예상과 다름 (5건의 언급)</li>
                                <li>포장이 부실함 (3건의 언급)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="rocket-sourcer-review-samples">
                        <h5>샘플 리뷰</h5>
                        
                        <div class="rocket-sourcer-review-item positive">
                            <div class="rocket-sourcer-review-header">
                                <span class="rocket-sourcer-review-rating">★★★★★</span>
                                <span class="rocket-sourcer-review-author">홍길동</span>
                                <span class="rocket-sourcer-review-date">2023-05-12</span>
                            </div>
                            <div class="rocket-sourcer-review-content">
                                가격 대비 정말 만족합니다. 배송도 빠르고 품질도 좋네요. 다음에도 구매할 의사 있습니다.
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-review-item positive">
                            <div class="rocket-sourcer-review-header">
                                <span class="rocket-sourcer-review-rating">★★★★☆</span>
                                <span class="rocket-sourcer-review-author">김철수</span>
                                <span class="rocket-sourcer-review-date">2023-04-28</span>
                            </div>
                            <div class="rocket-sourcer-review-content">
                                생각보다 튼튼하고 사용하기 편리합니다. 배송이 조금 늦었지만 제품 자체는 만족스럽습니다.
                            </div>
                        </div>
                        
                        <div class="rocket-sourcer-review-item negative">
                            <div class="rocket-sourcer-review-header">
                                <span class="rocket-sourcer-review-rating">★★☆☆☆</span>
                                <span class="rocket-sourcer-review-author">이영희</span>
                                <span class="rocket-sourcer-review-date">2023-04-15</span>
                            </div>
                            <div class="rocket-sourcer-review-content">
                                한 달 사용했더니 벌써 고장났습니다. 내구성이 너무 약해요. 디자인은 마음에 들었는데 아쉽습니다.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="rocket-sourcer-tab-content" id="tab-sourcing">
                    <h4>소싱 옵션</h4>
                    <p>해외 소싱 사이트에서 유사 제품을 찾아봤습니다. 아래의 옵션 중에서 선택하세요.</p>
                    
                    <div class="rocket-sourcer-search-form">
                        <div class="rocket-sourcer-form-group">
                            <label for="sourcing-platform">소싱 플랫폼</label>
                            <select id="sourcing-platform">
                                <option value="1688">1688</option>
                                <option value="alibaba">Alibaba</option>
                                <option value="aliexpress">AliExpress</option>
                            </select>
                        </div>
                        
                        <button type="button" id="search-sourcing" class="button button-primary">검색하기</button>
                    </div>
                    
                    <div class="rocket-sourcer-sourcing-results" style="display: none;">
                        <div class="rocket-sourcer-sourcing-grid" id="sourcing-results-grid">
                            <!-- 소싱 결과가 여기에 동적으로 추가됩니다 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 