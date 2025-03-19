<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

/**
 * 데이터베이스 백업 스케줄러
 * 
 * 데이터베이스 백업 일정을 관리하고 예약된 백업을 실행하는 서비스입니다.
 * 일일, 주간, 월간 백업 일정을 설정하고 관리할 수 있습니다.
 */
class BackupScheduler
{
    /**
     * 백업 서비스
     * 
     * @var BackupService
     */
    protected $backupService;
    
    /**
     * 스케줄 캐시 키
     * 
     * @var string
     */
    protected $cacheKey = 'db_backup_schedules';
    
    /**
     * 마지막 실행 캐시 키
     * 
     * @var string
     */
    protected $lastRunCacheKey = 'db_backup_last_run';
    
    /**
     * 기본 스케줄 설정
     * 
     * @var array
     */
    protected $defaultSchedules = [
        'daily' => [
            'enabled' => true,
            'time' => '01:00',
            'retention' => 7,
            'description' => '일일 자동 백업'
        ],
        'weekly' => [
            'enabled' => true,
            'day' => 0, // 일요일
            'time' => '02:00',
            'retention' => 4,
            'description' => '주간 자동 백업'
        ],
        'monthly' => [
            'enabled' => true,
            'day' => 1, // 매월 1일
            'time' => '03:00',
            'retention' => 12,
            'description' => '월간 자동 백업'
        ]
    ];
    
    /**
     * 생성자
     * 
     * @param BackupService $backupService 백업 서비스
     */
    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        
        // 기본 스케줄 설정 로드
        $this->loadSchedules();
    }
    
    /**
     * 스케줄 설정을 로드합니다.
     * 
     * @return array 스케줄 설정
     */
    protected function loadSchedules()
    {
        $schedules = Cache::get($this->cacheKey);
        
        if (!$schedules) {
            $schedules = $this->defaultSchedules;
            Cache::forever($this->cacheKey, $schedules);
        }
        
        return $schedules;
    }
    
    /**
     * 스케줄 설정을 저장합니다.
     * 
     * @param array $schedules 스케줄 설정
     * @return bool 성공 여부
     */
    protected function saveSchedules(array $schedules)
    {
        return Cache::forever($this->cacheKey, $schedules);
    }
    
    /**
     * 스케줄 설정을 가져옵니다.
     * 
     * @return array 스케줄 설정
     */
    public function getSchedules()
    {
        return $this->loadSchedules();
    }
    
    /**
     * 스케줄 설정을 업데이트합니다.
     * 
     * @param string $type 스케줄 유형 (daily, weekly, monthly)
     * @param array $settings 설정 배열
     * @return array 업데이트 결과
     */
    public function updateSchedule(string $type, array $settings)
    {
        try {
            $schedules = $this->loadSchedules();
            
            if (!isset($schedules[$type])) {
                throw new \Exception("유효하지 않은 스케줄 유형: {$type}");
            }
            
            $oldSettings = $schedules[$type];
            $schedules[$type] = array_merge($schedules[$type], $settings);
            
            $this->saveSchedules($schedules);
            
            Log::info("백업 스케줄 업데이트됨", [
                'type' => $type,
                'old_settings' => $oldSettings,
                'new_settings' => $schedules[$type]
            ]);
            
            return [
                'success' => true,
                'type' => $type,
                'old_settings' => $oldSettings,
                'new_settings' => $schedules[$type]
            ];
        } catch (\Exception $e) {
            Log::error("백업 스케줄 업데이트 오류: {$e->getMessage()}", [
                'type' => $type,
                'settings' => $settings,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 스케줄을 활성화 또는 비활성화합니다.
     * 
     * @param string $type 스케줄 유형 (daily, weekly, monthly)
     * @param bool $enabled 활성화 여부
     * @return array 업데이트 결과
     */
    public function setScheduleEnabled(string $type, bool $enabled)
    {
        return $this->updateSchedule($type, ['enabled' => $enabled]);
    }
    
    /**
     * 예약된 백업을 실행합니다.
     * 
     * @return array 실행 결과
     */
    public function runScheduledBackups()
    {
        try {
            $now = Carbon::now();
            $schedules = $this->loadSchedules();
            $lastRun = Cache::get($this->lastRunCacheKey, []);
            $results = [];
            
            // 일일 백업 확인
            if ($this->shouldRunDailyBackup($schedules['daily'], $lastRun, $now)) {
                $result = $this->runBackup('daily', $schedules['daily']);
                $results['daily'] = $result;
                $lastRun['daily'] = $now->toDateTimeString();
            }
            
            // 주간 백업 확인
            if ($this->shouldRunWeeklyBackup($schedules['weekly'], $lastRun, $now)) {
                $result = $this->runBackup('weekly', $schedules['weekly']);
                $results['weekly'] = $result;
                $lastRun['weekly'] = $now->toDateTimeString();
            }
            
            // 월간 백업 확인
            if ($this->shouldRunMonthlyBackup($schedules['monthly'], $lastRun, $now)) {
                $result = $this->runBackup('monthly', $schedules['monthly']);
                $results['monthly'] = $result;
                $lastRun['monthly'] = $now->toDateTimeString();
            }
            
            // 마지막 실행 시간 업데이트
            Cache::forever($this->lastRunCacheKey, $lastRun);
            
            return [
                'success' => true,
                'results' => $results,
                'timestamp' => $now->toDateTimeString()
            ];
        } catch (\Exception $e) {
            Log::error("예약 백업 실행 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 일일 백업을 실행해야 하는지 확인합니다.
     * 
     * @param array $schedule 스케줄 설정
     * @param array $lastRun 마지막 실행 시간
     * @param Carbon $now 현재 시간
     * @return bool 실행 여부
     */
    protected function shouldRunDailyBackup(array $schedule, array $lastRun, Carbon $now)
    {
        if (!$schedule['enabled']) {
            return false;
        }
        
        // 마지막 실행 시간 확인
        if (isset($lastRun['daily'])) {
            $lastRunTime = Carbon::parse($lastRun['daily']);
            
            // 오늘 이미 실행되었는지 확인
            if ($lastRunTime->isSameDay($now)) {
                return false;
            }
        }
        
        // 예약 시간 확인
        list($hour, $minute) = explode(':', $schedule['time']);
        $scheduledTime = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
        
        return $now->gte($scheduledTime);
    }
    
    /**
     * 주간 백업을 실행해야 하는지 확인합니다.
     * 
     * @param array $schedule 스케줄 설정
     * @param array $lastRun 마지막 실행 시간
     * @param Carbon $now 현재 시간
     * @return bool 실행 여부
     */
    protected function shouldRunWeeklyBackup(array $schedule, array $lastRun, Carbon $now)
    {
        if (!$schedule['enabled']) {
            return false;
        }
        
        // 오늘이 지정된 요일인지 확인 (0: 일요일, 6: 토요일)
        if ($now->dayOfWeek != $schedule['day']) {
            return false;
        }
        
        // 마지막 실행 시간 확인
        if (isset($lastRun['weekly'])) {
            $lastRunTime = Carbon::parse($lastRun['weekly']);
            
            // 이번 주에 이미 실행되었는지 확인
            if ($lastRunTime->isSameDay($now) || $lastRunTime->greaterThan($now->copy()->startOfWeek())) {
                return false;
            }
        }
        
        // 예약 시간 확인
        list($hour, $minute) = explode(':', $schedule['time']);
        $scheduledTime = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
        
        return $now->gte($scheduledTime);
    }
    
    /**
     * 월간 백업을 실행해야 하는지 확인합니다.
     * 
     * @param array $schedule 스케줄 설정
     * @param array $lastRun 마지막 실행 시간
     * @param Carbon $now 현재 시간
     * @return bool 실행 여부
     */
    protected function shouldRunMonthlyBackup(array $schedule, array $lastRun, Carbon $now)
    {
        if (!$schedule['enabled']) {
            return false;
        }
        
        // 오늘이 지정된 날짜인지 확인
        if ($now->day != $schedule['day']) {
            return false;
        }
        
        // 마지막 실행 시간 확인
        if (isset($lastRun['monthly'])) {
            $lastRunTime = Carbon::parse($lastRun['monthly']);
            
            // 이번 달에 이미 실행되었는지 확인
            if ($lastRunTime->isSameDay($now) || $lastRunTime->greaterThan($now->copy()->startOfMonth())) {
                return false;
            }
        }
        
        // 예약 시간 확인
        list($hour, $minute) = explode(':', $schedule['time']);
        $scheduledTime = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
        
        return $now->gte($scheduledTime);
    }
    
    /**
     * 백업을 실행합니다.
     * 
     * @param string $type 백업 유형 (daily, weekly, monthly)
     * @param array $schedule 스케줄 설정
     * @return array 백업 결과
     */
    protected function runBackup(string $type, array $schedule)
    {
        try {
            $description = $schedule['description'] ?? "{$type} 자동 백업";
            
            // 백업 실행
            $result = $this->backupService->createBackup($description);
            
            if ($result['success']) {
                // 백업 설정 업데이트 (최대 파일 수 설정)
                if (isset($schedule['retention']) && $schedule['retention'] > 0) {
                    $this->backupService->updateSettings([
                        'max_backup_files' => $schedule['retention']
                    ]);
                    
                    // 오래된 백업 파일 정리
                    $this->backupService->cleanupOldBackups();
                }
                
                Log::info("{$type} 예약 백업 완료", [
                    'file' => $result['file'],
                    'description' => $description
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("{$type} 예약 백업 오류: {$e->getMessage()}", [
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 마지막 백업 실행 시간을 가져옵니다.
     * 
     * @return array 마지막 실행 시간
     */
    public function getLastRunTimes()
    {
        $lastRun = Cache::get($this->lastRunCacheKey, []);
        $result = [];
        
        foreach (['daily', 'weekly', 'monthly'] as $type) {
            $result[$type] = isset($lastRun[$type]) ? $lastRun[$type] : null;
        }
        
        return $result;
    }
    
    /**
     * 다음 예약 백업 시간을 계산합니다.
     * 
     * @return array 다음 예약 시간
     */
    public function getNextScheduledTimes()
    {
        $schedules = $this->loadSchedules();
        $now = Carbon::now();
        $result = [];
        
        // 일일 백업 다음 실행 시간
        if ($schedules['daily']['enabled']) {
            list($hour, $minute) = explode(':', $schedules['daily']['time']);
            $nextDaily = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
            
            if ($nextDaily->isPast()) {
                $nextDaily->addDay();
            }
            
            $result['daily'] = $nextDaily->toDateTimeString();
        } else {
            $result['daily'] = null;
        }
        
        // 주간 백업 다음 실행 시간
        if ($schedules['weekly']['enabled']) {
            list($hour, $minute) = explode(':', $schedules['weekly']['time']);
            $nextWeekly = $now->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
            
            // 지정된 요일로 설정
            $daysUntilWeekday = ($schedules['weekly']['day'] - $now->dayOfWeek + 7) % 7;
            $nextWeekly->addDays($daysUntilWeekday);
            
            if ($daysUntilWeekday === 0 && $nextWeekly->isPast()) {
                $nextWeekly->addWeek();
            }
            
            $result['weekly'] = $nextWeekly->toDateTimeString();
        } else {
            $result['weekly'] = null;
        }
        
        // 월간 백업 다음 실행 시간
        if ($schedules['monthly']['enabled']) {
            list($hour, $minute) = explode(':', $schedules['monthly']['time']);
            $day = $schedules['monthly']['day'];
            
            $nextMonthly = $now->copy()->setDay(1)->setHour($hour)->setMinute($minute)->setSecond(0);
            
            // 지정된 날짜가 현재 월의 유효한 날짜인지 확인
            $daysInMonth = $nextMonthly->daysInMonth;
            $validDay = min($day, $daysInMonth);
            $nextMonthly->setDay($validDay);
            
            if ($nextMonthly->isPast()) {
                $nextMonthly->addMonth();
                $daysInMonth = $nextMonthly->daysInMonth;
                $validDay = min($day, $daysInMonth);
                $nextMonthly->setDay($validDay);
            }
            
            $result['monthly'] = $nextMonthly->toDateTimeString();
        } else {
            $result['monthly'] = null;
        }
        
        return $result;
    }
    
    /**
     * 백업 스케줄 상태를 가져옵니다.
     * 
     * @return array 스케줄 상태
     */
    public function getScheduleStatus()
    {
        $schedules = $this->loadSchedules();
        $lastRun = $this->getLastRunTimes();
        $nextRun = $this->getNextScheduledTimes();
        
        $result = [];
        
        foreach (['daily', 'weekly', 'monthly'] as $type) {
            $result[$type] = [
                'enabled' => $schedules[$type]['enabled'],
                'settings' => $schedules[$type],
                'last_run' => $lastRun[$type],
                'next_run' => $nextRun[$type]
            ];
        }
        
        return $result;
    }
    
    /**
     * 수동으로 백업을 실행합니다.
     * 
     * @param string $type 백업 유형 (daily, weekly, monthly)
     * @return array 백업 결과
     */
    public function runManualBackup(string $type)
    {
        try {
            $schedules = $this->loadSchedules();
            
            if (!isset($schedules[$type])) {
                throw new \Exception("유효하지 않은 스케줄 유형: {$type}");
            }
            
            $schedule = $schedules[$type];
            $description = "수동 {$type} 백업 - " . Carbon::now()->format('Y-m-d H:i:s');
            
            // 백업 실행
            $result = $this->backupService->createBackup($description);
            
            if ($result['success']) {
                // 마지막 실행 시간 업데이트
                $lastRun = Cache::get($this->lastRunCacheKey, []);
                $lastRun[$type] = Carbon::now()->toDateTimeString();
                Cache::forever($this->lastRunCacheKey, $lastRun);
                
                Log::info("수동 {$type} 백업 완료", [
                    'file' => $result['file'],
                    'description' => $description
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("수동 백업 오류: {$e->getMessage()}", [
                'type' => $type,
                'exception' => $e
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 