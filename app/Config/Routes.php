<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override(static function () {
    $response = service('response');
    $response->setStatusCode(404);
    $response->setJSON([
        'status'  => 'error',
        'message' => 'Endpoint not found',
    ]);
    return $response;
});

// ──────────────────────────────────────────────
// HEALTH & VERSION (public)
// ──────────────────────────────────────────────
$routes->get('health', static function () {
    return service('response')->setJSON(['status' => 'ok', 'timestamp' => date('c')]);
});
$routes->get('version', static function () {
    return service('response')->setJSON(['version' => '1.0.0']);
});

$routes->group('api', static function (RouteCollection $routes) {
    $routes->post('portal-auth/register', 'PortalAuthController::register');
    $routes->post('portal-auth/login', 'PortalAuthController::login');
    $routes->post('portal-auth/refresh', 'PortalAuthController::refresh');
    $routes->post('portal-auth/logout', 'PortalAuthController::logout');
    $routes->post('portal-auth/forgot-password', 'PortalAuthController::forgotPassword');
    $routes->post('portal-auth/reset-password', 'PortalAuthController::resetPassword');
    $routes->get('portal-auth/me', 'PortalAuthController::me', ['filter' => 'portalAuth']);

    $routes->group('portal-user', ['filter' => 'portalAuth'], static function (RouteCollection $routes) {
        $routes->get('profile', 'PortalUserController::profile');
        $routes->put('profile', 'PortalUserController::profile');
        $routes->put('password', 'PortalUserController::password');

        $routes->get('preferences', 'PortalUserController::preferences');
        $routes->put('preferences', 'PortalUserController::preferences');

        $routes->get('saved-posts', 'PortalUserController::savedPosts');
        $routes->post('saved-posts/(:segment)', 'PortalUserController::savePost/$1');
        $routes->delete('saved-posts/(:segment)', 'PortalUserController::unsavePost/$1');

        $routes->post('interactions', 'PortalUserController::interactions');
        $routes->get('recommendations', 'PortalUserController::recommendations');
        $routes->get('home-feed', 'PortalUserController::homeFeed');
    });
});
// ──────────────────────────────────────────────
// API v1
// ──────────────────────────────────────────────
$routes->group('api/v1', static function (RouteCollection $routes) {

    // ── AUTH (public) ──
    $routes->post('auth/login', 'AuthController::login');
    $routes->options('auth/login', static fn() => service('response')->setStatusCode(204));

    // ── AUTH (authenticated) ──
    $routes->group('auth', ['filter' => 'auth'], static function (RouteCollection $routes) {
        $routes->post('refresh', 'AuthController::refresh');
        $routes->post('logout', 'AuthController::logout');
        $routes->get('me', 'AuthController::me');
    });

    // ── PUBLIC API (no auth) ──
    $routes->group('public', static function (RouteCollection $routes) {
        $routes->get('home', 'PublicApiController::home');
        $routes->get('news', 'PublicApiController::newsList');
        $routes->get('news/(:segment)', 'PublicApiController::newsDetail/$1');
        $routes->get('categories', 'PublicApiController::categories');
        $routes->get('tags', 'PublicApiController::tags');
        $routes->get('authors', 'PublicApiController::authors');
        $routes->get('search', 'PublicApiController::search');
        $routes->post('metrics/events', 'PublicApiController::trackEvent');
        $routes->post('newsletter/subscribe', 'NewsletterController::subscribe');
        $routes->post('newsletter/unsubscribe', 'NewsletterController::unsubscribePublic');
        $routes->get('newsletter/unsubscribe', 'NewsletterController::unsubscribeLink');
        $routes->get('integrations/(:segment)', 'IntegrationsController::publicData/$1');
    });

    // ── AUTHENTICATED ROUTES ──
    $routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {

        // ── USERS (super_admin) ──
        $routes->group('users', ['filter' => 'role:super_admin'], static function (RouteCollection $routes) {
            $routes->get('/', 'UsersController::index');
            $routes->post('/', 'UsersController::create');
            $routes->get('special-permissions', 'UsersController::specialPermissions');
            $routes->get('(:segment)/profile', 'UsersController::profile/$1');
            $routes->put('(:segment)', 'UsersController::update/$1');
            $routes->put('(:segment)/access', 'UsersController::updateAccess/$1');
            $routes->delete('(:segment)', 'UsersController::delete/$1');
        });

        // ── ROLES ──
        $routes->get('roles', 'RolesController::index');
        $routes->get('roles/special-permissions', 'RolesController::specialPermissions');
        $routes->put('roles/(:segment)', 'RolesController::update/$1', ['filter' => 'role:super_admin']);

        // ── AUTHORS ──
        $routes->group('authors', static function (RouteCollection $routes) {
            $routes->get('/', 'AuthorsController::index');
            $routes->post('/', 'AuthorsController::create', ['filter' => 'role:super_admin,editor']);
            $routes->put('(:segment)', 'AuthorsController::update/$1', ['filter' => 'role:super_admin,editor']);
            $routes->delete('(:segment)', 'AuthorsController::delete/$1', ['filter' => 'role:super_admin,editor']);
        });

        // ── CATEGORIES ──
        $routes->group('categories', static function (RouteCollection $routes) {
            $routes->get('/', 'CategoriesController::index');
            $routes->post('/', 'CategoriesController::create', ['filter' => 'role:super_admin,editor']);
            $routes->put('(:segment)', 'CategoriesController::update/$1', ['filter' => 'role:super_admin,editor']);
            $routes->delete('(:segment)', 'CategoriesController::delete/$1', ['filter' => 'role:super_admin,editor']);
        });

        // ── TAGS ──
        $routes->group('tags', static function (RouteCollection $routes) {
            $routes->get('/', 'TagsController::index');
            $routes->post('/', 'TagsController::create', ['filter' => 'role:super_admin,editor']);
            $routes->put('(:segment)', 'TagsController::update/$1', ['filter' => 'role:super_admin,editor']);
            $routes->delete('(:segment)', 'TagsController::delete/$1', ['filter' => 'role:super_admin,editor']);
        });

        // ── NEWS ──
        $routes->group('news', static function (RouteCollection $routes) {
            $routes->get('/', 'NewsController::index');
            $routes->get('(:segment)', 'NewsController::show/$1');
            $routes->post('/', 'NewsController::create');
            $routes->put('(:segment)', 'NewsController::update/$1');
            $routes->post('(:segment)/schedule', 'NewsController::schedule/$1');
            $routes->delete('(:segment)', 'NewsController::delete/$1');
        });

        // ── MEDIA / IMAGES ──
        $routes->group('images', static function (RouteCollection $routes) {
            $routes->get('/', 'MediaController::index');
            $routes->get('(:segment)', 'MediaController::show/$1');
            $routes->post('/', 'MediaController::upload');
            $routes->put('(:segment)', 'MediaController::update/$1');
            $routes->delete('(:segment)', 'MediaController::delete/$1');
        });

        // ── ADS ──
        $routes->group('ads', ['filter' => 'role:super_admin,editor'], static function (RouteCollection $routes) {
            $routes->get('slots', 'AdsController::index');
            $routes->post('slots', 'AdsController::create');
            $routes->put('slots/(:segment)', 'AdsController::update/$1');
            $routes->delete('slots/(:segment)', 'AdsController::delete/$1');
            $routes->get('campaigns', 'AdsController::campaigns');
        });

        // ── POLLS ──
        $routes->group('polls', static function (RouteCollection $routes) {
            $routes->get('/', 'PollsController::index');
            $routes->get('(:segment)', 'PollsController::show/$1');
            $routes->post('/', 'PollsController::create', ['filter' => 'role:super_admin,editor']);
            $routes->put('(:segment)', 'PollsController::update/$1', ['filter' => 'role:super_admin,editor']);
            $routes->delete('(:segment)', 'PollsController::delete/$1', ['filter' => 'role:super_admin,editor']);
            $routes->post('(:segment)/respond', 'PollsController::respond/$1');
            $routes->get('(:segment)/stats', 'PollsController::stats/$1');
        });

        // ── NEWSLETTER (admin) ──
        $routes->group('newsletter', ['filter' => 'role:super_admin,editor'], static function (RouteCollection $routes) {
            $routes->get('subscribers', 'NewsletterController::subscribers');
            $routes->post('subscribers/(:segment)/unsubscribe', 'NewsletterController::adminUnsubscribe/$1');
        });

        // ── INTEGRATIONS (config) ──
        $routes->group('integrations', ['filter' => 'role:super_admin,editor'], static function (RouteCollection $routes) {
            $routes->post('config', 'IntegrationsController::listConfigs');
            $routes->put('config/(:segment)', 'IntegrationsController::updateConfig/$1');
            $routes->get('status', 'IntegrationsController::status');
        });

        // ── HOME LAYOUT ──
        $routes->group('home-layout', ['filter' => 'role:super_admin,editor'], static function (RouteCollection $routes) {
            $routes->get('/', 'HomeLayoutController::index');
            $routes->put('/', 'HomeLayoutController::update');
        });

        // ── METRICS (super_admin) ──
        $routes->group('metrics', ['filter' => 'role:super_admin'], static function (RouteCollection $routes) {
            $routes->get('/', 'MetricsController::index');
            $routes->get('content', 'MetricsController::content');
            $routes->get('newsletter', 'MetricsController::newsletter');
            $routes->get('engagement', 'MetricsController::engagement');
            $routes->get('daily-engagement', 'MetricsController::dailyEngagement');
        });

        // ── SETTINGS ──
        $routes->get('settings/me', 'SettingsController::mySettings');
        $routes->put('settings/me', 'SettingsController::updateMySettings');
        $routes->group('settings', ['filter' => 'role:super_admin'], static function (RouteCollection $routes) {
            $routes->get('general', 'SettingsController::general');
            $routes->put('general', 'SettingsController::updateGeneral');
        });

        // ── REPORTS / EXPORTS ──
        $routes->group('reports', ['filter' => 'role:super_admin,editor'], static function (RouteCollection $routes) {
            $routes->get('news/csv', 'ReportsController::newsCsv');
            $routes->get('news/excel', 'ReportsController::newsExcel');
            $routes->get('news/txt', 'ReportsController::newsTxt');
            $routes->get('news/pdf', 'ReportsController::newsPdf');
            $routes->get('subscribers/csv', 'ReportsController::subscribersCsv');
            $routes->get('metrics/csv', 'ReportsController::metricsCsv');
        });
    });
});

