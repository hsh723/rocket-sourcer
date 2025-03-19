<?php

namespace RocketSourcer\Services\Coupang;

use RocketSourcer\Services\Coupang\Response\ProductResponse;

class CoupangProductService extends CoupangApiService
{
    /**
     * 제품 검색
     */
    public function search(string $keyword, array $options = []): ProductResponse
    {
        $params = array_merge([
            'keyword' => $keyword,
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sort'] ?? 'RELEVANCE',
            'filter' => $options['filter'] ?? [],
        ], $options);

        $response = $this->request('GET', '/v2/products/search', [
            'query' => $params
        ]);

        return new ProductResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    /**
     * 제품 상세 정보 조회
     */
    public function getProduct(string $productId): ProductResponse
    {
        $response = $this->request('GET', "/v2/products/{$productId}");

        return new ProductResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    /**
     * 카테고리별 제품 조회
     */
    public function getProductsByCategory(string $categoryId, array $options = []): ProductResponse
    {
        $parameters = array_merge([
            'categoryId' => $categoryId,
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sortType'] ?? 'BEST_SELLING',
        ], $options);

        $response = $this->request('GET', '/v2/products/category', $parameters);
        
        if (!$response->isSuccess()) {
            return new ProductResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return ProductResponse::fromApiResponse($response->toArray());
    }

    /**
     * 판매자별 제품 조회
     */
    public function getProductsBySeller(string $sellerId, array $options = []): ProductResponse
    {
        $parameters = array_merge([
            'sellerId' => $sellerId,
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sortType'] ?? 'BEST_SELLING',
        ], $options);

        $response = $this->request('GET', '/v2/products/seller', $parameters);
        
        if (!$response->isSuccess()) {
            return new ProductResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return ProductResponse::fromApiResponse($response->toArray());
    }

    /**
     * 제품 리뷰 조회
     */
    public function getProductReviews(string $productId, array $options = []): ProductResponse
    {
        $parameters = array_merge([
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sortType'] ?? 'RECENT',
        ], $options);

        $response = $this->request('GET', "/v2/products/{$productId}/reviews", $parameters);
        
        if (!$response->isSuccess()) {
            return new ProductResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return ProductResponse::fromApiResponse($response->toArray());
    }
} 