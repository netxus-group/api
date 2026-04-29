<?php

namespace App\Services;

use App\Libraries\SlugGenerator;
use App\Models\SurveyAnswerModel;
use App\Models\SurveyModel;
use App\Models\SurveyQuestionModel;
use App\Models\SurveyQuestionOptionModel;
use App\Models\SurveyResponseModel;
use App\Models\SurveySectionModel;

class SurveyService
{
    private SurveyModel $surveyModel;
    private SurveySectionModel $sectionModel;
    private SurveyQuestionModel $questionModel;
    private SurveyQuestionOptionModel $optionModel;
    private SurveyResponseModel $responseModel;
    private SurveyAnswerModel $answerModel;

    public function __construct()
    {
        $this->surveyModel = new SurveyModel();
        $this->sectionModel = new SurveySectionModel();
        $this->questionModel = new SurveyQuestionModel();
        $this->optionModel = new SurveyQuestionOptionModel();
        $this->responseModel = new SurveyResponseModel();
        $this->answerModel = new SurveyAnswerModel();
    }

    public function listSurveys(): array
    {
        if (!$this->hasTables(['surveys'])) {
            return [];
        }

        $surveys = $this->surveyModel->orderBy('created_at', 'DESC')->findAll();
        return array_map(fn(array $survey) => $this->attachSurveySummary($survey), $surveys);
    }

    public function listPublicSurveys(?string $portalUserId = null): array
    {
        if (!$this->hasTables(['surveys'])) {
            return [
                'items' => [],
                'meta' => [
                    'total' => 0,
                    'available' => 0,
                    'pending' => 0,
                    'completed' => 0,
                ],
            ];
        }

        $openSurveys = $this->surveyModel
            ->where('deleted_at', null)
            ->where('status', 'published')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $itemsById = [];
        $counts = [
            'available' => 0,
            'pending' => 0,
            'completed' => 0,
        ];
        $userStates = [];

        if ($portalUserId && $this->hasTables(['survey_responses'])) {
            $responses = $this->responseModel
                ->where('user_id', $portalUserId)
                ->orderBy('updated_at', 'DESC')
                ->findAll();

            if ($responses !== []) {
                $responseSurveyIds = array_values(array_unique(array_column($responses, 'survey_id')));
                $responseSurveys = $responseSurveyIds !== [] ? $this->surveyModel->whereIn('id', $responseSurveyIds)->findAll() : [];
                $surveysById = [];
                foreach ($responseSurveys as $survey) {
                    $payload = $this->buildSurveyPayload($survey, false);
                    $surveysById[(string) $payload['id']] = $payload;
                }

                foreach ($responses as $response) {
                    $surveyId = (string) ($response['survey_id'] ?? '');
                    $status = (string) ($response['status'] ?? '');
                    if ($surveyId === '' || isset($userStates[$surveyId]) || !in_array($status, ['in_progress', 'completed'], true)) {
                        continue;
                    }

                    $userStates[$surveyId] = [
                        'state' => $status === 'completed' ? 'completed' : 'pending',
                        'survey' => $surveysById[$surveyId] ?? null,
                    ];
                }
            }
        }

        foreach ($openSurveys as $survey) {
            $payload = $this->buildSurveyPayload($survey, false);

            if (!$this->surveyCanAcceptResponses($payload) && !isset($userStates[(string) $payload['id']])) {
                continue;
            }

            $surveyId = (string) $payload['id'];
            $state = $userStates[$surveyId]['state'] ?? 'available';
            $itemsById[$surveyId] = $this->buildSurveyListItem($payload, $state);
            $counts[$state]++;
            unset($userStates[$surveyId]);
        }

        foreach ($userStates as $surveyId => $entry) {
            if (!isset($entry['survey']) || !is_array($entry['survey'])) {
                continue;
            }

            $itemsById[$surveyId] = $this->buildSurveyListItem($entry['survey'], (string) $entry['state']);
            $counts[(string) $entry['state']]++;
        }

        $items = array_values($itemsById);
        usort($items, function (array $left, array $right): int {
            $rank = static function (string $state): int {
                return match ($state) {
                    'pending' => 0,
                    'available' => 1,
                    'completed' => 2,
                    default => 3,
                };
            };

            $leftRank = $rank((string) ($left['status'] ?? 'available'));
            $rightRank = $rank((string) ($right['status'] ?? 'available'));
            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            $leftEndsAt = !empty($left['endsAt']) ? (strtotime((string) $left['endsAt']) ?: PHP_INT_MAX) : PHP_INT_MAX;
            $rightEndsAt = !empty($right['endsAt']) ? (strtotime((string) $right['endsAt']) ?: PHP_INT_MAX) : PHP_INT_MAX;
            if ($leftEndsAt !== $rightEndsAt) {
                return $leftEndsAt <=> $rightEndsAt;
            }

            return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return [
            'items' => $items,
            'meta' => [
                'total' => count($items),
                'available' => $counts['available'],
                'pending' => $counts['pending'],
                'completed' => $counts['completed'],
            ],
        ];
    }

    public function getSurveyById(string $id, bool $withResponses = true): ?array
    {
        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            return null;
        }

        return $this->buildSurveyPayload($survey, $withResponses);
    }

    public function getSurveyBySlug(string $slug, ?string $portalUserId = null, ?string $anonymousKey = null): ?array
    {
        $survey = $this->surveyModel->where('slug', $slug)->where('deleted_at', null)->first();
        if (!$survey) {
            return null;
        }

        $payload = $this->buildSurveyPayload($survey, true);
        $payload['canRespond'] = $this->canRespond($payload);
        $payload['availabilityMessage'] = $this->availabilityMessage($payload);

        $response = $this->findActiveResponse(
            $payload['id'],
            $portalUserId,
            $anonymousKey !== null ? $this->normalizeAnonymousKey($anonymousKey, '', '') : null
        );
        $payload['currentResponse'] = $response ? $this->buildResponsePayload($response, $payload) : null;

        if ((string) $payload['status'] === 'draft') {
            return null;
        }

        return $payload;
    }

    public function create(array $data, ?string $userId): array
    {
        $surveyId = $this->uuid();
        $slug = $this->normalizeSlug((string) ($data['slug'] ?? ''), (string) ($data['title'] ?? ''));

        if ($this->surveyModel->where('slug', $slug)->countAllResults() > 0) {
            throw new \RuntimeException('Slug already exists', 409);
        }

        $now = date('Y-m-d H:i:s');
        $this->surveyModel->insert([
            'id' => $surveyId,
            'title' => $this->sanitizeText((string) ($data['title'] ?? '')),
            'slug' => $slug,
            'description' => $this->sanitizeNullableText($data['description'] ?? null),
            'initial_message' => $this->sanitizeNullableText($data['initial_message'] ?? null),
            'final_message' => $this->sanitizeNullableText($data['final_message'] ?? null),
            'status' => $this->normalizeStatus((string) ($data['status'] ?? 'draft')),
            'starts_at' => $this->normalizeDateTime($data['starts_at'] ?? $data['startsAt'] ?? null),
            'ends_at' => $this->normalizeDateTime($data['ends_at'] ?? $data['endsAt'] ?? null),
            'requires_login' => $this->toBool($data['requires_login'] ?? $data['requiresLogin'] ?? false),
            'allow_back_navigation' => $this->toBool($data['allow_back_navigation'] ?? $data['allowBackNavigation'] ?? false),
            'questions_per_view' => $this->normalizeNullableInt($data['questions_per_view'] ?? $data['questionsPerView'] ?? null),
            'notify_on_publish' => $this->toBool($data['notify_on_publish'] ?? $data['notifyOnPublish'] ?? false),
            'notify_active_users' => $this->toBool($data['notify_active_users'] ?? $data['notifyActiveUsers'] ?? true),
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sections = $this->normalizeSections($data['sections'] ?? []);
        if ($sections === []) {
            $sections = [
                [
                    'id' => null,
                    'title' => 'Seccion 1',
                    'description' => null,
                    'sort_order' => 1,
                    'questions' => [],
                ],
            ];
        }

        $this->syncStructure($surveyId, $sections, false);

        $created = $this->buildSurveyPayload($this->surveyModel->find($surveyId), false);
        if ($this->toBool($created['notifyOnPublish'] ?? false) && $this->surveyCanAcceptResponses($created)) {
            $this->notifySurvey($surveyId, false);
        }

        return $this->getSurveyById($surveyId) ?? [];
    }

    public function update(string $id, array $data, ?string $userId): array
    {
        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $existing = $this->buildSurveyPayload($survey, true);
        $hasResponses = $this->surveyHasResponses($id);

        $updateData = [];
        foreach ([
            'title' => 'title',
            'description' => 'description',
            'initial_message' => 'initial_message',
            'final_message' => 'final_message',
            'status' => 'status',
            'starts_at' => 'starts_at',
            'ends_at' => 'ends_at',
            'requires_login' => 'requires_login',
            'allow_back_navigation' => 'allow_back_navigation',
            'questions_per_view' => 'questions_per_view',
            'notify_on_publish' => 'notify_on_publish',
            'notify_active_users' => 'notify_active_users',
        ] as $field => $sourceField) {
            if (array_key_exists($sourceField, $data) || array_key_exists($this->camelize($sourceField), $data)) {
                $value = $data[$sourceField] ?? $data[$this->camelize($sourceField)] ?? null;
                $updateData[$field] = match ($field) {
                    'title' => $this->sanitizeText((string) $value),
                    'description', 'initial_message', 'final_message' => $this->sanitizeNullableText($value),
                    'status' => $this->normalizeStatus((string) $value),
                    'starts_at', 'ends_at' => $this->normalizeDateTime($value),
                    'requires_login', 'allow_back_navigation' => $this->toBool($value),
                    'notify_on_publish', 'notify_active_users' => $this->toBool($value),
                    'questions_per_view' => $this->normalizeNullableInt($value),
                    default => $value,
                };
            }
        }

        $slugInput = $data['slug'] ?? null;
        if ($slugInput !== null && (string) $slugInput !== (string) ($existing['slug'] ?? '')) {
            $slug = $this->normalizeSlug((string) $slugInput, (string) ($data['title'] ?? $existing['title'] ?? ''));
            $duplicate = $this->surveyModel->where('slug', $slug)->where('id !=', $id)->countAllResults();
            if ($duplicate > 0) {
                throw new \RuntimeException('Slug already exists', 409);
            }
            $updateData['slug'] = $slug;
        }

        $updateData['updated_by'] = $userId;
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->surveyModel->update($id, $updateData);

        if (array_key_exists('sections', $data) || array_key_exists('sections', $existing)) {
            $sections = $this->normalizeSections($data['sections'] ?? $existing['sections'] ?? []);
            $this->syncStructure($id, $sections, $hasResponses);
        }

        $updated = $this->buildSurveyPayload($this->surveyModel->find($id), false);
        if (($existing['status'] ?? null) !== 'published' && ($updated['status'] ?? null) === 'published' && $this->toBool($updated['notifyOnPublish'] ?? false) && $this->surveyCanAcceptResponses($updated)) {
            $this->notifySurvey($id, false);
        }

        return $this->getSurveyById($id) ?? [];
    }

    public function delete(string $id): void
    {
        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        if ($this->surveyHasResponses($id)) {
            throw new \RuntimeException('Survey has responses and cannot be deleted', 409);
        }

        $this->surveyModel->delete($id);
    }

    public function changeStatus(string $id, string $status, ?string $userId): array
    {
        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $update = [
            'status' => $this->normalizeStatus($status),
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->surveyModel->update($id, $update);

        $updated = $this->buildSurveyPayload($this->surveyModel->find($id), false);
        if (($survey['status'] ?? null) !== 'published' && $update['status'] === 'published' && $this->toBool($updated['notifyOnPublish'] ?? false) && $this->surveyCanAcceptResponses($updated)) {
            $this->notifySurvey($id, false);
        }

        return $this->getSurveyById($id) ?? [];
    }

    public function statsSummary(): array
    {
        if (!$this->hasTables(['surveys', 'survey_responses'])) {
            return [
                'totalSurveys' => 0,
                'activeSurveys' => 0,
                'pausedSurveys' => 0,
                'closedSurveys' => 0,
                'totalResponses' => 0,
                'completedResponses' => 0,
                'incompleteResponses' => 0,
                'completionRate' => 0.0,
            ];
        }

        $surveys = $this->surveyModel->where('deleted_at', null)->findAll();
        $total = count($surveys);
        $active = 0;
        $paused = 0;
        $closed = 0;
        foreach ($surveys as $survey) {
            $status = (string) ($survey['status'] ?? 'draft');
            if ($status === 'published') {
                $active++;
            } elseif ($status === 'paused') {
                $paused++;
            } elseif ($status === 'closed') {
                $closed++;
            }
        }

        $responses = $this->responseModel->builder()->countAllResults();
        $completed = $this->responseModel->where('status', 'completed')->countAllResults();
        $incomplete = max(0, $responses - $completed);

        return [
            'totalSurveys' => $total,
            'activeSurveys' => $active,
            'pausedSurveys' => $paused,
            'closedSurveys' => $closed,
            'totalResponses' => $responses,
            'completedResponses' => $completed,
            'incompleteResponses' => $incomplete,
            'completionRate' => $responses > 0 ? round(($completed / $responses) * 100, 2) : 0.0,
        ];
    }

    public function surveyStats(string $surveyId): array
    {
        $survey = $this->getSurveyById($surveyId);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $responsesBuilder = $this->responseModel->where('survey_id', $surveyId);
        $totalResponses = $responsesBuilder->countAllResults(false);
        $completedResponses = $this->responseModel->where('survey_id', $surveyId)->where('status', 'completed')->countAllResults();

        $answers = $this->answerModel->select('survey_answers.*, survey_questions.type, survey_questions.question_text, survey_questions.section_id as question_section_id')
            ->join('survey_questions', 'survey_questions.id = survey_answers.question_id', 'left')
            ->where('survey_answers.survey_id', $surveyId)
            ->findAll();

        $questionOptions = [];
        foreach ($survey['sections'] as $section) {
            foreach ($section['questions'] as $question) {
                foreach ($question['options'] ?? [] as $option) {
                    $questionOptions[$question['id']][$option['value']] = $option['label'];
                }
            }
        }

        $responsesBySection = [];
        $questionResults = [];
        foreach ($survey['sections'] as $section) {
            $responsesBySection[$section['id']] = [];
        }

        foreach ($answers as $answer) {
            $sectionId = (string) ($answer['section_id'] ?? '');
            if ($sectionId !== '' && isset($responsesBySection[$sectionId])) {
                $responsesBySection[$sectionId][(string) ($answer['survey_response_id'] ?? '')] = true;
            }

            $questionId = (string) ($answer['question_id'] ?? '');
            if ($questionId === '') {
                continue;
            }

            if (!isset($questionResults[$questionId])) {
                $questionResults[$questionId] = [
                    'questionId' => $questionId,
                    'sectionId' => (string) ($answer['question_section_id'] ?? ''),
                    'questionText' => (string) ($answer['question_text'] ?? ''),
                    'type' => (string) ($answer['type'] ?? 'short_text'),
                    'answerCount' => 0,
                    'distribution' => [],
                    'numericAverage' => null,
                    'numericMin' => null,
                    'numericMax' => null,
                ];
            }

            $questionResults[$questionId]['answerCount']++;
            $type = (string) ($answer['type'] ?? '');
            if (in_array($type, ['single_choice', 'multiple_choice', 'dropdown'], true)) {
                $values = $type === 'multiple_choice'
                    ? (array) json_decode((string) ($answer['value_json'] ?? '[]'), true)
                    : [(string) ($answer['value_text'] ?? '')];

                foreach ($values as $value) {
                    $key = (string) $value;
                    if ($key === '') {
                        continue;
                    }

                    $label = $questionOptions[$questionId][$key] ?? $key;
                    $questionResults[$questionId]['distribution'][$key] = [
                        'value' => $key,
                        'label' => $label,
                        'count' => ($questionResults[$questionId]['distribution'][$key]['count'] ?? 0) + 1,
                    ];
                }
            }

            if ($type === 'numeric_scale') {
                $numeric = (float) ($answer['value_text'] ?? 0);
                $existing = $questionResults[$questionId];
                $existingCount = $existing['answerCount'];
                $existing['numericAverage'] = $existing['numericAverage'] === null
                    ? $numeric
                    : (($existing['numericAverage'] * ($existingCount - 1)) + $numeric) / $existingCount;
                $existing['numericMin'] = $existing['numericMin'] === null ? $numeric : min((float) $existing['numericMin'], $numeric);
                $existing['numericMax'] = $existing['numericMax'] === null ? $numeric : max((float) $existing['numericMax'], $numeric);
                $questionResults[$questionId] = $existing;
            }
        }

        foreach ($questionResults as &$result) {
            if (!empty($result['distribution'])) {
                $result['distribution'] = array_values($result['distribution']);
            }
        }

        foreach ($responsesBySection as $sectionId => $rows) {
            $responsesBySection[$sectionId] = count($rows);
        }

        return [
            'survey' => $survey,
            'summary' => [
                'responsesStarted' => $totalResponses,
                'responsesCompleted' => $completedResponses,
                'completionRate' => $totalResponses > 0 ? round(($completedResponses / $totalResponses) * 100, 2) : 0.0,
            ],
            'responsesBySection' => $responsesBySection,
            'questionResults' => array_values($questionResults),
        ];
    }

    public function listResponses(string $surveyId): array
    {
        $surveyRow = $this->surveyModel->find($surveyId);
        if (!$surveyRow) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $responses = $this->responseModel->where('survey_id', $surveyId)->orderBy('created_at', 'DESC')->findAll();
        if ($responses === []) {
            return [];
        }

        $answers = $this->answerModel->where('survey_id', $surveyId)->findAll();
        $answersByResponse = [];
        foreach ($answers as $answer) {
            $answersByResponse[$answer['survey_response_id']][] = $answer;
        }

        $portalUserIds = [];
        foreach ($responses as $response) {
            if (!empty($response['user_id'])) {
                $portalUserIds[] = (string) $response['user_id'];
            }
        }
        $portalUsers = [];
        if ($portalUserIds !== []) {
            $rows = db_connect()->table('portal_users')->whereIn('id', array_values(array_unique($portalUserIds)))->get()->getResultArray();
            foreach ($rows as $row) {
                $portalUsers[$row['id']] = $row;
            }
        }

        $sections = [];
        foreach ($this->buildSurveyPayload($surveyRow, true)['sections'] as $section) {
            $sections[$section['id']] = $section;
        }

        $questions = [];
        foreach ($sections as $section) {
            foreach ($section['questions'] as $question) {
                $questions[$question['id']] = $question;
            }
        }

        return array_map(function (array $response) use ($answersByResponse, $portalUsers, $questions, $sections): array {
            $mappedAnswers = [];
            foreach ($answersByResponse[$response['id']] ?? [] as $answer) {
                $question = $questions[$answer['question_id']] ?? null;
                $section = $sections[$answer['section_id']] ?? null;
                $mappedAnswers[] = [
                    'questionId' => $answer['question_id'],
                    'sectionId' => $answer['section_id'],
                    'questionText' => $question['questionText'] ?? null,
                    'sectionTitle' => $section['title'] ?? null,
                    'type' => $question['type'] ?? null,
                    'valueText' => $answer['value_text'],
                    'valueJson' => $answer['value_json'] !== null ? json_decode((string) $answer['value_json'], true) : null,
                ];
            }

            $user = !empty($response['user_id']) ? ($portalUsers[$response['user_id']] ?? null) : null;

            return [
                'id' => $response['id'],
                'surveyId' => $response['survey_id'],
                'userId' => $response['user_id'],
                'anonymousKey' => $response['anonymous_key'],
                'status' => $response['status'],
                'currentSectionId' => $response['current_section_id'],
                'completedAt' => $response['completed_at'],
                'ipHash' => $response['ip_hash'],
                'userAgentHash' => $response['user_agent_hash'],
                'createdAt' => $response['created_at'],
                'updatedAt' => $response['updated_at'],
                'user' => $user ? [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'displayName' => $user['display_name'] ?? $user['email'],
                ] : null,
                'answers' => $mappedAnswers,
            ];
        }, $responses);
    }

    public function pendingForUser(string $portalUserId): array
    {
        $responses = $this->responseModel->where('user_id', $portalUserId)->where('status', 'in_progress')->findAll();
        return $this->buildUserSurveyRows($responses);
    }

    public function completedForUser(string $portalUserId): array
    {
        $responses = $this->responseModel->where('user_id', $portalUserId)->where('status', 'completed')->findAll();
        return $this->buildUserSurveyRows($responses);
    }

    public function staleInProgress(int $days = 3): array
    {
        $threshold = date('Y-m-d H:i:s', time() - max(1, $days) * 86400);
        return $this->responseModel->where('status', 'in_progress')
            ->where('updated_at <', $threshold)
            ->findAll();
    }

    public function notificationStats(string $surveyId): array
    {
        return service('communicationService')->surveyNotificationStats($surveyId);
    }

    public function notifySurvey(string $surveyId, bool $manual = false): array
    {
        $survey = $this->surveyModel->find($surveyId);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $payload = $this->buildSurveyPayload($survey, false);
        $recipients = $this->buildSurveyNotificationRecipients($surveyId, $payload);
        $communication = service('communicationService');

        $result = $communication->notifySurveyPublished($surveyId, $recipients, [
            'survey_title' => (string) ($payload['title'] ?? ''),
            'survey_url' => rtrim((string) config('Communications')->portalUrl, '/') . '/encuestas/' . (string) ($payload['slug'] ?? ''),
            'survey_initial_message' => (string) ($payload['initialMessage'] ?? ''),
            'survey_ends_at' => (string) ($payload['endsAt'] ?? ''),
            'site_name' => (string) config('Communications')->siteName,
            'site_url' => (string) config('Communications')->portalUrl,
        ], $manual);

        $portalUserService = service('portalUserService');
        foreach ($recipients as $recipient) {
            $recipientUserId = is_array($recipient) ? (string) ($recipient['user_id'] ?? '') : '';
            if ($recipientUserId === '') {
                continue;
            }

            $portalUserService->createNotification($recipientUserId, [
                'type' => 'survey',
                'title' => (string) ($payload['title'] ?? 'Nueva encuesta disponible'),
                'body' => (string) ($payload['initialMessage'] ?? 'Ya podes participar en la encuesta publicada.'),
                'url' => rtrim((string) config('Communications')->portalUrl, '/') . '/encuestas/' . (string) ($payload['slug'] ?? ''),
                'metadata' => [
                    'surveyId' => $surveyId,
                    'surveySlug' => (string) ($payload['slug'] ?? ''),
                    'manual' => $manual,
                ],
            ], 'survey_published:' . $surveyId . ':' . $recipientUserId);
        }

        return [
            'surveyId' => $surveyId,
            'recipients' => count($recipients),
            'sent' => $result['sent'] ?? 0,
            'failed' => $result['failed'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
        ];
    }

    public function runSurveyReminders(int $daysAfter = 3, int $maxReminders = 1, ?string $surveyId = null): array
    {
        return service('communicationService')->runSurveyReminders($surveyId, $daysAfter, $maxReminders);
    }

    public function startResponse(
        string $slug,
        ?string $portalUserId,
        ?string $anonymousKey,
        string $ipAddress,
        string $userAgent
    ): array {
        $survey = $this->resolveOpenSurvey($slug);
        if (!$survey) {
            throw new \RuntimeException('Survey not available', 404);
        }

        $anonymousKey = $this->normalizeAnonymousKey($anonymousKey, $ipAddress, $userAgent);
        $existing = $this->findActiveResponse($survey['id'], $portalUserId, $anonymousKey);
        if ($existing) {
            if ((string) ($existing['status'] ?? '') === 'completed') {
                throw new \RuntimeException('Survey already completed', 409);
            }
            return $this->buildResponsePayload($existing, $survey);
        }

        if ($this->surveyRequiresLogin($survey) && !$portalUserId) {
            throw new \RuntimeException('Login required for this survey', 401);
        }

        $sections = $survey['sections'] ?? [];
        $firstSection = $sections[0]['id'] ?? null;
        $responseId = $this->uuid();
        $now = date('Y-m-d H:i:s');
        $this->responseModel->insert([
            'id' => $responseId,
            'survey_id' => $survey['id'],
            'user_id' => $portalUserId,
            'anonymous_key' => $portalUserId ? null : $anonymousKey,
            'status' => 'in_progress',
            'current_section_id' => $firstSection,
            'completed_at' => null,
            'ip_hash' => sha1($ipAddress ?: 'unknown'),
            'user_agent_hash' => sha1($userAgent ?: 'unknown'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->responseModel->find($responseId);
        return [
            'response' => $this->buildResponsePayload($response, $survey),
            'nextSectionId' => $firstSection,
            'canComplete' => $firstSection === null,
        ];
    }

    public function saveSection(
        string $slug,
        string $sectionId,
        array $payload,
        ?string $portalUserId,
        ?string $anonymousKey,
        string $ipAddress,
        string $userAgent
    ): array {
        $survey = $this->resolveOpenSurvey($slug);
        if (!$survey) {
            throw new \RuntimeException('Survey not available', 404);
        }
        if (!$this->surveyCanAcceptResponses($survey)) {
            throw new \RuntimeException($this->availabilityMessage($survey), 409);
        }

        $anonymousKey = $this->normalizeAnonymousKey($anonymousKey, $ipAddress, $userAgent);
        $response = $this->findActiveResponse($survey['id'], $portalUserId, $anonymousKey);
        if (!$response) {
            throw new \RuntimeException('Response not found', 404);
        }
        if ((string) ($response['status'] ?? '') === 'completed') {
            throw new \RuntimeException('Survey already completed', 409);
        }

        $sections = $survey['sections'] ?? [];
        $sectionIndex = $this->findSectionIndex($sections, $sectionId);
        if ($sectionIndex === null) {
            throw new \RuntimeException('Section not found', 404);
        }

        $currentSectionId = (string) ($response['current_section_id'] ?? '');
        $currentSectionIndex = $currentSectionId !== '' ? $this->findSectionIndex($sections, $currentSectionId) : null;
        if ($currentSectionIndex !== null && $sectionIndex < $currentSectionIndex && !$this->toBool($survey['allowBackNavigation'] ?? false)) {
            throw new \RuntimeException('Back navigation is disabled', 409);
        }
        if ($currentSectionIndex !== null && $sectionIndex > $currentSectionIndex) {
            throw new \RuntimeException('Section progression is invalid', 409);
        }

        $section = $sections[$sectionIndex];
        $answers = $this->normalizeAnswersInput($payload['answers'] ?? $payload, $section);
        $validation = $this->validateSectionAnswers($survey, $section, $answers);
        if ($validation !== null) {
            throw new \RuntimeException($validation, 422);
        }

        $this->persistSectionAnswers($survey['id'], $response['id'], $section, $answers);

        $nextSection = $sections[$sectionIndex + 1] ?? null;
        $this->responseModel->update($response['id'], [
            'current_section_id' => $nextSection['id'] ?? $section['id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->responseModel->find($response['id']);
        return [
            'response' => $this->buildResponsePayload($response, $survey),
            'nextSectionId' => $nextSection['id'] ?? null,
            'canComplete' => $nextSection === null,
        ];
    }

    public function completeSurvey(
        string $slug,
        array $payload,
        ?string $portalUserId,
        ?string $anonymousKey,
        string $ipAddress,
        string $userAgent
    ): array {
        $survey = $this->resolveOpenSurvey($slug);
        if (!$survey) {
            throw new \RuntimeException('Survey not available', 404);
        }
        if (!$this->surveyCanAcceptResponses($survey)) {
            throw new \RuntimeException($this->availabilityMessage($survey), 409);
        }

        $anonymousKey = $this->normalizeAnonymousKey($anonymousKey, $ipAddress, $userAgent);
        $response = $this->findActiveResponse($survey['id'], $portalUserId, $anonymousKey);
        if (!$response) {
            throw new \RuntimeException('Response not found', 404);
        }
        if ((string) ($response['status'] ?? '') === 'completed') {
            throw new \RuntimeException('Survey already completed', 409);
        }

        $sectionId = $payload['sectionId'] ?? $payload['section_id'] ?? null;
        if ($sectionId) {
            $this->saveSection($slug, (string) $sectionId, $payload, $portalUserId, $anonymousKey, $ipAddress, $userAgent);
            $response = $this->findActiveResponse($survey['id'], $portalUserId, $anonymousKey) ?? $response;
        }

        $this->ensureAllRequiredAnswersPresent($survey, $response['id']);

        $this->responseModel->update($response['id'], [
            'status' => 'completed',
            'current_section_id' => null,
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->responseModel->find($response['id']);
        return $this->buildResponsePayload($response, $survey);
    }

    public function exportExcel(string $surveyId): string
    {
        $survey = $this->getSurveyById($surveyId, true);
        if (!$survey) {
            throw new \RuntimeException('Survey not found', 404);
        }

        $responses = $this->listResponses($surveyId);
        $questions = [];
        foreach ($survey['sections'] as $section) {
            foreach ($section['questions'] as $question) {
                $questions[] = [
                    'sectionTitle' => $section['title'],
                    'questionText' => $question['questionText'],
                    'questionId' => $question['id'],
                    'type' => $question['type'],
                ];
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $overview = $spreadsheet->getActiveSheet();
        $overview->setTitle('Resumen');
        $overview->setCellValue('A1', 'Encuesta');
        $overview->setCellValue('B1', $survey['title']);
        $overview->setCellValue('A2', 'Slug');
        $overview->setCellValue('B2', $survey['slug']);
        $overview->setCellValue('A3', 'Estado');
        $overview->setCellValue('B3', $survey['status']);
        $overview->setCellValue('A4', 'Respuestas');
        $overview->setCellValue('B4', count($responses));

        $rowsSheet = $spreadsheet->createSheet();
        $rowsSheet->setTitle('Respuestas');
        $headers = ['Response ID', 'Usuario', 'Email', 'Estado', 'Inicio', 'Finalizacion', 'Anonymous Key'];
        foreach ($questions as $question) {
            $headers[] = $question['sectionTitle'] . ' - ' . $question['questionText'];
        }
        foreach ($headers as $index => $header) {
            $rowsSheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        foreach ($responses as $rowIndex => $response) {
            $sheetRow = $rowIndex + 2;
            $rowsSheet->setCellValueByColumnAndRow(1, $sheetRow, $response['id']);
            $rowsSheet->setCellValueByColumnAndRow(2, $sheetRow, $response['user']['displayName'] ?? '');
            $rowsSheet->setCellValueByColumnAndRow(3, $sheetRow, $response['user']['email'] ?? '');
            $rowsSheet->setCellValueByColumnAndRow(4, $sheetRow, $response['status']);
            $rowsSheet->setCellValueByColumnAndRow(5, $sheetRow, $response['createdAt']);
            $rowsSheet->setCellValueByColumnAndRow(6, $sheetRow, $response['completedAt']);
            $rowsSheet->setCellValueByColumnAndRow(7, $sheetRow, $response['anonymousKey'] ?? '');

            $answerMap = [];
            foreach ($response['answers'] as $answer) {
                $key = $answer['questionId'];
                $value = $answer['valueJson'] !== null ? json_encode($answer['valueJson'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) ($answer['valueText'] ?? '');
                $answerMap[$key] = $value;
            }

            foreach ($questions as $qIndex => $question) {
                $rowsSheet->setCellValueByColumnAndRow($qIndex + 8, $sheetRow, $answerMap[$question['questionId']] ?? '');
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'netxus_survey_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content ?: '';
    }

    private function attachSurveySummary(array $survey): array
    {
        $survey = $this->buildSurveyPayload($survey, false);
        $stats = $this->responseModel->where('survey_id', $survey['id']);
        $total = $stats->countAllResults(false);
        $completed = $this->responseModel->where('survey_id', $survey['id'])->where('status', 'completed')->countAllResults();
        $survey['responsesStarted'] = $total;
        $survey['responsesCompleted'] = $completed;
        $survey['completionRate'] = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;
        return $survey;
    }

    private function buildSurveyPayload(array $survey, bool $withStructure): array
    {
        $payload = [
            'id' => $survey['id'],
            'title' => $survey['title'],
            'slug' => $survey['slug'],
            'description' => $survey['description'],
            'initialMessage' => $survey['initial_message'],
            'finalMessage' => $survey['final_message'],
            'status' => $survey['status'],
            'startsAt' => $survey['starts_at'],
            'endsAt' => $survey['ends_at'],
            'requiresLogin' => (bool) $survey['requires_login'],
            'allowBackNavigation' => (bool) $survey['allow_back_navigation'],
            'questionsPerView' => $survey['questions_per_view'] !== null ? (int) $survey['questions_per_view'] : null,
            'notifyOnPublish' => (bool) ($survey['notify_on_publish'] ?? false),
            'notifyActiveUsers' => (bool) ($survey['notify_active_users'] ?? true),
            'createdBy' => $survey['created_by'],
            'updatedBy' => $survey['updated_by'],
            'createdAt' => $survey['created_at'],
            'updatedAt' => $survey['updated_at'],
        ];

        if (!$withStructure) {
            return $payload;
        }

        $sections = $this->sectionModel->where('survey_id', $survey['id'])->orderBy('sort_order', 'ASC')->findAll();
        $questions = $this->questionModel->where('survey_id', $survey['id'])->orderBy('sort_order', 'ASC')->findAll();
        $options = [];
        if ($questions !== []) {
            $options = $this->optionModel->whereIn('question_id', array_column($questions, 'id'))->orderBy('sort_order', 'ASC')->findAll();
        }

        $optionsByQuestion = [];
        foreach ($options as $option) {
            $optionsByQuestion[$option['question_id']][] = [
                'id' => $option['id'],
                'questionId' => $option['question_id'],
                'label' => $option['label'],
                'value' => $option['value'],
                'order' => (int) $option['sort_order'],
                'createdAt' => $option['created_at'],
                'updatedAt' => $option['updated_at'],
            ];
        }

        $questionsBySection = [];
        foreach ($questions as $question) {
            $questionsBySection[$question['section_id']][] = [
                'id' => $question['id'],
                'surveyId' => $question['survey_id'],
                'sectionId' => $question['section_id'],
                'questionText' => $question['question_text'],
                'helpText' => $question['help_text'],
                'type' => $question['type'],
                'isRequired' => (bool) $question['is_required'],
                'order' => (int) $question['sort_order'],
                'config' => $question['config'] !== null ? json_decode((string) $question['config'], true) : null,
                'options' => $optionsByQuestion[$question['id']] ?? [],
                'createdAt' => $question['created_at'],
                'updatedAt' => $question['updated_at'],
            ];
        }

        $payload['sections'] = array_map(function (array $section) use ($questionsBySection): array {
            return [
                'id' => $section['id'],
                'surveyId' => $section['survey_id'],
                'title' => $section['title'],
                'description' => $section['description'],
                'order' => (int) $section['sort_order'],
                'questions' => $questionsBySection[$section['id']] ?? [],
                'createdAt' => $section['created_at'],
                'updatedAt' => $section['updated_at'],
            ];
        }, $sections);

        return $payload;
    }

    private function normalizeSections(array $sections): array
    {
        $normalized = [];
        foreach (array_values($sections) as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $questions = $section['questions'] ?? [];
            $normalized[] = [
                'id' => isset($section['id']) && $section['id'] !== '' ? (string) $section['id'] : null,
                'title' => $this->sanitizeText((string) ($section['title'] ?? ('Seccion ' . ($index + 1)))),
                'description' => $this->sanitizeNullableText($section['description'] ?? null),
                'sort_order' => $this->normalizeNullableInt($section['order'] ?? $section['sort_order'] ?? $index + 1) ?? ($index + 1),
                'questions' => $this->normalizeQuestions($questions),
            ];
        }

        return $normalized;
    }

    private function normalizeQuestions(array $questions): array
    {
        $normalized = [];
        foreach (array_values($questions) as $index => $question) {
            if (!is_array($question)) {
                continue;
            }

            $normalized[] = [
                'id' => isset($question['id']) && $question['id'] !== '' ? (string) $question['id'] : null,
                'question_text' => $this->sanitizeText((string) ($question['questionText'] ?? $question['question_text'] ?? '')),
                'help_text' => $this->sanitizeNullableText($question['helpText'] ?? $question['help_text'] ?? null),
                'type' => $this->normalizeQuestionType((string) ($question['type'] ?? 'short_text')),
                'is_required' => $this->toBool($question['isRequired'] ?? $question['is_required'] ?? false),
                'sort_order' => $this->normalizeNullableInt($question['order'] ?? $question['sort_order'] ?? $index + 1) ?? ($index + 1),
                'config' => is_array($question['config'] ?? null) ? ($question['config'] ?? null) : null,
                'options' => $this->normalizeOptions($question['options'] ?? []),
            ];
        }

        return $normalized;
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach (array_values($options) as $index => $option) {
            if (!is_array($option)) {
                continue;
            }

            $label = $this->sanitizeText((string) ($option['label'] ?? $option['value'] ?? ''));
            $value = $this->sanitizeText((string) ($option['value'] ?? $label));

            $normalized[] = [
                'id' => isset($option['id']) && $option['id'] !== '' ? (string) $option['id'] : null,
                'label' => $label,
                'value' => $value !== '' ? $value : SlugGenerator::slugify($label),
                'sort_order' => $this->normalizeNullableInt($option['order'] ?? $option['sort_order'] ?? $index + 1) ?? ($index + 1),
            ];
        }

        return $normalized;
    }

    private function syncStructure(string $surveyId, array $sections, bool $hasResponses): void
    {
        $existingSections = $this->sectionModel->where('survey_id', $surveyId)->orderBy('sort_order', 'ASC')->findAll();
        $existingSectionsById = [];
        foreach ($existingSections as $section) {
            $existingSectionsById[$section['id']] = $section;
        }

        $existingQuestions = $this->questionModel->where('survey_id', $surveyId)->orderBy('sort_order', 'ASC')->findAll();
        $existingQuestionsById = [];
        foreach ($existingQuestions as $question) {
            $existingQuestionsById[$question['id']] = $question;
        }

        $existingOptions = [];
        if ($existingQuestions !== []) {
            $options = $this->optionModel->whereIn('question_id', array_column($existingQuestions, 'id'))->orderBy('sort_order', 'ASC')->findAll();
            foreach ($options as $option) {
                $existingOptions[$option['question_id']][$option['id']] = $option;
            }
        }

        if ($hasResponses) {
            $incomingSectionIds = [];
            $incomingQuestionIds = [];
            $incomingOptionIdsByQuestion = [];

            foreach ($sections as $section) {
                if (!empty($section['id'])) {
                    $incomingSectionIds[] = (string) $section['id'];
                }

                foreach ($section['questions'] ?? [] as $question) {
                    if (!empty($question['id'])) {
                        $incomingQuestionIds[] = (string) $question['id'];
                    }

                    foreach ($question['options'] ?? [] as $option) {
                        if (!empty($option['id'])) {
                            $incomingOptionIdsByQuestion[(string) ($question['id'] ?? '')][] = (string) $option['id'];
                        }
                    }
                }
            }

            $removedSections = array_diff(array_keys($existingSectionsById), $incomingSectionIds);
            if ($removedSections !== []) {
                throw new \RuntimeException('Sections cannot be removed after responses exist', 409);
            }

            $removedQuestions = array_diff(array_keys($existingQuestionsById), $incomingQuestionIds);
            if ($removedQuestions !== []) {
                throw new \RuntimeException('Questions cannot be removed after responses exist', 409);
            }

            foreach ($existingOptions as $questionId => $questionOptions) {
                $incomingOptionIds = $incomingOptionIdsByQuestion[$questionId] ?? [];
                $removedOptions = array_diff(array_keys($questionOptions), $incomingOptionIds);
                if ($removedOptions !== []) {
                    throw new \RuntimeException('Options cannot be removed after responses exist', 409);
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $processedSectionIds = [];
        $processedQuestionIds = [];

        foreach (array_values($sections) as $sectionIndex => $section) {
            $sectionId = $section['id'] ?? null;
            if ($sectionId && isset($existingSectionsById[$sectionId])) {
                $this->sectionModel->update($sectionId, [
                    'title' => $section['title'],
                    'description' => $section['description'],
                    'sort_order' => $section['sort_order'] ?? $sectionIndex + 1,
                    'updated_at' => $now,
                ]);
            } else {
                $sectionId = $this->uuid();
                $this->sectionModel->insert([
                    'id' => $sectionId,
                    'survey_id' => $surveyId,
                    'title' => $section['title'],
                    'description' => $section['description'],
                    'sort_order' => $section['sort_order'] ?? $sectionIndex + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $processedSectionIds[] = $sectionId;

            foreach (array_values($section['questions'] ?? []) as $questionIndex => $question) {
                $questionId = $question['id'] ?? null;
                if ($questionId && isset($existingQuestionsById[$questionId])) {
                    $existingQuestion = $existingQuestionsById[$questionId];
                    if ($hasResponses && (string) $existingQuestion['type'] !== (string) $question['type']) {
                        throw new \RuntimeException('Question type cannot change after responses exist', 409);
                    }

                    $this->questionModel->update($questionId, [
                        'section_id' => $sectionId,
                        'question_text' => $question['question_text'],
                        'help_text' => $question['help_text'],
                        'type' => $question['type'],
                        'is_required' => $question['is_required'] ? 1 : 0,
                        'sort_order' => $question['sort_order'] ?? $questionIndex + 1,
                        'config' => $question['config'] !== null ? json_encode($question['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                        'updated_at' => $now,
                    ]);
                } else {
                    $questionId = $this->uuid();
                    $this->questionModel->insert([
                        'id' => $questionId,
                        'survey_id' => $surveyId,
                        'section_id' => $sectionId,
                        'question_text' => $question['question_text'],
                        'help_text' => $question['help_text'],
                        'type' => $question['type'],
                        'is_required' => $question['is_required'] ? 1 : 0,
                        'sort_order' => $question['sort_order'] ?? $questionIndex + 1,
                        'config' => $question['config'] !== null ? json_encode($question['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $processedQuestionIds[] = $questionId;

                $supportsOptions = in_array($question['type'], ['single_choice', 'multiple_choice', 'dropdown'], true);
                $incomingOptions = $question['options'] ?? [];
                $existingQuestionOptions = $existingOptions[$questionId] ?? [];

                if (!$supportsOptions) {
                    if ($incomingOptions !== []) {
                        throw new \RuntimeException('Options are only valid for choice questions', 422);
                    }
                    if (!$hasResponses && $existingQuestionOptions !== []) {
                        $this->optionModel->where('question_id', $questionId)->delete();
                    }
                    continue;
                }

                if ($hasResponses && $existingQuestionOptions !== [] && $incomingOptions === []) {
                    throw new \RuntimeException('Options cannot be removed after responses exist', 409);
                }

                $processedForQuestion = [];
                foreach (array_values($incomingOptions) as $optionIndex => $option) {
                    $optionId = $option['id'] ?? null;
                    if ($optionId && isset($existingQuestionOptions[$optionId])) {
                        $this->optionModel->update($optionId, [
                            'label' => $option['label'],
                            'value' => $option['value'],
                            'sort_order' => $option['sort_order'] ?? $optionIndex + 1,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $optionId = $this->uuid();
                        $this->optionModel->insert([
                            'id' => $optionId,
                            'question_id' => $questionId,
                            'label' => $option['label'],
                            'value' => $option['value'],
                            'sort_order' => $option['sort_order'] ?? $optionIndex + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $processedForQuestion[] = $optionId;
                }

                if (!$hasResponses) {
                    foreach (array_keys($existingQuestionOptions) as $optionId) {
                        if (!in_array($optionId, $processedForQuestion, true)) {
                            $this->optionModel->delete($optionId);
                        }
                    }
                } elseif (count($existingQuestionOptions) > count($processedForQuestion)) {
                    throw new \RuntimeException('Options cannot be deleted after responses exist', 409);
                }
            }
        }

        if (!$hasResponses) {
            foreach ($existingQuestionsById as $questionId => $question) {
                if (!in_array($questionId, $processedQuestionIds, true)) {
                    $this->optionModel->where('question_id', $questionId)->delete();
                    $this->questionModel->delete($questionId);
                }
            }

            foreach ($existingSectionsById as $sectionId => $section) {
                if (!in_array($sectionId, $processedSectionIds, true)) {
                    $this->sectionModel->delete($sectionId);
                }
            }
        } elseif (count($processedSectionIds) < count($existingSectionsById)) {
            throw new \RuntimeException('Sections cannot be removed after responses exist', 409);
        }
    }

    private function canRespond(array $survey): bool
    {
        return $this->surveyCanAcceptResponses($survey);
    }

    private function surveyCanAcceptResponses(array $survey): bool
    {
        if ((string) ($survey['status'] ?? '') !== 'published') {
            return false;
        }

        $now = time();
        if (!empty($survey['startsAt']) && strtotime((string) $survey['startsAt']) > $now) {
            return false;
        }
        if (!empty($survey['endsAt']) && strtotime((string) $survey['endsAt']) < $now) {
            return false;
        }

        return true;
    }

    private function resolveOpenSurvey(string $slug): ?array
    {
        $survey = $this->surveyModel->where('slug', $slug)->where('deleted_at', null)->first();
        if (!$survey) {
            return null;
        }

        if ((string) ($survey['status'] ?? '') === 'draft') {
            return null;
        }

        return $this->buildSurveyPayload($survey, true);
    }

    private function availabilityMessage(array $survey): string
    {
        $status = (string) ($survey['status'] ?? '');
        if ($status === 'paused') {
            return 'Survey paused';
        }
        if ($status === 'closed') {
            return 'Survey closed';
        }
        if ($status !== 'published') {
            return 'Survey not available';
        }
        if (!empty($survey['startsAt']) && strtotime((string) $survey['startsAt']) > time()) {
            return 'Survey has not started yet';
        }
        if (!empty($survey['endsAt']) && strtotime((string) $survey['endsAt']) < time()) {
            return 'Survey has ended';
        }
        return 'Survey not available';
    }

    private function surveyRequiresLogin(array $survey): bool
    {
        return $this->toBool($survey['requiresLogin'] ?? false);
    }

    private function findActiveResponse(string $surveyId, ?string $portalUserId, ?string $anonymousKey): ?array
    {
        $builder = $this->responseModel->where('survey_id', $surveyId);
        if ($portalUserId) {
            $builder->where('user_id', $portalUserId);
        } elseif ($anonymousKey) {
            $builder->where('anonymous_key', $anonymousKey);
        } else {
            return null;
        }

        $response = $builder->orderBy('updated_at', 'DESC')->first();
        if (!$response) {
            return null;
        }

        return $this->expandResponseRow($response);
    }

    private function buildResponsePayload(array $response, array $survey): array
    {
        $answers = $this->answerModel->where('survey_response_id', $response['id'])->orderBy('created_at', 'ASC')->findAll();
        return [
            'id' => $response['id'],
            'surveyId' => $response['survey_id'],
            'userId' => $response['user_id'],
            'anonymousKey' => $response['anonymous_key'],
            'status' => $response['status'],
            'currentSectionId' => $response['current_section_id'],
            'completedAt' => $response['completed_at'],
            'ipHash' => $response['ip_hash'],
            'userAgentHash' => $response['user_agent_hash'],
            'createdAt' => $response['created_at'],
            'updatedAt' => $response['updated_at'],
            'answers' => array_map(static function (array $answer): array {
                return [
                    'questionId' => $answer['question_id'],
                    'sectionId' => $answer['section_id'],
                    'valueText' => $answer['value_text'],
                    'valueJson' => $answer['value_json'] !== null ? json_decode((string) $answer['value_json'], true) : null,
                ];
            }, $answers),
            'survey' => [
                'id' => $survey['id'],
                'title' => $survey['title'],
                'slug' => $survey['slug'],
            ],
        ];
    }

    private function persistSectionAnswers(string $surveyId, string $responseId, array $section, array $answers): void
    {
        $questionIds = array_column($section['questions'], 'id');
        if ($questionIds !== []) {
            $this->answerModel->where('survey_response_id', $responseId)->whereIn('question_id', $questionIds)->delete();
        }

        $now = date('Y-m-d H:i:s');
        foreach ($answers as $questionId => $answer) {
            $this->answerModel->insert([
                'id' => $this->uuid(),
                'survey_response_id' => $responseId,
                'survey_id' => $surveyId,
                'section_id' => $section['id'],
                'question_id' => $questionId,
                'value_text' => $answer['value_text'],
                'value_json' => $answer['value_json'] !== null ? json_encode($answer['value_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function validateSectionAnswers(array $survey, array $section, array $answers): ?string
    {
        $hasRequired = false;
        foreach ($section['questions'] as $question) {
            if (!empty($question['isRequired'])) {
                $hasRequired = true;
                break;
            }
        }

        foreach ($section['questions'] as $question) {
            $questionId = $question['id'];
            $type = (string) $question['type'];
            $rawAnswer = $this->unwrapSurveyAnswerValue($answers[$questionId] ?? null);
            $isRequired = (bool) $question['isRequired'];
            $isEmpty = $this->isBlankAnswer($rawAnswer);

            if ($isRequired && $isEmpty) {
                $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'Todas las preguntas obligatorias deben responderse');
                return 'Todas las preguntas obligatorias deben responderse';
            }

            if (!$isRequired && !$hasRequired && $isEmpty && in_array($type, ['short_text', 'long_text'], true)) {
                $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'No se puede avanzar con preguntas de texto vacias en esta seccion');
                return 'No se puede avanzar con preguntas de texto vacias en esta seccion';
            }

            if ($isEmpty) {
                continue;
            }

            $optionValues = $question['options'] ?? [];
            $normalizedOptionTokens = [];
            foreach ($optionValues as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $normalizedOptionTokens[] = trim((string) ($option['value'] ?? ''));
                $normalizedOptionTokens[] = trim((string) ($option['label'] ?? ''));
            }
            $normalizedOptionTokens = array_values(array_unique(array_filter($normalizedOptionTokens, static fn(string $value): bool => $value !== '')));
            switch ($type) {
                case 'short_text':
                case 'long_text':
                    break;
                case 'single_choice':
                case 'dropdown':
                    if (is_array($rawAnswer)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'Respuesta invalida para la pregunta seleccionada');
                        return 'Respuesta invalida para la pregunta seleccionada';
                    }
                    if (!in_array(trim((string) $rawAnswer), $normalizedOptionTokens, true)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'Respuesta invalida para la pregunta seleccionada');
                        return 'Respuesta invalida para la pregunta seleccionada';
                    }
                    break;
                case 'multiple_choice':
                    $multipleValues = is_array($rawAnswer) ? $rawAnswer : [$rawAnswer];
                    $normalizedMultipleValues = array_map(static fn(mixed $value): string => trim((string) $value), array_values($multipleValues));
                    if (array_diff($normalizedMultipleValues, $normalizedOptionTokens) !== []) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'Respuesta invalida para la pregunta seleccionada');
                        return 'Respuesta invalida para la pregunta seleccionada';
                    }
                    break;
                case 'numeric_scale':
                    if (is_array($rawAnswer)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'La escala numerica requiere un valor numerico');
                        return 'La escala numerica requiere un valor numerico';
                    }
                    if (!is_numeric($rawAnswer)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'La escala numerica requiere un valor numerico');
                        return 'La escala numerica requiere un valor numerico';
                    }
                    break;
                case 'date':
                    if (is_array($rawAnswer)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'La fecha ingresada no es valida');
                        return 'La fecha ingresada no es valida';
                    }
                    if (!$this->isValidDateInput((string) $rawAnswer)) {
                        $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'La fecha ingresada no es valida');
                        return 'La fecha ingresada no es valida';
                    }
                    break;
                default:
                    $this->logSurveyValidationFailure($survey, $section, $question, $rawAnswer, 'Tipo de pregunta no soportado');
                    return 'Tipo de pregunta no soportado';
            }
        }

        return null;
    }

    private function normalizeAnswersInput(array $rawAnswers, array $section): array
    {
        $normalized = [];
        $questionsById = [];
        foreach ($section['questions'] as $question) {
            $questionsById[$question['id']] = $question;
        }

        foreach ($rawAnswers as $questionId => $value) {
            if (!isset($questionsById[$questionId])) {
                continue;
            }

            $question = $questionsById[$questionId];
            $type = (string) $question['type'];
            $value = $this->unwrapSurveyAnswerValue($value);
            if ($this->isBlankAnswer($value)) {
                continue;
            }

            if (in_array($type, ['short_text', 'long_text', 'numeric_scale', 'date'], true)) {
                if (is_array($value)) {
                    continue;
                }
                $normalized[$questionId] = [
                    'value_text' => $type === 'date' ? $this->normalizeDateValue((string) $value) : $this->sanitizeText((string) $value),
                    'value_json' => null,
                ];
                continue;
            }

            if (in_array($type, ['single_choice', 'dropdown'], true)) {
                if (is_array($value)) {
                    $firstValue = array_values(array_filter(array_map(static fn(mixed $item): string => trim((string) $item), $value), static fn(string $item): bool => $item !== ''))[0] ?? null;
                    if ($firstValue === null) {
                        continue;
                    }
                    $value = $firstValue;
                }
                $normalized[$questionId] = [
                    'value_text' => $this->sanitizeText((string) $value),
                    'value_json' => null,
                ];
                continue;
            }

            if ($type === 'multiple_choice') {
                $normalized[$questionId] = [
                    'value_text' => null,
                    'value_json' => array_values(array_map('strval', is_array($value) ? $value : [$value])),
                ];
            }
        }

        return $normalized;
    }

    private function ensureAllRequiredAnswersPresent(array $survey, string $responseId): void
    {
        $responses = $this->answerModel->where('survey_response_id', $responseId)->findAll();
        $answered = [];
        foreach ($responses as $response) {
            $answered[$response['question_id']] = true;
        }

        foreach ($survey['sections'] as $section) {
            foreach ($section['questions'] as $question) {
                if (!empty($question['isRequired']) && empty($answered[$question['id']])) {
                    throw new \RuntimeException('There are unanswered required questions', 422);
                }
            }
        }
    }

    private function buildUserSurveyRows(array $responses): array
    {
        if ($responses === []) {
            return [];
        }

        $surveyIds = array_values(array_unique(array_column($responses, 'survey_id')));
        $surveys = $this->surveyModel->whereIn('id', $surveyIds)->findAll();
        $surveysById = [];
        foreach ($surveys as $survey) {
            $surveysById[$survey['id']] = $this->buildSurveyPayload($survey, false);
        }

        return array_map(function (array $response) use ($surveysById): array {
            $survey = $surveysById[$response['survey_id']] ?? null;
            return [
                'responseId' => $response['id'],
                'surveyId' => $response['survey_id'],
                'status' => $response['status'],
                'currentSectionId' => $response['current_section_id'],
                'completedAt' => $response['completed_at'],
                'startedAt' => $response['created_at'],
                'updatedAt' => $response['updated_at'],
                'survey' => $survey,
            ];
        }, $responses);
    }

    private function buildSurveyListItem(array $survey, string $state): array
    {
        $normalizedState = in_array($state, ['available', 'pending', 'completed'], true) ? $state : 'available';
        $statusLabels = [
            'available' => 'Disponible',
            'pending' => 'Pendiente',
            'completed' => 'Completada',
        ];
        $actionLabels = [
            'available' => 'Responder',
            'pending' => 'Continuar',
            'completed' => 'Ver',
        ];

        return [
            'id' => (string) ($survey['id'] ?? ''),
            'title' => (string) ($survey['title'] ?? ''),
            'slug' => (string) ($survey['slug'] ?? ''),
            'status' => $normalizedState,
            'statusLabel' => $statusLabels[$normalizedState],
            'actionLabel' => $actionLabels[$normalizedState],
            'endsAt' => $survey['endsAt'] ?? null,
            'requiresLogin' => (bool) ($survey['requiresLogin'] ?? false),
            'path' => '/encuestas/' . (string) ($survey['slug'] ?? ''),
        ];
    }

    private function surveyHasResponses(string $surveyId): bool
    {
        return $this->responseModel->where('survey_id', $surveyId)->countAllResults() > 0;
    }

    private function normalizeQuestionType(string $type): string
    {
        $allowed = ['short_text', 'long_text', 'single_choice', 'multiple_choice', 'dropdown', 'numeric_scale', 'date'];
        return in_array($type, $allowed, true) ? $type : 'short_text';
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = ['draft', 'published', 'paused', 'closed'];
        return in_array($status, $allowed, true) ? $status : 'draft';
    }

    private function normalizeSlug(string $slug, string $title): string
    {
        $normalized = trim($slug);
        if ($normalized === '') {
            $normalized = $title;
        }
        $normalized = SlugGenerator::slugify($normalized);
        if ($normalized === '') {
            $normalized = 'survey-' . substr(bin2hex(random_bytes(4)), 0, 8);
        }
        return $normalized;
    }

    private function sanitizeText(string $value): string
    {
        return trim(mb_substr(strip_tags($value), 0, 5000));
    }

    private function sanitizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = $this->sanitizeText((string) $value);
        return $text !== '' ? $text : null;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private function normalizeDateValue(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : trim($value);
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeAnonymousKey(?string $anonymousKey, string $ipAddress, string $userAgent): string
    {
        $normalized = trim((string) $anonymousKey);
        if ($normalized !== '') {
            // Keep already-normalized SHA-1 keys stable across requests.
            if (preg_match('/^[a-f0-9]{40}$/i', $normalized) === 1) {
                return strtolower($normalized);
            }
            return sha1($normalized);
        }
        return sha1(($ipAddress ?: 'unknown') . '|' . ($userAgent ?: 'unknown'));
    }

    private function isBlankAnswer(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_array($value)) {
            return $value === [];
        }
        return trim((string) $value) === '';
    }

    private function isValidDateInput(string $value): bool
    {
        return strtotime($value) !== false;
    }

    private function findSectionIndex(array $sections, string $sectionId): ?int
    {
        foreach ($sections as $index => $section) {
            if ((string) ($section['id'] ?? '') === $sectionId) {
                return $index;
            }
        }
        return null;
    }

    private function expandResponseRow(array $response): array
    {
        return $response;
    }

    private function camelize(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function buildSurveyNotificationRecipients(string $surveyId, array $survey): array
    {
        $db = db_connect();
        $notifyActiveOnly = $this->toBool($survey['notifyActiveUsers'] ?? $survey['notify_active_users'] ?? true);

        $builder = $db->table('portal_users')->select('id, email, first_name, last_name, display_name, active');
        if ($notifyActiveOnly) {
            $builder->where('active', 1);
        }

        $rows = $builder->get()->getResultArray();
        return array_map(static function (array $row): array {
            $displayName = trim((string) ($row['display_name'] ?? ''));
            if ($displayName === '') {
                $displayName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            }
            if ($displayName === '') {
                $displayName = (string) ($row['email'] ?? 'Portal user');
            }

            return [
                'user_id' => (string) ($row['id'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'display_name' => $displayName,
            ];
        }, $rows);
    }

    private function hasTables(array $tables): bool
    {
        try {
            $db = db_connect();
            foreach ($tables as $table) {
                if (!$db->tableExists($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function logSurveyValidationFailure(array $survey, array $section, array $question, mixed $rawAnswer, string $reason): void
    {
        try {
            log_message('error', 'Survey validation failed: ' . json_encode([
                'surveyId' => $survey['id'] ?? null,
                'surveySlug' => $survey['slug'] ?? null,
                'sectionId' => $section['id'] ?? null,
                'questionId' => $question['id'] ?? null,
                'questionType' => $question['type'] ?? null,
                'reason' => $reason,
                'rawAnswerType' => gettype($rawAnswer),
                'rawAnswer' => $rawAnswer,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable) {
            // no-op
        }
    }

    private function unwrapSurveyAnswerValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value) && (array_key_exists('value_text', $value) || array_key_exists('value_json', $value))) {
            return $value['value_text'] ?? $value['value_json'] ?? null;
        }

        return $value;
    }
}
