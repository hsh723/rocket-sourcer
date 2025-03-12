<?php
/**
 * Rocket Sourcer 설정 페이지
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/admin/partials
 */

// 직접 접근 방지
if (!defined('WPINC')) {
    die;
}

// 설정 저장 처리
if (isset($_POST['rocket_sourcer_save_settings'])) {
    if (check_admin_referer('rocket_sourcer_settings', 'rocket_sourcer_settings_nonce')) {
        // API 키 설정 저장
        if (isset($_POST['rocket_sourcer_api_key'])) {
            update_option('rocket_sourcer_api_key', sanitize_text_field($_POST['rocket_sourcer_api_key']));
        }
        
        // 기타 설정 저장
        $settings = array(
            'auto_analysis' => isset($_POST['auto_analysis']) ? '1' : '0',
            'save_results' => isset($_POST['save_results']) ? '1' : '0',
            'result_lifetime' => intval($_POST['result_lifetime']),
            'notification_email' => sanitize_email($_POST['notification_email']),
            'currency' => sanitize_text_field($_POST['currency']),
            'language' => sanitize_text_field($_POST['language'])
        );
        update_option('rocket_sourcer_settings', $settings);
        
        echo '<div class="notice notice-success"><p>설정이 저장되었습니다.</p></div>';
    }
}

// 현재 설정 가져오기
$api_key = get_option('rocket_sourcer_api_key', '');
$settings = get_option('rocket_sourcer_settings', array(
    'auto_analysis' => '0',
    'save_results' => '1',
    'result_lifetime' => 30,
    'notification_email' => get_option('admin_email'),
    'currency' => 'KRW',
    'language' => 'ko'
));
?>

<div class="wrap rocket-sourcer-settings">
    <h1 class="wp-heading-inline">Rocket Sourcer 설정</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rocket_sourcer_settings', 'rocket_sourcer_settings_nonce'); ?>
        
        <!-- API 설정 -->
        <div class="settings-section">
            <h2>API 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_api_key">API 키</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="rocket_sourcer_api_key" 
                               id="rocket_sourcer_api_key"
                               value="<?php echo esc_attr($api_key); ?>"
                               class="regular-text"
                               required>
                        <p class="description">
                            API 키가 없으신가요? <a href="https://rocketsourcer.com/get-api-key" target="_blank">여기</a>에서 발급받으세요.
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 분석 설정 -->
        <div class="settings-section">
            <h2>분석 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">자동 분석</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="auto_analysis" 
                                   value="1"
                                   <?php checked($settings['auto_analysis'], '1'); ?>>
                            새로운 제품이 등록될 때 자동으로 분석 실행
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">결과 저장</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="save_results" 
                                   value="1"
                                   <?php checked($settings['save_results'], '1'); ?>>
                            분석 결과 저장
                        </label>
                        <p class="description">분석 결과를 데이터베이스에 저장하여 나중에 참조할 수 있습니다.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="result_lifetime">결과 보관 기간</label>
                    </th>
                    <td>
                        <input type="number" 
                               name="result_lifetime" 
                               id="result_lifetime"
                               value="<?php echo esc_attr($settings['result_lifetime']); ?>"
                               min="1"
                               max="365"
                               class="small-text">
                        일
                        <p class="description">분석 결과를 보관할 기간을 설정합니다. 기간이 지난 결과는 자동으로 삭제됩니다.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 알림 설정 -->
        <div class="settings-section">
            <h2>알림 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="notification_email">알림 이메일</label>
                    </th>
                    <td>
                        <input type="email" 
                               name="notification_email" 
                               id="notification_email"
                               value="<?php echo esc_attr($settings['notification_email']); ?>"
                               class="regular-text">
                        <p class="description">분석 완료 및 중요 알림을 받을 이메일 주소를 입력하세요.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 지역화 설정 -->
        <div class="settings-section">
            <h2>지역화 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="currency">통화</label>
                    </th>
                    <td>
                        <select name="currency" id="currency">
                            <option value="KRW" <?php selected($settings['currency'], 'KRW'); ?>>원화 (₩)</option>
                            <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>달러 ($)</option>
                            <option value="JPY" <?php selected($settings['currency'], 'JPY'); ?>>엔화 (¥)</option>
                            <option value="CNY" <?php selected($settings['currency'], 'CNY'); ?>>위안화 (¥)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="language">언어</label>
                    </th>
                    <td>
                        <select name="language" id="language">
                            <option value="ko" <?php selected($settings['language'], 'ko'); ?>>한국어</option>
                            <option value="en" <?php selected($settings['language'], 'en'); ?>>English</option>
                            <option value="ja" <?php selected($settings['language'], 'ja'); ?>>日本語</option>
                            <option value="zh" <?php selected($settings['language'], 'zh'); ?>>中文</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 데이터 관리 -->
        <div class="settings-section">
            <h2>데이터 관리</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">데이터베이스 정리</th>
                    <td>
                        <button type="button" 
                                class="button" 
                                id="clear-old-data">오래된 데이터 정리</button>
                        <button type="button" 
                                class="button" 
                                id="clear-all-data">모든 데이터 정리</button>
                        <p class="description">데이터베이스를 정리하여 공간을 확보합니다.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">데이터 내보내기</th>
                    <td>
                        <button type="button" 
                                class="button" 
                                id="export-data">데이터 내보내기</button>
                        <p class="description">분석 결과와 설정을 CSV 파일로 내보냅니다.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 저장 버튼 -->
        <p class="submit">
            <input type="submit" 
                   name="rocket_sourcer_save_settings" 
                   class="button button-primary" 
                   value="설정 저장">
        </p>
    </form>

    <!-- API 상태 확인 -->
    <div class="api-status">
        <h2>API 상태</h2>
        <div class="status-grid">
            <div class="status-card">
                <h3>API 연결 상태</h3>
                <div class="status-indicator" id="api-connection-status">
                    확인 중...
                </div>
            </div>
            <div class="status-card">
                <h3>일일 사용량</h3>
                <div class="usage-info" id="api-usage-info">
                    로딩 중...
                </div>
            </div>
            <div class="status-card">
                <h3>구독 정보</h3>
                <div class="subscription-info" id="subscription-info">
                    로딩 중...
                </div>
            </div>
        </div>
        <button type="button" 
                class="button" 
                id="check-api-status">API 상태 새로고침</button>
    </div>

    <!-- 도움말 -->
    <div class="settings-help">
        <h3>도움말</h3>
        <div class="help-content">
            <h4>API 키 관리</h4>
            <ul>
                <li>API 키는 안전한 곳에 보관하세요.</li>
                <li>API 키가 노출되었다면 즉시 재발급받으세요.</li>
                <li>무료 계정은 일일 분석 횟수가 제한됩니다.</li>
            </ul>

            <h4>데이터 관리</h4>
            <ul>
                <li>오래된 데이터는 자동으로 정리됩니다.</li>
                <li>중요한 분석 결과는 CSV로 내보내서 보관하세요.</li>
                <li>데이터베이스 정리는 되돌릴 수 없습니다.</li>
            </ul>
        </div>
    </div>
</div> 