<?php
namespace RocketSourcer\Api;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Services\Analysis\CrossCategoryAnalysisService;
use Psr\Log\LoggerInterface;

class CrossCategoryController
{
    private CrossCategoryAnalysisService $analysisService;
    private LoggerInterface $logger;

    public function __construct(CrossCategoryAnalysisService $analysisService, LoggerInterface $logger)
    {
        $this->analysisService = $analysisService;
        $this->logger = $logger;
    }

    public function index(Request $request): Response
    {
        $this->logger->info('크로스 카테고리 페이지 접속');
        
        ob_start();
        include __DIR__ . '/../Views/cross-category.php';
        $content = ob_get_clean();
        
        return new Response(
            $content,
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
