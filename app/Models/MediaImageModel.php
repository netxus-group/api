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
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('alt_text', $search)
                ->orLike('original_file_name', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $items = $builder->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->find();

        return ['items' => $items, 'total' => $total];
    }
}
