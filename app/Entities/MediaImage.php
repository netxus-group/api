<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MediaImage extends Entity
{
    protected $casts = [
        'active'         => 'boolean',
        'marketing_meta' => 'json-array',
    ];

    protected $datamap = [
        'filePath'         => 'file_path',
        'publicUrl'        => 'public_url',
        'originalFileName' => 'original_file_name',
        'mimeType'         => 'mime_type',
        'altText'          => 'alt_text',
        'marketingMeta'    => 'marketing_meta',
        'uploadedBy'       => 'uploaded_by',
    ];
}
