<?php

namespace App\Commands;

use App\Models\PortalUserModel;
use App\Services\PortalRecommendationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RefreshPortalRecommendations extends BaseCommand
{
    protected $group = 'Portal';
    protected $name = 'portal:refresh-recommendations';
    protected $description = 'Recalculate personalized recommendation scores for active portal users.';

    public function run(array $params)
    {
        $limit = isset($params[0]) ? max(5, (int) $params[0]) : 20;

        $userModel = new PortalUserModel();
        $service = new PortalRecommendationService();

        $users = $userModel->select('id')->where('active', 1)->findAll();

        if ($users === []) {
            CLI::write('No active portal users found.', 'yellow');
            return;
        }

        foreach ($users as $user) {
            $service->getRecommendations((string) $user['id'], $limit, true);
            CLI::write('Refreshed recommendations for portal user ' . $user['id'], 'green');
        }

        CLI::write('Portal recommendation refresh completed for ' . count($users) . ' users.', 'green');
    }
}
