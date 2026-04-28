<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds comprehensive test data.
 * Usage: php spark db:seed TestDataSeeder
 *
 * Creates: 2 users per role, 5 authors, 8 categories, 10 tags,
 * 12 articles (6 per writer), ads, newsletter subs, a poll, engagement events.
 */
class TestDataSeeder extends Seeder
{
    public function run()
    {
        // First run initial seed if role_profiles is empty
        if ($this->db->table('role_profiles')->countAll() === 0) {
            $this->call('InitialSeeder');
        }

        $now = date('Y-m-d H:i:s');
        $pw  = password_hash('Test1234!', PASSWORD_BCRYPT, ['cost' => 10]);

        // Make seeder re-runnable by clearing deterministic fixture rows first.
        $testUserIds = [
            '10000000-0000-0000-0000-000000000002',
            '10000000-0000-0000-0000-000000000003',
            '10000000-0000-0000-0000-000000000004',
            '10000000-0000-0000-0000-000000000005',
            '10000000-0000-0000-0000-000000000006',
        ];

        $authorIds = [
            'a0000000-0000-0000-0000-000000000001',
            'a0000000-0000-0000-0000-000000000002',
            'a0000000-0000-0000-0000-000000000003',
            'a0000000-0000-0000-0000-000000000004',
            'a0000000-0000-0000-0000-000000000005',
        ];

        $categoryIds = [
            'c0000000-0000-0000-0000-000000000001',
            'c0000000-0000-0000-0000-000000000002',
            'c0000000-0000-0000-0000-000000000003',
            'c0000000-0000-0000-0000-000000000004',
            'c0000000-0000-0000-0000-000000000005',
            'c0000000-0000-0000-0000-000000000006',
            'c0000000-0000-0000-0000-000000000007',
            'c0000000-0000-0000-0000-000000000008',
        ];

        $tagIds = [
            't0000000-0000-0000-0000-000000000001',
            't0000000-0000-0000-0000-000000000002',
            't0000000-0000-0000-0000-000000000003',
            't0000000-0000-0000-0000-000000000004',
            't0000000-0000-0000-0000-000000000005',
            't0000000-0000-0000-0000-000000000006',
            't0000000-0000-0000-0000-000000000007',
            't0000000-0000-0000-0000-000000000008',
            't0000000-0000-0000-0000-000000000009',
            't0000000-0000-0000-0000-000000000010',
        ];

        $newsIds = [
            'n0000000-0000-0000-0000-000000000001',
            'n0000000-0000-0000-0000-000000000002',
            'n0000000-0000-0000-0000-000000000003',
            'n0000000-0000-0000-0000-000000000004',
            'n0000000-0000-0000-0000-000000000005',
            'n0000000-0000-0000-0000-000000000006',
            'n0000000-0000-0000-0000-000000000007',
            'n0000000-0000-0000-0000-000000000008',
            'n0000000-0000-0000-0000-000000000009',
            'n0000000-0000-0000-0000-000000000010',
            'n0000000-0000-0000-0000-000000000011',
            'n0000000-0000-0000-0000-000000000012',
        ];

        $pollId = 'p0000000-0000-0000-0000-000000000001';
        $q1Id   = 'pq000000-0000-0000-0000-000000000001';
        $q2Id   = 'pq000000-0000-0000-0000-000000000002';

        $survey1Id = 's0000000-0000-0000-0000-000000000001';
        $survey2Id = 's0000000-0000-0000-0000-000000000002';
        $survey3Id = 's0000000-0000-0000-0000-000000000003';

        $survey1Section1Id = 'ss000000-0000-0000-0000-000000000001';
        $survey1Section2Id = 'ss000000-0000-0000-0000-000000000002';
        $survey2Section1Id = 'ss000000-0000-0000-0000-000000000003';
        $survey3Section1Id = 'ss000000-0000-0000-0000-000000000004';

        $survey1Question1Id = 'sq000000-0000-0000-0000-000000000001';
        $survey1Question2Id = 'sq000000-0000-0000-0000-000000000002';
        $survey1Question3Id = 'sq000000-0000-0000-0000-000000000003';
        $survey1Question4Id = 'sq000000-0000-0000-0000-000000000004';
        $survey2Question1Id = 'sq000000-0000-0000-0000-000000000005';
        $survey2Question2Id = 'sq000000-0000-0000-0000-000000000006';
        $survey2Question3Id = 'sq000000-0000-0000-0000-000000000007';
        $survey3Question1Id = 'sq000000-0000-0000-0000-000000000008';

        $survey1Option1Id = 'so000000-0000-0000-0000-000000000001';
        $survey1Option2Id = 'so000000-0000-0000-0000-000000000002';
        $survey1Option3Id = 'so000000-0000-0000-0000-000000000003';
        $survey1Option4Id = 'so000000-0000-0000-0000-000000000004';
        $survey1Option5Id = 'so000000-0000-0000-0000-000000000005';
        $survey1Option6Id = 'so000000-0000-0000-0000-000000000006';
        $survey2Option1Id = 'so000000-0000-0000-0000-000000000007';
        $survey2Option2Id = 'so000000-0000-0000-0000-000000000008';
        $survey2Option3Id = 'so000000-0000-0000-0000-000000000009';

        $newsletterEmails = [];
        for ($i = 1; $i <= 10; $i++) {
            $newsletterEmails[] = "lector{$i}@gmail.com";
        }

        $this->db->table('user_roles')->whereIn('user_id', $testUserIds)->delete();
        $this->db->table('users')->whereIn('id', $testUserIds)->delete();

        $this->db->table('news_tags')->whereIn('news_id', $newsIds)->delete();
        $this->db->table('news_categories')->whereIn('news_id', $newsIds)->delete();
        $this->db->table('post_status_history')->whereIn('news_id', $newsIds)->delete();
        $this->db->table('news')->whereIn('id', $newsIds)->delete();

        $this->db->table('poll_response_details')->whereIn('question_id', [$q1Id, $q2Id])->delete();
        $this->db->table('poll_options')->whereIn('question_id', [$q1Id, $q2Id])->delete();
        $this->db->table('poll_questions')->where('poll_id', $pollId)->delete();
        $this->db->table('poll_responses')->where('poll_id', $pollId)->delete();
        $this->db->table('polls')->where('id', $pollId)->delete();

        $surveyIds = [$survey1Id, $survey2Id, $survey3Id];
        $surveyQuestionIds = [
            $survey1Question1Id,
            $survey1Question2Id,
            $survey1Question3Id,
            $survey1Question4Id,
            $survey2Question1Id,
            $survey2Question2Id,
            $survey2Question3Id,
            $survey3Question1Id,
        ];
        $surveyResponseIds = [
            'sr000000-0000-0000-0000-000000000001',
            'sr000000-0000-0000-0000-000000000002',
            'sr000000-0000-0000-0000-000000000003',
            'sr000000-0000-0000-0000-000000000004',
        ];

        $this->db->table('survey_answers')->whereIn('question_id', $surveyQuestionIds)->delete();
        $this->db->table('survey_responses')->whereIn('id', $surveyResponseIds)->delete();
        $this->db->table('survey_question_options')->whereIn('question_id', $surveyQuestionIds)->delete();
        $this->db->table('survey_questions')->whereIn('id', $surveyQuestionIds)->delete();
        $this->db->table('survey_sections')->whereIn('id', [$survey1Section1Id, $survey1Section2Id, $survey2Section1Id, $survey3Section1Id])->delete();
        $this->db->table('surveys')->whereIn('id', $surveyIds)->delete();

        $this->db->table('authors')->whereIn('id', $authorIds)->delete();
        $this->db->table('categories')->whereIn('id', $categoryIds)->delete();
        $this->db->table('tags')->whereIn('id', $tagIds)->delete();
        $this->db->table('newsletter_subscribers')->whereIn('email', $newsletterEmails)->delete();

        // ---- Extra Users: 1 more admin + 2 editors + 2 writers ----
        $this->db->table('users')->insertBatch([
            ['id' => '10000000-0000-0000-0000-000000000002', 'email' => 'admin2@netxus.com',  'password_hash' => $pw, 'first_name' => 'Carlos',  'last_name' => 'Méndez',    'display_name' => 'Carlos Méndez',    'active' => 1, 'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '10000000-0000-0000-0000-000000000003', 'email' => 'editor1@netxus.com', 'password_hash' => $pw, 'first_name' => 'Laura',   'last_name' => 'Giménez',   'display_name' => 'Laura Giménez',    'active' => 1, 'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '10000000-0000-0000-0000-000000000004', 'email' => 'editor2@netxus.com', 'password_hash' => $pw, 'first_name' => 'Martín',  'last_name' => 'López',     'display_name' => 'Martín López',     'active' => 1, 'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '10000000-0000-0000-0000-000000000005', 'email' => 'writer1@netxus.com', 'password_hash' => $pw, 'first_name' => 'Ana',     'last_name' => 'Rodríguez', 'display_name' => 'Ana Rodríguez',    'active' => 1, 'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '10000000-0000-0000-0000-000000000006', 'email' => 'writer2@netxus.com', 'password_hash' => $pw, 'first_name' => 'Diego',   'last_name' => 'Fernández', 'display_name' => 'Diego Fernández',  'active' => 1, 'email_verified' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('user_roles')->insertBatch([
            ['id' => $this->uuid(), 'user_id' => '10000000-0000-0000-0000-000000000002', 'role_profile_id' => '00000000-0000-0000-0000-000000000001', 'created_at' => $now],
            ['id' => $this->uuid(), 'user_id' => '10000000-0000-0000-0000-000000000003', 'role_profile_id' => '00000000-0000-0000-0000-000000000002', 'created_at' => $now],
            ['id' => $this->uuid(), 'user_id' => '10000000-0000-0000-0000-000000000004', 'role_profile_id' => '00000000-0000-0000-0000-000000000002', 'created_at' => $now],
            ['id' => $this->uuid(), 'user_id' => '10000000-0000-0000-0000-000000000005', 'role_profile_id' => '00000000-0000-0000-0000-000000000003', 'created_at' => $now],
            ['id' => $this->uuid(), 'user_id' => '10000000-0000-0000-0000-000000000006', 'role_profile_id' => '00000000-0000-0000-0000-000000000003', 'created_at' => $now],
        ]);

        // ---- Authors ----
        $authors = [
            ['id' => 'a0000000-0000-0000-0000-000000000001', 'name' => 'María Belén Torres', 'slug' => 'maria-belen-torres', 'bio' => 'Periodista especializada en política y economía.', 'email' => 'maria@netxus.com'],
            ['id' => 'a0000000-0000-0000-0000-000000000002', 'name' => 'Joaquín Pérez',      'slug' => 'joaquin-perez',      'bio' => 'Corresponsal de deportes y actualidad.',         'email' => 'joaquin@netxus.com'],
            ['id' => 'a0000000-0000-0000-0000-000000000003', 'name' => 'Valentina Ruiz',     'slug' => 'valentina-ruiz',     'bio' => 'Especialista en tecnología y cultura digital.',  'email' => 'vale@netxus.com'],
            ['id' => 'a0000000-0000-0000-0000-000000000004', 'name' => 'Roberto Sánchez',    'slug' => 'roberto-sanchez',    'bio' => 'Editor de opinión y análisis internacional.',    'email' => 'roberto@netxus.com'],
            ['id' => 'a0000000-0000-0000-0000-000000000005', 'name' => 'Camila Herrera',     'slug' => 'camila-herrera',     'bio' => 'Periodista de investigación y sociedad.',        'email' => 'camila@netxus.com'],
        ];
        foreach ($authors as $a) {
            $a['active']     = 1;
            $a['created_at'] = $now;
            $a['updated_at'] = $now;
            $this->db->table('authors')->insert($a);
        }

        // ---- Categories ----
        $categories = [
            ['c0000000-0000-0000-0000-000000000001', 'Política',      'politica',      '#E53E3E', 1],
            ['c0000000-0000-0000-0000-000000000002', 'Economía',      'economia',      '#DD6B20', 2],
            ['c0000000-0000-0000-0000-000000000003', 'Deportes',      'deportes',      '#38A169', 3],
            ['c0000000-0000-0000-0000-000000000004', 'Tecnología',    'tecnologia',    '#3182CE', 4],
            ['c0000000-0000-0000-0000-000000000005', 'Cultura',       'cultura',       '#805AD5', 5],
            ['c0000000-0000-0000-0000-000000000006', 'Sociedad',      'sociedad',      '#D69E2E', 6],
            ['c0000000-0000-0000-0000-000000000007', 'Internacional', 'internacional', '#2D3748', 7],
            ['c0000000-0000-0000-0000-000000000008', 'Opinión',       'opinion',       '#718096', 8],
        ];
        foreach ($categories as [$id, $name, $slug, $color, $order]) {
            $this->db->table('categories')->insert([
                'id' => $id, 'name' => $name, 'slug' => $slug,
                'color' => $color, 'sort_order' => $order, 'active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- Tags ----
        $tags = [
            ['t0000000-0000-0000-0000-000000000001', 'Elecciones',              'elecciones'],
            ['t0000000-0000-0000-0000-000000000002', 'Inflación',               'inflacion'],
            ['t0000000-0000-0000-0000-000000000003', 'Fútbol',                  'futbol'],
            ['t0000000-0000-0000-0000-000000000004', 'Inteligencia Artificial', 'inteligencia-artificial'],
            ['t0000000-0000-0000-0000-000000000005', 'Medio Ambiente',          'medio-ambiente'],
            ['t0000000-0000-0000-0000-000000000006', 'Reforma Laboral',         'reforma-laboral'],
            ['t0000000-0000-0000-0000-000000000007', 'Criptomonedas',           'criptomonedas'],
            ['t0000000-0000-0000-0000-000000000008', 'Salud',                   'salud'],
            ['t0000000-0000-0000-0000-000000000009', 'Educación',               'educacion'],
            ['t0000000-0000-0000-0000-000000000010', 'Urgente',                 'urgente'],
        ];
        foreach ($tags as [$id, $name, $slug]) {
            $this->db->table('tags')->insert([
                'id' => $id, 'name' => $name, 'slug' => $slug,
                'active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- News articles (12 total) ----
        $articles = [
            // Writer 1 (Ana) — 6 articles
            ['n0000000-0000-0000-0000-000000000001', 'El gobierno anuncia nuevas medidas económicas para el segundo semestre', 'gobierno-anuncia-nuevas-medidas-economicas-segundo-semestre', 'a0000000-0000-0000-0000-000000000001', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', 1450, date('Y-m-d H:i:s', strtotime('-2 days'))],
            ['n0000000-0000-0000-0000-000000000002', 'Debate legislativo por la reforma del sistema previsional', 'debate-legislativo-reforma-sistema-previsional', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', 890, date('Y-m-d H:i:s', strtotime('-3 days'))],
            ['n0000000-0000-0000-0000-000000000003', 'Acuerdo histórico en la cumbre climática regional', 'acuerdo-historico-cumbre-climatica-regional', 'a0000000-0000-0000-0000-000000000005', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', 670, date('Y-m-d H:i:s', strtotime('-5 days'))],
            ['n0000000-0000-0000-0000-000000000004', 'La selección se prepara para las eliminatorias', 'seleccion-prepara-eliminatorias', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', 2200, date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['n0000000-0000-0000-0000-000000000005', 'Nuevo avance en inteligencia artificial aplicada a la medicina', 'nuevo-avance-inteligencia-artificial-medicina', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', 1800, date('Y-m-d H:i:s', strtotime('-4 days'))],
            ['n0000000-0000-0000-0000-000000000006', 'Crisis hídrica: advierten sobre la baja del río Paraná', 'crisis-hidrica-baja-rio-parana', 'a0000000-0000-0000-0000-000000000005', 'in_review', 0, 0, '10000000-0000-0000-0000-000000000005', 0, null],
            // Writer 2 (Diego) — 6 articles
            ['n0000000-0000-0000-0000-000000000007', 'Mercados: el dólar se estabiliza tras semanas de volatilidad', 'mercados-dolar-estabiliza-semanas-volatilidad', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', 1350, date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['n0000000-0000-0000-0000-000000000008', 'River y Boca definen la final del campeonato local', 'river-boca-definen-final-campeonato-local', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 1, '10000000-0000-0000-0000-000000000006', 5400, date('Y-m-d H:i:s', strtotime('-6 hours'))],
            ['n0000000-0000-0000-0000-000000000009', 'Festival internacional de cine: Argentina gana tres premios', 'festival-internacional-cine-argentina-gana-tres-premios', 'a0000000-0000-0000-0000-000000000004', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', 920, date('Y-m-d H:i:s', strtotime('-7 days'))],
            ['n0000000-0000-0000-0000-000000000010', 'Lanzamiento del primer satélite argentino de comunicaciones 5G', 'lanzamiento-primer-satelite-argentino-comunicaciones-5g', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000006', 3100, date('Y-m-d H:i:s', strtotime('-2 days'))],
            ['n0000000-0000-0000-0000-000000000011', 'Reforma educativa: provincias acuerdan nuevo diseño curricular', 'reforma-educativa-provincias-nuevo-diseno-curricular', 'a0000000-0000-0000-0000-000000000005', 'draft', 0, 0, '10000000-0000-0000-0000-000000000006', 0, null],
            ['n0000000-0000-0000-0000-000000000012', 'Opinión: El futuro de las criptomonedas en la región', 'opinion-futuro-criptomonedas-region', 'a0000000-0000-0000-0000-000000000004', 'approved', 0, 0, '10000000-0000-0000-0000-000000000006', 0, null],
        ];

        foreach ($articles as [$id, $title, $slug, $authorId, $status, $featured, $breaking, $createdBy, $views, $publishedAt]) {
            $this->db->table('news')->insert([
                'id'              => $id,
                'title'           => $title,
                'slug'            => $slug,
                'excerpt'         => substr($title, 0, 100) . '...',
                'body'            => '<p>' . $title . '. Contenido de prueba para el artículo.</p>',
                'author_id'       => $authorId,
                'status'          => $status,
                'featured'        => $featured,
                'breaking'        => $breaking,
                'created_by'      => $createdBy,
                'view_count'      => $views,
                'published_at'    => $publishedAt,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ---- News-Category associations ----
        $nc = [
            ['n0000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000001'],
            ['n0000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000002'],
            ['n0000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000001'],
            ['n0000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000007'],
            ['n0000000-0000-0000-0000-000000000004', 'c0000000-0000-0000-0000-000000000003'],
            ['n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000004'],
            ['n0000000-0000-0000-0000-000000000006', 'c0000000-0000-0000-0000-000000000006'],
            ['n0000000-0000-0000-0000-000000000007', 'c0000000-0000-0000-0000-000000000002'],
            ['n0000000-0000-0000-0000-000000000008', 'c0000000-0000-0000-0000-000000000003'],
            ['n0000000-0000-0000-0000-000000000009', 'c0000000-0000-0000-0000-000000000005'],
            ['n0000000-0000-0000-0000-000000000010', 'c0000000-0000-0000-0000-000000000004'],
            ['n0000000-0000-0000-0000-000000000011', 'c0000000-0000-0000-0000-000000000006'],
            ['n0000000-0000-0000-0000-000000000012', 'c0000000-0000-0000-0000-000000000008'],
        ];
        foreach ($nc as [$newsId, $catId]) {
            $this->db->table('news_categories')->insert([
                'id' => $this->uuid(), 'news_id' => $newsId, 'category_id' => $catId,
            ]);
        }

        // ---- News-Tag associations ----
        $nt = [
            ['n0000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000002'],
            ['n0000000-0000-0000-0000-000000000002', 't0000000-0000-0000-0000-000000000001'],
            ['n0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000005'],
            ['n0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000003'],
            ['n0000000-0000-0000-0000-000000000005', 't0000000-0000-0000-0000-000000000004'],
            ['n0000000-0000-0000-0000-000000000007', 't0000000-0000-0000-0000-000000000002'],
            ['n0000000-0000-0000-0000-000000000008', 't0000000-0000-0000-0000-000000000003'],
            ['n0000000-0000-0000-0000-000000000008', 't0000000-0000-0000-0000-000000000010'],
            ['n0000000-0000-0000-0000-000000000010', 't0000000-0000-0000-0000-000000000004'],
            ['n0000000-0000-0000-0000-000000000012', 't0000000-0000-0000-0000-000000000007'],
        ];
        foreach ($nt as [$newsId, $tagId]) {
            $this->db->table('news_tags')->insert([
                'id' => $this->uuid(), 'news_id' => $newsId, 'tag_id' => $tagId,
            ]);
        }

        // ---- Ad Slots ----
        $this->db->table('ad_slots')->insertBatch([
            ['id' => $this->uuid(), 'name' => 'Banner principal superior', 'placement' => 'header', 'type' => 'image', 'content' => '{"imageUrl":"/uploads/ads/banner-header.jpg","width":728,"height":90}', 'target_url' => 'https://example.com/promo1', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $this->uuid(), 'name' => 'Banner lateral derecho', 'placement' => 'sidebar', 'type' => 'image', 'content' => '{"imageUrl":"/uploads/ads/banner-sidebar.jpg","width":300,"height":250}', 'target_url' => 'https://example.com/promo2', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $this->uuid(), 'name' => 'Banner entre artículos', 'placement' => 'inline', 'type' => 'html', 'content' => '{"html":"<div>Publicidad</div>"}', 'target_url' => null, 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $this->uuid(), 'name' => 'Banner pie de página', 'placement' => 'footer', 'type' => 'image', 'content' => '{"imageUrl":"/uploads/ads/banner-footer.jpg","width":728,"height":90}', 'target_url' => 'https://example.com/promo3', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---- Newsletter Subscribers ----
        for ($i = 1; $i <= 10; $i++) {
            $status = $i <= 6 ? 'active' : ($i <= 8 ? 'pending' : 'unsubscribed');
            $this->db->table('newsletter_subscribers')->insert([
                'id'           => $this->uuid(),
                'email'        => "lector{$i}@gmail.com",
                'name'         => "Lector {$i}",
                'status'       => $status,
                'confirmed_at' => $status === 'active' ? $now : null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // ---- Poll ----

        $this->db->table('polls')->insert([
            'id' => $pollId, 'title' => '¿Cuál es la temática que más te interesa?',
            'description' => 'Ayudanos a mejorar nuestro contenido.', 'active' => 1,
            'starts_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_by' => '10000000-0000-0000-0000-000000000001',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->db->table('poll_questions')->insertBatch([
            ['id' => $q1Id, 'poll_id' => $pollId, 'text' => '¿Qué sección visitás más?', 'type' => 'single', 'sort_order' => 1, 'required' => 1, 'created_at' => $now],
            ['id' => $q2Id, 'poll_id' => $pollId, 'text' => '¿Qué tipo de contenido preferís?', 'type' => 'multiple', 'sort_order' => 2, 'required' => 1, 'created_at' => $now],
        ]);

        $q1Options = ['Política', 'Economía', 'Deportes', 'Tecnología', 'Cultura'];
        foreach ($q1Options as $i => $text) {
            $this->db->table('poll_options')->insert([
                'id' => $this->uuid(), 'question_id' => $q1Id,
                'text' => $text, 'sort_order' => $i + 1, 'created_at' => $now,
            ]);
        }

        $q2Options = ['Noticias breves', 'Análisis extenso', 'Videos', 'Infografías', 'Entrevistas'];
        foreach ($q2Options as $i => $text) {
            $this->db->table('poll_options')->insert([
                'id' => $this->uuid(), 'question_id' => $q2Id,
                'text' => $text, 'sort_order' => $i + 1, 'created_at' => $now,
            ]);
        }

        // ---- Surveys ----
        $this->db->table('surveys')->insertBatch([
            [
                'id' => $survey1Id,
                'title' => 'Satisfaccion general del lector',
                'slug' => 'satisfaccion-general-del-lector',
                'description' => 'Encuesta publica para medir experiencia de lectura.',
                'initial_message' => 'Queremos saber como te sentis con la experiencia actual del portal.',
                'final_message' => 'Gracias por completar la encuesta. Tu feedback nos ayuda a mejorar.',
                'status' => 'published',
                'starts_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'requires_login' => 0,
                'allow_back_navigation' => 1,
                'questions_per_view' => 3,
                'created_by' => '10000000-0000-0000-0000-000000000001',
                'updated_by' => '10000000-0000-0000-0000-000000000001',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey2Id,
                'title' => 'Preferencias editoriales del usuario',
                'slug' => 'preferencias-editoriales-del-usuario',
                'description' => 'Encuesta publica con login para usuarios registrados.',
                'initial_message' => 'Tu perfil nos ayuda a personalizar el contenido que ves.',
                'final_message' => 'Listo. Guardamos tus preferencias para futuras recomendaciones.',
                'status' => 'published',
                'starts_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+45 days')),
                'requires_login' => 1,
                'allow_back_navigation' => 1,
                'questions_per_view' => 2,
                'created_by' => '10000000-0000-0000-0000-000000000001',
                'updated_by' => '10000000-0000-0000-0000-000000000001',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey3Id,
                'title' => 'Encuesta de prueba cerrada',
                'slug' => 'encuesta-de-prueba-cerrada',
                'description' => 'Muestra de una encuesta ya finalizada.',
                'initial_message' => 'Esta encuesta quedo cerrada para nuevas respuestas.',
                'final_message' => 'Gracias por tu participacion.',
                'status' => 'closed',
                'starts_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'ends_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'requires_login' => 0,
                'allow_back_navigation' => 0,
                'questions_per_view' => 1,
                'created_by' => '10000000-0000-0000-0000-000000000001',
                'updated_by' => '10000000-0000-0000-0000-000000000001',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->db->table('survey_sections')->insertBatch([
            ['id' => $survey1Section1Id, 'survey_id' => $survey1Id, 'title' => 'Uso y costumbres', 'description' => 'Primer bloque de experiencia general.', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Section2Id, 'survey_id' => $survey1Id, 'title' => 'Cierre', 'description' => 'Evaluacion final del portal.', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey2Section1Id, 'survey_id' => $survey2Id, 'title' => 'Preferencias personales', 'description' => 'Bloque unico con perfil editorial.', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey3Section1Id, 'survey_id' => $survey3Id, 'title' => 'Encuesta cerrada', 'description' => 'Seccion de referencia.', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('survey_questions')->insertBatch([
            [
                'id' => $survey1Question1Id,
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_text' => '¿Con que frecuencia lees Netxus?',
                'help_text' => 'Eleginos la opcion que mejor te describa.',
                'type' => 'single_choice',
                'is_required' => 1,
                'sort_order' => 1,
                'config' => json_encode(['layout' => 'vertical']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey1Question2Id,
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_text' => '¿Que tema te interesa mas?',
                'help_text' => null,
                'type' => 'multiple_choice',
                'is_required' => 0,
                'sort_order' => 2,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey1Question3Id,
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_text' => '¿Que tanto te gusta la navegacion actual?',
                'help_text' => '1 es poco y 5 es excelente.',
                'type' => 'numeric_scale',
                'is_required' => 1,
                'sort_order' => 3,
                'config' => json_encode(['min' => 1, 'max' => 5]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey1Question4Id,
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section2Id,
                'question_text' => '¿Dejarias un comentario final?',
                'help_text' => null,
                'type' => 'long_text',
                'is_required' => 0,
                'sort_order' => 1,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey2Question1Id,
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_text' => '¿Cual es tu seccion favorita?',
                'help_text' => null,
                'type' => 'dropdown',
                'is_required' => 1,
                'sort_order' => 1,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey2Question2Id,
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_text' => '¿Que formato consumis mas?',
                'help_text' => null,
                'type' => 'long_text',
                'is_required' => 0,
                'sort_order' => 2,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey2Question3Id,
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_text' => '¿Cuantas noticias lees por dia?',
                'help_text' => null,
                'type' => 'numeric_scale',
                'is_required' => 1,
                'sort_order' => 3,
                'config' => json_encode(['min' => 1, 'max' => 10]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $survey3Question1Id,
                'survey_id' => $survey3Id,
                'section_id' => $survey3Section1Id,
                'question_text' => '¿Seguira visible?',
                'help_text' => null,
                'type' => 'date',
                'is_required' => 0,
                'sort_order' => 1,
                'config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->db->table('survey_question_options')->insertBatch([
            ['id' => $survey1Option1Id, 'question_id' => $survey1Question1Id, 'label' => 'Todos los dias', 'value' => 'todos-los-dias', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Option2Id, 'question_id' => $survey1Question1Id, 'label' => 'Varias veces por semana', 'value' => 'varias-veces-por-semana', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Option3Id, 'question_id' => $survey1Question1Id, 'label' => 'Una vez por semana', 'value' => 'una-vez-por-semana', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Option4Id, 'question_id' => $survey1Question2Id, 'label' => 'Noticias', 'value' => 'noticias', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Option5Id, 'question_id' => $survey1Question2Id, 'label' => 'Analisis', 'value' => 'analisis', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey1Option6Id, 'question_id' => $survey1Question2Id, 'label' => 'Entrevistas', 'value' => 'entrevistas', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey2Option1Id, 'question_id' => $survey2Question1Id, 'label' => 'Actualidad', 'value' => 'actualidad', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey2Option2Id, 'question_id' => $survey2Question1Id, 'label' => 'Economia', 'value' => 'economia', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $survey2Option3Id, 'question_id' => $survey2Question1Id, 'label' => 'Tecnologia', 'value' => 'tecnologia', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ---- Portal user feature test data ----
        $this->call('PortalUserFeatureSeeder');

        // ---- Survey responses ----
        $surveyResponses = [
            [
                'id' => 'sr000000-0000-0000-0000-000000000001',
                'survey_id' => $survey1Id,
                'user_id' => null,
                'anonymous_key' => 'survey-anon-lector-a',
                'status' => 'completed',
                'current_section_id' => null,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'ip_hash' => sha1('192.168.1.10'),
                'user_agent_hash' => sha1('Mozilla/5.0 Netxus Survey'),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id' => 'sr000000-0000-0000-0000-000000000002',
                'survey_id' => $survey1Id,
                'user_id' => null,
                'anonymous_key' => 'survey-anon-lector-b',
                'status' => 'in_progress',
                'current_section_id' => $survey1Section2Id,
                'completed_at' => null,
                'ip_hash' => sha1('192.168.1.11'),
                'user_agent_hash' => sha1('Mozilla/5.0 Netxus Survey'),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'id' => 'sr000000-0000-0000-0000-000000000003',
                'survey_id' => $survey2Id,
                'user_id' => '50000000-0000-0000-0000-000000000001',
                'anonymous_key' => null,
                'status' => 'completed',
                'current_section_id' => null,
                'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'ip_hash' => sha1('192.168.1.12'),
                'user_agent_hash' => sha1('Mozilla/5.0 Netxus Survey'),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'id' => 'sr000000-0000-0000-0000-000000000004',
                'survey_id' => $survey2Id,
                'user_id' => '50000000-0000-0000-0000-000000000002',
                'anonymous_key' => null,
                'status' => 'in_progress',
                'current_section_id' => $survey2Section1Id,
                'completed_at' => null,
                'ip_hash' => sha1('192.168.1.13'),
                'user_agent_hash' => sha1('Mozilla/5.0 Netxus Survey'),
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            ],
        ];

        $this->db->table('survey_responses')->insertBatch($surveyResponses);

        $this->db->table('survey_answers')->insertBatch([
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000001',
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_id' => $survey1Question1Id,
                'value_text' => 'varias-veces-por-semana',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000001',
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_id' => $survey1Question2Id,
                'value_text' => null,
                'value_json' => json_encode(['noticias', 'tecnologia']),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000001',
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section1Id,
                'question_id' => $survey1Question3Id,
                'value_text' => '4',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000001',
                'survey_id' => $survey1Id,
                'section_id' => $survey1Section2Id,
                'question_id' => $survey1Question4Id,
                'value_text' => 'Me gusta la propuesta, aunque podria sumar mas filtros.',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000003',
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_id' => $survey2Question1Id,
                'value_text' => 'actualidad',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000003',
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_id' => $survey2Question2Id,
                'value_text' => 'Videos y analisis cortos',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id' => $this->uuid(),
                'survey_response_id' => 'sr000000-0000-0000-0000-000000000003',
                'survey_id' => $survey2Id,
                'section_id' => $survey2Section1Id,
                'question_id' => $survey2Question3Id,
                'value_text' => '6',
                'value_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
        ]);

        echo "Test data seeded including portal user feature dataset.\n";
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

