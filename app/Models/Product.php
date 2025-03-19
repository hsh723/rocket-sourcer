<?php

namespace RocketSourcer\Models;

class Product extends Model
{
    protected static string $table = 'products';
    protected static bool $softDelete = true;

    protected static array $fillable = [
        'name',
        'url',
        'status',
        'price',
        'review_count',
        'rating',
        'categories',
        'images',
        'specifications',
        'metadata',
        'user_id'
    ];

    protected static array $casts = [
        'price' => 'float',
        'review_count' => 'integer',
        'rating' => 'float',
        'categories' => 'array',
        'images' => 'array',
        'specifications' => 'array',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analyses()
    {
        return $this->morphMany(Analysis::class, 'analyzable');
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class, 'keyword_product')
            ->withTimestamps()
            ->withPivot(['relevance_score', 'metadata']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAnalyzing(): bool
    {
        return $this->status === 'analyzing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsAnalyzing(): bool
    {
        return $this->update(['status' => 'analyzing']);
    }

    public function markAsCompleted(): bool
    {
        return $this->update(['status' => 'completed']);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => 'failed']);
    }

    public function updateAnalysisResult(array $result): bool
    {
        return $this->update([
            'price' => $result['price'] ?? $this->price,
            'review_count' => $result['review_count'] ?? $this->review_count,
            'rating' => $result['rating'] ?? $this->rating,
            'categories' => $result['categories'] ?? $this->categories,
            'images' => $result['images'] ?? $this->images,
            'specifications' => $result['specifications'] ?? $this->specifications,
            'metadata' => array_merge($this->metadata ?? [], $result['metadata'] ?? []),
            'status' => 'completed'
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getReviewCount(): int
    {
        return $this->review_count;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function getCategories(): array
    {
        return $this->categories ?? [];
    }

    public function getImages(): array
    {
        return $this->images ?? [];
    }

    public function getSpecifications(): array
    {
        return $this->specifications ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function addKeyword(Keyword $keyword, float $relevanceScore = 0.0, array $metadata = []): bool
    {
        return $this->keywords()->attach($keyword->getId(), [
            'relevance_score' => $relevanceScore,
            'metadata' => json_encode($metadata)
        ]);
    }

    public function removeKeyword(Keyword $keyword): bool
    {
        return $this->keywords()->detach($keyword->getId());
    }

    public function updateKeywordRelevance(Keyword $keyword, float $relevanceScore, array $metadata = []): bool
    {
        return $this->keywords()->updateExistingPivot($keyword->getId(), [
            'relevance_score' => $relevanceScore,
            'metadata' => json_encode($metadata)
        ]);
    }
} 