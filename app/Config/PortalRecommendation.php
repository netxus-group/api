<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class PortalRecommendation extends BaseConfig
{
    public array $weights = [
        'favoriteCategory'      => 20,
        'favoriteTag'           => 10,
        'favoriteAuthor'        => 8,
        'interactionCategory'   => 6,
        'interactionTag'        => 4,
        'interactionAuthor'     => 5,
        'relatedSavedPost'      => 12,
        'recentHoursWindow'     => 24,
        'recentMaxBonus'        => 15,
        'trendingMaxBonus'      => 10,
        'featuredBonus'         => 10,
        'breakingBonus'         => 12,
        'editorialPriorityBonus'=> 8,
        'seenPenalty'           => 10,
        'themeOverloadPenalty'  => 5,
        'globalFallbackBonus'   => 5,
    ];

    public array $interactionActionWeight = [
        'view_post'      => 1.5,
        'save_post'      => 4.0,
        'unsave_post'    => -1.5,
        'click_category' => 2.0,
        'click_tag'      => 1.8,
        'read_time'      => 0.05,
    ];

    public int $candidateLimit = 180;

    public int $cacheTtlSeconds = 900;

    public int $minEditorialGuarantee = 3;

    public int $maxPerPrimaryCategory = 3;
}
