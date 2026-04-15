<?php

namespace App\Services;

use App\Models\PollModel;
use App\Models\PollQuestionModel;
use App\Models\PollOptionModel;
use App\Models\PollResponseModel;
use App\Models\PollResponseDetailModel;

class PollService
{
    private PollModel $pollModel;
    private PollQuestionModel $questionModel;
    private PollOptionModel $optionModel;
    private PollResponseModel $responseModel;
    private PollResponseDetailModel $detailModel;

    public function __construct()
    {
        $this->pollModel     = new PollModel();
        $this->questionModel = new PollQuestionModel();
        $this->optionModel   = new PollOptionModel();
        $this->responseModel = new PollResponseModel();
        $this->detailModel   = new PollResponseDetailModel();
    }

    /**
     * Create a poll with questions and options.
     */
    public function create(array $data, string $userId): array
    {
        $pollId = $this->generateUuid();

        $this->pollModel->insert([
            'id'                       => $pollId,
            'title'                    => $data['title'],
            'description'              => $data['description'] ?? null,
            'status'                   => $data['status'] ?? 'draft',
            'active'                   => true,
            'allow_multiple_responses' => $data['allowMultipleResponses'] ?? false,
            'created_by'               => $userId,
            'starts_at'                => $data['startsAt'] ?? null,
            'ends_at'                  => $data['endsAt'] ?? null,
        ]);

        // Create questions
        if (!empty($data['questions'])) {
            foreach ($data['questions'] as $qi => $question) {
                $qId = $this->generateUuid();
                $this->questionModel->insert([
                    'id'            => $qId,
                    'poll_id'       => $pollId,
                    'question_text' => $question['text'],
                    'question_type' => $question['type'] ?? 'single_choice',
                    'required'      => $question['required'] ?? true,
                    'sort_order'    => $qi,
                ]);

                if (!empty($question['options'])) {
                    foreach ($question['options'] as $oi => $opt) {
                        $this->optionModel->insert([
                            'id'          => $this->generateUuid(),
                            'question_id' => $qId,
                            'option_text' => is_string($opt) ? $opt : ($opt['text'] ?? ''),
                            'sort_order'  => $oi,
                        ]);
                    }
                }
            }
        }

        return $this->pollModel->findWithQuestions($pollId);
    }

    /**
     * Update a poll.
     */
    public function update(string $id, array $data): array
    {
        $poll = $this->pollModel->find($id);
        if (!$poll || !$poll->active) {
            throw new \RuntimeException('Poll not found', 404);
        }

        $updateData = [];
        foreach (['title', 'description', 'status'] as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        if (isset($data['allowMultipleResponses'])) {
            $updateData['allow_multiple_responses'] = $data['allowMultipleResponses'];
        }
        if (isset($data['startsAt'])) {
            $updateData['starts_at'] = $data['startsAt'];
        }
        if (isset($data['endsAt'])) {
            $updateData['ends_at'] = $data['endsAt'];
        }

        if (!empty($updateData)) {
            $this->pollModel->update($id, $updateData);
        }

        // If questions provided, replace all
        if (isset($data['questions'])) {
            // Delete existing
            $oldQuestions = $this->questionModel->where('poll_id', $id)->findAll();
            foreach ($oldQuestions as $q) {
                $this->optionModel->where('question_id', $q['id'])->delete();
            }
            $this->questionModel->where('poll_id', $id)->delete();

            // Create new
            foreach ($data['questions'] as $qi => $question) {
                $qId = $this->generateUuid();
                $this->questionModel->insert([
                    'id'            => $qId,
                    'poll_id'       => $id,
                    'question_text' => $question['text'],
                    'question_type' => $question['type'] ?? 'single_choice',
                    'required'      => $question['required'] ?? true,
                    'sort_order'    => $qi,
                ]);

                if (!empty($question['options'])) {
                    foreach ($question['options'] as $oi => $opt) {
                        $this->optionModel->insert([
                            'id'          => $this->generateUuid(),
                            'question_id' => $qId,
                            'option_text' => is_string($opt) ? $opt : ($opt['text'] ?? ''),
                            'sort_order'  => $oi,
                        ]);
                    }
                }
            }
        }

        return $this->pollModel->findWithQuestions($id);
    }

    /**
     * Submit a response (supports progressive saving).
     */
    public function respond(string $pollId, array $answers, ?string $userId, ?string $ipHash): array
    {
        $poll = $this->pollModel->find($pollId);
        if (!$poll || !$poll->active || $poll->status !== 'active') {
            throw new \RuntimeException('Poll not available', 400);
        }

        // Check time window
        if ($poll->starts_at && strtotime($poll->starts_at) > time()) {
            throw new \RuntimeException('Poll has not started yet', 400);
        }
        if ($poll->ends_at && strtotime($poll->ends_at) < time()) {
            throw new \RuntimeException('Poll has ended', 400);
        }

        // Check if already responded (unless multiple allowed)
        if (!$poll->allow_multiple_responses) {
            if ($this->responseModel->hasResponded($pollId, $userId, $ipHash)) {
                throw new \RuntimeException('Already responded to this poll', 409);
            }
        }

        $responseId = $this->generateUuid();
        $isComplete = !empty($answers['complete']);

        $this->responseModel->insert([
            'id'                => $responseId,
            'poll_id'           => $pollId,
            'respondent_id'     => $userId,
            'respondent_ip_hash' => $ipHash,
            'status'            => $isComplete ? 'completed' : 'in_progress',
            'completed_at'      => $isComplete ? date('Y-m-d H:i:s') : null,
        ]);

        // Save answer details
        if (!empty($answers['answers'])) {
            foreach ($answers['answers'] as $answer) {
                $this->detailModel->insert([
                    'id'          => $this->generateUuid(),
                    'response_id' => $responseId,
                    'question_id' => $answer['questionId'],
                    'option_id'   => $answer['optionId'] ?? null,
                    'text_answer' => $answer['textAnswer'] ?? null,
                ]);
            }
        }

        return ['responseId' => $responseId, 'status' => $isComplete ? 'completed' : 'in_progress'];
    }

    /**
     * Get statistics for a poll.
     */
    public function getStats(string $pollId): array
    {
        $poll = $this->pollModel->findWithQuestions($pollId);
        if (!$poll) {
            throw new \RuntimeException('Poll not found', 404);
        }

        $rawStats = $this->detailModel->getStatsForPoll($pollId);

        // Total responses
        $totalResponses = $this->responseModel
            ->where('poll_id', $pollId)
            ->where('status', 'completed')
            ->countAllResults();

        // Group stats by question
        $questionStats = [];
        foreach ($rawStats as $stat) {
            $qId = $stat['question_id'];
            if (!isset($questionStats[$qId])) {
                $questionStats[$qId] = ['questionId' => $qId, 'options' => []];
            }
            if ($stat['option_id']) {
                $questionStats[$qId]['options'][] = [
                    'optionId' => $stat['option_id'],
                    'votes'    => (int) $stat['votes'],
                ];
            }
        }

        return [
            'pollId'         => $pollId,
            'title'          => $poll['title'],
            'totalResponses' => $totalResponses,
            'questions'      => array_values($questionStats),
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
