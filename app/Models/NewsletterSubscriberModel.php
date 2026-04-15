<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\NewsletterSubscriber;

class NewsletterSubscriberModel extends Model
{
    protected $table         = 'newsletter_subscribers';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = NewsletterSubscriber::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id', 'email', 'status', 'subscribed_at',
        'unsubscribed_at', 'source', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'json-array',
    ];

    /**
     * Find subscriber by email.
     */
    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->where('email', $email)->first();
    }

    /**
     * List subscribers with pagination.
     */
    public function listFiltered(?string $status, int $page, int $perPage): array
    {
        $builder = $this;

        if ($status) {
            $builder = $builder->where('status', $status);
        }

        $total = $builder->countAllResults(false);

        $items = $builder->orderBy('subscribed_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->findAll();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get subscriber count by status.
     */
    public function countByStatus(): array
    {
        $results = $this->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->findAll();

        $counts = ['subscribed' => 0, 'unsubscribed' => 0];
        foreach ($results as $row) {
            $counts[$row->status ?? $row['status']] = (int) ($row->count ?? $row['count']);
        }

        return $counts;
    }
}
