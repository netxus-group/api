<?php

namespace App\Commands;

use App\Models\PortalUserSessionModel;
use App\Models\RefreshTokenModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cleans up expired and revoked refresh tokens.
 *
 * Cron: 0 3 * * * cd /path/to/project && php spark auth:clean-tokens
 * (run once a day at 3 AM)
 */
class CleanExpiredTokens extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:clean-tokens';
    protected $description = 'Removes expired and revoked refresh tokens.';

    public function run(array $params)
    {
        $editorialModel = new RefreshTokenModel();
        $editorialCount = $editorialModel->cleanExpired();

        $portalModel = new PortalUserSessionModel();
        $portalCount = $portalModel->cleanExpired();

        CLI::write("Cleaned {$editorialCount} editorial tokens and {$portalCount} portal tokens.", 'green');
    }
}
