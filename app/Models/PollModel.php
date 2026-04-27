<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Poll;

class PollModel extends Model
{
    protected $table         = 'polls';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = Poll::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'title', 'description', 'status', 'active',
        'allow_multiple_responses', 'created_by',
        'starts_at', 'ends_at',
    ];

    protected array $casts = [
        'active'                   => 'boolean',
        'allow_multiple_responses' => 'boolean',
    ];

    public function findWithQuestions(string $id): ?array
    {
        $poll = $this->find($id);
        if (!$poll) {
            return null;
        }

        $data = $poll->toArray();

        $questionModel = new PollQuestionModel();
        $questions     = $questionModel->where('poll_id', $id)->orderBy('sort_order')->findAll();

        $optionModel = new PollOptionModel();
        foreach ($questions as &$q) {
            $q['options'] = $optionModel->where('question_id', $q['id'])->orderBy('sort_order')->findAll();
        }

        $data['questions'] = $questions;

        return $data;
    }
}
