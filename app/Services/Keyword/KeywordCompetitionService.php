<?php

namespace RocketSourcer\Services\Keyword;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Cache;
use RocketSourcer\Models\Keyword;
use RocketSourcer\Services\Coupang\CoupangKeywordService;

class KeywordCompetitionService
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
     * 키워드 경쟁 분석
     */
    public function analyzeCompetition(Keyword $keyword): array
    {
        $cacheKey = "keyword_competition:{$keyword->getId()}";
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        try {
            // 경쟁 데이터 수집
            $competitionData = $this->coupangKeywordService->getKeywordCompetition($keyword->getKeyword());
            
            if (!$competitionData->isSuccess()) {
                throw new \Exception($competitionData->getMessage());
            }

            // 경쟁사 데이터 수집
            $competitors = $this->coupangKeywordService->getCompetitors($keyword->getKeyword(), [
                'limit' => 10,
                'sort' => 'RELEVANCE'
            ]);

            $result = [
                'score' => $competitionData->getData()['competition']['score'] ?? 0,
                'level' => $this->determineCompetitionLevel($competitionData->getData()['competition']['score'] ?? 0),
                'metrics' => [
                    'total_competitors' => $competitionData->getData()['total_competitors'] ?? 0,
                    'average_price' => $competitionData->getData()['average_price'] ?? 0,
                    'price_range' => [
                        'min' => $competitionData->getData()['price_range']['min'] ?? 0,
                        'max' => $competitionData->getData()['price_range']['max'] ?? 0,
                    ],
                ],
                'top_competitors' => $this->processCompetitors($competitors->getData()['competitors'] ?? []),
                'difficulty_factors' => $this->analyzeDifficultyFactors($competitionData->getData()),
                'opportunities' => $this->findOpportunities($competitionData->getData()),
            ];

            // 캐시에 결과 저장
            $this->cache->set($cacheKey, $result, 3600); // 1시간 캐시

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('키워드 경쟁 분석 실패', [
                'keyword_id' => $keyword->getId(),
                'keyword' => $keyword->getKeyword(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 경쟁 수준 결정
     */
    protected function determineCompetitionLevel(float $score): string
    {
        if ($score >= 80) {
            return 'VERY_HIGH';
        } elseif ($score >= 60) {
            return 'HIGH';
        } elseif ($score >= 40) {
            return 'MEDIUM';
        } elseif ($score >= 20) {
            return 'LOW';
        } else {
            return 'VERY_LOW';
        }
    }

    /**
     * 경쟁사 데이터 처리
     */
    protected function processCompetitors(array $competitors): array
    {
        return array_map(function ($competitor) {
            return [
                'seller_id' => $competitor['seller_id'] ?? null,
                'seller_name' => $competitor['seller_name'] ?? null,
                'product_count' => $competitor['product_count'] ?? 0,
                'average_rating' => $competitor['average_rating'] ?? 0,
                'review_count' => $competitor['review_count'] ?? 0,
                'market_share' => $competitor['market_share'] ?? 0,
                'price_competitiveness' => $competitor['price_competitiveness'] ?? 0,
            ];
        }, $competitors);
    }

    /**
     * 난이도 요소 분석
     */
    protected function analyzeDifficultyFactors(array $data): array
    {
        $factors = [];

        // 경쟁사 수 분석
        if (($data['total_competitors'] ?? 0) > 100) {
            $factors[] = [
                'type' => 'competitor_count',
                'level' => 'high',
                'description' => '경쟁사가 많아 시장 진입이 어려울 수 있습니다.',
            ];
        }

        // 가격 경쟁 분석
        if (($data['price_competition'] ?? 0) > 70) {
            $factors[] = [
                'type' => 'price_competition',
                'level' => 'high',
                'description' => '가격 경쟁이 치열합니다.',
            ];
        }

        // 브랜드 점유율 분석
        if (($data['brand_dominance'] ?? 0) > 60) {
            $factors[] = [
                'type' => 'brand_dominance',
                'level' => 'high',
                'description' => '상위 브랜드의 시장 지배력이 강합니다.',
            ];
        }

        return $factors;
    }

    /**
     * 기회 요소 분석
     */
    protected function findOpportunities(array $data): array
    {
        $opportunities = [];

        // 가격 기회 분석
        if (($data['price_gap'] ?? 0) > 30) {
            $opportunities[] = [
                'type' => 'price_opportunity',
                'description' => '가격 차별화 기회가 있습니다.',
                'potential_score' => 8,
            ];
        }

        // 품질 기회 분석
        if (($data['average_rating'] ?? 5) < 4) {
            $opportunities[] = [
                'type' => 'quality_opportunity',
                'description' => '품질 개선을 통한 시장 진입 기회가 있습니다.',
                'potential_score' => 7,
            ];
        }

        // 틈새 시장 기회 분석
        if (($data['niche_opportunity'] ?? 0) > 50) {
            $opportunities[] = [
                'type' => 'niche_opportunity',
                'description' => '틈새 시장 진입 기회가 있습니다.',
                'potential_score' => 6,
            ];
        }

        return $opportunities;
    }
} 