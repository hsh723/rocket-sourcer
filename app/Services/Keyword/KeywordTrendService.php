<?php

namespace RocketSourcer\Services\Keyword;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Keyword;
use RocketSourcer\Services\Coupang\CoupangKeywordService;

class KeywordTrendService
{
    protected CoupangKeywordService $coupangKeywordService;
    protected Cache $cache;
    protected LoggerInterface $logger;

    public function __construct(
        CoupangKeywordService $coupangKeywordService,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->coupangKeywordService = $coupangKeywordService;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 키워드 트렌드 분석
     */
    public function analyzeTrends(Keyword $keyword): array
    {
        $cacheKey = "keyword_trends:{$keyword->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 일간, 주간, 월간 트렌드 데이터 수집
            $dailyTrends = $this->coupangKeywordService->getTrends($keyword->getKeyword(), [
                'period' => '30D',
                'interval' => 'DAY'
            ]);

            $weeklyTrends = $this->coupangKeywordService->getTrends($keyword->getKeyword(), [
                'period' => '12W',
                'interval' => 'WEEK'
            ]);

            $monthlyTrends = $this->coupangKeywordService->getTrends($keyword->getKeyword(), [
                'period' => '12M',
                'interval' => 'MONTH'
            ]);

            $trends = [
                'daily' => $this->processTrendData($dailyTrends->getData()['trends'] ?? []),
                'weekly' => $this->processTrendData($weeklyTrends->getData()['trends'] ?? []),
                'monthly' => $this->processTrendData($monthlyTrends->getData()['trends'] ?? []),
                'seasonality' => $this->analyzeSeasonality($monthlyTrends->getData()['trends'] ?? []),
                'growth_rate' => $this->calculateGrowthRate($monthlyTrends->getData()['trends'] ?? []),
            ];

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $trends, 3600); // 1시간 캐시

            return $trends;

        } catch (\Exception $e) {
            $this->logger->error('키워드 트렌드 분석 실패', [
                'keyword_id' => $keyword->getId(),
                'keyword' => $keyword->getKeyword(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 트렌드 데이터 처리
     */
    protected function processTrendData(array $trends): array
    {
        return array_map(function ($trend) {
            return [
                'date' => $trend['date'],
                'search_volume' => $trend['search_volume'],
                'relative_volume' => $trend['relative_volume'] ?? null,
            ];
        }, $trends);
    }

    /**
     * 시즌성 분석
     */
    protected function analyzeSeasonality(array $trends): array
    {
        if (empty($trends)) {
            return [
                'has_seasonality' => false,
                'peak_months' => [],
                'low_months' => [],
            ];
        }

        // 월별 평균 검색량 계산
        $monthlyAverages = [];
        foreach ($trends as $trend) {
            $month = date('n', strtotime($trend['date']));
            if (!isset($monthlyAverages[$month])) {
                $monthlyAverages[$month] = ['sum' => 0, 'count' => 0];
            }
            $monthlyAverages[$month]['sum'] += $trend['search_volume'];
            $monthlyAverages[$month]['count']++;
        }

        // 전체 평균 계산
        $totalAverage = array_sum(array_column($monthlyAverages, 'sum')) / array_sum(array_column($monthlyAverages, 'count'));

        // 시즌성 판단 (평균보다 20% 이상 높거나 낮은 월을 시즌으로 간주)
        $peakMonths = [];
        $lowMonths = [];
        foreach ($monthlyAverages as $month => $data) {
            $average = $data['sum'] / $data['count'];
            if ($average > $totalAverage * 1.2) {
                $peakMonths[] = $month;
            } elseif ($average < $totalAverage * 0.8) {
                $lowMonths[] = $month;
            }
        }

        return [
            'has_seasonality' => !empty($peakMonths) || !empty($lowMonths),
            'peak_months' => $peakMonths,
            'low_months' => $lowMonths,
        ];
    }

    /**
     * 성장률 계산
     */
    protected function calculateGrowthRate(array $trends): array
    {
        if (count($trends) < 2) {
            return [
                'monthly' => 0,
                'quarterly' => 0,
                'yearly' => 0,
            ];
        }

        $trends = array_values($trends);
        $lastIndex = count($trends) - 1;

        // 월간 성장률
        $monthlyGrowth = $this->calculatePercentageChange(
            $trends[$lastIndex - 1]['search_volume'],
            $trends[$lastIndex]['search_volume']
        );

        // 분기 성장률
        $quarterlyGrowth = 0;
        if ($lastIndex >= 3) {
            $quarterlyGrowth = $this->calculatePercentageChange(
                $trends[$lastIndex - 3]['search_volume'],
                $trends[$lastIndex]['search_volume']
            );
        }

        // 연간 성장률
        $yearlyGrowth = 0;
        if ($lastIndex >= 12) {
            $yearlyGrowth = $this->calculatePercentageChange(
                $trends[$lastIndex - 12]['search_volume'],
                $trends[$lastIndex]['search_volume']
            );
        }

        return [
            'monthly' => $monthlyGrowth,
            'quarterly' => $quarterlyGrowth,
            'yearly' => $yearlyGrowth,
        ];
    }

    /**
     * 변화율 계산
     */
    protected function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }
} 