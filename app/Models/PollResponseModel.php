<?php

namespace App\Models;

use CodeIgniter\Model;

class PollResponseModel extends Model
{
    protected $table         = 'poll_responses';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id', 'poll_id', 'respondent_id', 'respondent_ip_hash',
        'status', 'completed_at',
    ];

    /**
     * Check if a user/IP already responded.
     */
    public function hasResponded(string $pollId, ?string $userId, ?string $ipHash): bool
    {
        $builder = $this->where('poll_id', $pollId);

        if ($userId) {
            $builder->where('respondent_id', $userId);
        } elseif ($ipHash) {
            $builder->where('respondent_ip_hash', $ipHash);
        } else {
            return false;
        }

        return $builder->countAllResults() > 0;
    }
}
