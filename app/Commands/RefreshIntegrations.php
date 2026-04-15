<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Refreshes all active external integrations (weather, dollar, etc.).
 *
 * Cron: */30 * * * * cd /path/to/project && php spark integrations:refresh
 */
class RefreshIntegrations extends BaseCommand
{
    protected $group       = 'Integrations';
    protected $name        = 'integrations:refresh';
    protected $description = 'Refreshes data from all active integrations.';

    public function run(array $params)
    {
        $service = service('integrationService');
        $results = $service->refreshAll();

        foreach ($results as $provider => $result) {
            $status = $result['success'] ? 'OK' : 'FAIL';
            $color  = $result['success'] ? 'green' : 'red';
            CLI::write("[{$status}] {$provider}", $color);
        }

        $ok = count(array_filter($results, fn($r) => $r['success']));
        CLI::write("Refreshed {$ok}/" . count($results) . " integrations.", 'white');
    }
}
