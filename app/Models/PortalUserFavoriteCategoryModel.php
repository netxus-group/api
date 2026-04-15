<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserFavoriteCategoryModel extends Model
{
    protected $table            = 'portal_user_favorite_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = ['id', 'portal_user_id', 'category_id', 'weight'];

    public function listIdsByUser(string $portalUserId): array
    {
        return array_map(
            static fn(array $row): string => $row['category_id'],
            $this->select('category_id')->where('portal_user_id', $portalUserId)->findAll()
        );
    }

    public function replaceAll(string $portalUserId, array $categoryIds, callable $uuid): void
    {
        $this->where('portal_user_id', $portalUserId)->delete();

        if ($categoryIds === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach (array_values(array_unique($categoryIds)) as $categoryId) {
            $rows[] = [
                'id'             => $uuid(),
                'portal_user_id' => $portalUserId,
                'category_id'    => $categoryId,
                'weight'         => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->insertBatch($rows);
    }
}
