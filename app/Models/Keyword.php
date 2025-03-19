<?php

namespace RocketSourcer\Models;

class Keyword extends Model
{
    protected static string $table = 'keywords';
    protected static bool $softDelete = true;

    protected static array $fillable = [
        'keyword',
        'status',
        'search_volume',
        'competition',
        'cpc',
        'categories',
        'trends',
        'related_keywords',
        'metadata',
        'user_id'
    ];

    protected static array $casts = [
        'search_volume' => 'integer',
        'competition' => 'float',
        'cpc' => 'float',
        'categories' => 'array',
        'trends' => 'array',
        'related_keywords' => 'array',
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

    public function products()
    {
        return $this->belongsToMany(Product::class, 'keyword_product')
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
            'search_volume' => $result['search_volume'] ?? null,
            'competition' => $result['competition'] ?? null,
            'cpc' => $result['cpc'] ?? null,
            'categories' => $result['categories'] ?? null,
            'trends' => $result['trends'] ?? null,
            'related_keywords' => $result['related_keywords'] ?? null,
            'metadata' => array_merge($this->metadata ?? [], $result['metadata'] ?? []),
            'status' => 'completed'
        ]);
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSearchVolume(): ?int
    {
        return $this->search_volume;
    }

    public function getCompetition(): ?float
    {
        return $this->competition;
    }

    public function getCpc(): ?float
    {
        return $this->cpc;
    }

    public function getCategories(): array
    {
        return $this->categories ?? [];
    }

    public function getTrends(): array
    {
        return $this->trends ?? [];
    }

    public function getRelatedKeywords(): array
    {
        return $this->related_keywords ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
} 