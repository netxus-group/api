<?php

namespace App\Models;

use CodeIgniter\Model;

class PollResponseDetailModel extends Model
{
    protected $table         = 'poll_response_details';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id', 'response_id', 'question_id', 'option_id',
        'text_answer',
    ];

    /**
     * Get stats for a poll: votes per option.
     */
    public function getStatsForPoll(string $pollId): array
    {
        return $this->select('poll_response_details.question_id, poll_response_details.option_id, COUNT(*) as votes')
            ->join('poll_responses', 'poll_responses.id = poll_response_details.response_id')
            ->where('poll_responses.poll_id', $pollId)
            ->where('poll_responses.status', 'completed')
            ->groupBy('poll_response_details.question_id, poll_response_details.option_id')
            ->findAll();
    }
}
