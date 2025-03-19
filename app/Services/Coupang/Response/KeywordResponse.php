<?php

namespace RocketSourcer\Services\Coupang\Response;

class KeywordResponse extends ApiResponse
{
    protected array $keywords = [];
    protected ?array $statistics;

    public function __construct(
        bool $success,
        ?string $code = null,
        ?string $message = null,
        mixed $data = null,
        ?array $raw = null
    ) {
        parent::__construct($success, $code, $message, $data, $raw);
        
        if ($success && is_array($data)) {
            $this->parseKeywords($data);
        }
    }

    protected function parseKeywords(array $data): void
    {
        $this->keywords = array_map(function ($keyword) {
            return [
                'keyword' => $keyword['keyword'] ?? null,
                'search_volume' => [
                    'total' => $keyword['searchVolume'] ?? 0,
                    'pc' => $keyword['pcSearchVolume'] ?? 0,
                    'mobile' => $keyword['mobileSearchVolume'] ?? 0,
                ],
                'competition' => [
                    'score' => $keyword['competitionScore'] ?? 0,
                    'level' => $keyword['competitionLevel'] ?? null,
                ],
                'trends' => [
                    'daily' => $keyword['dailyTrend'] ?? [],
                    'weekly' => $keyword['weeklyTrend'] ?? [],
                    'monthly' => $keyword['monthlyTrend'] ?? [],
                ],
                'categories' => $keyword['categories'] ?? [],
                'related_keywords' => array_map(function ($related) {
                    return [
                        'keyword' => $related['keyword'] ?? null,
                        'search_volume' => $related['searchVolume'] ?? 0,
                        'relevance_score' => $related['relevanceScore'] ?? 0,
                    ];
                }, $keyword['relatedKeywords'] ?? []),
                'products' => array_map(function ($product) {
                    return [
                        'id' => $product['productId'] ?? null,
                        'name' => $product['productName'] ?? null,
                        'relevance_score' => $product['relevanceScore'] ?? 0,
                    ];
                }, $keyword['relatedProducts'] ?? []),
                'metadata' => [
                    'updated_at' => $keyword['updatedAt'] ?? null,
                    'source' => $keyword['source'] ?? null,
                ],
            ];
        }, $data['keywords'] ?? []);

        $this->statistics = $data['statistics'] ?? null;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getStatistics(): ?array
    {
        return $this->statistics;
    }

    public function getTotalKeywords(): int
    {
        return count($this->keywords);
    }

    public function getKeyword(int $index): ?array
    {
        return $this->keywords[$index] ?? null;
    }

    public function getAverageSearchVolume(): float
    {
        if (empty($this->keywords)) {
            return 0.0;
        }

        $total = array_sum(array_map(function ($keyword) {
            return $keyword['search_volume']['total'] ?? 0;
        }, $this->keywords));

        return $total / count($this->keywords);
    }

    public function getAverageCompetitionScore(): float
    {
        if (empty($this->keywords)) {
            return 0.0;
        }

        $total = array_sum(array_map(function ($keyword) {
            return $keyword['competition']['score'] ?? 0;
        }, $this->keywords));

        return $total / count($this->keywords);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'keywords' => $this->keywords,
            'statistics' => $this->statistics,
        ]);
    }

    /**
     * 키워드 데이터 설정
     */
    public function setKeywordData(array $data): void
    {
        $this->data = [
            'total' => $data['totalCount'] ?? 0,
            'keywords' => array_map(function ($keyword) {
                return [
                    'keyword' => $keyword['keyword'] ?? null,
                    'searchVolume' => $keyword['searchVolume'] ?? 0,
                    'competition' => $keyword['competition'] ?? 0.0,
                    'cpc' => $keyword['cpc'] ?? 0.0,
                    'category' => [
                        'id' => $keyword['categoryId'] ?? null,
                        'name' => $keyword['categoryName'] ?? null,
                    ],
                    'trends' => $keyword['trends'] ?? [],
                    'relatedKeywords' => $keyword['relatedKeywords'] ?? [],
                    'metadata' => [
                        'lastUpdated' => $keyword['lastUpdated'] ?? null,
                    ],
                ];
            }, $data['keywords'] ?? []),
        ];
    }

    /**
     * 총 키워드 수 반환
     */
    public function getTotalCount(): int
    {
        return $this->data['total'] ?? 0;
    }

    /**
     * 키워드 응답 생성
     */
    public static function fromApiResponse(array $response): self
    {
        $instance = new self(
            true,
            $response['code'] ?? '200',
            $response['message'] ?? null
        );
        
        $instance->setKeywordData($response['data'] ?? []);
        return $instance;
    }
} 