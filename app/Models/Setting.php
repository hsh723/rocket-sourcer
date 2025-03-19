<?php

namespace RocketSourcer\Models;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected static bool $softDelete = true;

    protected static array $fillable = [
        'key',
        'value',
        'type',
        'is_public',
        'is_system',
        'metadata',
        'user_id'
    ];

    protected static array $casts = [
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getValue()
    {
        return $this->castValue($this->value);
    }

    public function setValue($value): void
    {
        $this->value = $this->prepareValue($value);
    }

    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function updateMetadata(array $metadata): bool
    {
        return $this->update([
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);
    }

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->getValue() : $default;
    }

    public static function set(string $key, $value, array $options = []): self
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return static::create(array_merge([
                'key' => $key,
                'value' => $value,
                'type' => self::getValueType($value)
            ], $options));
        }

        $setting->setValue($value);
        $setting->update();

        return $setting;
    }

    public static function getPublic(): array
    {
        $settings = static::where('is_public', true)->get();
        return array_reduce($settings, function ($carry, $setting) {
            $carry[$setting->key] = $setting->getValue();
            return $carry;
        }, []);
    }

    public static function getForUser(User $user): array
    {
        $settings = static::where('user_id', $user->getId())->get();
        return array_reduce($settings, function ($carry, $setting) {
            $carry[$setting->key] = $setting->getValue();
            return $carry;
        }, []);
    }

    protected function castValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        switch ($this->type) {
            case 'boolean':
                return (bool)$value;
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    protected function prepareValue($value): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string)$value;
    }

    protected static function getValueType($value): string
    {
        switch (true) {
            case is_bool($value):
                return 'boolean';
            case is_int($value):
                return 'integer';
            case is_float($value):
                return 'float';
            case is_array($value):
                return 'array';
            default:
                return 'string';
        }
    }
} 