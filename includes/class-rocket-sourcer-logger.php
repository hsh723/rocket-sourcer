<?php
/**
 * Rocket Sourcer 로거 클래스
 */

class Rocket_Sourcer_Logger {
    /**
     * 로그 레벨 상수
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';

    /**
     * 로그 파일 경로
     */
    private $log_dir;
    private $max_file_size = 5242880; // 5MB
    private $max_files = 5;

    /**
     * 생성자
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/rocket-sourcer/logs';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        
        // 로그 디렉토리 보안
        $this->secure_log_directory();
    }

    /**
     * 디버그 로그 작성
     */
    public function log_debug($message, array $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 정보 로그 작성
     */
    public function log_info($message, array $context = []) {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * 경고 로그 작성
     */
    public function log_warning($message, array $context = []) {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * 에러 로그 작성
     */
    public function log_error($message, array $context = []) {
        $this->log(self::ERROR, $message, $context);
        
        // 중요 에러의 경우 관리자에게 알림
        if ($this->is_critical_error($message)) {
            $this->notify_admin($message, $context);
        }
    }

    /**
     * 로그 작성 메인 함수
     */
    private function log($level, $message, array $context = []) {
        $log_file = $this->get_log_file($level);
        
        // 로그 파일 크기 체크 및 순환
        $this->rotate_logs_if_needed($log_file);
        
        // 로그 메시지 포맷팅
        $log_entry = $this->format_log_entry($level, $message, $context);
        
        // 로그 파일 쓰기
        $this->write_log($log_file, $log_entry);
    }

    /**
     * 로그 파일 경로 가져오기
     */
    private function get_log_file($level) {
        return $this->log_dir . '/' . $level . '-' . date('Y-m-d') . '.log';
    }

    /**
     * 로그 메시지 포맷팅
     */
    private function format_log_entry($level, $message, array $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // 컨텍스트 데이터를 JSON으로 변환
        $context_json = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        
        return sprintf(
            "[%s] [%s] [User:%d] [IP:%s] %s%s\n",
            $timestamp,
            strtoupper($level),
            $user_id,
            $ip,
            $message,
            $context_json
        );
    }

    /**
     * 로그 파일 쓰기
     */
    private function write_log($file, $entry) {
        if (!is_writable(dirname($file))) {
            throw new Exception('로그 디렉토리에 쓰기 권한이 없습니다: ' . dirname($file));
        }
        
        if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new Exception('로그 파일 쓰기 실패: ' . $file);
        }
    }

    /**
     * 로그 파일 순환
     */
    private function rotate_logs_if_needed($log_file) {
        if (!file_exists($log_file)) {
            return;
        }
        
        if (filesize($log_file) < $this->max_file_size) {
            return;
        }
        
        // 기존 로그 파일 순환
        for ($i = $this->max_files - 1; $i > 0; $i--) {
            $old_file = $log_file . '.' . $i;
            $new_file = $log_file . '.' . ($i + 1);
            
            if (file_exists($old_file)) {
                rename($old_file, $new_file);
            }
        }
        
        // 현재 로그 파일 이동
        rename($log_file, $log_file . '.1');
    }

    /**
     * 로그 디렉토리 보안 설정
     */
    private function secure_log_directory() {
        // .htaccess 파일 생성
        $htaccess = $this->log_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess, $content);
        }
        
        // index.php 파일 생성
        $index = $this->log_dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
    }

    /**
     * 중요 에러 여부 확인
     */
    private function is_critical_error($message) {
        $critical_patterns = [
            'database connection',
            'api key',
            'permission denied',
            'memory exhausted',
            'maximum execution time'
        ];
        
        foreach ($critical_patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 관리자 알림
     */
    private function notify_admin($message, array $context = []) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] 중요 오류 발생', $site_name);
        
        $body = sprintf(
            "안녕하세요,\n\n%s에서 중요 오류가 발생했습니다.\n\n오류 메시지: %s\n\n컨텍스트 정보:\n%s",
            $site_name,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        
        wp_mail($admin_email, $subject, $body);
    }

    /**
     * 로그 파일 목록 가져오기
     */
    public function get_log_files() {
        $files = glob($this->log_dir . '/*.log');
        $log_files = [];
        
        foreach ($files as $file) {
            $log_files[] = [
                'name' => basename($file),
                'size' => size_format(filesize($file)),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        return $log_files;
    }

    /**
     * 로그 파일 내용 가져오기
     */
    public function get_log_content($file_name, $lines = 100) {
        $file = $this->log_dir . '/' . basename($file_name);
        
        if (!file_exists($file)) {
            throw new Exception('로그 파일을 찾을 수 없습니다: ' . $file_name);
        }
        
        // 파일의 마지막 n줄 읽기
        $content = [];
        $handle = fopen($file, 'r');
        
        if ($handle) {
            $position = -1;
            $found_lines = 0;
            
            while ($found_lines < $lines && fseek($handle, $position, SEEK_END) !== -1) {
                $char = fgetc($handle);
                if ($char === "\n") {
                    $found_lines++;
                }
                $position--;
            }
            
            while (!feof($handle)) {
                $content[] = fgets($handle);
            }
            
            fclose($handle);
        }
        
        return array_filter($content);
    }

    /**
     * 로그 파일 삭제
     */
    public function delete_log_file($file_name) {
        $file = $this->log_dir . '/' . basename($file_name);
        
        if (!file_exists($file)) {
            throw new Exception('로그 파일을 찾을 수 없습니다: ' . $file_name);
        }
        
        if (!unlink($file)) {
            throw new Exception('로그 파일 삭제 실패: ' . $file_name);
        }
        
        return true;
    }

    /**
     * 오래된 로그 파일 정리
     */
    public function cleanup_old_logs($days = 30) {
        $files = glob($this->log_dir . '/*.log*');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) >= 86400 * $days) {
                unlink($file);
            }
        }
    }
} 