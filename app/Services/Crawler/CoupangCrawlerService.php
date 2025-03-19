<?php

namespace RocketSourcer\Services\Crawler;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\CoupangProduct;

class CoupangCrawlerService
{
    private ?Client $client = null;
    private ?LoggerInterface $logger = null;
    protected $apiKey;
    protected $secretKey;
    protected $cacheExpiration = 3600; // 1시간

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->apiKey = config('services.coupang.api_key');
        $this->secretKey = config('services.coupang.secret_key');
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client([
                'base_uri' => 'https://api-gateway.coupang.com',
                'timeout' => 30.0,
            ]);
        }
        return $this->client;
    }

    /**
     * 쿠팡 제품 검색
     *
     * @param string $keyword 검색 키워드
     * @param int $page 페이지 번호
     * @param int $size 페이지 크기
     * @return array
     */
    public function searchProducts(string $keyword, int $page = 1, int $size = 20): array
    {
        $cacheKey = "coupang_search_{$keyword}_{$page}_{$size}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($keyword, $page, $size) {
            try {
                $response = $this->getClient()->request('GET', 'products/search', [
                    'query' => [
                        'keyword' => $keyword,
                        'page' => $page,
                        'size' => $size
                    ],
                    'headers' => $this->getAuthHeaders('GET', "products/search")
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 결과 저장
                $this->saveSearchResults($data['products'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'total' => $data['totalCount'] ?? 0,
                    'page' => $page,
                    'size' => $size
                ];
            } catch (RequestException $e) {
                Log::error('쿠팡 제품 검색 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 제품 상세 정보 조회
     *
     * @param string $productId 쿠팡 제품 ID
     * @return array
     */
    public function getProductDetails(string $productId): array
    {
        $cacheKey = "coupang_product_{$productId}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($productId) {
            try {
                $response = $this->getClient()->request('GET', "products/{$productId}", [
                    'headers' => $this->getAuthHeaders('GET', "products/{$productId}")
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 제품 정보 저장
                $this->saveProductDetails($data);
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (RequestException $e) {
                Log::error('쿠팡 제품 상세 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 제품 리뷰 조회
     *
     * @param string $productId 쿠팡 제품 ID
     * @param int $page 페이지 번호
     * @param int $size 페이지 크기
     * @return array
     */
    public function getProductReviews(string $productId, int $page = 1, int $size = 50): array
    {
        $cacheKey = "coupang_reviews_{$productId}_{$page}_{$size}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($productId, $page, $size) {
            try {
                $response = $this->getClient()->request('GET', "products/{$productId}/reviews", [
                    'query' => [
                        'page' => $page,
                        'size' => $size
                    ],
                    'headers' => $this->getAuthHeaders('GET', "products/{$productId}/reviews")
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 리뷰 저장
                $this->saveProductReviews($productId, $data['reviews'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'total' => $data['totalCount'] ?? 0,
                    'page' => $page,
                    'size' => $size
                ];
            } catch (RequestException $e) {
                Log::error('쿠팡 제품 리뷰 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 카테고리별 베스트셀러 제품 조회
     *
     * @param string $categoryId 카테고리 ID
     * @param int $limit 조회할 제품 수
     * @return array
     */
    public function getBestSellers(string $categoryId, int $limit = 100): array
    {
        $cacheKey = "coupang_bestsellers_{$categoryId}_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($categoryId, $limit) {
            try {
                $response = $this->getClient()->request('GET', "categories/{$categoryId}/bestsellers", [
                    'query' => [
                        'limit' => $limit
                    ],
                    'headers' => $this->getAuthHeaders('GET', "categories/{$categoryId}/bestsellers")
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 베스트셀러 저장
                $this->saveBestSellers($categoryId, $data['products'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'category' => $categoryId,
                    'count' => count($data['products'] ?? [])
                ];
            } catch (RequestException $e) {
                Log::error('쿠팡 베스트셀러 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 로켓배송 비율 분석
     *
     * @param array $products 제품 목록
     * @return array
     */
    public function analyzeRocketDeliveryRatio(array $products): array
    {
        $totalCount = count($products);
        if ($totalCount === 0) {
            return [
                'total' => 0,
                'rocket_delivery_count' => 0,
                'rocket_delivery_ratio' => 0,
                'non_rocket_delivery_count' => 0,
                'non_rocket_delivery_ratio' => 0
            ];
        }

        $rocketDeliveryCount = 0;
        $rocketDeliveryProducts = [];
        $nonRocketDeliveryProducts = [];

        foreach ($products as $product) {
            if (isset($product['rocket_delivery']) && $product['rocket_delivery']) {
                $rocketDeliveryCount++;
                $rocketDeliveryProducts[] = $product;
            } else {
                $nonRocketDeliveryProducts[] = $product;
            }
        }

        $rocketDeliveryRatio = ($rocketDeliveryCount / $totalCount) * 100;
        $nonRocketDeliveryCount = $totalCount - $rocketDeliveryCount;
        $nonRocketDeliveryRatio = 100 - $rocketDeliveryRatio;

        return [
            'total' => $totalCount,
            'rocket_delivery_count' => $rocketDeliveryCount,
            'rocket_delivery_ratio' => round($rocketDeliveryRatio, 2),
            'rocket_delivery_products' => $rocketDeliveryProducts,
            'non_rocket_delivery_count' => $nonRocketDeliveryCount,
            'non_rocket_delivery_ratio' => round($nonRocketDeliveryRatio, 2),
            'non_rocket_delivery_products' => $nonRocketDeliveryProducts
        ];
    }

    /**
     * 카테고리별 로켓배송 비율 분석
     *
     * @param string $categoryId 카테고리 ID
     * @return array
     */
    public function analyzeRocketDeliveryRatioByCategory(string $categoryId): array
    {
        $result = $this->getBestSellers($categoryId);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => '카테고리 제품 조회 실패',
                'data' => []
            ];
        }
        
        $products = $result['data']['products'] ?? [];
        $analysis = $this->analyzeRocketDeliveryRatio($products);
        
        return [
            'success' => true,
            'category_id' => $categoryId,
            'data' => $analysis
        ];
    }

    /**
     * 리뷰 시계열 분석
     *
     * @param string $productId 쿠팡 제품 ID
     * @param int $months 분석할 월 수
     * @return array
     */
    public function analyzeReviewTimeSeries(string $productId, int $months = 12): array
    {
        // 모든 리뷰 가져오기 (페이징 처리)
        $allReviews = $this->getAllProductReviews($productId);
        
        if (empty($allReviews)) {
            return [
                'success' => false,
                'message' => '리뷰 데이터가 없습니다',
                'data' => []
            ];
        }
        
        // 현재 날짜에서 지정된 월 수만큼 이전 날짜 계산
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // 월별 데이터 초기화
        $monthlyData = [];
        $currentDate = clone $startDate;
        
        while ($currentDate->lte($endDate)) {
            $yearMonth = $currentDate->format('Y-m');
            $monthlyData[$yearMonth] = [
                'year' => $currentDate->year,
                'month' => $currentDate->month,
                'count' => 0,
                'average_rating' => 0,
                'ratings' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'positive_count' => 0,
                'negative_count' => 0
            ];
            $currentDate->addMonth();
        }
        
        // 리뷰 데이터 집계
        $totalReviews = 0;
        
        foreach ($allReviews as $review) {
            $reviewDate = Carbon::parse($review['created_at']);
            
            // 분석 기간 내의 리뷰만 처리
            if ($reviewDate->between($startDate, $endDate)) {
                $yearMonth = $reviewDate->format('Y-m');
                
                if (isset($monthlyData[$yearMonth])) {
                    $monthlyData[$yearMonth]['count']++;
                    $monthlyData[$yearMonth]['ratings'][$review['rating']]++;
                    
                    $totalReviews++;
                    
                    // 긍정/부정 리뷰 카운트
                    if ($review['rating'] >= 4) {
                        $monthlyData[$yearMonth]['positive_count']++;
                    } elseif ($review['rating'] <= 2) {
                        $monthlyData[$yearMonth]['negative_count']++;
                    }
                }
            }
        }
        
        // 평균 평점 계산
        foreach ($monthlyData as $yearMonth => $data) {
            if ($data['count'] > 0) {
                $totalRating = 0;
                foreach ($data['ratings'] as $rating => $count) {
                    $totalRating += $rating * $count;
                }
                $monthlyData[$yearMonth]['average_rating'] = round($totalRating / $data['count'], 2);
            }
        }
        
        // 추세 분석
        $trend = $this->calculateReviewTrend($monthlyData);
        
        return [
            'success' => true,
            'product_id' => $productId,
            'total_reviews' => $totalReviews,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'months' => $months
            ],
            'monthly_data' => array_values($monthlyData),
            'trend' => $trend
        ];
    }

    /**
     * 리뷰 추세 계산
     *
     * @param array $monthlyData 월별 리뷰 데이터
     * @return array
     */
    protected function calculateReviewTrend(array $monthlyData): array
    {
        $months = array_keys($monthlyData);
        
        if (count($months) < 2) {
            return [
                'review_count_trend' => 'stable',
                'rating_trend' => 'stable'
            ];
        }
        
        // 첫 달과 마지막 달 비교
        $firstMonth = $monthlyData[$months[0]];
        $lastMonth = $monthlyData[$months[count($months) - 1]];
        
        // 리뷰 수 추세
        $reviewCountDiff = $lastMonth['count'] - $firstMonth['count'];
        $reviewCountTrend = 'stable';
        
        if ($reviewCountDiff > 0) {
            $reviewCountTrend = 'increasing';
        } elseif ($reviewCountDiff < 0) {
            $reviewCountTrend = 'decreasing';
        }
        
        // 평점 추세
        $ratingDiff = $lastMonth['average_rating'] - $firstMonth['average_rating'];
        $ratingTrend = 'stable';
        
        if ($ratingDiff > 0.2) {
            $ratingTrend = 'improving';
        } elseif ($ratingDiff < -0.2) {
            $ratingTrend = 'declining';
        }
        
        return [
            'review_count_trend' => $reviewCountTrend,
            'rating_trend' => $ratingTrend,
            'review_count_diff' => $reviewCountDiff,
            'rating_diff' => round($ratingDiff, 2)
        ];
    }

    /**
     * 모든 제품 리뷰 가져오기 (페이징 처리)
     *
     * @param string $productId 쿠팡 제품 ID
     * @return array
     */
    protected function getAllProductReviews(string $productId): array
    {
        $page = 1;
        $size = 100;
        $allReviews = [];
        
        do {
            $result = $this->getProductReviews($productId, $page, $size);
            
            if (!$result['success']) {
                break;
            }
            
            $reviews = $result['data']['reviews'] ?? [];
            $allReviews = array_merge($allReviews, $reviews);
            
            $totalPages = ceil(($result['total'] ?? 0) / $size);
            $page++;
            
        } while ($page <= $totalPages && $page <= 10); // 최대 10페이지(1000개)까지만 가져옴
        
        return $allReviews;
    }

    /**
     * 검색 결과 저장
     *
     * @param array $products 제품 목록
     * @return void
     */
    protected function saveSearchResults(array $products): void
    {
        foreach ($products as $product) {
            $this->saveProductDetails($product);
        }
    }

    /**
     * 제품 상세 정보 저장
     *
     * @param array $productData 제품 데이터
     * @return void
     */
    protected function saveProductDetails(array $productData): void
    {
        if (empty($productData) || !isset($productData['productId'])) {
            return;
        }

        try {
            CoupangProduct::updateOrCreate(
                ['product_id' => $productData['productId']],
                [
                    'title' => $productData['title'] ?? '',
                    'price' => $productData['price'] ?? 0,
                    'original_price' => $productData['originalPrice'] ?? 0,
                    'discount_rate' => $productData['discountRate'] ?? 0,
                    'image_url' => $productData['imageUrl'] ?? '',
                    'product_url' => $productData['productUrl'] ?? '',
                    'category_id' => $productData['categoryId'] ?? '',
                    'category_name' => $productData['categoryName'] ?? '',
                    'rating' => $productData['rating'] ?? 0,
                    'review_count' => $productData['reviewCount'] ?? 0,
                    'rocket_delivery' => $productData['rocketDelivery'] ?? false,
                    'free_shipping' => $productData['freeShipping'] ?? false,
                    'is_best_seller' => $productData['isBestSeller'] ?? false,
                    'data' => json_encode($productData),
                    'last_updated' => Carbon::now()
                ]
            );
        } catch (\Exception $e) {
            Log::error('쿠팡 제품 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * 제품 리뷰 저장
     *
     * @param string $productId 쿠팡 제품 ID
     * @param array $reviews 리뷰 목록
     * @return void
     */
    protected function saveProductReviews(string $productId, array $reviews): void
    {
        foreach ($reviews as $review) {
            try {
                ProductReview::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'review_id' => $review['reviewId'] ?? md5(json_encode($review))
                    ],
                    [
                        'platform' => 'coupang',
                        'rating' => $review['rating'] ?? 0,
                        'title' => $review['title'] ?? '',
                        'content' => $review['content'] ?? '',
                        'author' => $review['author'] ?? '',
                        'created_at' => isset($review['createdAt']) ? Carbon::parse($review['createdAt']) : Carbon::now(),
                        'helpful_count' => $review['helpfulCount'] ?? 0,
                        'data' => json_encode($review)
                    ]
                );
            } catch (\Exception $e) {
                Log::error('쿠팡 리뷰 저장 오류: ' . $e->getMessage());
            }
        }
    }

    /**
     * 베스트셀러 저장
     *
     * @param string $categoryId 카테고리 ID
     * @param array $products 제품 목록
     * @return void
     */
    protected function saveBestSellers(string $categoryId, array $products): void
    {
        foreach ($products as $index => $product) {
            $product['isBestSeller'] = true;
            $product['bestSellerRank'] = $index + 1;
            $product['categoryId'] = $categoryId;
            
            $this->saveProductDetails($product);
        }
    }

    /**
     * API 인증 헤더 생성
     *
     * @param string $method HTTP 메소드
     * @param string $path API 경로
     * @return array
     */
    protected function getAuthHeaders(string $method, string $path): array
    {
        $timestamp = time();
        $signature = $this->generateSignature($method, $path, $timestamp);
        
        return [
            'Authorization' => "CEA {$this->apiKey}:{$signature}",
            'X-Timestamp' => $timestamp
        ];
    }

    /**
     * API 서명 생성
     *
     * @param string $method HTTP 메소드
     * @param string $path API 경로
     * @param int $timestamp 타임스탬프
     * @return string
     */
    protected function generateSignature(string $method, string $path, int $timestamp): string
    {
        $message = "{$method} {$path}\n{$timestamp}";
        return hash_hmac('sha256', $message, $this->secretKey);
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}