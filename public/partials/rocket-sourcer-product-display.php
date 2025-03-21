<?php
/**
 * 단일 제품 표시 템플릿
 *
 * 이 파일은 단일 제품의 상세 정보를 표시하는 템플릿입니다.
 *
 * @link       https://www.yourwebsite.com
 * @since      1.0.0
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/public/partials
 */

// 보안 검사
if ( ! defined( 'ABSPATH' ) ) {
    exit; // 직접 접근 금지
}

// 제품 ID 가져오기
$product_id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;

// 제품 데이터 가져오기 (실제 구현 시 데이터베이스에서 가져와야 함)
$product = $this->get_product_by_id( $product_id );

// 제품이 존재하지 않는 경우
if ( empty( $product ) ) {
    echo '<div class="rocket-sourcer-error">제품을 찾을 수 없습니다.</div>';
    return;
}
?>

<div class="rocket-sourcer-container rocket-sourcer-product-detail">
    <div class="rocket-sourcer-product-header">
        <h1 class="rocket-sourcer-product-title"><?php echo esc_html( $product['title'] ); ?></h1>
        <div class="rocket-sourcer-product-meta">
            <span class="rocket-sourcer-product-category"><?php echo esc_html( $product['category'] ); ?></span>
            <span class="rocket-sourcer-product-date"><?php echo esc_html( $product['date'] ); ?></span>
        </div>
    </div>

    <div class="rocket-sourcer-product-content">
        <div class="rocket-sourcer-product-gallery">
            <div class="rocket-sourcer-product-main-image">
                <img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>">
            </div>
            <?php if ( ! empty( $product['gallery'] ) ) : ?>
                <div class="rocket-sourcer-product-thumbnails">
                    <?php foreach ( $product['gallery'] as $image ) : ?>
                        <div class="rocket-sourcer-product-thumbnail">
                            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rocket-sourcer-product-info">
            <div class="rocket-sourcer-product-price">
                <?php echo esc_html( '₩' . number_format( $product['price'] ) ); ?>
            </div>

            <div class="rocket-sourcer-product-description">
                <?php echo wpautop( esc_html( $product['description'] ) ); ?>
            </div>

            <?php if ( ! empty( $product['features'] ) ) : ?>
                <div class="rocket-sourcer-product-features">
                    <h3><?php _e( '제품 특징', 'rocket-sourcer' ); ?></h3>
                    <ul>
                        <?php foreach ( $product['features'] as $feature ) : ?>
                            <li><?php echo esc_html( $feature ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $product['specifications'] ) ) : ?>
                <div class="rocket-sourcer-product-specifications">
                    <h3><?php _e( '제품 사양', 'rocket-sourcer' ); ?></h3>
                    <table>
                        <?php foreach ( $product['specifications'] as $key => $value ) : ?>
                            <tr>
                                <th><?php echo esc_html( $key ); ?></th>
                                <td><?php echo esc_html( $value ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <div class="rocket-sourcer-product-actions">
                <?php if ( is_user_logged_in() ) : ?>
                    <button class="rocket-sourcer-product-save" data-product-id="<?php echo esc_attr( $product_id ); ?>">
                        <?php _e( '제품 저장', 'rocket-sourcer' ); ?>
                    </button>
                <?php endif; ?>

                <?php if ( ! empty( $product['source_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $product['source_url'] ); ?>" class="rocket-sourcer-product-source" target="_blank">
                        <?php _e( '원본 사이트에서 보기', 'rocket-sourcer' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ( ! empty( $product['related_products'] ) ) : ?>
        <div class="rocket-sourcer-related-products">
            <h2><?php _e( '관련 제품', 'rocket-sourcer' ); ?></h2>
            <div class="rocket-sourcer-products-grid">
                <?php foreach ( $product['related_products'] as $related_product ) : ?>
                    <div class="rocket-sourcer-product-card" data-product-id="<?php echo esc_attr( $related_product['id'] ); ?>">
                        <div class="rocket-sourcer-product-image">
                            <img src="<?php echo esc_url( $related_product['image'] ); ?>" alt="<?php echo esc_attr( $related_product['title'] ); ?>">
                        </div>
                        <div class="rocket-sourcer-product-details">
                            <h3 class="rocket-sourcer-product-title"><?php echo esc_html( $related_product['title'] ); ?></h3>
                            <div class="rocket-sourcer-product-price"><?php echo esc_html( '₩' . number_format( $related_product['price'] ) ); ?></div>
                            <div class="rocket-sourcer-product-buttons">
                                <a href="<?php echo esc_url( add_query_arg( 'id', $related_product['id'], get_permalink() ) ); ?>" class="rocket-sourcer-product-button rocket-sourcer-product-view">
                                    <?php _e( '상세 보기', 'rocket-sourcer' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // 제품 저장 버튼 클릭 이벤트
        $('.rocket-sourcer-product-save').on('click', function() {
            const productId = $(this).data('product-id');
            
            $.ajax({
                url: rocket_sourcer_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'rocket_sourcer_save_product',
                    security: rocket_sourcer_public.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '제품 저장 중 오류가 발생했습니다.');
                    }
                },
                error: function() {
                    alert('서버 연결 중 오류가 발생했습니다. 나중에 다시 시도해주세요.');
                }
            });
        });
        
        // 제품 갤러리 썸네일 클릭 이벤트
        $('.rocket-sourcer-product-thumbnail img').on('click', function() {
            const newSrc = $(this).attr('src');
            $('.rocket-sourcer-product-main-image img').attr('src', newSrc);
        });
    });
</script>
