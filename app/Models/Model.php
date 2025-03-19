<?php

namespace RocketSourcer\Models;

use RocketSourcer\Core\Database;

abstract class Model
{
    protected static Database $db;
    protected static string $table;
    protected static array $fillable = [];
    protected static array $hidden = [];
    protected static array $casts = [];
    protected static array $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected bool $exists = false;

    /**
     * PDO 인스턴스 설정
     *
     * @param PDO $pdo
     * @return void
     */
    public static function setDatabase(Database $db): void
    {
        static::$db = $db;
    }

    /**
     * 모델 생성
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * 속성 설정
     *
     * @param array $attributes
     * @return void
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * 모델 저장
     *
     * @return bool
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * 모델 삭제
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if (property_exists(static::class, 'softDelete') && static::$softDelete) {
            return $this->softDelete();
        }

        return static::query()->where('id', $this->attributes['id'])->delete();
    }

    /**
     * ID로 모델 찾기
     *
     * @param int $id
     * @return static|null
     */
    public static function find($id): ?static
    {
        return static::where('id', $id)->first();
    }

    /**
     * 조건으로 모델 찾기
     *
     * @param array $conditions
     * @return array
     */
    public static function where(string $column, $operator, $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * 모든 모델 조회
     *
     * @return array
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * 속성 조회
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelation($key);
        }

        return null;
    }

    /**
     * 속성 설정
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * 모델 삽입
     *
     * @return bool
     */
    protected function insert(): bool
    {
        if (in_array('created_at', static::$dates)) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }

        if (in_array('updated_at', static::$dates)) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $id = static::query()->insert($this->attributes);
        if ($id) {
            $this->attributes['id'] = $id;
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    /**
     * 모델 수정
     *
     * @return bool
     */
    public function update(array $attributes = []): bool
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }

        if (empty($this->getDirty())) {
            return true;
        }

        if (in_array('updated_at', static::$dates)) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $query = static::query();
        foreach ($this->getDirty() as $key => $value) {
            $query->set($key, $value);
        }

        return $query->where('id', $this->attributes['id'])->update();
    }

    /**
     * 배열로 변환
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, static::$hidden)) {
                $array[$key] = $this->castAttribute($key, $value);
            }
        }

        foreach ($this->relations as $key => $value) {
            if ($value instanceof Model) {
                $array[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $array[$key] = array_map(function ($item) {
                    return $item instanceof Model ? $item->toArray() : $item;
                }, $value);
            }
        }

        return $array;
    }

    protected function softDelete(): bool
    {
        $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
        return $this->save();
    }

    protected function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    protected function castAttribute(string $key, $value)
    {
        if (is_null($value)) {
            return null;
        }

        if (isset(static::$casts[$key])) {
            switch (static::$casts[$key]) {
                case 'int':
                case 'integer':
                    return (int)$value;
                case 'float':
                case 'double':
                    return (float)$value;
                case 'string':
                    return (string)$value;
                case 'bool':
                case 'boolean':
                    return (bool)$value;
                case 'array':
                case 'json':
                    return json_decode($value, true);
                case 'datetime':
                    return new \DateTime($value);
            }
        }

        return $value;
    }

    protected function getRelation(string $method)
    {
        if (!method_exists($this, $method)) {
            return null;
        }

        return $this->relations[$method] = $this->$method()->getResults();
    }

    protected function hasOne(string $related, string $foreignKey = null, string $localKey = 'id'): Relation
    {
        return new HasOne(static::$db, $this, $related, $foreignKey, $localKey);
    }

    protected function hasMany(string $related, string $foreignKey = null, string $localKey = 'id'): Relation
    {
        return new HasMany(static::$db, $this, $related, $foreignKey, $localKey);
    }

    protected function belongsTo(string $related, string $foreignKey = null, string $ownerKey = 'id'): Relation
    {
        return new BelongsTo(static::$db, $this, $related, $foreignKey, $ownerKey);
    }

    protected function belongsToMany(
        string $related,
        string $table = null,
        string $foreignPivotKey = null,
        string $relatedPivotKey = null
    ): Relation {
        return new BelongsToMany(
            static::$db,
            $this,
            $related,
            $table,
            $foreignPivotKey,
            $relatedPivotKey
        );
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$db, static::class, static::$table);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
} 