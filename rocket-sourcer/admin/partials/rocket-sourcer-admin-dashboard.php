<?php
/**
 * 관리자 대시보드 페이지 템플릿
 *
 * @since      1.0.0
 * @package    Rocket_Sourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 데이터베이스 인스턴스 생성
$db = new Rocket_Sourcer_DB();

// 통계 데이터 가져오기
$total_products = $db->get_products_count();
$recent_products = $db->get_products(5, 0, 'created_at', 'DESC');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rocket-sourcer-dashboard">
        <div class="rocket-sourcer-dashboard-header">
            <div class="rocket-sourcer-dashboard-welcome">
                <h2>로켓 소서에 오신 것을 환영합니다!</h2>
                <p>쿠팡 로켓그로스 셀러를 위한 소싱 추천 및 분석 도구입니다. 이 대시보드에서 제품 통계와 추천 제품을 확인할 수 있습니다.</p>
            </div>
        </div>
        
        <div class="rocket-sourcer-dashboard-stats">
            <div class="rocket-sourcer-stat-box">
                <h3>총 제품 수</h3>
                <div class="rocket-sourcer-stat-value"><?php echo esc_html($total_products); ?></div>
            </div>
            
            <div class="rocket-sourcer-stat-box">
                <h3>평균 ROI</h3>
                <div class="rocket-sourcer-stat-value">
                    <?php
                    // 평균 ROI 계산 로직 (예시)
                    echo '45%';
                    ?>
                </div>
            </div>
            
            <div class="rocket-sourcer-stat-box">
                <h3>최고 수익 제품</h3>
                <div class="rocket-sourcer-stat-value">
                    <?php
                    // 최고 수익 제품 로직 (예시)
                    echo '₩250,000';
                    ?>
                </div>
            </div>
            
            <div class="rocket-sourcer-stat-box">
                <h3>데이터 소스</h3>
                <div class="rocket-sourcer-stat-value">
                    <?php
                    // 데이터 소스 수 (예시)
                    $data_sources = get_option('rocket_sourcer_data_sources', array());
                    echo count($data_sources);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="rocket-sourcer-dashboard-recent">
            <h2>최근 추가된 제품</h2>
            
            <?php if (empty($recent_products)) : ?>
                <p>아직 추가된 제품이 없습니다.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>제품명</th>
                            <th>가격</th>
                            <th>카테고리</th>
                            <th>ROI</th>
                            <th>소스</th>
                            <th>추가일</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_products as $product) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($product->product_url); ?>" target="_blank">
                                        <?php echo esc_html($product->product_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(number_format($product->product_price)); ?>원</td>
                                <td><?php echo esc_html($product->product_category); ?></td>
                                <td><?php echo esc_html($product->product_roi); ?>%</td>
                                <td><?php echo esc_html($product->product_source); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($product->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="rocket-sourcer-view-all">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-products')); ?>" class="button button-primary">
                        모든 제품 보기
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="rocket-sourcer-dashboard-actions">
            <div class="rocket-sourcer-action-box">
                <h3>데이터 가져오기</h3>
                <p>쿠팡, 아마존, 알리익스프레스에서 새로운 제품 데이터를 가져옵니다.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-import')); ?>" class="button">
                    데이터 가져오기
                </a>
            </div>
            
            <div class="rocket-sourcer-action-box">
                <h3>설정 관리</h3>
                <p>API 키, 필터링 기준, 데이터 소스 등의 설정을 관리합니다.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rocket-sourcer-settings')); ?>" class="button">
                    설정 관리
                </a>
            </div>
        </div>
    </div>
</div> 