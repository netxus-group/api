<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds massive news data for Corrientes local media simulation.
 * Creates 200 news articles focused on Corrientes politics and local issues.
 * Usage: php spark db:seed NewsCorrientesSeeder
 */
class NewsCorrientesSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Authors (7 authors, some specialized in politics) ----
        $authors = [
            ['id' => 'a1000000-0000-0000-0000-000000000001', 'name' => 'María Elena Romero', 'slug' => 'maria-elena-romero', 'bio' => 'Periodista especializada en política provincial y nacional.', 'email' => 'mromero@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000002', 'name' => 'Juan Carlos Aguirre', 'slug' => 'juan-carlos-aguirre', 'bio' => 'Corresponsal político con más de 20 años de experiencia.', 'email' => 'jaguirre@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000003', 'name' => 'Patricia López', 'slug' => 'patricia-lopez', 'bio' => 'Especialista en economía regional y desarrollo.', 'email' => 'plopez@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000004', 'name' => 'Roberto Fernández', 'slug' => 'roberto-fernandez', 'bio' => 'Periodista de investigación y temas sociales.', 'email' => 'rfernandez@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000005', 'name' => 'Ana María González', 'slug' => 'ana-maria-gonzalez', 'bio' => 'Redactora de noticias nacionales e internacionales.', 'email' => 'agonzalez@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000006', 'name' => 'Carlos Eduardo Silva', 'slug' => 'carlos-eduardo-silva', 'bio' => 'Analista político y columnista de opinión.', 'email' => 'csilva@corrientesnoticias.com'],
            ['id' => 'a1000000-0000-0000-0000-000000000007', 'name' => 'Laura Beatriz Torres', 'slug' => 'laura-beatriz-torres', 'bio' => 'Periodista especializada en educación y cultura.', 'email' => 'ltorres@corrientesnoticias.com'],
        ];
        foreach ($authors as $a) {
            $a['active'] = 1;
            $a['created_at'] = $now;
            $a['updated_at'] = $now;
            $this->db->table('authors')->insert($a);
        }

        // ---- Categories ----
        $categories = [
            ['c1000000-0000-0000-0000-000000000001', 'Política', 'politica', '#E53E3E', 1],
            ['c1000000-0000-0000-0000-000000000002', 'Economía', 'economia', '#DD6B20', 2],
            ['c1000000-0000-0000-0000-000000000003', 'Sociedad', 'sociedad', '#D69E2E', 3],
            ['c1000000-0000-0000-0000-000000000004', 'Nacional', 'nacional', '#2D3748', 4],
            ['c1000000-0000-0000-0000-000000000005', 'Internacional', 'internacional', '#718096', 5],
        ];
        foreach ($categories as [$id, $name, $slug, $color, $order]) {
            $this->db->table('categories')->insert([
                'id' => $id, 'name' => $name, 'slug' => $slug,
                'color' => $color, 'sort_order' => $order, 'active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- Tags (20 tags) ----
        $tags = [
            ['t1000000-0000-0000-0000-000000000001', 'Elecciones', 'elecciones'],
            ['t1000000-0000-0000-0000-000000000002', 'Gobierno', 'gobierno'],
            ['t1000000-0000-0000-0000-000000000003', 'Intendentes', 'intendentes'],
            ['t1000000-0000-0000-0000-000000000004', 'Legislatura', 'legislatura'],
            ['t1000000-0000-0000-0000-000000000005', 'Inflación', 'inflacion'],
            ['t1000000-0000-0000-0000-000000000006', 'Obras Públicas', 'obras-publicas'],
            ['t1000000-0000-0000-0000-000000000007', 'Educación', 'educacion'],
            ['t1000000-0000-0000-0000-000000000008', 'Seguridad', 'seguridad'],
            ['t1000000-0000-0000-0000-000000000009', 'Salud', 'salud'],
            ['t1000000-0000-0000-0000-000000000010', 'Justicia', 'justicia'],
            ['t1000000-0000-0000-0000-000000000011', 'Medio Ambiente', 'medio-ambiente'],
            ['t1000000-0000-0000-0000-000000000012', 'Agricultura', 'agricultura'],
            ['t1000000-0000-0000-0000-000000000013', 'Turismo', 'turismo'],
            ['t1000000-0000-0000-0000-000000000014', 'Desarrollo', 'desarrollo'],
            ['t1000000-0000-0000-0000-000000000015', 'Corrupción', 'corrupcion'],
            ['t1000000-0000-0000-0000-000000000016', 'Presupuesto', 'presupuesto'],
            ['t1000000-0000-0000-0000-000000000017', 'Empleo', 'empleo'],
            ['t1000000-0000-0000-0000-000000000018', 'Transporte', 'transporte'],
            ['t1000000-0000-0000-0000-000000000019', 'Energía', 'energia'],
            ['t1000000-0000-0000-0000-000000000020', 'Derechos Humanos', 'derechos-humanos'],
        ];
        foreach ($tags as [$id, $name, $slug]) {
            $this->db->table('tags')->insert([
                'id' => $id, 'name' => $name, 'slug' => $slug,
                'active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ---- Media Images (200 images for news covers) ----
        $mediaImages = [];
        $imageUrls = [
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // political meeting
            'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // government building
            'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // press conference
            'https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // city hall
            'https://images.unsplash.com/photo-1577962917302-cd874c4e31d2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // parliament
            'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // river corrientes
            'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // corrientes city
            'https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // protest
            'https://images.unsplash.com/photo-1507679799987-c73779587ccf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // election
            'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // construction
            'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // hospital
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // school
            'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // police
            'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // court
            'https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // farm
            'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // tourism
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // development
            'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // corruption
            'https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // budget
            'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', // employment
        ];
        $altTexts = [
            'Reunión política en la Casa de Gobierno',
            'Edificio gubernamental de Corrientes',
            'Conferencia de prensa del gobernador',
            'Municipio de Corrientes capital',
            'Sesión en la Legislatura provincial',
            'Río Paraná visto desde Corrientes',
            'Panorama de la ciudad de Corrientes',
            'Manifestación ciudadana',
            'Proceso electoral en curso',
            'Obras de construcción pública',
            'Hospital provincial',
            'Escuela pública en Corrientes',
            'Fuerzas de seguridad',
            'Tribunal de justicia',
            'Campo agrícola en el interior',
            'Atracción turística del NEA',
            'Proyecto de desarrollo urbano',
            'Investigación de corrupción',
            'Presentación de presupuesto provincial',
            'Búsqueda de empleo en la provincia',
        ];
        for ($i = 0; $i < 200; $i++) {
            $mediaId = $this->uuid();
            $urlIndex = $i % count($imageUrls);
            $mediaImages[] = [
                'id' => $mediaId,
                'filename' => 'news_cover_' . ($i + 1) . '.jpg',
                'original_name' => 'news_cover_' . ($i + 1) . '.jpg',
                'mime_type' => 'image/jpeg',
                'size' => rand(50000, 200000),
                'width' => 1000,
                'height' => 600,
                'url' => $imageUrls[$urlIndex],
                'alt_text' => $altTexts[$urlIndex],
                'caption' => 'Imagen relacionada con la noticia',
                'folder' => 'news',
                'uploaded_by' => '10000000-0000-0000-0000-000000000001', // admin
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('media_images')->insertBatch($mediaImages);

        // ---- Generate 200 News Articles ----
        $newsData = [];
        $newsCategories = [];
        $newsTags = [];

        $politicaTitles = [
            'Gobernador Valdés anuncia nuevo plan de obras para el interior provincial',
            'Legislatura aprueba ley de presupuesto 2026 con énfasis en educación',
            'Intendente de Corrientes capital presenta proyecto de movilidad urbana',
            'Conflicto político en la oposición por candidaturas a gobernador',
            'Diputados provinciales debaten reforma del sistema electoral',
            'Gobierno provincial firma convenio con Nación para rutas',
            'Municipios del NEA reclaman mayor autonomía financiera',
            'Senadores nacionales analizan impacto de la sequía en Corrientes',
            'Elecciones municipales: candidatos presentan plataformas',
            'Casa de Gobierno recibe delegación internacional para inversiones',
            'Proyecto de ley para combatir la corrupción en la administración pública',
            'Gobernador se reúne con intendentes para coordinar políticas',
            'Legislatura debate presupuesto educativo para el próximo año',
            'Intendente propone plan de seguridad ciudadana',
            'Diputados opositores critican gestión provincial en salud',
            'Gobierno lanza campaña de vacunación masiva',
            'Conflicto por tierras en el interior genera tensión política',
            'Senado provincial aprueba ley de protección ambiental',
            'Candidatos a gobernador intensifican campaña electoral',
            'Municipio de Corrientes inaugura nueva sede administrativa',
            'Gobernador Valdés visita obras en ruta provincial',
            'Legislatura discute reforma laboral para el sector público',
            'Intendentes del NEA coordinan agenda turística',
            'Diputados nacionales analizan presupuesto nacional',
            'Gobierno provincial presenta balance de gestión 2025',
            'Conflicto político por nombramientos en la justicia',
            'Proyecto de infraestructura vial genera debate',
            'Municipios reclaman fondos para obras públicas',
            'Senadores provinciales visitan escuelas rurales',
            'Elecciones legislativas: panorama político en Corrientes',
            'Gobernador firma decreto de emergencia hídrica',
            'Casa de Gobierno organiza foro sobre desarrollo regional',
            'Intendente capitalino anuncia inversiones en cultura',
            'Diputados debaten ley de acceso a la información',
            'Gobierno nacional destina fondos para Corrientes',
            'Conflicto entre poderes genera crisis institucional',
            'Proyecto de parque industrial en el interior',
            'Municipios coordinan políticas de inclusión social',
            'Senado provincial homenajea figuras históricas',
            'Candidaturas presidenciales impactan en política local',
            'Gobernador preside acto por el día de la democracia',
            'Legislatura aprueba ley de protección al consumidor',
            'Intendente presenta plan estratégico municipal',
            'Diputados opositores piden interpelación al ministro',
            'Gobierno provincial lanza programa de empleo joven',
            'Conflicto por límites municipales en el NEA',
            'Proyecto de energía renovable genera expectativas',
            'Municipios del interior reclaman conectividad',
            'Senadores nacionales debaten reforma tributaria',
            'Elecciones provinciales: análisis de candidatos',
            'Casa de Gobierno recibe premio por transparencia',
            'Gobernador Valdés anuncia inversiones extranjeras',
            'Legislatura discute ley de protección animal',
            'Intendente coordina con provincia obras de saneamiento',
            'Diputados provinciales visitan zona de desastre',
            'Gobierno lanza campaña contra la violencia de género',
            'Conflicto político por control de recursos hídricos',
            'Proyecto de universidad pública en el interior',
            'Municipios coordinan agenda de seguridad',
            'Senado provincial aprueba presupuesto cultural',
            'Candidatos municipales intensifican recorridas',
            'Gobernador firma acuerdo con empresas privadas',
            'Casa de Gobierno organiza cumbre regional',
            'Intendente presenta balance de gestión municipal',
            'Diputados debaten reforma de la justicia',
            'Gobierno provincial destina fondos a la salud',
            'Conflicto por tierras indígenas genera debate',
            'Proyecto de turismo sostenible en Corrientes',
            'Municipios reclaman mayor participación en presupuesto',
            'Senadores provinciales analizan coyuntura económica',
            'Elecciones nacionales: impacto en Corrientes',
            'Gobernador preside inauguración de hospital',
            'Legislatura aprueba ley de promoción industrial',
            'Intendente coordina con Nación programa social',
            'Diputados opositores critican política económica',
            'Gobierno lanza plan de alfabetización digital',
            'Conflicto político por control partidario',
            'Proyecto de infraestructura deportiva',
            'Municipios del NEA firman acuerdo regional',
            'Senado provincial debate ley ambiental',
            'Candidaturas legislativas generan expectativa',
            'Casa de Gobierno recibe visita de ministros nacionales',
            'Gobernador Valdés anuncia plan de vivienda',
            'Legislatura discute reforma educativa',
            'Intendente presenta proyecto de parque urbano',
            'Diputados provinciales analizan presupuesto nacional',
            'Gobierno provincial coordina con municipios',
            'Conflicto por distribución de fondos',
            'Proyecto de conectividad digital en escuelas',
            'Municipios reclaman atención a la salud mental',
            'Senadores nacionales visitan Corrientes',
            'Elecciones municipales: campaña electoral intensa',
            'Gobernador firma convenio internacional',
            'Casa de Gobierno organiza jornada de diálogo',
            'Intendente coordina obras de infraestructura',
            'Diputados debaten ley de protección al trabajo',
            'Gobierno lanza programa de inclusión laboral',
            'Conflicto político por candidaturas legislativas',
            'Proyecto de desarrollo urbano sostenible',
            'Municipios coordinan agenda cultural',
            'Senado provincial aprueba ley de tránsito',
            'Candidatos presidenciales visitan la provincia',
            'Gobernador preside acto por la independencia',
            'Legislatura discute presupuesto de seguridad',
            'Intendente presenta plan de desarrollo local',
            'Diputados opositores piden auditoría provincial',
            'Gobierno provincial destina fondos a educación rural',
            'Conflicto por recursos naturales en el NEA',
            'Proyecto de parque eólico en Corrientes',
            'Municipios firman pacto por la transparencia',
            'Senadores provinciales analizan reforma política',
            'Elecciones provinciales: debate entre candidatos',
        ];

        $economiaTitles = [
            'Inflación en Corrientes supera el promedio nacional',
            'Sector agrícola enfrenta dificultades por sequía',
            'Gobierno provincial lanza plan de incentivo económico',
            'Empresas del NEA reclaman mayor competitividad',
            'Turismo en Corrientes crece durante temporada alta',
            'Comercio local sufre impacto de la crisis económica',
            'Industria tabacalera genera empleo en el interior',
            'Banco Nación amplía sucursales en la provincia',
            'Exportaciones de Corrientes alcanzan nuevo récord',
            'Municipios coordinan feria de emprendedores',
            'Sector ganadero enfrenta desafíos sanitarios',
            'Gobierno destina fondos para desarrollo industrial',
            'Comercio electrónico crece en Corrientes capital',
            'Agricultura familiar recibe subsidios provinciales',
            'Turismo rural genera ingresos en municipios',
            'Inflación afecta precios en mercados locales',
            'Empresas tecnológicas se instalan en Corrientes',
            'Banco provincial ofrece créditos blandos',
            'Exportaciones de cítricos aumentan significativamente',
            'Municipios lanzan programa de capacitación laboral',
            'Sector pesquero del Paraná enfrenta regulaciones',
            'Gobierno provincial incentiva inversiones extranjeras',
            'Comercio minorista se recupera lentamente',
            'Industria del software crece en la universidad',
            'Turismo aventura atrae visitantes al NEA',
            'Precios de combustibles impactan en transporte',
            'Empresas locales participan en feria internacional',
            'Banco Central monitorea situación financiera',
            'Producción de arroz genera divisas',
            'Municipios coordinan plan de desarrollo económico',
            'Sector textil enfrenta competencia extranjera',
            'Gobierno lanza bono para trabajadores informales',
            'Comercio de proximidad se fortalece',
            'Industria forestal sostenible en Corrientes',
            'Turismo cultural impulsa economía local',
            'Inflación mensual registra leve descenso',
            'Empresas del NEA exportan a Brasil',
            'Banco provincial financia proyectos productivos',
            'Producción de yerba mate en crecimiento',
        ];

        $sociedadTitles = [
            'Campaña de vacunación contra COVID alcanza cobertura del 90%',
            'Escuelas de Corrientes implementan jornada extendida',
            'Violencia de género: municipio lanza línea de ayuda',
            'Comunidad educativa reclama mejoras en infraestructura',
            'Programa de inclusión digital llega a escuelas rurales',
            'Hospital provincial atiende emergencia sanitaria',
            'Centros culturales ofrecen talleres gratuitos',
            'Municipio coordina jornada de limpieza urbana',
            'Escuelas técnicas capacitan en oficios demandados',
            'Campaña contra adicciones en jóvenes',
            'Hospital de campaña atiende demanda creciente',
            'Programa social beneficia a familias vulnerables',
            'Escuelas rurales reciben equipamiento tecnológico',
            'Violencia intrafamiliar genera alerta social',
            'Centro de día para adultos mayores inaugura',
            'Municipio lanza campaña de reciclaje',
            'Escuelas implementan programa de alimentación',
            'Hospital provincial incorpora nueva tecnología',
            'Programa de alfabetización para adultos',
            'Comunidad reclama mejoras en transporte público',
        ];

        $nacionalTitles = [
            'Gobierno nacional anuncia reforma tributaria',
            'Elecciones presidenciales: candidatos presentan propuestas',
            'Congreso debate ley de financiamiento político',
            'Ministro de Economía presenta presupuesto 2026',
            'Corte Suprema falla en caso de corrupción',
            'Gobierno lanza plan nacional de vivienda',
            'Elecciones legislativas generan expectativa',
            'Congreso aprueba ley de protección ambiental',
            'Ministro de Salud anuncia campaña nacional',
            'Corte Suprema analiza reforma judicial',
            'Gobierno nacional destina fondos a provincias',
            'Elecciones municipales: participación ciudadana',
            'Congreso debate presupuesto de defensa',
            'Ministro de Educación presenta reforma',
            'Corte Suprema falla en habeas corpus',
            'Gobierno lanza programa de empleo nacional',
            'Elecciones provinciales: análisis nacional',
            'Congreso aprueba ley laboral',
            'Ministro de Desarrollo presenta plan',
            'Corte Suprema analiza caso político',
            'Gobierno nacional coordina con provincias',
            'Elecciones nacionales: campaña intensa',
            'Congreso debate ley de seguridad',
            'Ministro de Justicia anuncia medidas',
            'Corte Suprema falla en amparo ambiental',
            'Gobierno lanza plan de conectividad',
            'Elecciones legislativas: resultados parciales',
            'Congreso aprueba presupuesto social',
            'Ministro de Trabajo presenta reforma',
            'Corte Suprema analiza conflicto sindical',
        ];

        $internacionalTitles = [
            'Cumbre internacional discute cambio climático',
            'Guerra en Ucrania impacta economía global',
            'ONU aprueba resolución sobre derechos humanos',
            'Elecciones en Estados Unidos generan incertidumbre',
            'Cumbre del G20 aborda crisis económica',
            'Conflicto en Medio Oriente escalan tensiones',
            'ONU lanza campaña contra hambre mundial',
            'Elecciones en Europa: triunfo de extrema derecha',
            'Cumbre iberoamericana discute migración',
            'Guerra comercial entre potencias económicas',
        ];

        $allTitles = [
            'politica' => $politicaTitles,
            'economia' => $economiaTitles,
            'sociedad' => $sociedadTitles,
            'nacional' => $nacionalTitles,
            'internacional' => $internacionalTitles,
        ];

        $categoryIds = [
            'politica' => 'c1000000-0000-0000-0000-000000000001',
            'economia' => 'c1000000-0000-0000-0000-000000000002',
            'sociedad' => 'c1000000-0000-0000-0000-000000000003',
            'nacional' => 'c1000000-0000-0000-0000-000000000004',
            'internacional' => 'c1000000-0000-0000-0000-000000000005',
        ];

        $authorIds = array_column($authors, 'id');
        $tagIdsList = array_column($tags, 'id');

        $statusOptions = ['published', 'draft', 'in_review', 'approved'];
        $statusWeights = [50, 30, 10, 10]; // percentages

        $newsCount = 200;
        for ($i = 0; $i < $newsCount; $i++) {
            $newsId = $this->uuid();

            // Assign category based on distribution
            $rand = rand(1, 100);
            if ($rand <= 50) $cat = 'politica';
            elseif ($rand <= 70) $cat = 'economia';
            elseif ($rand <= 80) $cat = 'sociedad';
            elseif ($rand <= 95) $cat = 'nacional';
            else $cat = 'internacional';

            $titles = $allTitles[$cat];
            $title = $titles[array_rand($titles)];
            $slug = url_title($title, '-', true) . '-' . ($i + 1);

            // Excerpt
            $excerpt = substr($title, 0, 120) . '...';

            // Body HTML
            $body = $title . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.';

            // Author (political authors more likely for politics)
            if ($cat === 'politica') {
                $authorWeights = [0.3, 0.3, 0.1, 0.1, 0.05, 0.1, 0.05]; // favor first two
            } else {
                $authorWeights = array_fill(0, 7, 1/7);
            }
            $authorId = $authorIds[$this->weightedRandom($authorWeights)];

            // Status
            $status = $statusOptions[$this->weightedRandom($statusWeights)];

            // Published at
            $daysAgo = rand(0, 45);
            $future = rand(1, 10) === 1; // 10% future
            if ($future) {
                $publishedAt = date('Y-m-d H:i:s', strtotime('+' . rand(1, 30) . ' days'));
                $status = 'scheduled';
            } elseif ($status === 'published') {
                $publishedAt = date('Y-m-d H:i:s', strtotime('-' . $daysAgo . ' days ' . rand(0, 23) . ' hours ' . rand(0, 59) . ' minutes'));
            } else {
                $publishedAt = null;
            }

            // Featured
            $featured = rand(1, 20) === 1 ? 1 : 0; // 5% featured

            // Cover image
            $coverUrl = $mediaImages[$i]['url'];

            $newsData[] = [
                'id' => $newsId,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'body' => $body,
                'cover_image_url' => $coverUrl,
                'author_id' => $authorId,
                'status' => $status,
                'featured' => $featured,
                'breaking' => 0,
                'published_at' => $publishedAt,
                'created_by' => '10000000-0000-0000-0000-000000000001',
                'view_count' => rand(0, 5000),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Category
            $newsCategories[] = [
                'id' => $this->uuid(),
                'news_id' => $newsId,
                'category_id' => $categoryIds[$cat],
            ];

            // Tags (2-4 random)
            $numTags = rand(2, 4);
            shuffle($tagIdsList);
            $selectedTags = array_slice($tagIdsList, 0, $numTags);
            foreach ((array)$selectedTags as $tagId) {
                $newsTags[] = [
                    'id' => $this->uuid(),
                    'news_id' => $newsId,
                    'tag_id' => $tagId,
                ];
            }
        }

        // Insert in batches
        $this->db->table('news')->insertBatch($newsData);
        $this->db->table('news_categories')->insertBatch($newsCategories);
        $this->db->table('news_tags')->insertBatch($newsTags);
    }

    private function weightedRandom($weights) {
        $total = array_sum($weights);
        $rand = mt_rand() / mt_getrandmax() * $total;
        $cumulative = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) return $i;
        }
        return count($weights) - 1;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}