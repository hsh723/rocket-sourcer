<?php

namespace RocketSourcer\Models;

class Analysis extends Model
{
    protected static string $table = 'analyses';
    protected static bool $softDelete = true;

    protected static array $fillable = [
        'analyzable_type',
        'analyzable_id',
        'type',
        'status',
        'result',
        'metadata',
        'started_at',
        'completed_at',
        'error',
        'user_id'
    ];

    protected static array $casts = [
        'result' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analyzable()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function start(): bool
    {
        return $this->update([
            'status' => 'running',
            'started_at' => now(),
            'error' => null
        ]);
    }

    public function complete(array $result = []): bool
    {
        return $this->update([
            'status' => 'completed',
            'result' => $result,
            'completed_at' => now()
        ]);
    }

    public function fail(string $error): bool
    {
        return $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now()
        ]);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getResult(): array
    {
        return $this->result ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->started_at;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completed_at;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->getTimestamp() - $this->started_at->getTimestamp();
    }

    public function updateMetadata(array $metadata): bool
    {
        return $this->update([
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);
    }

    public static function createFor($analyzable, string $type, array $metadata = []): self
    {
        return static::create([
            'analyzable_type' => get_class($analyzable),
            'analyzable_id' => $analyzable->getId(),
            'type' => $type,
            'status' => 'pending',
            'metadata' => $metadata,
            'user_id' => $analyzable->user_id
        ]);
    }
} 