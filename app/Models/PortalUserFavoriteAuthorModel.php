<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserFavoriteAuthorModel extends Model
{
    protected $table            = 'portal_user_favorite_authors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = ['id', 'portal_user_id', 'author_id', 'weight'];

    public function listIdsByUser(string $portalUserId): array
    {
        return array_map(
            static fn(array $row): string => $row['author_id'],
            $this->select('author_id')->where('portal_user_id', $portalUserId)->findAll()
        );
    }

    public function replaceAll(string $portalUserId, array $authorIds, callable $uuid): void
    {
        $this->where('portal_user_id', $portalUserId)->delete();

        if ($authorIds === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach (array_values(array_unique($authorIds)) as $authorId) {
            $rows[] = [
                'id'             => $uuid(),
                'portal_user_id' => $portalUserId,
                'author_id'      => $authorId,
                'weight'         => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->insertBatch($rows);
    }
}
