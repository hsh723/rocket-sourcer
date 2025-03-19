<?php

namespace RocketSourcer\Api;

use Psr\Log\LoggerInterface;
use RocketSourcer\Core\Http\Request;
use RocketSourcer\Core\Http\Response;
use RocketSourcer\Models\Keyword;
use RocketSourcer\Services\Keyword\KeywordAnalysisService;
use RocketSourcer\Services\Keyword\KeywordRecommendationService;

class KeywordController extends BaseController
{
    protected KeywordAnalysisService $analysisService;
    protected KeywordRecommendationService $recommendationService;
    protected LoggerInterface $logger;

    public function __construct(
        KeywordAnalysisService $analysisService,
        KeywordRecommendationService $recommendationService,
        LoggerInterface $logger
    ) {
        $this->analysisService = $analysisService;
        $this->recommendationService = $recommendationService;
        $this->logger = $logger;
    }

    /**
     * 키워드 목록 조회
     */
    public function index(Request $request): Response
    {
        try {
            $query = Keyword::query();

            // 필터링
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            if ($category = $request->get('category')) {
                $query->whereJsonContains('categories', $category);
            }

            if ($search = $request->get('search')) {
                $query->where('keyword', 'LIKE', "%{$search}%");
            }

            // 정렬
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 페이지네이션
            $perPage = $request->get('per_page', 15);
            $keywords = $query->paginate($perPage);

            return $this->success([
                'keywords' => $keywords->items(),
                'pagination' => [
                    'total' => $keywords->total(),
                    'per_page' => $keywords->perPage(),
                    'current_page' => $keywords->currentPage(),
                    'last_page' => $keywords->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('키워드 목록 조회 실패', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드 목록을 조회하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 키워드 추가
     */
    public function store(Request $request): Response
    {
        try {
            $this->validate($request, [
                'keyword' => 'required|string|max:255',
                'categories' => 'array',
                'metadata' => 'array',
            ]);

            $keyword = new Keyword([
                'keyword' => $request->get('keyword'),
                'categories' => $request->get('categories', []),
                'metadata' => $request->get('metadata', []),
                'status' => 'pending',
            ]);

            $keyword->save();

            // 비동기 분석 작업 시작
            $analysis = $this->analysisService->startAsyncAnalysis($keyword);

            return $this->success([
                'keyword' => $keyword,
                'analysis' => $analysis,
            ], '키워드가 성공적으로 추가되었습니다.');

        } catch (\Exception $e) {
            $this->logger->error('키워드 추가 실패', [
                'keyword' => $request->get('keyword'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드를 추가하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 키워드 상세 정보 조회
     */
    public function show(Request $request, string $id): Response
    {
        try {
            $keyword = Keyword::findOrFail($id);
            $analysis = $this->analysisService->getAnalysisResult($keyword);

            return $this->success([
                'keyword' => $keyword,
                'analysis' => $analysis,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('키워드 상세 정보 조회 실패', [
                'keyword_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드 정보를 조회하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 키워드 삭제
     */
    public function destroy(Request $request, string $id): Response
    {
        try {
            $keyword = Keyword::findOrFail($id);
            $keyword->delete();

            return $this->success(null, '키워드가 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            $this->logger->error('키워드 삭제 실패', [
                'keyword_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드를 삭제하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 키워드 분석 실행
     */
    public function analyze(Request $request): Response
    {
        try {
            $this->validate($request, [
                'keyword_id' => 'required|string',
            ]);

            $keyword = Keyword::findOrFail($request->get('keyword_id'));
            $analysis = $this->analysisService->analyze($keyword);

            return $this->success([
                'analysis' => $analysis,
            ], '키워드 분석이 완료되었습니다.');

        } catch (\Exception $e) {
            $this->logger->error('키워드 분석 실패', [
                'keyword_id' => $request->get('keyword_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드 분석 중 오류가 발생했습니다.');
        }
    }

    /**
     * 키워드 트렌드 조회
     */
    public function trends(Request $request): Response
    {
        try {
            $this->validate($request, [
                'keyword_id' => 'required|string',
            ]);

            $keyword = Keyword::findOrFail($request->get('keyword_id'));
            $analysis = $this->analysisService->getAnalysisResult($keyword);

            if (!$analysis) {
                return $this->error('분석 결과가 없습니다.');
            }

            return $this->success([
                'trends' => $analysis->result['trends'] ?? [],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('키워드 트렌드 조회 실패', [
                'keyword_id' => $request->get('keyword_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('키워드 트렌드를 조회하는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 추천 키워드 조회
     */
    public function recommendations(Request $request): Response
    {
        try {
            $this->validate($request, [
                'keyword_id' => 'required|string',
                'limit' => 'integer|min:1|max:100',
            ]);

            $keyword = Keyword::findOrFail($request->get('keyword_id'));
            $recommendations = $this->recommendationService->getRecommendations($keyword, [
                'limit' => $request->get('limit', 20),
            ]);

            return $this->success([
                'recommendations' => $recommendations,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('추천 키워드 조회 실패', [
                'keyword_id' => $request->get('keyword_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('추천 키워드를 조회하는 중 오류가 발생했습니다.');
        }
    }
} 