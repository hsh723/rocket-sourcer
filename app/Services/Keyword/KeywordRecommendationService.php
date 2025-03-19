<?php

namespace RocketSourcer\Services\Keyword;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Keyword;
use RocketSourcer\Services\Coupang\CoupangKeywordService;

class KeywordRecommendationService
{
    protected CoupangKeywordService $coupangKeywordService;
    protected KeywordAnalysisService $analysisService;
    protected Cache $cache;
    protected LoggerInterface $logger;

    public function __construct(
        CoupangKeywordService $coupangKeywordService,
        KeywordAnalysisService $analysisService,
        Cache $cache,
        LoggerInterface $logger
    ) {
        $this->coupangKeywordService = $coupangKeywordService;
        $this->analysisService = $analysisService;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 키워드 기반 추천
     */
    public function getRecommendations(Keyword $keyword, array $options = []): array
    {
        $cacheKey = "keyword_recommendations:{$keyword->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 연관 키워드 수집
            $relatedKeywords = $this->coupangKeywordService->getRelatedKeywords($keyword->getKeyword(), [
                'limit' => $options['limit'] ?? 20,
                'sort' => 'RELEVANCE'
            ]);

            if (!$relatedKeywords->isSuccess()) {
                throw new \Exception($relatedKeywords->getMessage());
            }

            // 인기 키워드 수집
            $popularKeywords = $this->coupangKeywordService->getPopularKeywords([
                'categoryId' => $keyword->getCategoryId(),
                'limit' => 10
            ]);

            // 급상승 키워드 수집
            $risingKeywords = $this->coupangKeywordService->getRisingKeywords([
                'categoryId' => $keyword->getCategoryId(),
                'limit' => 10
            ]);

            $recommendations = [
                'related' => $this->processKeywords($relatedKeywords->getData()['keywords'] ?? []),
                'popular' => $this->processKeywords($popularKeywords->getData()['keywords'] ?? []),
                'rising' => $this->processKeywords($risingKeywords->getData()['keywords'] ?? []),
                'opportunities' => $this->findOpportunities(
                    $relatedKeywords->getData()['keywords'] ?? [],
                    $popularKeywords->getData()['keywords'] ?? [],
                    $risingKeywords->getData()['keywords'] ?? []
                ),
            ];

            // 추천 점수 계산 및 정렬
            $recommendations = $this->scoreAndSortRecommendations($recommendations);

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $recommendations, 3600); // 1시간 캐시

            return $recommendations;

        } catch (\Exception $e) {
            $this->logger->error('키워드 추천 생성 실패', [
                'keyword_id' => $keyword->getId(),
                'keyword' => $keyword->getKeyword(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 키워드 데이터 처리
     */
    protected function processKeywords(array $keywords): array
    {
        return array_map(function ($keyword) {
            return [
                'keyword' => $keyword['keyword'],
                'search_volume' => $keyword['search_volume'] ?? 0,
                'competition' => $keyword['competition'] ?? 0,
                'relevance_score' => $keyword['relevance_score'] ?? 0,
                'trends' => $keyword['trends'] ?? [],
                'categories' => $keyword['categories'] ?? [],
            ];
        }, $keywords);
    }

    /**
     * 기회 키워드 찾기
     */
    protected function findOpportunities(array $related, array $popular, array $rising): array
    {
        $opportunities = [];

        // 낮은 경쟁도의 연관 키워드 찾기
        foreach ($related as $keyword) {
            if (($keyword['competition'] ?? 1) < 0.4 && ($keyword['search_volume'] ?? 0) > 1000) {
                $opportunities[] = array_merge($keyword, [
                    'type' => 'low_competition',
                    'opportunity_score' => $this->calculateOpportunityScore($keyword),
                ]);
            }
        }

        // 급상승 중인 키워드 중 경쟁도가 아직 낮은 것 찾기
        foreach ($rising as $keyword) {
            if (($keyword['competition'] ?? 1) < 0.6) {
                $opportunities[] = array_merge($keyword, [
                    'type' => 'rising_trend',
                    'opportunity_score' => $this->calculateOpportunityScore($keyword),
                ]);
            }
        }

        // 기회 점수로 정렬
        usort($opportunities, function ($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });

        return array_slice($opportunities, 0, 10); // 상위 10개만 반환
    }

    /**
     * 기회 점수 계산
     */
    protected function calculateOpportunityScore(array $keyword): float
    {
        $searchVolume = $keyword['search_volume'] ?? 0;
        $competition = $keyword['competition'] ?? 1;
        $relevance = $keyword['relevance_score'] ?? 0;
        $trend = $this->calculateTrendScore($keyword['trends'] ?? []);

        // 검색량(30%), 경쟁도(30%), 연관성(20%), 트렌드(20%) 반영
        $score = (
            (min($searchVolume / 10000, 1) * 0.3) +
            ((1 - $competition) * 0.3) +
            ($relevance * 0.2) +
            ($trend * 0.2)
        ) * 100;

        return round($score, 2);
    }

    /**
     * 트렌드 점수 계산
     */
    protected function calculateTrendScore(array $trends): float
    {
        if (empty($trends)) {
            return 0;
        }

        // 최근 3개월의 성장률 계산
        $recentTrends = array_slice($trends, -3);
        if (count($recentTrends) < 2) {
            return 0;
        }

        $growth = (end($recentTrends) - reset($recentTrends)) / reset($recentTrends);
        return min(max($growth, 0), 1); // 0~1 사이로 정규화
    }

    /**
     * 추천 결과 점수 계산 및 정렬
     */
    protected function scoreAndSortRecommendations(array $recommendations): array
    {
        foreach (['related', 'popular', 'rising'] as $type) {
            if (isset($recommendations[$type])) {
                $recommendations[$type] = array_map(function ($keyword) {
                    return array_merge($keyword, [
                        'recommendation_score' => $this->calculateRecommendationScore($keyword),
                    ]);
                }, $recommendations[$type]);

                usort($recommendations[$type], function ($a, $b) {
                    return $b['recommendation_score'] <=> $a['recommendation_score'];
                });
            }
        }

        return $recommendations;
    }

    /**
     * 추천 점수 계산
     */
    protected function calculateRecommendationScore(array $keyword): float
    {
        $searchVolume = $keyword['search_volume'] ?? 0;
        $competition = $keyword['competition'] ?? 1;
        $relevance = $keyword['relevance_score'] ?? 0;

        // 검색량(40%), 경쟁도(30%), 연관성(30%) 반영
        $score = (
            (min($searchVolume / 10000, 1) * 0.4) +
            ((1 - $competition) * 0.3) +
            ($relevance * 0.3)
        ) * 100;

        return round($score, 2);
    }
} 