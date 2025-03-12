<?php
/**
 * 설정 페이지
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 설정 저장 처리
if (isset($_POST['rocket_sourcer_save_settings'])) {
    if (check_admin_referer('rocket_sourcer_settings', 'rocket_sourcer_settings_nonce')) {
        // API 키 설정 저장
        update_option('rocket_sourcer_coupang_api_key', sanitize_text_field($_POST['coupang_api_key']));
        update_option('rocket_sourcer_coupang_secret_key', sanitize_text_field($_POST['coupang_secret_key']));
        
        // 크롤링 설정 저장
        update_option('rocket_sourcer_crawling_interval', sanitize_text_field($_POST['crawling_interval']));
        update_option('rocket_sourcer_max_products', intval($_POST['max_products']));
        
        // 데이터 저장 설정
        update_option('rocket_sourcer_data_retention', intval($_POST['data_retention']));
        
        // 알림 설정
        update_option('rocket_sourcer_enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0');
        update_option('rocket_sourcer_notification_email', sanitize_email($_POST['notification_email']));
        
        // 설정 저장 메시지 표시
        add_settings_error('rocket_sourcer_settings', 'settings_updated', '설정이 저장되었습니다.', 'updated');
    }
}

// 저장된 설정 불러오기
$coupang_api_key = get_option('rocket_sourcer_coupang_api_key', '');
$coupang_secret_key = get_option('rocket_sourcer_coupang_secret_key', '');
$crawling_interval = get_option('rocket_sourcer_crawling_interval', 'daily');
$max_products = get_option('rocket_sourcer_max_products', 100);
$data_retention = get_option('rocket_sourcer_data_retention', 30);
$enable_notifications = get_option('rocket_sourcer_enable_notifications', '0');
$notification_email = get_option('rocket_sourcer_notification_email', get_option('admin_email'));
?>

<div class="wrap rocket-sourcer-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('rocket_sourcer_settings'); ?>
    
    <div class="rocket-sourcer-settings">
        <form method="post" action="">
            <?php wp_nonce_field('rocket_sourcer_settings', 'rocket_sourcer_settings_nonce'); ?>
            
            <div class="rocket-sourcer-settings-section">
                <h2>API 설정</h2>
                <p>쿠팡 파트너스 API 연동을 위한 설정입니다. <a href="https://partners.coupang.com/" target="_blank">쿠팡 파트너스</a>에서 API 키를 발급받을 수 있습니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="coupang_api_key">쿠팡 API 키</label></th>
                        <td>
                            <input type="text" id="coupang_api_key" name="coupang_api_key" value="<?php echo esc_attr($coupang_api_key); ?>" class="regular-text">
                            <p class="description">쿠팡 파트너스에서 발급받은 API 키를 입력하세요.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="coupang_secret_key">쿠팡 시크릿 키</label></th>
                        <td>
                            <input type="password" id="coupang_secret_key" name="coupang_secret_key" value="<?php echo esc_attr($coupang_secret_key); ?>" class="regular-text">
                            <p class="description">쿠팡 파트너스에서 발급받은 시크릿 키를 입력하세요.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="rocket-sourcer-settings-section">
                <h2>크롤링 설정</h2>
                <p>데이터 수집 주기 및 제한을 설정합니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="crawling_interval">크롤링 주기</label></th>
                        <td>
                            <select id="crawling_interval" name="crawling_interval">
                                <option value="hourly" <?php selected($crawling_interval, 'hourly'); ?>>매시간</option>
                                <option value="daily" <?php selected($crawling_interval, 'daily'); ?>>매일</option>
                                <option value="weekly" <?php selected($crawling_interval, 'weekly'); ?>>매주</option>
                                <option value="manual" <?php selected($crawling_interval, 'manual'); ?>>수동 실행</option>
                            </select>
                            <p class="description">데이터 수집 주기를 선택하세요.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_products">최대 제품 수</label></th>
                        <td>
                            <input type="number" id="max_products" name="max_products" value="<?php echo esc_attr($max_products); ?>" class="small-text" min="10" max="500">
                            <p class="description">키워드당 수집할 최대 제품 수를 설정하세요. (10-500)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="rocket-sourcer-settings-section">
                <h2>데이터 관리</h2>
                <p>데이터 보관 기간 및 정리 설정입니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="data_retention">데이터 보관 기간</label></th>
                        <td>
                            <input type="number" id="data_retention" name="data_retention" value="<?php echo esc_attr($data_retention); ?>" class="small-text" min="1" max="365">
                            <span>일</span>
                            <p class="description">수집된 데이터를 보관할 기간을 설정하세요. 기간이 지난 데이터는 자동으로 삭제됩니다.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">데이터 정리</th>
                        <td>
                            <button type="button" id="clear-cache" class="button">캐시 비우기</button>
                            <button type="button" id="clear-keywords" class="button">키워드 데이터 정리</button>
                            <button type="button" id="clear-products" class="button">제품 데이터 정리</button>
                            <p class="description">캐시 및 오래된 데이터를 수동으로 정리합니다.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="rocket-sourcer-settings-section">
                <h2>알림 설정</h2>
                <p>제품 추천 및 시스템 알림 설정입니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enable_notifications">알림 활성화</label></th>
                        <td>
                            <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" <?php checked($enable_notifications, '1'); ?>>
                            <span>이메일 알림 활성화</span>
                            <p class="description">새로운 제품 추천 및 시스템 알림을 이메일로 받습니다.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notification_email">알림 이메일</label></th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                            <p class="description">알림을 받을 이메일 주소를 입력하세요.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="rocket-sourcer-settings-section">
                <h2>시스템 정보</h2>
                <p>시스템 상태 및 정보입니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">플러그인 버전</th>
                        <td><?php echo ROCKET_SOURCER_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th scope="row">PHP 버전</th>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">MySQL 버전</th>
                        <td><?php echo $wpdb->db_version(); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">워드프레스 버전</th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">저장된 키워드</th>
                        <td>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'rocket_sourcer_keywords';
                            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                            echo $count ? $count : '0';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">저장된 제품</th>
                        <td>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'rocket_sourcer_products';
                            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                            echo $count ? $count : '0';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="rocket_sourcer_save_settings" class="button-primary" value="설정 저장">
            </p>
        </form>
    </div>
</div> 