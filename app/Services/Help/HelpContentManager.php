<?php

namespace App\Services\Help;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;

class HelpContentManager
{
    /**
     * 도움말 콘텐츠 저장소 경로
     */
    protected $storagePath = 'help_content';
    
    /**
     * 캐시 만료 시간 (분)
     */
    protected $cacheExpiration = 60;
    
    /**
     * Markdown 변환기
     */
    protected $markdownConverter;
    
    /**
     * 생성자
     */
    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AutolinkExtension());
        
        $this->markdownConverter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ], $environment);
    }
    
    /**
     * 모든 도움말 카테고리 목록을 가져옵니다.
     *
     * @return array
     */
    public function getCategories(): array
    {
        return Cache::remember('help_categories', $this->cacheExpiration, function () {
            try {
                $categories = [];
                $files = Storage::disk('local')->files($this->storagePath . '/categories');
                
                foreach ($files as $file) {
                    if (Str::endsWith($file, '.json')) {
                        $content = Storage::disk('local')->get($file);
                        $data = json_decode($content, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $categories[] = $data;
                        }
                    }
                }
                
                return $categories;
            } catch (\Exception $e) {
                Log::error('도움말 카테고리를 가져오는 중 오류 발생: ' . $e->getMessage());
                return [];
            }
        });
    }
    
    /**
     * 특정 카테고리의 모든 도움말 항목을 가져옵니다.
     *
     * @param string $categorySlug
     * @return array
     */
    public function getArticlesByCategory(string $categorySlug): array
    {
        $cacheKey = 'help_articles_' . $categorySlug;
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($categorySlug) {
            try {
                $path = $this->storagePath . '/articles/' . $categorySlug;
                
                if (!Storage::disk('local')->exists($path)) {
                    return [];
                }
                
                $articles = [];
                $files = Storage::disk('local')->files($path);
                
                foreach ($files as $file) {
                    if (Str::endsWith($file, '.md')) {
                        $slug = basename($file, '.md');
                        $content = Storage::disk('local')->get($file);
                        $title = $this->extractTitle($content);
                        
                        $articles[] = [
                            'slug' => $slug,
                            'title' => $title,
                            'excerpt' => $this->generateExcerpt($content),
                        ];
                    }
                }
                
                return $articles;
            } catch (\Exception $e) {
                Log::error('도움말 항목을 가져오는 중 오류 발생: ' . $e->getMessage());
                return [];
            }
        });
    }
    
    /**
     * 특정 도움말 항목을 가져옵니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @return array|null
     */
    public function getArticle(string $categorySlug, string $articleSlug): ?array
    {
        $cacheKey = 'help_article_' . $categorySlug . '_' . $articleSlug;
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($categorySlug, $articleSlug) {
            try {
                $path = $this->storagePath . '/articles/' . $categorySlug . '/' . $articleSlug . '.md';
                
                if (!Storage::disk('local')->exists($path)) {
                    return null;
                }
                
                $content = Storage::disk('local')->get($path);
                $title = $this->extractTitle($content);
                $html = $this->markdownToHtml($content);
                
                return [
                    'slug' => $articleSlug,
                    'title' => $title,
                    'content' => $html,
                    'related_articles' => $this->getRelatedArticles($categorySlug, $articleSlug),
                ];
            } catch (\Exception $e) {
                Log::error('도움말 항목을 가져오는 중 오류 발생: ' . $e->getMessage());
                return null;
            }
        });
    }
    
    /**
     * 도움말 콘텐츠를 검색합니다.
     *
     * @param string $query
     * @return array
     */
    public function searchContent(string $query): array
    {
        try {
            $results = [];
            $categories = $this->getCategories();
            
            foreach ($categories as $category) {
                $articles = $this->getArticlesByCategory($category['slug']);
                
                foreach ($articles as $article) {
                    $path = $this->storagePath . '/articles/' . $category['slug'] . '/' . $article['slug'] . '.md';
                    $content = Storage::disk('local')->get($path);
                    
                    if (Str::contains(strtolower($article['title']), strtolower($query)) || 
                        Str::contains(strtolower($content), strtolower($query))) {
                        $results[] = [
                            'category' => $category['name'],
                            'category_slug' => $category['slug'],
                            'title' => $article['title'],
                            'slug' => $article['slug'],
                            'excerpt' => $this->generateExcerpt($content),
                        ];
                    }
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('도움말 콘텐츠 검색 중 오류 발생: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 도움말 항목을 저장합니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @param string $content
     * @return bool
     */
    public function saveArticle(string $categorySlug, string $articleSlug, string $content): bool
    {
        try {
            $path = $this->storagePath . '/articles/' . $categorySlug;
            
            if (!Storage::disk('local')->exists($path)) {
                Storage::disk('local')->makeDirectory($path, 0755, true);
            }
            
            $filePath = $path . '/' . $articleSlug . '.md';
            Storage::disk('local')->put($filePath, $content);
            
            // 캐시 삭제
            Cache::forget('help_articles_' . $categorySlug);
            Cache::forget('help_article_' . $categorySlug . '_' . $articleSlug);
            
            return true;
        } catch (\Exception $e) {
            Log::error('도움말 항목 저장 중 오류 발생: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 도움말 항목을 삭제합니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @return bool
     */
    public function deleteArticle(string $categorySlug, string $articleSlug): bool
    {
        try {
            $filePath = $this->storagePath . '/articles/' . $categorySlug . '/' . $articleSlug . '.md';
            
            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
                
                // 캐시 삭제
                Cache::forget('help_articles_' . $categorySlug);
                Cache::forget('help_article_' . $categorySlug . '_' . $articleSlug);
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('도움말 항목 삭제 중 오류 발생: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 도움말 카테고리를 저장합니다.
     *
     * @param string $slug
     * @param array $data
     * @return bool
     */
    public function saveCategory(string $slug, array $data): bool
    {
        try {
            $path = $this->storagePath . '/categories';
            
            if (!Storage::disk('local')->exists($path)) {
                Storage::disk('local')->makeDirectory($path, 0755, true);
            }
            
            $filePath = $path . '/' . $slug . '.json';
            Storage::disk('local')->put($filePath, json_encode($data, JSON_PRETTY_PRINT));
            
            // 캐시 삭제
            Cache::forget('help_categories');
            
            return true;
        } catch (\Exception $e) {
            Log::error('도움말 카테고리 저장 중 오류 발생: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 도움말 카테고리를 삭제합니다.
     *
     * @param string $slug
     * @return bool
     */
    public function deleteCategory(string $slug): bool
    {
        try {
            $filePath = $this->storagePath . '/categories/' . $slug . '.json';
            $articlesPath = $this->storagePath . '/articles/' . $slug;
            
            $success = true;
            
            if (Storage::disk('local')->exists($filePath)) {
                $success = Storage::disk('local')->delete($filePath);
            }
            
            if (Storage::disk('local')->exists($articlesPath)) {
                $success = $success && Storage::disk('local')->deleteDirectory($articlesPath);
            }
            
            // 캐시 삭제
            Cache::forget('help_categories');
            Cache::forget('help_articles_' . $slug);
            
            return $success;
        } catch (\Exception $e) {
            Log::error('도움말 카테고리 삭제 중 오류 발생: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Markdown 콘텐츠에서 제목을 추출합니다.
     *
     * @param string $content
     * @return string
     */
    protected function extractTitle(string $content): string
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (Str::startsWith($line, '# ')) {
                return trim(substr($line, 2));
            }
        }
        
        return '제목 없음';
    }
    
    /**
     * Markdown 콘텐츠에서 발췌문을 생성합니다.
     *
     * @param string $content
     * @param int $length
     * @return string
     */
    protected function generateExcerpt(string $content, int $length = 150): string
    {
        // 제목 제거
        $content = preg_replace('/^# .+$/m', '', $content);
        
        // Markdown 형식 제거
        $content = preg_replace('/[#*`_\[\]\(\)]+/', '', $content);
        
        // 여러 줄바꿈 제거
        $content = preg_replace('/\n{2,}/', ' ', $content);
        
        // 공백 정리
        $content = trim(preg_replace('/\s+/', ' ', $content));
        
        // 길이 제한
        if (mb_strlen($content) > $length) {
            $content = mb_substr($content, 0, $length) . '...';
        }
        
        return $content;
    }
    
    /**
     * Markdown을 HTML로 변환합니다.
     *
     * @param string $markdown
     * @return string
     */
    protected function markdownToHtml(string $markdown): string
    {
        return (string) $this->markdownConverter->convert($markdown);
    }
    
    /**
     * 관련 도움말 항목을 가져옵니다.
     *
     * @param string $categorySlug
     * @param string $currentArticleSlug
     * @param int $limit
     * @return array
     */
    protected function getRelatedArticles(string $categorySlug, string $currentArticleSlug, int $limit = 3): array
    {
        $articles = $this->getArticlesByCategory($categorySlug);
        $related = [];
        $count = 0;
        
        foreach ($articles as $article) {
            if ($article['slug'] !== $currentArticleSlug && $count < $limit) {
                $related[] = $article;
                $count++;
            }
        }
        
        return $related;
    }
} 