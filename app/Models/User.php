<?php

namespace RocketSourcer\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    private int $id;
    private string $email;
    private string $passwordHash;
    private string $name;
    private ?string $apiKey = null;
    private \DateTime $createdAt;
    private ?\DateTime $lastLoginAt = null;

    protected static string $table = 'users';
    protected static bool $softDelete = true;

    protected static array $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'roles',
        'permissions',
        'is_active'
    ];

    protected static array $hidden = [
        'password',
        'remember_token'
    ];

    protected static array $casts = [
        'roles' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean'
    ];

    public static function findByEmail(string $email): ?self
    {
        return static::query()->where('email', $email)->first();
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function can(string $ability, ...$args): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->hasPermission($ability);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles ?? [];
    }

    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setRememberToken(string $token): void
    {
        $this->remember_token = $token;
        $this->save();
    }
}