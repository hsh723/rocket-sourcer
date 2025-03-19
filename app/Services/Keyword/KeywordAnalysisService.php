<?php

namespace RocketSourcer\Services\Keyword;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Keyword;
use RocketSourcer\Models\Analysis;
use RocketSourcer\Services\Coupang\CoupangKeywordService;
use RocketSourcer\Services\Crawler\KeywordCrawlerService;

class KeywordAnalysisService
{
    protected CoupangKeywordService $coupangKeywordService;
    protected KeywordTrendService $trendService;
    protected KeywordCompetitionService $competitionService;
    protected Cache $cache;
    protected LoggerInterface $logger;
    private KeywordCrawlerService $crawler;

    public function __construct(
        CoupangKeywordService $coupangKeywordService,
        KeywordTrendService $trendService,
        KeywordCompetitionService $competitionService,
        Cache $cache,
        LoggerInterface $logger,
        KeywordCrawlerService $crawler
    ) {
        $this->coupangKeywordService = $coupangKeywordService;
        $this->trendService = $trendService;
        $this->competitionService = $competitionService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->crawler = $crawler;
    }

    /**
     * 키워드 분석 실행
     */
    public function analyze(Keyword $keyword): Analysis
    {
        $cacheKey = "keyword_analysis:{$keyword->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $analysis = new Analysis([
            'analyzable_type' => Keyword::class,
            'analyzable_id' => $keyword->getId(),
            'type' => 'keyword_analysis',
            'status' => 'processing',
        ]);

        try {
            // 쿠팡 API를 통한 키워드 분석
            $keywordData = $this->coupangKeywordService->analyze($keyword->getKeyword());
            
            if (!$keywordData->isSuccess()) {
                throw new \Exception($keywordData->getMessage());
            }

            // 트렌드 분석
            $trends = $this->trendService->analyzeTrends($keyword);

            // 경쟁 분석
            $competition = $this->competitionService->analyzeCompetition($keyword);

            // 분석 결과 저장
            $analysis->result = [
                'search_volume' => $keywordData->getData()['search_volume'] ?? 0,
                'competition_score' => $keywordData->getData()['competition']['score'] ?? 0,
                'trends' => $trends,
                'competition' => $competition,
                'categories' => $keywordData->getData()['categories'] ?? [],
                'related_keywords' => $keywordData->getData()['related_keywords'] ?? [],
            ];

            $analysis->status = 'completed';
            $analysis->completed_at = now();

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $analysis, 3600); // 1시간 캐시

            $this->logger->info('키워드 분석 완료', [
                'keyword_id' => $keyword->getId(),
                'keyword' => $keyword->getKeyword(),
            ]);

        } catch (\Exception $e) {
            $analysis->status = 'failed';
            $analysis->error = $e->getMessage();

            $this->logger->error('키워드 분석 실패', [
                'keyword_id' => $keyword->getId(),
                'keyword' => $keyword->getKeyword(),
                'error' => $e->getMessage(),
            ]);
        }

        $analysis->save();
        return $analysis;
    }

    /**
     * 비동기 분석 작업 시작
     */
    public function startAsyncAnalysis(Keyword $keyword): Analysis
    {
        $analysis = new Analysis([
            'analyzable_type' => Keyword::class,
            'analyzable_id' => $keyword->getId(),
            'type' => 'keyword_analysis',
            'status' => 'pending',
        ]);

        $analysis->save();

        // 비동기 작업 큐에 추가
        dispatch(new AnalyzeKeywordJob($keyword));

        return $analysis;
    }

    /**
     * 분석 결과 조회
     */
    public function getAnalysisResult(Keyword $keyword): ?Analysis
    {
        return Analysis::where('analyzable_type', Keyword::class)
            ->where('analyzable_id', $keyword->getId())
            ->where('type', 'keyword_analysis')
            ->latest()
            ->first();
    }

    /**
     * 분석 결과 캐시 삭제
     */
    public function clearAnalysisCache(Keyword $keyword): void
    {
        $this->cache->delete("keyword_analysis:{$keyword->getId()}");
    }

    /**
     * 실제 분석 수행
     */
    private function performAnalysis(string $keyword, array $options): array
    {
        // 쿠팡 API를 통한 기본 데이터 수집
        $apiData = $this->coupangKeywordService->search($keyword);
        
        if (!$apiData->isSuccess()) {
            throw new \RuntimeException('쿠팡 API 데이터 조회 실패');
        }

        // 크롤링을 통한 추가 데이터 수집
        $crawledData = $this->crawler->collectData($keyword);
        
        // 트렌드 분석
        $trendData = $this->trendService->analyze($keyword, [
            'api_data' => $apiData->getData(),
            'crawled_data' => $crawledData
        ]);
        
        // 경쟁 분석
        $competitionData = $this->competitionService->analyze($keyword, [
            'api_data' => $apiData->getData(),
            'crawled_data' => $crawledData
        ]);

        // 종합 분석 결과
        return [
            'keyword' => $keyword,
            'search_volume' => $apiData->getData()['keywords'][0]['searchVolume'] ?? 0,
            'competition' => $apiData->getData()['keywords'][0]['competition'] ?? 0,
            'trends' => $trendData,
            'competition_analysis' => $competitionData,
            'recommendations' => [
                'score' => $this->calculateScore($apiData, $trendData, $competitionData),
                'potential' => $this->assessPotential($apiData, $trendData, $competitionData),
                'suggestions' => $this->generateSuggestions($apiData, $trendData, $competitionData)
            ],
            'metadata' => [
                'analyzed_at' => date('Y-m-d H:i:s'),
                'data_sources' => [
                    'api' => true,
                    'crawler' => !empty($crawledData)
                ]
            ]
        ];
    }

    /**
     * 종합 점수 계산
     */
    private function calculateScore(
        $apiData,
        array $trendData,
        array $competitionData
    ): float {
        $searchVolume = $apiData->getData()['keywords'][0]['searchVolume'] ?? 0;
        $competition = $apiData->getData()['keywords'][0]['competition'] ?? 1;
        $trendScore = $trendData['trend_score'] ?? 0;
        $competitionScore = $competitionData['competition_score'] ?? 0;

        // 검색량과 경쟁도를 고려한 기본 점수
        $baseScore = ($searchVolume / 1000) * (1 - $competition);
        
        // 트렌드와 경쟁 분석 점수를 반영
        $finalScore = ($baseScore * 0.4) + ($trendScore * 0.3) + ($competitionScore * 0.3);
        
        return round(min(max($finalScore, 0), 100), 2);
    }

    /**
     * 잠재력 평가
     */
    private function assessPotential(
        $apiData,
        array $trendData,
        array $competitionData
    ): array {
        return [
            'growth_potential' => $this->calculateGrowthPotential($trendData),
            'market_opportunity' => $this->calculateMarketOpportunity($apiData, $competitionData),
            'seasonality' => $this->analyzeSeasonal($trendData),
            'difficulty' => $this->assessDifficulty($competitionData)
        ];
    }

    /**
     * 성장 잠재력 계산
     */
    private function calculateGrowthPotential(array $trendData): float
    {
        $trends = $trendData['monthly_trends'] ?? [];
        if (empty($trends)) {
            return 0.0;
        }

        // 최근 3개월의 증가율 계산
        $recentTrends = array_slice($trends, -3);
        $growth = 0;
        
        for ($i = 1; $i < count($recentTrends); $i++) {
            $growth += ($recentTrends[$i] - $recentTrends[$i-1]) / $recentTrends[$i-1];
        }

        return round(($growth / 2) * 100, 2); // 평균 성장률을 퍼센트로 변환
    }

    /**
     * 시장 기회 계산
     */
    private function calculateMarketOpportunity($apiData, array $competitionData): float
    {
        $searchVolume = $apiData->getData()['keywords'][0]['searchVolume'] ?? 0;
        $competition = $competitionData['competition_level'] ?? 1;

        // 검색량과 경쟁도를 고려한 시장 기회 점수
        return round(($searchVolume / 1000) * (1 - $competition) * 100, 2);
    }

    /**
     * 계절성 분석
     */
    private function analyzeSeasonal(array $trendData): array
    {
        $monthlyTrends = $trendData['monthly_trends'] ?? [];
        if (count($monthlyTrends) < 12) {
            return [
                'is_seasonal' => false,
                'peak_months' => [],
                'low_months' => []
            ];
        }

        $average = array_sum($monthlyTrends) / count($monthlyTrends);
        $peaks = [];
        $lows = [];

        foreach ($monthlyTrends as $month => $value) {
            if ($value > $average * 1.2) {
                $peaks[] = $month + 1;
            } elseif ($value < $average * 0.8) {
                $lows[] = $month + 1;
            }
        }

        return [
            'is_seasonal' => !empty($peaks),
            'peak_months' => $peaks,
            'low_months' => $lows
        ];
    }

    /**
     * 진입 난이도 평가
     */
    private function assessDifficulty(array $competitionData): string
    {
        $score = $competitionData['competition_score'] ?? 0;

        if ($score < 0.3) return '쉬움';
        if ($score < 0.6) return '보통';
        if ($score < 0.8) return '어려움';
        return '매우 어려움';
    }

    /**
     * 제안사항 생성
     */
    private function generateSuggestions(
        $apiData,
        array $trendData,
        array $competitionData
    ): array {
        $suggestions = [];
        $searchVolume = $apiData->getData()['keywords'][0]['searchVolume'] ?? 0;
        $competition = $competitionData['competition_level'] ?? 0;
        $seasonal = $this->analyzeSeasonal($trendData);

        // 검색량 기반 제안
        if ($searchVolume < 1000) {
            $suggestions[] = '검색량이 적습니다. 연관 키워드를 추가로 발굴하는 것을 추천합니다.';
        }

        // 경쟁도 기반 제안
        if ($competition > 0.8) {
            $suggestions[] = '경쟁이 매우 치열합니다. 틈새 시장을 노리는 것을 추천합니다.';
        } elseif ($competition < 0.3 && $searchVolume > 5000) {
            $suggestions[] = '블루오션 키워드입니다. 빠른 시장 진입을 추천합니다.';
        }

        // 계절성 기반 제안
        if ($seasonal['is_seasonal']) {
            $peakMonths = implode(', ', $seasonal['peak_months']);
            $suggestions[] = "계절성이 있는 키워드입니다. {$peakMonths}월에 집중하는 것을 추천합니다.";
        }

        return $suggestions;
    }
} 