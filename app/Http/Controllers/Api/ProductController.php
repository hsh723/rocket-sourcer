<?php

namespace RocketSourcer\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use RocketSourcer\Http\Controllers\Controller;
use RocketSourcer\Models\Product;
use RocketSourcer\Services\Product\ProductAnalysisService;
use RocketSourcer\Services\Product\ProfitCalculatorService;
use RocketSourcer\Services\Coupang\CoupangProductService;
use RocketSourcer\Http\Requests\Product\{
    StoreProductRequest,
    UpdateProductRequest,
    AnalyzeProductRequest
};

class ProductController extends Controller
{
    protected ProductAnalysisService $analysisService;
    protected ProfitCalculatorService $profitCalculator;
    protected CoupangProductService $coupangService;

    public function __construct(
        ProductAnalysisService $analysisService,
        ProfitCalculatorService $profitCalculator,
        CoupangProductService $coupangService
    ) {
        $this->analysisService = $analysisService;
        $this->profitCalculator = $profitCalculator;
        $this->coupangService = $coupangService;
    }

    /**
     * 제품 목록 조회
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // 필터링
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 정렬
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 페이지네이션
        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => '제품 목록을 성공적으로 조회했습니다.'
        ]);
    }

    /**
     * 제품 상세 조회
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => '제품 정보를 성공적으로 조회했습니다.'
        ]);
    }

    /**
     * 제품 등록
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // 쿠팡 제품 정보 조회
        $productInfo = $this->coupangService->getProduct($validated['product_id']);
        
        if (!$productInfo->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => '쿠팡 제품 정보 조회에 실패했습니다.',
                'errors' => ['product_id' => [$productInfo->getMessage()]]
            ], 422);
        }

        // 제품 생성
        $product = Product::create([
            'user_id' => auth()->id(),
            'product_id' => $validated['product_id'],
            'name' => $productInfo->getData()['name'],
            'description' => $productInfo->getData()['description'],
            'price' => $productInfo->getData()['price'],
            'category' => $productInfo->getData()['category'],
            'status' => 'pending',
            'metadata' => $productInfo->getData(),
        ]);

        // 분석 작업 시작
        $this->analysisService->analyze($product);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => '제품이 성공적으로 등록되었습니다.'
        ], 201);
    }

    /**
     * 제품 수정
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $validated = $request->validated();

        $product->update($validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => '제품 정보가 성공적으로 수정되었습니다.'
        ]);
    }

    /**
     * 제품 삭제
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => '제품이 성공적으로 삭제되었습니다.'
        ]);
    }

    /**
     * 제품 분석
     */
    public function analyze(AnalyzeProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        try {
            $analysis = $this->analysisService->analyze($product);
            
            return response()->json([
                'success' => true,
                'data' => $analysis,
                'message' => '제품 분석이 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '제품 분석 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 수익성 분석
     */
    public function calculateProfit(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        try {
            $profitAnalysis = $this->profitCalculator->calculateProfit($product);
            
            return response()->json([
                'success' => true,
                'data' => $profitAnalysis,
                'message' => '수익성 분석이 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '수익성 분석 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 경쟁사 제품 조회
     */
    public function competitors(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        try {
            $competitors = $this->analysisService->getCompetitors($product);
            
            return response()->json([
                'success' => true,
                'data' => $competitors,
                'message' => '경쟁사 제품 조회가 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '경쟁사 제품 조회 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 제품 추천
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            $recommendations = $this->analysisService->getRecommendations([
                'category' => $request->input('category'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'min_margin' => $request->input('min_margin'),
                'sort_by' => $request->input('sort_by', 'score'),
                'limit' => $request->input('limit', 10)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'message' => '제품 추천이 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '제품 추천 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 제품 트렌드 분석
     */
    public function trends(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        try {
            $trends = $this->analysisService->analyzeTrends($product);
            
            return response()->json([
                'success' => true,
                'data' => $trends,
                'message' => '트렌드 분석이 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '트렌드 분석 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 