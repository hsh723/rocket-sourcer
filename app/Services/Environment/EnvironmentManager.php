<?php

namespace App\Services\Environment;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * 환경 관리 서비스
 * 
 * 애플리케이션의 환경 설정을 관리하고 로드하는 서비스입니다.
 * 프로덕션, 스테이징, 개발 등 다양한 환경에 대한 설정을 처리합니다.
 */
class EnvironmentManager
{
    /**
     * 현재 환경
     *
     * @var string
     */
    protected $currentEnvironment;
    
    /**
     * 환경 설정 경로
     *
     * @var string
     */
    protected $environmentsPath;
    
    /**
     * 사용 가능한 환경 목록
     *
     * @var array
     */
    protected $availableEnvironments = ['production', 'staging', 'development', 'testing', 'local'];
    
    /**
     * 생성자
     */
    public function __construct()
    {
        $this->currentEnvironment = App::environment();
        $this->environmentsPath = config_path('environments');
    }
    
    /**
     * 환경 설정을 로드합니다.
     *
     * @return void
     */
    public function loadEnvironmentConfig()
    {
        $environment = $this->getCurrentEnvironment();
        $configFile = $this->getEnvironmentConfigFile($environment);
        
        if (File::exists($configFile)) {
            $config = require $configFile;
            
            foreach ($config as $key => $value) {
                Config::set($key, $value);
            }
            
            Log::info("환경 설정 로드 완료", ['environment' => $environment]);
        } else {
            Log::warning("환경 설정 파일을 찾을 수 없습니다", [
                'environment' => $environment,
                'config_file' => $configFile
            ]);
        }
    }
    
    /**
     * 현재 환경을 가져옵니다.
     *
     * @return string
     */
    public function getCurrentEnvironment()
    {
        return $this->currentEnvironment;
    }
    
    /**
     * 현재 환경이 프로덕션인지 확인합니다.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->currentEnvironment === 'production';
    }
    
    /**
     * 현재 환경이 스테이징인지 확인합니다.
     *
     * @return bool
     */
    public function isStaging()
    {
        return $this->currentEnvironment === 'staging';
    }
    
    /**
     * 현재 환경이 개발 환경인지 확인합니다.
     *
     * @return bool
     */
    public function isDevelopment()
    {
        return in_array($this->currentEnvironment, ['development', 'local']);
    }
    
    /**
     * 현재 환경이 테스트 환경인지 확인합니다.
     *
     * @return bool
     */
    public function isTesting()
    {
        return $this->currentEnvironment === 'testing';
    }
    
    /**
     * 환경 설정 파일 경로를 가져옵니다.
     *
     * @param string $environment
     * @return string
     */
    public function getEnvironmentConfigFile($environment)
    {
        return $this->environmentsPath . '/' . $environment . '.php';
    }
    
    /**
     * 사용 가능한 환경 목록을 가져옵니다.
     *
     * @return array
     */
    public function getAvailableEnvironments()
    {
        $environments = [];
        
        foreach ($this->availableEnvironments as $environment) {
            $configFile = $this->getEnvironmentConfigFile($environment);
            
            if (File::exists($configFile)) {
                $environments[] = $environment;
            }
        }
        
        return $environments;
    }
    
    /**
     * 특정 환경의 설정을 가져옵니다.
     *
     * @param string $environment
     * @return array|null
     */
    public function getEnvironmentConfig($environment)
    {
        $configFile = $this->getEnvironmentConfigFile($environment);
        
        if (File::exists($configFile)) {
            return require $configFile;
        }
        
        return null;
    }
    
    /**
     * 특정 환경의 특정 설정을 가져옵니다.
     *
     * @param string $key
     * @param string $environment
     * @param mixed $default
     * @return mixed
     */
    public function getEnvironmentConfigValue($key, $environment = null, $default = null)
    {
        $environment = $environment ?? $this->currentEnvironment;
        $config = $this->getEnvironmentConfig($environment);
        
        if ($config === null) {
            return $default;
        }
        
        return Arr::get($config, $key, $default);
    }
    
    /**
     * 현재 환경에 따라 설정 값을 가져옵니다.
     *
     * @param array $values 환경별 설정 값 배열 ['production' => 값1, 'staging' => 값2, ...]
     * @param mixed $default 기본값
     * @return mixed
     */
    public function getValueForCurrentEnvironment(array $values, $default = null)
    {
        if (isset($values[$this->currentEnvironment])) {
            return $values[$this->currentEnvironment];
        }
        
        // 현재 환경에 대한 설정이 없는 경우 환경 그룹에 따라 처리
        if ($this->isProduction() && isset($values['production'])) {
            return $values['production'];
        }
        
        if ($this->isStaging() && isset($values['staging'])) {
            return $values['staging'];
        }
        
        if ($this->isDevelopment()) {
            if (isset($values['development'])) {
                return $values['development'];
            }
            
            if (isset($values['local'])) {
                return $values['local'];
            }
        }
        
        if ($this->isTesting() && isset($values['testing'])) {
            return $values['testing'];
        }
        
        return $default;
    }
    
    /**
     * 환경 변수를 설정합니다.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setEnvironmentVariable($key, $value)
    {
        $envFile = base_path('.env');
        
        if (File::exists($envFile)) {
            $content = File::get($envFile);
            
            // 이미 존재하는 변수인 경우 업데이트
            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $content);
            } else {
                // 존재하지 않는 변수인 경우 추가
                $content .= PHP_EOL . "{$key}={$value}";
            }
            
            File::put($envFile, $content);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 환경 변수를 가져옵니다.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getEnvironmentVariable($key, $default = null)
    {
        return env($key, $default);
    }
    
    /**
     * 현재 환경에 대한 디버그 정보를 가져옵니다.
     *
     * @return array
     */
    public function getDebugInfo()
    {
        return [
            'environment' => $this->currentEnvironment,
            'is_production' => $this->isProduction(),
            'is_staging' => $this->isStaging(),
            'is_development' => $this->isDevelopment(),
            'is_testing' => $this->isTesting(),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'available_environments' => $this->getAvailableEnvironments(),
        ];
    }
    
    /**
     * 환경 설정 파일을 생성합니다.
     *
     * @param string $environment
     * @param array $config
     * @return bool
     */
    public function createEnvironmentConfig($environment, array $config)
    {
        if (!in_array($environment, $this->availableEnvironments)) {
            Log::error("유효하지 않은 환경입니다", ['environment' => $environment]);
            return false;
        }
        
        $configFile = $this->getEnvironmentConfigFile($environment);
        $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($config, true) . ';';
        
        try {
            File::put($configFile, $content);
            Log::info("환경 설정 파일 생성 완료", ['environment' => $environment]);
            return true;
        } catch (\Exception $e) {
            Log::error("환경 설정 파일 생성 실패", [
                'environment' => $environment,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 환경 설정 파일을 업데이트합니다.
     *
     * @param string $environment
     * @param array $config
     * @return bool
     */
    public function updateEnvironmentConfig($environment, array $config)
    {
        $configFile = $this->getEnvironmentConfigFile($environment);
        
        if (!File::exists($configFile)) {
            return $this->createEnvironmentConfig($environment, $config);
        }
        
        $existingConfig = $this->getEnvironmentConfig($environment);
        $mergedConfig = array_merge($existingConfig, $config);
        
        return $this->createEnvironmentConfig($environment, $mergedConfig);
    }
    
    /**
     * 환경 설정 파일을 삭제합니다.
     *
     * @param string $environment
     * @return bool
     */
    public function deleteEnvironmentConfig($environment)
    {
        $configFile = $this->getEnvironmentConfigFile($environment);
        
        if (File::exists($configFile)) {
            try {
                File::delete($configFile);
                Log::info("환경 설정 파일 삭제 완료", ['environment' => $environment]);
                return true;
            } catch (\Exception $e) {
                Log::error("환경 설정 파일 삭제 실패", [
                    'environment' => $environment,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        return false;
    }
} 