<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\JwtManager;
use App\Libraries\PortalJwtManager;
use App\Services\AuthService;
use App\Services\NewsService;
use App\Services\MediaService;
use App\Services\IntegrationService;
use App\Services\MetricsService;
use App\Services\PollService;
use App\Services\CommunicationService;
use App\Services\SurveyService;
use App\Services\ExportService;
use App\Services\PortalAuthService;
use App\Services\PortalRecommendationService;
use App\Services\PortalUserService;

class Services extends BaseService
{
    public static function jwtManager(bool $getShared = true): JwtManager
    {
        if ($getShared) {
            return static::getSharedInstance('jwtManager');
        }
        return new JwtManager(config('Auth'));
    }

    public static function authService(bool $getShared = true): AuthService
    {
        if ($getShared) {
            return static::getSharedInstance('authService');
        }
        return new AuthService();
    }

    public static function portalJwtManager(bool $getShared = true): PortalJwtManager
    {
        if ($getShared) {
            return static::getSharedInstance('portalJwtManager');
        }

        return new PortalJwtManager(config('PortalAuth'));
    }

    public static function portalAuthService(bool $getShared = true): PortalAuthService
    {
        if ($getShared) {
            return static::getSharedInstance('portalAuthService');
        }

        return new PortalAuthService();
    }

    public static function portalUserService(bool $getShared = true): PortalUserService
    {
        if ($getShared) {
            return static::getSharedInstance('portalUserService');
        }

        return new PortalUserService();
    }

    public static function portalRecommendationService(bool $getShared = true): PortalRecommendationService
    {
        if ($getShared) {
            return static::getSharedInstance('portalRecommendationService');
        }

        return new PortalRecommendationService();
    }

    public static function newsService(bool $getShared = true): NewsService
    {
        if ($getShared) {
            return static::getSharedInstance('newsService');
        }
        return new NewsService();
    }

    public static function mediaService(bool $getShared = true): MediaService
    {
        if ($getShared) {
            return static::getSharedInstance('mediaService');
        }
        return new MediaService();
    }

    public static function integrationService(bool $getShared = true): IntegrationService
    {
        if ($getShared) {
            return static::getSharedInstance('integrationService');
        }
        return new IntegrationService();
    }

    public static function metricsService(bool $getShared = true): MetricsService
    {
        if ($getShared) {
            return static::getSharedInstance('metricsService');
        }
        return new MetricsService();
    }

    public static function pollService(bool $getShared = true): PollService
    {
        if ($getShared) {
            return static::getSharedInstance('pollService');
        }
        return new PollService();
    }

    public static function surveyService(bool $getShared = true): SurveyService
    {
        if ($getShared) {
            return static::getSharedInstance('surveyService');
        }

        return new SurveyService();
    }

    public static function exportService(bool $getShared = true): ExportService
    {
        if ($getShared) {
            return static::getSharedInstance('exportService');
        }
        return new ExportService();
    }

    public static function communicationService(bool $getShared = true): CommunicationService
    {
        if ($getShared) {
            return static::getSharedInstance('communicationService');
        }

        return new CommunicationService();
    }
}
