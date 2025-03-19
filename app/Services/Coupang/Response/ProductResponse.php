<?php

namespace RocketSourcer\Services\Coupang\Response;

class ProductResponse extends ApiResponse
{
    protected array $products = [];
    protected ?array $pagination;

    public function __construct(
        bool $success,
        ?string $code = null,
        ?string $message = null,
        mixed $data = null,
        ?array $raw = null
    ) {
        parent::__construct($success, $code, $message, $data, $raw);
        
        if ($success && is_array($data)) {
            $this->parseProducts($data);
        }
    }

    protected function parseProducts(array $data): void
    {
        $this->products = array_map(function ($product) {
            return [
                'id' => $product['productId'] ?? null,
                'name' => $product['productName'] ?? null,
                'url' => $product['productUrl'] ?? null,
                'price' => [
                    'base' => $product['basePrice'] ?? null,
                    'sale' => $product['salePrice'] ?? null,
                    'discount_rate' => $product['discountRate'] ?? null,
                ],
                'rating' => [
                    'average' => $product['ratingAverage'] ?? null,
                    'count' => $product['ratingCount'] ?? null,
                ],
                'images' => $product['images'] ?? [],
                'delivery' => [
                    'type' => $product['deliveryType'] ?? null,
                    'fee' => $product['deliveryFee'] ?? null,
                    'message' => $product['deliveryMessage'] ?? null,
                ],
                'category' => [
                    'id' => $product['categoryId'] ?? null,
                    'name' => $product['categoryName'] ?? null,
                ],
                'brand' => [
                    'id' => $product['brandId'] ?? null,
                    'name' => $product['brandName'] ?? null,
                ],
                'seller' => [
                    'id' => $product['sellerId'] ?? null,
                    'name' => $product['sellerName'] ?? null,
                    'grade' => $product['sellerGrade'] ?? null,
                ],
                'status' => [
                    'sales' => $product['salesStatus'] ?? null,
                    'inventory' => $product['inventoryStatus'] ?? null,
                ],
                'badges' => $product['badges'] ?? [],
                'attributes' => $product['attributes'] ?? [],
                'metadata' => [
                    'created_at' => $product['createdAt'] ?? null,
                    'updated_at' => $product['updatedAt'] ?? null,
                ],
            ];
        }, $data['products'] ?? []);

        $this->pagination = $data['pagination'] ?? null;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPagination(): ?array
    {
        return $this->pagination;
    }

    public function getTotalCount(): int
    {
        return $this->pagination['totalCount'] ?? 0;
    }

    public function getCurrentPage(): int
    {
        return $this->pagination['currentPage'] ?? 1;
    }

    public function getPageSize(): int
    {
        return $this->pagination['pageSize'] ?? 0;
    }

    public function getTotalPages(): int
    {
        return $this->pagination['totalPages'] ?? 0;
    }

    public function hasNextPage(): bool
    {
        return $this->getCurrentPage() < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->getCurrentPage() > 1;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'products' => $this->products,
            'pagination' => $this->pagination,
        ]);
    }

    /**
     * 제품 데이터 설정
     */
    public function setProductData(array $data): void
    {
        $this->data = [
            'total' => $data['totalCount'] ?? 0,
            'products' => array_map(function ($product) {
                return [
                    'id' => $product['productId'] ?? null,
                    'name' => $product['productName'] ?? null,
                    'price' => $product['price'] ?? null,
                    'originalPrice' => $product['originalPrice'] ?? null,
                    'salePrice' => $product['salePrice'] ?? null,
                    'category' => [
                        'id' => $product['categoryId'] ?? null,
                        'name' => $product['categoryName'] ?? null,
                    ],
                    'images' => $product['images'] ?? [],
                    'url' => $product['productUrl'] ?? null,
                    'brand' => $product['brand'] ?? null,
                    'seller' => [
                        'id' => $product['sellerId'] ?? null,
                        'name' => $product['sellerName'] ?? null,
                    ],
                    'shipping' => [
                        'fee' => $product['shippingFee'] ?? null,
                        'method' => $product['shippingMethod'] ?? null,
                    ],
                    'rating' => [
                        'average' => $product['rating'] ?? null,
                        'count' => $product['reviewCount'] ?? 0,
                    ],
                    'status' => $product['status'] ?? null,
                    'metadata' => [
                        'createdAt' => $product['createdAt'] ?? null,
                        'updatedAt' => $product['updatedAt'] ?? null,
                    ],
                ];
            }, $data['products'] ?? []),
        ];
    }

    /**
     * 제품 응답 생성
     */
    public static function fromApiResponse(array $response): self
    {
        $instance = new self(
            true,
            $response['code'] ?? '200',
            $response['message'] ?? null
        );
        
        $instance->setProductData($response['data'] ?? []);
        return $instance;
    }
} 