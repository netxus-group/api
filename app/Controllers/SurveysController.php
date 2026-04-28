<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;

class SurveysController extends BaseApiController
{
    public function index()
    {
        return ApiResponse::ok(service('surveyService')->listSurveys());
    }

    public function show(string $id)
    {
        $survey = service('surveyService')->getSurveyById($id, true);
        if (!$survey) {
            return ApiResponse::notFound('Survey not found');
        }

        return ApiResponse::ok($survey);
    }

    public function create()
    {
        $data = $this->getJsonInput();
        if (trim((string) ($data['title'] ?? '')) === '') {
            return ApiResponse::validationError(['title' => 'Title is required']);
        }

        try {
            $survey = service('surveyService')->create($data, $this->userId());
            return ApiResponse::created($survey, 'Survey created');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function update(string $id)
    {
        $data = $this->getJsonInput();

        try {
            $survey = service('surveyService')->update($id, $data, $this->userId());
            return ApiResponse::ok($survey, 'Survey updated');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function delete(string $id)
    {
        try {
            service('surveyService')->delete($id);
            return ApiResponse::noContent('Survey deleted');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function changeStatus(string $id)
    {
        $data = $this->getJsonInput();
        $status = (string) ($data['status'] ?? '');
        if ($status === '') {
            return ApiResponse::validationError(['status' => 'Status is required']);
        }

        try {
            $survey = service('surveyService')->changeStatus($id, $status, $this->userId());
            return ApiResponse::ok($survey, 'Survey status updated');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function stats()
    {
        return ApiResponse::ok(service('surveyService')->statsSummary());
    }

    public function surveyStats(string $id)
    {
        try {
            return ApiResponse::ok(service('surveyService')->surveyStats($id));
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function responses(string $id)
    {
        try {
            return ApiResponse::ok(service('surveyService')->listResponses($id));
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function exportExcel(string $id)
    {
        try {
            $content = service('surveyService')->exportExcel($id);
            $filename = 'survey-' . $id . '-' . date('Y-m-d') . '.xlsx';
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->setBody($content);
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function incompleteUsers()
    {
        $days = max(1, (int) ($this->request->getGet('days') ?? 3));
        return ApiResponse::ok(service('surveyService')->staleInProgress($days));
    }

    public function notify(string $id)
    {
        try {
            return ApiResponse::ok(service('surveyService')->notifySurvey($id, true), 'Survey notifications sent');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function notificationStats(string $id)
    {
        try {
            return ApiResponse::ok(service('surveyService')->notificationStats($id));
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function runReminders()
    {
        $days = max(1, (int) ($this->request->getGet('days') ?? 3));
        $maxReminders = max(1, (int) ($this->request->getGet('maxReminders') ?? 1));
        $surveyId = trim((string) ($this->request->getGet('surveyId') ?? ''));

        try {
            return ApiResponse::ok(service('surveyService')->runSurveyReminders($days, $maxReminders, $surveyId !== '' ? $surveyId : null));
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    private function mapException(\RuntimeException $exception)
    {
        return match ($exception->getCode()) {
            401 => ApiResponse::unauthorized($exception->getMessage()),
            404 => ApiResponse::notFound($exception->getMessage()),
            409 => ApiResponse::conflict($exception->getMessage()),
            422 => ApiResponse::validationError(['survey' => $exception->getMessage()]),
            default => ApiResponse::badRequest($exception->getMessage()),
        };
    }
}
