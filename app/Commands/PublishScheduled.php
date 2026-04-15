<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\NewsModel;

/**
 * Publishes scheduled articles whose scheduled_at has passed.
 *
 * Cron: * * * * * cd /path/to/project && php spark news:publish-scheduled
 * (run every minute or every 5 minutes)
 */
class PublishScheduled extends BaseCommand
{
    protected $group       = 'News';
    protected $name        = 'news:publish-scheduled';
    protected $description = 'Publishes articles scheduled for the past.';

    public function run(array $params)
    {
        $model = new NewsModel();
        $count = $model->publishScheduled();

        if ($count > 0) {
            CLI::write("✓ Published {$count} scheduled article(s).", 'green');
        } else {
            CLI::write('No articles to publish.', 'yellow');
        }
    }
}
