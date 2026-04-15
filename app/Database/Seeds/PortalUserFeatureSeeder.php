<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PortalUserFeatureSeeder extends Seeder
{
    public function run()
    {
        if (!$this->db->tableExists('portal_users')) {
            echo "Portal user tables not found. Run migrations first.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $passwordHash = password_hash('Portal123!', PASSWORD_BCRYPT, ['cost' => 10]);

        $portalUsers = [
            [
                'id' => '50000000-0000-0000-0000-000000000001',
                'email' => 'lector.a@netxus.com',
                'first_name' => 'Lectora',
                'last_name' => 'A',
                'display_name' => 'Lectora A',
            ],
            [
                'id' => '50000000-0000-0000-0000-000000000002',
                'email' => 'lector.b@netxus.com',
                'first_name' => 'Lector',
                'last_name' => 'B',
                'display_name' => 'Lector B',
            ],
            [
                'id' => '50000000-0000-0000-0000-000000000003',
                'email' => 'lector.c@netxus.com',
                'first_name' => 'Lectora',
                'last_name' => 'C',
                'display_name' => 'Lectora C',
            ],
            [
                'id' => '50000000-0000-0000-0000-000000000004',
                'email' => 'lector.d@netxus.com',
                'first_name' => 'Lector',
                'last_name' => 'D',
                'display_name' => 'Lector D',
            ],
        ];

        foreach ($portalUsers as $user) {
            $exists = $this->db->table('portal_users')->where('id', $user['id'])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('portal_users')->insert([
                'id' => $user['id'],
                'email' => $user['email'],
                'password_hash' => $passwordHash,
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'display_name' => $user['display_name'],
                'active' => 1,
                'last_login_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->db->table('portal_user_preferences')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $user['id'],
                'timezone' => 'America/Argentina/Buenos_Aires',
                'language' => 'es',
                'digest_frequency' => 'daily',
                'personalization_opt_in' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->seedFavorites();
        $this->seedSavedPosts();
        $this->seedInteractions();
        $this->seedRecommendationScores();

        echo "Portal user feature test data seeded (users, preferences, favorites, saved posts, interactions, scores).\n";
    }

    private function seedFavorites(): void
    {
        $now = date('Y-m-d H:i:s');

        $favoriteCategories = [
            ['50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000004'],
            ['50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000002'],
            ['50000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000003'],
            ['50000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000005'],
            ['50000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000001'],
            ['50000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000008'],
        ];

        foreach ($favoriteCategories as [$userId, $categoryId]) {
            $exists = $this->db->table('portal_user_favorite_categories')
                ->where('portal_user_id', $userId)
                ->where('category_id', $categoryId)
                ->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('portal_user_favorite_categories')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'category_id' => $categoryId,
                'weight' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $favoriteTags = [
            ['50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000004'],
            ['50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000007'],
            ['50000000-0000-0000-0000-000000000002', 't0000000-0000-0000-0000-000000000003'],
            ['50000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000001'],
        ];

        foreach ($favoriteTags as [$userId, $tagId]) {
            $exists = $this->db->table('portal_user_favorite_tags')
                ->where('portal_user_id', $userId)
                ->where('tag_id', $tagId)
                ->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('portal_user_favorite_tags')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'tag_id' => $tagId,
                'weight' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $favoriteAuthors = [
            ['50000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000003'],
            ['50000000-0000-0000-0000-000000000002', 'a0000000-0000-0000-0000-000000000002'],
            ['50000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000001'],
        ];

        foreach ($favoriteAuthors as [$userId, $authorId]) {
            $exists = $this->db->table('portal_user_favorite_authors')
                ->where('portal_user_id', $userId)
                ->where('author_id', $authorId)
                ->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('portal_user_favorite_authors')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'author_id' => $authorId,
                'weight' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedSavedPosts(): void
    {
        $now = date('Y-m-d H:i:s');

        $savedPosts = [
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', null],
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', null],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000004', null],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', null],
            ['50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000001', 'Revisar evolucion de medidas'],
        ];

        foreach ($savedPosts as [$userId, $newsId, $note]) {
            $exists = $this->db->table('portal_user_saved_posts')
                ->where('portal_user_id', $userId)
                ->where('news_id', $newsId)
                ->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('portal_user_saved_posts')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'news_id' => $newsId,
                'note' => $note,
                'saved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedInteractions(): void
    {
        $now = date('Y-m-d H:i:s');

        $interactions = [
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'view_post', 180],
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'save_post', 0],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', 'c0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000002', 'view_post', 240],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000004', 'c0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000002', 'save_post', 0],
            ['50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000002', 'a0000000-0000-0000-0000-000000000001', 'view_post', 360],
            ['50000000-0000-0000-0000-000000000003', null, 'c0000000-0000-0000-0000-000000000001', null, null, 'click_category', 0],
        ];

        foreach ($interactions as [$userId, $newsId, $categoryId, $tagId, $authorId, $action, $timeSpent]) {
            $this->db->table('portal_user_interactions')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'news_id' => $newsId,
                'category_id' => $categoryId,
                'tag_id' => $tagId,
                'author_id' => $authorId,
                'action' => $action,
                'context' => 'seed',
                'time_spent_seconds' => $timeSpent,
                'score_delta' => 0,
                'metadata' => json_encode(['source' => 'test_seed']),
                'created_at' => $now,
            ]);
        }
    }

    private function seedRecommendationScores(): void
    {
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

        $scores = [
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', 91.3, 1],
            ['50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', 86.5, 2],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', 94.1, 1],
            ['50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000004', 89.2, 2],
            ['50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000001', 88.8, 1],
            ['50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000002', 80.4, 2],
        ];

        foreach ($scores as [$userId, $newsId, $score, $rank]) {
            $exists = $this->db->table('portal_user_recommendation_scores')
                ->where('portal_user_id', $userId)
                ->where('news_id', $newsId)
                ->countAllResults() > 0;

            if ($exists) {
                continue;
            }

            $this->db->table('portal_user_recommendation_scores')->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $userId,
                'news_id' => $newsId,
                'score' => $score,
                'rank_position' => $rank,
                'components' => json_encode([
                    'seeded' => true,
                    'favoriteCategory' => $score * 0.35,
                    'favoriteTags' => $score * 0.2,
                    'trendingBonus' => $score * 0.12,
                ]),
                'calculated_at' => $now,
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
