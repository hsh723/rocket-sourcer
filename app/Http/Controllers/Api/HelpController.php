<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Help\HelpContentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HelpController extends Controller
{
    /**
     * 도움말 콘텐츠 관리자
     */
    protected $helpContentManager;
    
    /**
     * 생성자
     *
     * @param HelpContentManager $helpContentManager
     */
    public function __construct(HelpContentManager $helpContentManager)
    {
        $this->helpContentManager = $helpContentManager;
    }
    
    /**
     * 초기 도움말 데이터를 가져옵니다.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInitialData()
    {
        $categories = $this->helpContentManager->getCategories();
        $popularArticles = $this->getPopularArticles();
        
        return response()->json([
            'categories' => $categories,
            'popular_articles' => $popularArticles
        ]);
    }
    
    /**
     * 도움말 콘텐츠를 검색합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        
        if ($query) {
            $results = $this->helpContentManager->searchContent($query);
        }
        
        return response()->json([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    /**
     * 특정 카테고리의 도움말 항목 목록을 가져옵니다.
     *
     * @param string $categorySlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategory(string $categorySlug)
    {
        $categories = $this->helpContentManager->getCategories();
        $category = collect($categories)->firstWhere('slug', $categorySlug);
        
        if (!$category) {
            return response()->json(['error' => '도움말 카테고리를 찾을 수 없습니다.'], 404);
        }
        
        $articles = $this->helpContentManager->getArticlesByCategory($categorySlug);
        
        return response()->json([
            'category' => $category,
            'articles' => $articles
        ]);
    }
    
    /**
     * 특정 도움말 항목을 가져옵니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArticle(string $categorySlug, string $articleSlug)
    {
        $categories = $this->helpContentManager->getCategories();
        $category = collect($categories)->firstWhere('slug', $categorySlug);
        
        if (!$category) {
            return response()->json(['error' => '도움말 카테고리를 찾을 수 없습니다.'], 404);
        }
        
        $article = $this->helpContentManager->getArticle($categorySlug, $articleSlug);
        
        if (!$article) {
            return response()->json(['error' => '도움말 항목을 찾을 수 없습니다.'], 404);
        }
        
        // 조회수 증가 (실제 구현에서는 데이터베이스에 저장)
        Log::info("도움말 항목 조회 (API): {$categorySlug}/{$articleSlug}");
        
        return response()->json([
            'category' => $category,
            'article' => $article
        ]);
    }
    
    /**
     * 도움말 항목에 대한 피드백을 제출합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitFeedback(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
            'article_slug' => 'required|string',
            'is_helpful' => 'required|boolean'
        ]);
        
        $categorySlug = $request->input('category_slug');
        $articleSlug = $request->input('article_slug');
        $isHelpful = $request->input('is_helpful');
        $userId = Auth::id();
        
        // 실제 구현에서는 피드백을 데이터베이스에 저장
        Log::info("도움말 피드백: {$categorySlug}/{$articleSlug}, 도움이 됨: " . ($isHelpful ? '예' : '아니오') . ", 사용자: {$userId}");
        
        return response()->json(['success' => true]);
    }
    
    /**
     * 인기 도움말 항목을 가져옵니다.
     *
     * @param int $limit
     * @return array
     */
    protected function getPopularArticles($limit = 5)
    {
        // 실제 구현에서는 조회수 데이터베이스에서 가져오기
        // 여기서는 간단한 예시로 대체
        $popularArticles = [];
        $categories = $this->helpContentManager->getCategories();
        
        foreach ($categories as $category) {
            $articles = $this->helpContentManager->getArticlesByCategory($category['slug']);
            
            foreach ($articles as $article) {
                $popularArticles[] = [
                    'category' => $category['name'],
                    'category_slug' => $category['slug'],
                    'title' => $article['title'],
                    'slug' => $article['slug'],
                    'excerpt' => $article['excerpt'],
                ];
                
                if (count($popularArticles) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $popularArticles;
    }
} 