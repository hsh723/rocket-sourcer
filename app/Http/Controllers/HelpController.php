<?php

namespace App\Http\Controllers;

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
     * 도움말 홈 페이지를 표시합니다.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $categories = $this->helpContentManager->getCategories();
        
        return view('help.index', [
            'categories' => $categories,
            'popularArticles' => $this->getPopularArticles(),
        ]);
    }
    
    /**
     * 특정 카테고리의 도움말 항목 목록을 표시합니다.
     *
     * @param string $categorySlug
     * @return \Illuminate\View\View
     */
    public function category($categorySlug)
    {
        $categories = $this->helpContentManager->getCategories();
        $category = collect($categories)->firstWhere('slug', $categorySlug);
        
        if (!$category) {
            abort(404, '도움말 카테고리를 찾을 수 없습니다.');
        }
        
        $articles = $this->helpContentManager->getArticlesByCategory($categorySlug);
        
        return view('help.category', [
            'category' => $category,
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }
    
    /**
     * 특정 도움말 항목을 표시합니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @return \Illuminate\View\View
     */
    public function article($categorySlug, $articleSlug)
    {
        $categories = $this->helpContentManager->getCategories();
        $category = collect($categories)->firstWhere('slug', $categorySlug);
        
        if (!$category) {
            abort(404, '도움말 카테고리를 찾을 수 없습니다.');
        }
        
        $article = $this->helpContentManager->getArticle($categorySlug, $articleSlug);
        
        if (!$article) {
            abort(404, '도움말 항목을 찾을 수 없습니다.');
        }
        
        // 조회수 증가
        $this->incrementArticleViews($categorySlug, $articleSlug);
        
        return view('help.article', [
            'category' => $category,
            'article' => $article,
            'categories' => $categories,
        ]);
    }
    
    /**
     * 도움말 콘텐츠를 검색합니다.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        
        if ($query) {
            $results = $this->helpContentManager->searchContent($query);
        }
        
        $categories = $this->helpContentManager->getCategories();
        
        return view('help.search', [
            'query' => $query,
            'results' => $results,
            'categories' => $categories,
        ]);
    }
    
    /**
     * 도움말 항목 편집 페이지를 표시합니다.
     *
     * @param string|null $categorySlug
     * @param string|null $articleSlug
     * @return \Illuminate\View\View
     */
    public function edit($categorySlug = null, $articleSlug = null)
    {
        // 관리자 권한 확인
        if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, '이 페이지에 접근할 권한이 없습니다.');
        }
        
        $categories = $this->helpContentManager->getCategories();
        $article = null;
        $category = null;
        
        if ($categorySlug) {
            $category = collect($categories)->firstWhere('slug', $categorySlug);
            
            if (!$category) {
                abort(404, '도움말 카테고리를 찾을 수 없습니다.');
            }
            
            if ($articleSlug) {
                $article = $this->helpContentManager->getArticle($categorySlug, $articleSlug);
                
                if (!$article) {
                    abort(404, '도움말 항목을 찾을 수 없습니다.');
                }
            }
        }
        
        return view('help.edit', [
            'categories' => $categories,
            'category' => $category,
            'article' => $article,
        ]);
    }
    
    /**
     * 도움말 항목을 저장합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        // 관리자 권한 확인
        if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, '이 페이지에 접근할 권한이 없습니다.');
        }
        
        $request->validate([
            'category_slug' => 'required|string|max:100',
            'article_slug' => 'required|string|max:100',
            'content' => 'required|string',
        ]);
        
        $categorySlug = $request->input('category_slug');
        $articleSlug = $request->input('article_slug');
        $content = $request->input('content');
        
        $success = $this->helpContentManager->saveArticle($categorySlug, $articleSlug, $content);
        
        if ($success) {
            return redirect()->route('help.article', ['categorySlug' => $categorySlug, 'articleSlug' => $articleSlug])
                ->with('success', '도움말 항목이 성공적으로 저장되었습니다.');
        } else {
            return back()->withInput()->with('error', '도움말 항목 저장 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 도움말 항목을 삭제합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        // 관리자 권한 확인
        if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, '이 페이지에 접근할 권한이 없습니다.');
        }
        
        $request->validate([
            'category_slug' => 'required|string|max:100',
            'article_slug' => 'required|string|max:100',
        ]);
        
        $categorySlug = $request->input('category_slug');
        $articleSlug = $request->input('article_slug');
        
        $success = $this->helpContentManager->deleteArticle($categorySlug, $articleSlug);
        
        if ($success) {
            return redirect()->route('help.category', ['categorySlug' => $categorySlug])
                ->with('success', '도움말 항목이 성공적으로 삭제되었습니다.');
        } else {
            return back()->with('error', '도움말 항목 삭제 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 도움말 카테고리를 저장합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveCategory(Request $request)
    {
        // 관리자 권한 확인
        if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, '이 페이지에 접근할 권한이 없습니다.');
        }
        
        $request->validate([
            'slug' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
        ]);
        
        $slug = $request->input('slug');
        $data = [
            'slug' => $slug,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'icon' => $request->input('icon'),
        ];
        
        $success = $this->helpContentManager->saveCategory($slug, $data);
        
        if ($success) {
            return redirect()->route('help.category', ['categorySlug' => $slug])
                ->with('success', '도움말 카테고리가 성공적으로 저장되었습니다.');
        } else {
            return back()->withInput()->with('error', '도움말 카테고리 저장 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 도움말 카테고리를 삭제합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteCategory(Request $request)
    {
        // 관리자 권한 확인
        if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, '이 페이지에 접근할 권한이 없습니다.');
        }
        
        $request->validate([
            'slug' => 'required|string|max:100',
        ]);
        
        $slug = $request->input('slug');
        
        $success = $this->helpContentManager->deleteCategory($slug);
        
        if ($success) {
            return redirect()->route('help.index')
                ->with('success', '도움말 카테고리가 성공적으로 삭제되었습니다.');
        } else {
            return back()->with('error', '도움말 카테고리 삭제 중 오류가 발생했습니다.');
        }
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
    
    /**
     * 도움말 항목의 조회수를 증가시킵니다.
     *
     * @param string $categorySlug
     * @param string $articleSlug
     * @return void
     */
    protected function incrementArticleViews($categorySlug, $articleSlug)
    {
        // 실제 구현에서는 조회수를 데이터베이스에 저장
        // 여기서는 로깅만 수행
        Log::info("도움말 항목 조회: {$categorySlug}/{$articleSlug}");
    }
} 