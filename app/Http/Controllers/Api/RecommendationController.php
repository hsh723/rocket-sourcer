<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Recommendation\SourcingRecommendationService;
use App\Services\Recommendation\OpportunityScoreCalculator;
use App\Services\Recommendation\ProfitabilityAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RecommendationController extends Controller
{
    protected SourcingRecommendationService $recommendationService;
    protected OpportunityScoreCalculator $opportunityScoreCalculator;
    protected ProfitabilityAnalyzer $profitabilityAnalyzer;
    
    /**
     * 컨트롤러 생성자
     */
    public function __construct(
        SourcingRecommendationService $recommendationService,
        OpportunityScoreCalculator $opportunityScoreCalculator,
        ProfitabilityAnalyzer $profitabilityAnalyzer
    ) {
        $this->recommendationService = $recommendationService;
        $this->opportunityScoreCalculator = $opportunityScoreCalculator;
        $this->profitabilityAnalyzer = $profitabilityAnalyzer;
    }
    
    /**
     * 개인화된 소싱 추천 가져오기
     */
    public function getPersonalizedRecommendations(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'category_id' => 'nullable|integer|exists:categories,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'min_margin' => 'nullable|numeric|min:0',
                'sort_by' => 'nullable|string|in:opportunity_score,profit,margin,growth',
                'sort_direction' => 'nullable|string|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 필터 구성
            $filters = $this->buildFiltersFromRequest($request);
            
            // 사용자 ID 가져오기
            $userId = Auth::id();
            
            // 추천 가져오기
            $limit = $request->input('limit', 10);
            $recommendations = $this->recommendationService->getPersonalizedRecommendations($userId, $filters, $limit);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'count' => $recommendations->count(),
                    'filters' => $filters
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('개인화된 추천 가져오기 중 오류 발생: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '추천을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 트렌드 기반 추천 가져오기
     */
    public function getTrendBasedRecommendations(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'category_id' => 'nullable|integer|exists:categories,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'min_margin' => 'nullable|numeric|min:0',
                'sort_by' => 'nullable|string|in:opportunity_score,profit,margin,growth',
                'sort_direction' => 'nullable|string|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 필터 구성
            $filters = $this->buildFiltersFromRequest($request);
            
            // 추천 가져오기
            $limit = $request->input('limit', 10);
            $recommendations = $this->recommendationService->getTrendBasedRecommendations($filters, $limit);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'count' => $recommendations->count(),
                    'filters' => $filters
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('트렌드 기반 추천 가져오기 중 오류 발생: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '추천을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 카테고리 기반 추천 가져오기
     */
    public function getCategoryRecommendations(Request $request, $categoryId)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'min_margin' => 'nullable|numeric|min:0',
                'sort_by' => 'nullable|string|in:opportunity_score,profit,margin,growth',
                'sort_direction' => 'nullable|string|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 필터 구성
            $filters = $this->buildFiltersFromRequest($request);
            
            // 추천 가져오기
            $limit = $request->input('limit', 10);
            $recommendations = $this->recommendationService->getCategoryRecommendations($categoryId, $filters, $limit);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'count' => $recommendations->count(),
                    'category_id' => $categoryId,
                    'filters' => $filters
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('카테고리 기반 추천 가져오기 중 오류 발생: ' . $e->getMessage(), [
                'category_id' => $categoryId,
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '추천을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 가격 차이 기반 추천 가져오기
     */
    public function getPriceGapRecommendations(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'category_id' => 'nullable|integer|exists:categories,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'min_margin' => 'nullable|numeric|min:0',
                'min_price_gap' => 'nullable|numeric|min:0',
                'sort_by' => 'nullable|string|in:opportunity_score,profit,margin,price_gap',
                'sort_direction' => 'nullable|string|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 필터 구성
            $filters = $this->buildFiltersFromRequest($request);
            
            // 추천 가져오기
            $limit = $request->input('limit', 10);
            $recommendations = $this->recommendationService->getPriceGapRecommendations($filters, $limit);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'count' => $recommendations->count(),
                    'filters' => $filters
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('가격 차이 기반 추천 가져오기 중 오류 발생: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '추천을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 제품 수익성 분석
     */
    public function analyzeProfitability(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'wholesale_price' => 'required|numeric|min:0',
                'retail_price' => 'required|numeric|min:0',
                'costs' => 'nullable|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 제품 데이터 구성
            $product = [
                'wholesale_price' => $request->input('wholesale_price'),
                'retail_price' => $request->input('retail_price')
            ];
            
            // 비용 데이터가 제공된 경우 추가
            if ($request->has('costs')) {
                $product['costs'] = $request->input('costs');
            }
            
            // 수익성 분석
            $profitability = $this->profitabilityAnalyzer->analyzeProfitability($product);
            
            return response()->json([
                'success' => true,
                'data' => $profitability
            ]);
        } catch (\Exception $e) {
            Log::error('수익성 분석 중 오류 발생: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '수익성 분석 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 최적 판매 가격 계산
     */
    public function calculateOptimalPrice(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'wholesale_price' => 'required|numeric|min:0',
                'target_margin' => 'nullable|numeric|min:0|max:100',
                'costs' => 'nullable|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 제품 데이터 구성
            $product = [
                'wholesale_price' => $request->input('wholesale_price')
            ];
            
            // 비용 데이터가 제공된 경우 추가
            if ($request->has('costs')) {
                $product['costs'] = $request->input('costs');
            }
            
            // 목표 마진율
            $targetMargin = $request->input('target_margin', 30.0);
            
            // 최적 가격 계산
            $optimalPrice = $this->profitabilityAnalyzer->calculateOptimalPrice($product, $targetMargin);
            
            // 가격 시뮬레이션
            $pricePoints = [
                $optimalPrice * 0.8,
                $optimalPrice * 0.9,
                $optimalPrice,
                $optimalPrice * 1.1,
                $optimalPrice * 1.2
            ];
            
            $simulations = $this->profitabilityAnalyzer->simulatePricing($product, $pricePoints);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'optimal_price' => $optimalPrice,
                    'target_margin' => $targetMargin,
                    'simulations' => $simulations
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('최적 가격 계산 중 오류 발생: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '최적 가격 계산 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 사용자 피드백 제출
     */
    public function submitFeedback(Request $request)
    {
        try {
            // 요청 유효성 검사
            $validator = Validator::make($request->all(), [
                'recommendation_id' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
                'feedback_type' => 'required|string|in:relevance,profitability,quality,other',
                'comment' => 'nullable|string|max:500'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '유효하지 않은 요청 파라미터',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // 사용자 ID 가져오기
            $userId = Auth::id();
            
            // 피드백 데이터 구성
            $feedback = [
                'recommendation_id' => $request->input('recommendation_id'),
                'rating' => $request->input('rating'),
                'feedback_type' => $request->input('feedback_type'),
                'comment' => $request->input('comment')
            ];
            
            // 피드백 처리
            $success = $this->recommendationService->improveRecommendationsWithFeedback($userId, $feedback);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => '피드백 처리 중 오류가 발생했습니다.'
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => '피드백이 성공적으로 제출되었습니다.'
            ]);
        } catch (\Exception $e) {
            Log::error('피드백 제출 중 오류 발생: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '피드백 제출 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 요청에서 필터 구성
     */
    protected function buildFiltersFromRequest(Request $request): array
    {
        $filters = [];
        
        // 카테고리 필터
        if ($request->has('category_id')) {
            $filters['category_id'] = $request->input('category_id');
        }
        
        // 가격 범위 필터
        if ($request->has('min_price')) {
            $filters['min_price'] = $request->input('min_price');
        }
        
        if ($request->has('max_price')) {
            $filters['max_price'] = $request->input('max_price');
        }
        
        // 마진 필터
        if ($request->has('min_margin')) {
            $filters['min_margin'] = $request->input('min_margin');
        }
        
        // 가격 차이 필터
        if ($request->has('min_price_gap')) {
            $filters['min_price_gap'] = $request->input('min_price_gap');
        }
        
        // 정렬 설정
        if ($request->has('sort_by')) {
            $filters['sort_by'] = $request->input('sort_by');
            $filters['sort_direction'] = $request->input('sort_direction', 'desc');
        }
        
        return $filters;
    }
} 