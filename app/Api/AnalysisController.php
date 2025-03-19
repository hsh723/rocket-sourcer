<?php

namespace RocketSourcer\Api;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Analysis\CrossCategoryAnalysisService;
use Psr\Log\LoggerInterface;

class AnalysisController
{
    private CrossCategoryAnalysisService $analysisService;
    private LoggerInterface $logger;

    public function __construct(CrossCategoryAnalysisService $analysisService, LoggerInterface $logger)
    {
        $this->analysisService = $analysisService;
        $this->logger = $logger;
    }

    public function analyzeCrossCategory(Request $request): Response
    {
        $productId = $request->getQuery('product_id');
        
        if (!$productId) {
            return new Response(
                json_encode(['error' => '제품 ID가 필요합니다']),
                400,
                ['Content-Type' => 'application/json']
            );
        }
        
        $options = [
            'includeRecommendations' => $request->getQuery('include_recommendations', true),
            'analyzeCompetitors' => $request->getQuery('analyze_competitors', true)
        ];
        
        $result = $this->analysisService->analyzeProductCategories($productId, $options);
        
        return new Response(
            json_encode($result),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
