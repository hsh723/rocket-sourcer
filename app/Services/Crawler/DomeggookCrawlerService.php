<?php

namespace App\Services\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\DomeggookProduct;
use App\Models\ProductMatch;

class DomeggookCrawlerService
{
    protected $client;
    protected $apiKey;
    protected $cacheExpiration = 3600; // 1시간

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.domeggook.api_url'),
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
        
        $this->apiKey = config('services.domeggook.api_key');
    }

    /**
     * 도매꾹 제품 검색
     *
     * @param string $keyword 검색 키워드
     * @param int $page 페이지 번호
     * @param int $size 페이지 크기
     * @param array $options 추가 검색 옵션
     * @return array
     */
    public function searchProducts(string $keyword, int $page = 1, int $size = 20, array $options = []): array
    {
        $cacheKey = "domeggook_search_" . md5($keyword . json_encode([$page, $size, $options]));
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($keyword, $page, $size, $options) {
            try {
                $query = [
                    'keyword' => $keyword,
                    'page' => $page,
                    'size' => $size,
                    'apiKey' => $this->apiKey
                ];
                
                // 추가 검색 옵션 병합
                $query = array_merge($query, $options);
                
                $response = $this->client->request('GET', 'products/search', [
                    'query' => $query
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
                Log::error('도매꾹 제품 검색 오류: ' . $e->getMessage());
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
     * @param string $productId 도매꾹 제품 ID
     * @return array
     */
    public function getProductDetails(string $productId): array
    {
        $cacheKey = "domeggook_product_{$productId}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($productId) {
            try {
                $response = $this->client->request('GET', "products/{$productId}", [
                    'query' => [
                        'apiKey' => $this->apiKey
                    ]
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 제품 정보 저장
                $this->saveProductDetails($data);
                
                return [
                    'success' => true,
                    'data' => $data
                ];
            } catch (RequestException $e) {
                Log::error('도매꾹 제품 상세 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 카테고리별 제품 조회
     *
     * @param string $categoryId 카테고리 ID
     * @param int $page 페이지 번호
     * @param int $size 페이지 크기
     * @return array
     */
    public function getProductsByCategory(string $categoryId, int $page = 1, int $size = 20): array
    {
        $cacheKey = "domeggook_category_{$categoryId}_{$page}_{$size}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($categoryId, $page, $size) {
            try {
                $response = $this->client->request('GET', "categories/{$categoryId}/products", [
                    'query' => [
                        'page' => $page,
                        'size' => $size,
                        'apiKey' => $this->apiKey
                    ]
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
                Log::error('도매꾹 카테고리 제품 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 인기 제품 조회
     *
     * @param int $limit 조회할 제품 수
     * @return array
     */
    public function getPopularProducts(int $limit = 100): array
    {
        $cacheKey = "domeggook_popular_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($limit) {
            try {
                $response = $this->client->request('GET', "products/popular", [
                    'query' => [
                        'limit' => $limit,
                        'apiKey' => $this->apiKey
                    ]
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 결과 저장
                $this->saveSearchResults($data['products'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'count' => count($data['products'] ?? [])
                ];
            } catch (RequestException $e) {
                Log::error('도매꾹 인기 제품 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 신규 제품 조회
     *
     * @param int $days 최근 며칠 이내의 제품을 조회할지
     * @param int $limit 조회할 제품 수
     * @return array
     */
    public function getNewProducts(int $days = 7, int $limit = 100): array
    {
        $cacheKey = "domeggook_new_{$days}_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($days, $limit) {
            try {
                $response = $this->client->request('GET', "products/new", [
                    'query' => [
                        'days' => $days,
                        'limit' => $limit,
                        'apiKey' => $this->apiKey
                    ]
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 결과 저장
                $this->saveSearchResults($data['products'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'count' => count($data['products'] ?? [])
                ];
            } catch (RequestException $e) {
                Log::error('도매꾹 신규 제품 조회 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 이미지 기반 유사 제품 검색
     *
     * @param string $imageUrl 이미지 URL
     * @param int $limit 조회할 제품 수
     * @return array
     */
    public function searchSimilarProductsByImage(string $imageUrl, int $limit = 20): array
    {
        $cacheKey = "domeggook_image_search_" . md5($imageUrl . $limit);
        
        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($imageUrl, $limit) {
            try {
                $response = $this->client->request('POST', "products/image-search", [
                    'json' => [
                        'imageUrl' => $imageUrl,
                        'limit' => $limit
                    ],
                    'query' => [
                        'apiKey' => $this->apiKey
                    ]
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                // 결과 저장
                $this->saveSearchResults($data['products'] ?? []);
                
                return [
                    'success' => true,
                    'data' => $data,
                    'count' => count($data['products'] ?? [])
                ];
            } catch (RequestException $e) {
                Log::error('도매꾹 이미지 검색 오류: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
        });
    }

    /**
     * 제품 매칭 검색
     * 
     * @param array $product 매칭할 제품 정보
     * @param int $limit 조회할 매칭 제품 수
     * @return array
     */
    public function findProductMatches(array $product, int $limit = 10): array
    {
        if (empty($product['name'])) {
            return [
                'success' => false,
                'message' => '제품 이름이 필요합니다',
                'data' => []
            ];
        }

        // 키워드 추출
        $keywords = $this->extractKeywords($product);
        
        // 검색 결과
        $searchResults = $this->searchProducts(implode(' ', $keywords), 1, $limit * 3);
        
        if (!$searchResults['success']) {
            return $searchResults;
        }
        
        $products = $searchResults['data']['products'] ?? [];
        
        // 유사도 계산 및 정렬
        $matches = $this->calculateSimilarity($product, $products);
        
        // 상위 N개 결과만 반환
        $topMatches = array_slice($matches, 0, $limit);
        
        // 매칭 결과 저장
        $this->saveProductMatches($product, $topMatches);
        
        return [
            'success' => true,
            'data' => [
                'matches' => $topMatches,
                'total' => count($matches)
            ]
        ];
    }

    /**
     * 키워드 추출
     *
     * @param array $product 제품 정보
     * @return array
     */
    protected function extractKeywords(array $product): array
    {
        $keywords = [];
        
        // 제품명에서 키워드 추출
        if (!empty($product['name'])) {
            // 불용어 제거 및 키워드 추출
            $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $product['name']);
            $words = preg_split('/\s+/', $name);
            
            foreach ($words as $word) {
                $word = trim($word);
                if (mb_strlen($word) >= 2) {
                    $keywords[] = $word;
                }
            }
        }
        
        // 카테고리 정보 추가
        if (!empty($product['category'])) {
            $keywords[] = $product['category'];
        }
        
        // 브랜드 정보 추가
        if (!empty($product['brand'])) {
            $keywords[] = $product['brand'];
        }
        
        // 중복 제거 및 상위 5개 키워드만 사용
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 5);
        
        return $keywords;
    }

    /**
     * 유사도 계산
     *
     * @param array $sourceProduct 원본 제품
     * @param array $targetProducts 대상 제품 목록
     * @return array
     */
    protected function calculateSimilarity(array $sourceProduct, array $targetProducts): array
    {
        $matches = [];
        
        foreach ($targetProducts as $targetProduct) {
            $similarity = 0;
            
            // 제품명 유사도 (가장 중요)
            if (!empty($sourceProduct['name']) && !empty($targetProduct['name'])) {
                $nameSimilarity = $this->calculateTextSimilarity($sourceProduct['name'], $targetProduct['name']);
                $similarity += $nameSimilarity * 0.6; // 60% 가중치
            }
            
            // 가격 유사도
            if (!empty($sourceProduct['price']) && !empty($targetProduct['price'])) {
                $priceDiff = abs($sourceProduct['price'] - $targetProduct['price']) / max($sourceProduct['price'], $targetProduct['price']);
                $priceSimilarity = 1 - min($priceDiff, 1);
                $similarity += $priceSimilarity * 0.2; // 20% 가중치
            }
            
            // 카테고리 유사도
            if (!empty($sourceProduct['category']) && !empty($targetProduct['category'])) {
                $categorySimilarity = $sourceProduct['category'] === $targetProduct['category'] ? 1 : 0;
                $similarity += $categorySimilarity * 0.1; // 10% 가중치
            }
            
            // 브랜드 유사도
            if (!empty($sourceProduct['brand']) && !empty($targetProduct['brand'])) {
                $brandSimilarity = $this->calculateTextSimilarity($sourceProduct['brand'], $targetProduct['brand']);
                $similarity += $brandSimilarity * 0.1; // 10% 가중치
            }
            
            $targetProduct['similarity'] = round($similarity, 4);
            $matches[] = $targetProduct;
        }
        
        // 유사도 기준으로 내림차순 정렬
        usort($matches, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return $matches;
    }

    /**
     * 텍스트 유사도 계산
     *
     * @param string $text1 텍스트1
     * @param string $text2 텍스트2
     * @return float
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        $text1 = mb_strtolower(trim($text1));
        $text2 = mb_strtolower(trim($text2));
        
        if ($text1 === $text2) {
            return 1.0;
        }
        
        // 자카드 유사도 계산
        $words1 = preg_split('/\s+/', $text1);
        $words2 = preg_split('/\s+/', $text2);
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        if (empty($union)) {
            return 0.0;
        }
        
        return count($intersection) / count($union);
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
            DomeggookProduct::updateOrCreate(
                ['product_id' => $productData['productId']],
                [
                    'name' => $productData['name'] ?? '',
                    'price' => $productData['price'] ?? 0,
                    'wholesale_price' => $productData['wholesalePrice'] ?? 0,
                    'min_order_quantity' => $productData['minOrderQuantity'] ?? 1,
                    'image_url' => $productData['imageUrl'] ?? '',
                    'product_url' => $productData['productUrl'] ?? '',
                    'category_id' => $productData['categoryId'] ?? '',
                    'category_name' => $productData['categoryName'] ?? '',
                    'supplier_id' => $productData['supplierId'] ?? '',
                    'supplier_name' => $productData['supplierName'] ?? '',
                    'rating' => $productData['rating'] ?? 0,
                    'review_count' => $productData['reviewCount'] ?? 0,
                    'stock' => $productData['stock'] ?? 0,
                    'shipping_fee' => $productData['shippingFee'] ?? 0,
                    'shipping_days' => $productData['shippingDays'] ?? 0,
                    'data' => json_encode($productData),
                    'last_updated' => Carbon::now()
                ]
            );
        } catch (\Exception $e) {
            Log::error('도매꾹 제품 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * 제품 매칭 결과 저장
     *
     * @param array $sourceProduct 원본 제품
     * @param array $matches 매칭 결과
     * @return void
     */
    protected function saveProductMatches(array $sourceProduct, array $matches): void
    {
        if (empty($sourceProduct) || empty($matches)) {
            return;
        }

        $sourceId = $sourceProduct['id'] ?? null;
        if (!$sourceId) {
            return;
        }

        try {
            // 기존 매칭 결과 삭제
            ProductMatch::where('source_product_id', $sourceId)
                ->where('platform', 'domeggook')
                ->delete();
            
            // 새 매칭 결과 저장
            foreach ($matches as $index => $match) {
                ProductMatch::create([
                    'source_product_id' => $sourceId,
                    'target_product_id' => $match['productId'],
                    'platform' => 'domeggook',
                    'similarity' => $match['similarity'],
                    'rank' => $index + 1,
                    'price_difference' => isset($sourceProduct['price']) && isset($match['price']) 
                        ? $sourceProduct['price'] - $match['price'] 
                        : 0,
                    'data' => json_encode($match),
                    'created_at' => Carbon::now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('도매꾹 제품 매칭 저장 오류: ' . $e->getMessage());
        }
    }
} 