<?php
/**
 * 데이터 내보내기/가져오기 처리 클래스
 *
 * @package    Rocket_Sourcer
 * @subpackage Rocket_Sourcer/includes
 */

class Rocket_Sourcer_Import_Export {

    /**
     * 데이터베이스 테이블 목록
     */
    private $tables = array(
        'rocket_sourcer_keywords',
        'rocket_sourcer_products',
        'rocket_sourcer_margins',
        'rocket_sourcer_searches'
    );

    /**
     * 데이터 내보내기
     *
     * @param array $options 내보내기 옵션
     * @return array|WP_Error 내보내기 결과 또는 오류
     */
    public function export_data($options = array()) {
        global $wpdb;

        try {
            $data = array(
                'version' => ROCKET_SOURCER_VERSION,
                'timestamp' => current_time('mysql'),
                'tables' => array()
            );

            // 테이블별 데이터 내보내기
            foreach ($this->tables as $table) {
                if (!empty($options['tables']) && !in_array($table, $options['tables'])) {
                    continue;
                }

                $table_name = $wpdb->prefix . $table;
                $results = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);

                if ($results === false) {
                    throw new Exception($wpdb->last_error);
                }

                $data['tables'][$table] = $results;
            }

            // 설정 데이터 내보내기
            if (empty($options['tables']) || in_array('settings', $options['tables'])) {
                $data['settings'] = get_option('rocket_sourcer_settings', array());
            }

            return $data;
        } catch (Exception $e) {
            return new WP_Error('export_error', $e->getMessage());
        }
    }

    /**
     * 데이터 가져오기
     *
     * @param array $data 가져올 데이터
     * @param array $options 가져오기 옵션
     * @return bool|WP_Error 성공 여부 또는 오류
     */
    public function import_data($data, $options = array()) {
        global $wpdb;

        try {
            // 버전 확인
            if (empty($data['version'])) {
                throw new Exception('유효하지 않은 데이터 형식입니다.');
            }

            // 트랜잭션 시작
            $wpdb->query('START TRANSACTION');

            // 테이블별 데이터 가져오기
            foreach ($data['tables'] as $table => $records) {
                if (!in_array($table, $this->tables)) {
                    continue;
                }

                if (!empty($options['tables']) && !in_array($table, $options['tables'])) {
                    continue;
                }

                $table_name = $wpdb->prefix . $table;

                // 기존 데이터 삭제 (옵션에 따라)
                if (!empty($options['clear_existing'])) {
                    $wpdb->query("TRUNCATE TABLE {$table_name}");
                }

                // 새 데이터 삽입
                foreach ($records as $record) {
                    $result = $wpdb->insert(
                        $table_name,
                        $record,
                        $this->get_column_formats($table)
                    );

                    if ($result === false) {
                        throw new Exception($wpdb->last_error);
                    }
                }
            }

            // 설정 데이터 가져오기
            if (isset($data['settings']) && (empty($options['tables']) || in_array('settings', $options['tables']))) {
                update_option('rocket_sourcer_settings', $data['settings']);
            }

            // 트랜잭션 커밋
            $wpdb->query('COMMIT');

            return true;
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $wpdb->query('ROLLBACK');
            return new WP_Error('import_error', $e->getMessage());
        }
    }

    /**
     * 데이터 내보내기 파일 생성
     *
     * @param array $data 내보낼 데이터
     * @return string|WP_Error 파일 경로 또는 오류
     */
    public function create_export_file($data) {
        try {
            $upload_dir = wp_upload_dir();
            $filename = 'rocket-sourcer-export-' . date('Y-m-d-H-i-s') . '.json';
            $filepath = $upload_dir['path'] . '/' . $filename;

            // JSON 파일 생성
            $json_data = wp_json_encode($data, JSON_PRETTY_PRINT);
            if ($json_data === false) {
                throw new Exception('데이터를 JSON으로 변환할 수 없습니다.');
            }

            // 파일 저장
            if (file_put_contents($filepath, $json_data) === false) {
                throw new Exception('파일을 저장할 수 없습니다.');
            }

            return array(
                'path' => $filepath,
                'url' => $upload_dir['url'] . '/' . $filename
            );
        } catch (Exception $e) {
            return new WP_Error('file_creation_error', $e->getMessage());
        }
    }

    /**
     * 가져오기 파일 처리
     *
     * @param array $file $_FILES 배열의 파일 정보
     * @return array|WP_Error 파일 데이터 또는 오류
     */
    public function process_import_file($file) {
        try {
            // 파일 유효성 검사
            if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
                throw new Exception('업로드된 파일을 찾을 수 없습니다.');
            }

            // 파일 확장자 확인
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'json') {
                throw new Exception('JSON 파일만 가져올 수 있습니다.');
            }

            // 파일 내용 읽기
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new Exception('파일을 읽을 수 없습니다.');
            }

            // JSON 디코딩
            $data = json_decode($content, true);
            if ($data === null) {
                throw new Exception('유효하지 않은 JSON 형식입니다.');
            }

            return $data;
        } catch (Exception $e) {
            return new WP_Error('file_processing_error', $e->getMessage());
        }
    }

    /**
     * 테이블 컬럼 포맷 가져오기
     *
     * @param string $table 테이블 이름
     * @return array 컬럼 포맷 배열
     */
    private function get_column_formats($table) {
        $formats = array(
            'rocket_sourcer_keywords' => array(
                'id' => '%d',
                'keyword' => '%s',
                'category' => '%s',
                'volume' => '%d',
                'competition' => '%s',
                'trend' => '%s',
                'trend_percentage' => '%f',
                'estimated_cpc' => '%f',
                'created_at' => '%s',
                'updated_at' => '%s'
            ),
            'rocket_sourcer_products' => array(
                'id' => '%d',
                'product_id' => '%s',
                'title' => '%s',
                'price' => '%d',
                'original_price' => '%d',
                'category' => '%s',
                'rating' => '%f',
                'review_count' => '%d',
                'seller' => '%s',
                'daily_sales' => '%d',
                'monthly_sales' => '%d',
                'market_share' => '%f',
                'competition_level' => '%s',
                'created_at' => '%s',
                'updated_at' => '%s'
            ),
            'rocket_sourcer_margins' => array(
                'id' => '%d',
                'product_id' => '%s',
                'selling_price' => '%d',
                'cost_price' => '%d',
                'shipping_cost' => '%d',
                'commission' => '%d',
                'additional_costs' => '%d',
                'margin_rate' => '%f',
                'break_even_quantity' => '%d',
                'break_even_amount' => '%d',
                'created_at' => '%s',
                'updated_at' => '%s'
            ),
            'rocket_sourcer_searches' => array(
                'id' => '%d',
                'user_id' => '%d',
                'search_type' => '%s',
                'keyword' => '%s',
                'created_at' => '%s'
            )
        );

        return isset($formats[$table]) ? $formats[$table] : array();
    }

    /**
     * 내보내기 파일 삭제
     *
     * @param string $filepath 파일 경로
     * @return bool 성공 여부
     */
    public function delete_export_file($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * 오래된 내보내기 파일 정리
     *
     * @param int $days 보관 기간 (일)
     * @return int 삭제된 파일 수
     */
    public function cleanup_export_files($days = 7) {
        $upload_dir = wp_upload_dir();
        $pattern = $upload_dir['path'] . '/rocket-sourcer-export-*.json';
        $files = glob($pattern);
        $deleted = 0;

        if ($files) {
            $threshold = strtotime("-{$days} days");

            foreach ($files as $file) {
                if (filemtime($file) < $threshold) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }
} 