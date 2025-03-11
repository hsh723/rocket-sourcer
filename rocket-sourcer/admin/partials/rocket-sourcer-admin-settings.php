<?php
/**
 * 설정 페이지 템플릿
 *
 * @package RocketSourcer
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('rocket_sourcer_options');
        do_settings_sections('rocket_sourcer_options');
        ?>
        
        <div class="rocket-sourcer-settings-section">
            <h2>API 설정</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_coupang_access_key">쿠팡 Access Key</label>
                    </th>
                    <td>
                        <input type="text" id="rocket_sourcer_coupang_access_key" 
                               name="rocket_sourcer_options[coupang_access_key]" 
                               value="<?php echo esc_attr(get_option('rocket_sourcer_coupang_access_key')); ?>" 
                               class="regular-text">
                        <p class="description">쿠팡 파트너스 API Access Key를 입력하세요.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_coupang_secret_key">쿠팡 Secret Key</label>
                    </th>
                    <td>
                        <input type="password" id="rocket_sourcer_coupang_secret_key" 
                               name="rocket_sourcer_options[coupang_secret_key]" 
                               value="<?php echo esc_attr(get_option('rocket_sourcer_coupang_secret_key')); ?>" 
                               class="regular-text">
                        <p class="description">쿠팡 파트너스 API Secret Key를 입력하세요.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="rocket-sourcer-settings-section">
            <h2>검색 설정</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_search_limit">검색 결과 제한</label>
                    </th>
                    <td>
                        <input type="number" id="rocket_sourcer_search_limit" 
                               name="rocket_sourcer_options[search_limit]" 
                               value="<?php echo esc_attr(get_option('rocket_sourcer_search_limit', '50')); ?>" 
                               min="10" max="100" step="10">
                        <p class="description">한 번에 표시할 검색 결과의 수를 설정하세요. (10-100)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_cache_duration">캐시 유지 시간</label>
                    </th>
                    <td>
                        <input type="number" id="rocket_sourcer_cache_duration" 
                               name="rocket_sourcer_options[cache_duration]" 
                               value="<?php echo esc_attr(get_option('rocket_sourcer_cache_duration', '24')); ?>" 
                               min="1" max="72">
                        <p class="description">검색 결과 캐시 유지 시간을 설정하세요. (시간 단위, 1-72)</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="rocket-sourcer-settings-section">
            <h2>디스플레이 설정</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">제품 표시 방식</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">제품 표시 방식</legend>
                            <label>
                                <input type="radio" name="rocket_sourcer_options[display_type]" 
                                       value="grid" <?php checked(get_option('rocket_sourcer_display_type', 'grid'), 'grid'); ?>>
                                그리드 뷰
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="rocket_sourcer_options[display_type]" 
                                       value="list" <?php checked(get_option('rocket_sourcer_display_type'), 'list'); ?>>
                                리스트 뷰
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rocket_sourcer_items_per_page">페이지당 항목 수</label>
                    </th>
                    <td>
                        <select id="rocket_sourcer_items_per_page" name="rocket_sourcer_options[items_per_page]">
                            <option value="12" <?php selected(get_option('rocket_sourcer_items_per_page'), '12'); ?>>12</option>
                            <option value="24" <?php selected(get_option('rocket_sourcer_items_per_page'), '24'); ?>>24</option>
                            <option value="36" <?php selected(get_option('rocket_sourcer_items_per_page'), '36'); ?>>36</option>
                            <option value="48" <?php selected(get_option('rocket_sourcer_items_per_page'), '48'); ?>>48</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button('설정 저장'); ?>
    </form>
</div> 