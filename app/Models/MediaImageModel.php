<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\MediaImage;

class MediaImageModel extends Model
{
    protected $table         = 'media_images';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = MediaImage::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'file_path', 'public_url', 'original_file_name',
        'mime_type', 'title', 'alt_text', 'caption',
        'marketing_meta', 'uploaded_by', 'active',
        'filename', 'original_name', 'size', 'width', 'height',
        'url', 'folder',
    ];

    protected array $casts = [
        'marketing_meta' => 'json-array',
        'active'         => 'boolean',
    ];

    /**
     * List images with search and pagination.
     */
    public function listFiltered(?string $search, int $page, int $perPage): array
    {
        $builder = $this->where('active', 1);

        if ($search) {
            $searchableColumns = [];

            if ($this->hasColumn('title')) {
                $searchableColumns[] = 'title';
            }
            if ($this->hasColumn('alt_text')) {
                $searchableColumns[] = 'alt_text';
            }
            if ($this->hasColumn('original_file_name')) {
                $searchableColumns[] = 'original_file_name';
            } elseif ($this->hasColumn('original_name')) {
                $searchableColumns[] = 'original_name';
            }

            if (!empty($searchableColumns)) {
                $first = array_shift($searchableColumns);
                $builder->groupStart()->like($first, $search);
                foreach ($searchableColumns as $column) {
                    $builder->orLike($column, $search);
                }
                $builder->groupEnd();
            }
        }

        $total = $builder->countAllResults(false);

        $orderColumn = $this->hasColumn('created_at') ? 'created_at' : 'id';
        $items = $builder->orderBy($orderColumn, 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->find();

        return ['items' => $items, 'total' => $total];
    }

    private ?array $tableColumns = null;

    private function hasColumn(string $column): bool
    {
        if ($this->tableColumns === null) {
            $this->tableColumns = array_map('strtolower', $this->db->getFieldNames($this->table));
        }

        return in_array(strtolower($column), $this->tableColumns, true);
    }
}
