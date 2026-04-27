<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'id'            => null,
        'email'         => null,
        'password_hash' => null,
        'role'          => null,
        'first_name'    => null,
        'last_name'     => null,
        'username'      => null,
        'active'        => true,
        'last_login_at' => null,
        'created_at'    => null,
        'updated_at'    => null,
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $datamap = [
        'firstName' => 'first_name',
        'lastName'  => 'last_name',
        'lastLogin' => 'last_login_at',
    ];

    /**
     * Set password (auto-hash with bcrypt).
     */
    public function setPassword(string $password): self
    {
        $cost = config('Auth')->bcryptCost ?? 10;
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
        return $this;
    }

    /**
     * Verify a plain password against the stored hash.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password_hash']);
    }

    /**
     * Return public-safe array (no password_hash).
     */
    public function toPublicArray(): array
    {
        $data = $this->toArray();
        unset($data['password_hash']);
        return $data;
    }
}
