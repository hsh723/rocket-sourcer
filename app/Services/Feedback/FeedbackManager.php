<?php

namespace App\Services\Feedback;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Services\Recommendation\SourcingRecommendationService;

/**
 * 피드백 관리자
 * 
 * 사용자 피드백을 수집, 분석하고 시스템 개선에 활용합니다.
 * 피드백 기반 추천 개선, 감성 분석, 트렌드 분석 등의 기능을 제공합니다.
 */
class FeedbackManager
{
    /**
     * 피드백 유형 정의
     */
    const TYPE_RECOMMENDATION = 'recommendation';
    const TYPE_FEATURE = 'feature';
    const TYPE_UI = 'ui';
    const TYPE_BUG = 'bug';
    const TYPE_GENERAL = 'general';
    
    /**
     * 피드백 감성 정의
     */
    const SENTIMENT_POSITIVE = 'positive';
    const SENTIMENT_NEUTRAL = 'neutral';
    const SENTIMENT_NEGATIVE = 'negative';
    
    /**
     * 추천 서비스 인스턴스
     * 
     * @var SourcingRecommendationService|null
     */
    protected $recommendationService;
    
    /**
     * 피드백 테이블 이름
     * 
     * @var string
     */
    protected $feedbackTable = 'user_feedback';
    
    /**
     * 생성자
     * 
     * @param SourcingRecommendationService|null $recommendationService
     */
    public function __construct(SourcingRecommendationService $recommendationService = null)
    {
        $this->recommendationService = $recommendationService;
    }
    
    /**
     * 피드백을 저장합니다.
     * 
     * @param int $userId 사용자 ID
     * @param string $type 피드백 유형
     * @param string $content 피드백 내용
     * @param array $metadata 추가 메타데이터
     * @return bool 성공 여부
     */
    public function storeFeedback(int $userId, string $type, string $content, array $metadata = [])
    {
        try {
            // 피드백 유형 검증
            if (!$this->isValidFeedbackType($type)) {
                Log::warning("유효하지 않은 피드백 유형: {$type}", [
                    'user_id' => $userId,
                    'content' => $content
                ]);
                return false;
            }
            
            // 감성 분석
            $sentiment = $this->analyzeSentiment($content);
            
            // 피드백 저장
            $result = DB::table($this->feedbackTable)->insert([
                'user_id' => $userId,
                'type' => $type,
                'content' => $content,
                'sentiment' => $sentiment,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // 추천 피드백인 경우 추천 서비스에 전달
            if ($type === self::TYPE_RECOMMENDATION && $this->recommendationService) {
                $this->recommendationService->improveRecommendationsWithFeedback(
                    $userId,
                    $content,
                    $sentiment,
                    $metadata
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("피드백 저장 오류: {$e->getMessage()}", [
                'user_id' => $userId,
                'type' => $type,
                'content' => $content,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * 피드백을 조회합니다.
     * 
     * @param array $filters 필터 옵션
     * @param array $pagination 페이지네이션 옵션
     * @return Collection 피드백 컬렉션
     */
    public function getFeedback(array $filters = [], array $pagination = [])
    {
        try {
            $query = DB::table($this->feedbackTable);
            
            // 필터 적용
            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (isset($filters['sentiment'])) {
                $query->where('sentiment', $filters['sentiment']);
            }
            
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            
            if (isset($filters['search'])) {
                $query->where('content', 'like', '%' . $filters['search'] . '%');
            }
            
            // 정렬
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDir = $filters['sort_dir'] ?? 'desc';
            $query->orderBy($sortBy, $sortDir);
            
            // 페이지네이션
            $perPage = $pagination['per_page'] ?? 20;
            $page = $pagination['page'] ?? 1;
            
            $feedback = $query->paginate($perPage, ['*'], 'page', $page);
            
            // 메타데이터 필드 JSON 디코딩
            $feedback->getCollection()->transform(function ($item) {
                $item->metadata = json_decode($item->metadata);
                return $item;
            });
            
            return $feedback;
        } catch (\Exception $e) {
            Log::error("피드백 조회 오류: {$e->getMessage()}", [
                'filters' => $filters,
                'pagination' => $pagination,
                'exception' => $e
            ]);
            
            return collect();
        }
    }
    
    /**
     * 피드백 통계를 가져옵니다.
     * 
     * @param array $filters 필터 옵션
     * @return array 통계 데이터
     */
    public function getFeedbackStats(array $filters = [])
    {
        try {
            $query = DB::table($this->feedbackTable);
            
            // 필터 적용
            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            
            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }
            
            // 유형별 통계
            $typeStats = $query->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray();
            
            // 감성별 통계
            $sentimentStats = $query->select('sentiment', DB::raw('count(*) as count'))
                ->groupBy('sentiment')
                ->get()
                ->pluck('count', 'sentiment')
                ->toArray();
            
            // 시간별 통계
            $timeStats = $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();
            
            // 결과 포맷팅
            $stats = [
                'total_feedback' => array_sum($typeStats),
                'type_stats' => $typeStats,
                'sentiment_stats' => $sentimentStats,
                'time_stats' => $timeStats
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error("피드백 통계 조회 오류: {$e->getMessage()}", [
                'filters' => $filters,
                'exception' => $e
            ]);
            
            return [
                'total_feedback' => 0,
                'type_stats' => [],
                'sentiment_stats' => [],
                'time_stats' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 피드백 트렌드를 분석합니다.
     * 
     * @param string $type 피드백 유형
     * @param int $duration 기간 (일)
     * @return array 트렌드 데이터
     */
    public function analyzeTrends(string $type = null, int $duration = 30)
    {
        try {
            $query = DB::table($this->feedbackTable);
            
            // 유형 필터링
            if ($type) {
                $query->where('type', $type);
            }
            
            // 기간 설정
            $endDate = now();
            $startDate = now()->subDays($duration);
            $query->whereBetween('created_at', [$startDate, $endDate]);
            
            // 일별 피드백 수
            $dailyTrends = $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();
            
            // 일별 감성 분포
            $sentimentTrends = [];
            foreach ([self::SENTIMENT_POSITIVE, self::SENTIMENT_NEUTRAL, self::SENTIMENT_NEGATIVE] as $sentiment) {
                $sentimentQuery = clone $query;
                $sentimentTrends[$sentiment] = $sentimentQuery->where('sentiment', $sentiment)
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('date')
                    ->get()
                    ->pluck('count', 'date')
                    ->toArray();
            }
            
            // 빈 날짜 채우기
            $filledDailyTrends = $this->fillEmptyDates($dailyTrends, $startDate, $endDate);
            
            foreach ($sentimentTrends as $sentiment => $trends) {
                $sentimentTrends[$sentiment] = $this->fillEmptyDates($trends, $startDate, $endDate);
            }
            
            // 결과 포맷팅
            $trends = [
                'type' => $type ?? 'all',
                'duration' => $duration,
                'daily_trends' => $filledDailyTrends,
                'sentiment_trends' => $sentimentTrends
            ];
            
            return $trends;
        } catch (\Exception $e) {
            Log::error("피드백 트렌드 분석 오류: {$e->getMessage()}", [
                'type' => $type,
                'duration' => $duration,
                'exception' => $e
            ]);
            
            return [
                'type' => $type ?? 'all',
                'duration' => $duration,
                'daily_trends' => [],
                'sentiment_trends' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 피드백 내용에서 주요 키워드를 추출합니다.
     * 
     * @param string $type 피드백 유형
     * @param string $sentiment 감성
     * @param int $limit 키워드 수
     * @return array 키워드 배열
     */
    public function extractKeywords(string $type = null, string $sentiment = null, int $limit = 10)
    {
        try {
            $query = DB::table($this->feedbackTable);
            
            // 필터 적용
            if ($type) {
                $query->where('type', $type);
            }
            
            if ($sentiment) {
                $query->where('sentiment', $sentiment);
            }
            
            // 최근 피드백 가져오기
            $feedback = $query->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->pluck('content')
                ->toArray();
            
            // 키워드 추출 (간단한 구현)
            $keywords = $this->simpleKeywordExtraction(implode(' ', $feedback), $limit);
            
            return $keywords;
        } catch (\Exception $e) {
            Log::error("키워드 추출 오류: {$e->getMessage()}", [
                'type' => $type,
                'sentiment' => $sentiment,
                'exception' => $e
            ]);
            
            return [];
        }
    }
    
    /**
     * 피드백 유형이 유효한지 확인합니다.
     * 
     * @param string $type 피드백 유형
     * @return bool 유효 여부
     */
    protected function isValidFeedbackType(string $type)
    {
        return in_array($type, [
            self::TYPE_RECOMMENDATION,
            self::TYPE_FEATURE,
            self::TYPE_UI,
            self::TYPE_BUG,
            self::TYPE_GENERAL
        ]);
    }
    
    /**
     * 텍스트의 감성을 분석합니다.
     * 
     * @param string $text 분석할 텍스트
     * @return string 감성 (positive, neutral, negative)
     */
    protected function analyzeSentiment(string $text)
    {
        // 간단한 감성 분석 구현
        // 실제 구현에서는 자연어 처리 라이브러리나 API를 사용해야 함
        
        $positiveWords = [
            '좋아요', '훌륭해요', '만족', '좋은', '유용한', '편리한', '도움이', '감사',
            '최고', '멋진', '빠른', '효율적', '개선', '추천', '직관적', '쉬운'
        ];
        
        $negativeWords = [
            '나쁜', '불만', '문제', '오류', '버그', '느린', '불편한', '어려운',
            '복잡한', '혼란', '실망', '안좋은', '불필요한', '비효율적', '개선필요', '싫어요'
        ];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (stripos($text, $word) !== false) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (stripos($text, $word) !== false) {
                $negativeCount++;
            }
        }
        
        if ($positiveCount > $negativeCount) {
            return self::SENTIMENT_POSITIVE;
        } elseif ($negativeCount > $positiveCount) {
            return self::SENTIMENT_NEGATIVE;
        } else {
            return self::SENTIMENT_NEUTRAL;
        }
    }
    
    /**
     * 간단한 키워드 추출 구현
     * 
     * @param string $text 텍스트
     * @param int $limit 키워드 수
     * @return array 키워드 배열
     */
    protected function simpleKeywordExtraction(string $text, int $limit)
    {
        // 불용어 정의
        $stopWords = [
            '이', '그', '저', '것', '이것', '저것', '그것', '이런', '저런', '그런',
            '하다', '있다', '되다', '않다', '이다', '아니다', '같다', '때', '및', '등',
            '를', '을', '이', '가', '에', '에서', '로', '으로', '와', '과', '의', '도',
            '은', '는', '이나', '나', '또는', '혹은', '그리고', '따라서', '그래서', '그러나',
            '하지만', '그런데', '그러면', '그렇지만', '그럼에도', '그리하여', '그러므로', '그러니까'
        ];
        
        // 텍스트 전처리
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        // 단어 분리
        $words = preg_split('/\s+/', $text);
        
        // 단어 빈도 계산
        $wordCounts = [];
        foreach ($words as $word) {
            if (strlen($word) > 1 && !in_array($word, $stopWords)) {
                $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
            }
        }
        
        // 빈도순 정렬
        arsort($wordCounts);
        
        // 상위 키워드 반환
        return array_slice($wordCounts, 0, $limit, true);
    }
    
    /**
     * 빈 날짜를 0으로 채웁니다.
     * 
     * @param array $data 데이터
     * @param \DateTime $startDate 시작 날짜
     * @param \DateTime $endDate 종료 날짜
     * @return array 채워진 데이터
     */
    protected function fillEmptyDates(array $data, \DateTime $startDate, \DateTime $endDate)
    {
        $filledData = [];
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $filledData[$dateKey] = $data[$dateKey] ?? 0;
            $current->modify('+1 day');
        }
        
        return $filledData;
    }
} 