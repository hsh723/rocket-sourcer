<?php

namespace RocketSourcer\Services\Coupang;

use RocketSourcer\Services\Coupang\Response\KeywordResponse;

class CoupangKeywordService extends CoupangApiService
{
    /**
     * 키워드 검색
     */
    public function search(string $keyword): KeywordResponse
    {
        $response = $this->request('GET', '/v2/keywords/search', [
            'keyword' => $keyword
        ]);
        
        if (!$response->isSuccess()) {
            return new KeywordResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return KeywordResponse::fromApiResponse($response->toArray());
    }

    /**
     * 연관 키워드 조회
     */
    public function getRelatedKeywords(string $keyword): KeywordResponse
    {
        $response = $this->request('GET', '/v2/keywords/related', [
            'keyword' => $keyword
        ]);
        
        if (!$response->isSuccess()) {
            return new KeywordResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return KeywordResponse::fromApiResponse($response->toArray());
    }

    /**
     * 키워드 트렌드 조회
     */
    public function getKeywordTrends(string $keyword, array $options = []): KeywordResponse
    {
        $parameters = array_merge([
            'keyword' => $keyword,
            'startDate' => $options['startDate'] ?? date('Y-m-d', strtotime('-30 days')),
            'endDate' => $options['endDate'] ?? date('Y-m-d'),
        ], $options);

        $response = $this->request('GET', '/v2/keywords/trends', $parameters);
        
        if (!$response->isSuccess()) {
            return new KeywordResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return KeywordResponse::fromApiResponse($response->toArray());
    }

    /**
     * 카테고리별 인기 키워드 조회
     */
    public function getPopularKeywordsByCategory(string $categoryId, array $options = []): KeywordResponse
    {
        $parameters = array_merge([
            'categoryId' => $categoryId,
            'limit' => $options['limit'] ?? 20,
        ], $options);

        $response = $this->request('GET', '/v2/keywords/popular/category', $parameters);
        
        if (!$response->isSuccess()) {
            return new KeywordResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return KeywordResponse::fromApiResponse($response->toArray());
    }

    /**
     * 키워드 경쟁 정보 조회
     */
    public function getKeywordCompetition(string $keyword): KeywordResponse
    {
        $response = $this->request('GET', '/v2/keywords/competition', [
            'keyword' => $keyword
        ]);
        
        if (!$response->isSuccess()) {
            return new KeywordResponse(
                false,
                $response->getCode(),
                $response->getMessage()
            );
        }

        return KeywordResponse::fromApiResponse($response->toArray());
    }

    public function analyze(string $keyword): KeywordResponse
    {
        $response = $this->request('GET', '/v2/keywords/analyze', [
            'query' => ['keyword' => $keyword]
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getSuggestions(string $keyword, array $options = []): KeywordResponse
    {
        $params = array_merge([
            'keyword' => $keyword,
            'limit' => $options['limit'] ?? 10,
        ], $options);

        $response = $this->request('GET', '/v2/keywords/suggestions', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getRelatedKeywords(string $keyword, array $options = []): KeywordResponse
    {
        $params = array_merge([
            'keyword' => $keyword,
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sort'] ?? 'RELEVANCE',
        ], $options);

        $response = $this->request('GET', '/v2/keywords/related', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getTrends(string $keyword, array $options = []): KeywordResponse
    {
        $params = array_merge([
            'keyword' => $keyword,
            'period' => $options['period'] ?? '1M',
            'interval' => $options['interval'] ?? 'DAY',
        ], $options);

        $response = $this->request('GET', '/v2/keywords/trends', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getCompetitors(string $keyword, array $options = []): KeywordResponse
    {
        $params = array_merge([
            'keyword' => $keyword,
            'limit' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sortType' => $options['sort'] ?? 'RELEVANCE',
        ], $options);

        $response = $this->request('GET', '/v2/keywords/competitors', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getCategories(string $keyword): KeywordResponse
    {
        $response = $this->request('GET', '/v2/keywords/categories', [
            'query' => ['keyword' => $keyword]
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getPopularKeywords(array $options = []): KeywordResponse
    {
        $params = array_merge([
            'categoryId' => $options['categoryId'] ?? null,
            'limit' => $options['limit'] ?? 20,
            'period' => $options['period'] ?? '1D',
        ], $options);

        $response = $this->request('GET', '/v2/keywords/popular', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }

    public function getRisingKeywords(array $options = []): KeywordResponse
    {
        $params = array_merge([
            'categoryId' => $options['categoryId'] ?? null,
            'limit' => $options['limit'] ?? 20,
            'period' => $options['period'] ?? '1D',
        ], $options);

        $response = $this->request('GET', '/v2/keywords/rising', [
            'query' => $params
        ]);

        return new KeywordResponse(
            $response->isSuccess(),
            $response->getCode(),
            $response->getMessage(),
            $response->getData(),
            $response->getRaw()
        );
    }
} 