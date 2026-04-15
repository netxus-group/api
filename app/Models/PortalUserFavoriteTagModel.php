<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserFavoriteTagModel extends Model
{
    protected $table            = 'portal_user_favorite_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = ['id', 'portal_user_id', 'tag_id', 'weight'];

    public function listIdsByUser(string $portalUserId): array
    {
        return array_map(
            static fn(array $row): string => $row['tag_id'],
            $this->select('tag_id')->where('portal_user_id', $portalUserId)->findAll()
        );
    }

    public function replaceAll(string $portalUserId, array $tagIds, callable $uuid): void
    {
        $this->where('portal_user_id', $portalUserId)->delete();

        if ($tagIds === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach (array_values(array_unique($tagIds)) as $tagId) {
            $rows[] = [
                'id'             => $uuid(),
                'portal_user_id' => $portalUserId,
                'tag_id'         => $tagId,
                'weight'         => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->insertBatch($rows);
    }
}
