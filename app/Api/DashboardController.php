<?php
namespace RocketSourcer\Api;

use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use Psr\Log\LoggerInterface;

class DashboardController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function index(Request $request): Response
    {
        $this->logger->info('대시보드 페이지 접속');
        
        ob_start();
        include __DIR__ . '/../Views/dashboard.php';
        $content = ob_get_clean();
        
        return new Response(
            $content,
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
