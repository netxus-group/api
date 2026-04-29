-- ============================================================
-- Netxus Portal News - Test Data
-- 2 usuarios por rol, 5+ artÃƒÆ’Ã‚Â­culos por redactor,
-- categorÃƒÆ’Ã‚Â­as, tags, autores, ads, encuestas, suscriptores
-- ============================================================
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Prerequisite: Run schema.sql and seed-initial.sql first

DROP PROCEDURE IF EXISTS `seed_test_data`;
DELIMITER //
CREATE PROCEDURE `seed_test_data`()
BEGIN

-- -----------------------------------------------------------
-- Users: 2 editors + 2 writers (admin already seeded)
-- Password for all: Test1234!
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `email_verified`, `created_at`, `updated_at`) VALUES
    ('10000000-0000-0000-0000-000000000002', 'admin2@netxus.com',   '$2y$10$rxLAXbn5yKMaWCRFILOD8ulkOdR9AHyAcXChs6MfgIjzD2zszg77.', 'Carlos',  'MÃƒÆ’Ã‚Â©ndez',   'Carlos MÃƒÆ’Ã‚Â©ndez',   1, 1, NOW(), NOW()),
    ('10000000-0000-0000-0000-000000000003', 'editor1@netxus.com',  '$2y$10$rxLAXbn5yKMaWCRFILOD8ulkOdR9AHyAcXChs6MfgIjzD2zszg77.', 'Laura',   'GimÃƒÆ’Ã‚Â©nez',  'Laura GimÃƒÆ’Ã‚Â©nez',   1, 1, NOW(), NOW()),
    ('10000000-0000-0000-0000-000000000004', 'editor2@netxus.com',  '$2y$10$rxLAXbn5yKMaWCRFILOD8ulkOdR9AHyAcXChs6MfgIjzD2zszg77.', 'MartÃƒÆ’Ã‚Â­n',  'LÃƒÆ’Ã‚Â³pez',    'MartÃƒÆ’Ã‚Â­n LÃƒÆ’Ã‚Â³pez',    1, 1, NOW(), NOW()),
    ('10000000-0000-0000-0000-000000000005', 'writer1@netxus.com',  '$2y$10$rxLAXbn5yKMaWCRFILOD8ulkOdR9AHyAcXChs6MfgIjzD2zszg77.', 'Ana',     'RodrÃƒÆ’Ã‚Â­guez','Ana RodrÃƒÆ’Ã‚Â­guez',   1, 1, NOW(), NOW()),
    ('10000000-0000-0000-0000-000000000006', 'writer2@netxus.com',  '$2y$10$rxLAXbn5yKMaWCRFILOD8ulkOdR9AHyAcXChs6MfgIjzD2zszg77.', 'Diego',   'FernÃƒÆ’Ã‚Â¡ndez','Diego FernÃƒÆ’Ã‚Â¡ndez', 1, 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `email` = VALUES(`email`),
      `password_hash` = VALUES(`password_hash`),
      `first_name` = VALUES(`first_name`),
      `last_name` = VALUES(`last_name`),
      `display_name` = VALUES(`display_name`),
      `active` = VALUES(`active`),
      `email_verified` = VALUES(`email_verified`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- Assign roles
  IF TRUE THEN
    INSERT INTO `user_roles` (`id`, `user_id`, `role_profile_id`, `created_at`) VALUES
    ('20000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', NOW()),
    ('20000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', NOW()),
    ('20000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', NOW()),
    ('20000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000003', NOW()),
    ('20000000-0000-0000-0000-000000000006', '10000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000003', NOW())
    ON DUPLICATE KEY UPDATE
      `created_at` = VALUES(`created_at`);
  END IF;

-- -----------------------------------------------------------
-- Authors
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `authors` (`id`, `name`, `slug`, `bio`, `email`, `active`, `created_at`, `updated_at`) VALUES
    ('a0000000-0000-0000-0000-000000000001', 'MarÃƒÆ’Ã‚Â­a BelÃƒÆ’Ã‚Â©n Torres',   'maria-belen-torres',   'Periodista especializada en polÃƒÆ’Ã‚Â­tica y economÃƒÆ’Ã‚Â­a.',        'maria@netxus.com',  1, NOW(), NOW()),
    ('a0000000-0000-0000-0000-000000000002', 'JoaquÃƒÆ’Ã‚Â­n PÃƒÆ’Ã‚Â©rez',        'joaquin-perez',         'Corresponsal de deportes y actualidad.',                  'joaquin@netxus.com',1, NOW(), NOW()),
    ('a0000000-0000-0000-0000-000000000003', 'Valentina Ruiz',       'valentina-ruiz',        'Especialista en tecnologÃƒÆ’Ã‚Â­a y cultura digital.',           'vale@netxus.com',   1, NOW(), NOW()),
    ('a0000000-0000-0000-0000-000000000004', 'Roberto SÃƒÆ’Ã‚Â¡nchez',      'roberto-sanchez',       'Editor de opiniÃƒÆ’Ã‚Â³n y anÃƒÆ’Ã‚Â¡lisis internacional.',             'roberto@netxus.com',1, NOW(), NOW()),
    ('a0000000-0000-0000-0000-000000000005', 'Camila Herrera',       'camila-herrera',        'Periodista de investigaciÃƒÆ’Ã‚Â³n y sociedad.',                 'camila@netxus.com', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `name` = VALUES(`name`),
      `slug` = VALUES(`slug`),
      `bio` = VALUES(`bio`),
      `email` = VALUES(`email`),
      `active` = VALUES(`active`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- Categories
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `color`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
    ('c0000000-0000-0000-0000-000000000001', 'PolÃƒÆ’Ã‚Â­tica',     'politica',     'Noticias polÃƒÆ’Ã‚Â­ticas nacionales e internacionales', '#E53E3E', 1, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000002', 'EconomÃƒÆ’Ã‚Â­a',     'economia',     'Indicadores econÃƒÆ’Ã‚Â³micos, finanzas y mercados',     '#DD6B20', 2, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000003', 'Deportes',     'deportes',     'FÃƒÆ’Ã‚Âºtbol, tenis, automovilismo y mÃƒÆ’Ã‚Â¡s',              '#38A169', 3, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000004', 'TecnologÃƒÆ’Ã‚Â­a',   'tecnologia',   'InnovaciÃƒÆ’Ã‚Â³n, gadgets y transformaciÃƒÆ’Ã‚Â³n digital',    '#3182CE', 4, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000005', 'Cultura',      'cultura',      'Arte, mÃƒÆ’Ã‚Âºsica, cine y espectÃƒÆ’Ã‚Â¡culos',               '#805AD5', 5, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000006', 'Sociedad',     'sociedad',     'EducaciÃƒÆ’Ã‚Â³n, salud, medio ambiente',                '#D69E2E', 6, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000007', 'Internacional','internacional','Noticias del mundo',                              '#2D3748', 7, 1, NOW(), NOW()),
    ('c0000000-0000-0000-0000-000000000008', 'OpiniÃƒÆ’Ã‚Â³n',      'opinion',      'Columnas de opiniÃƒÆ’Ã‚Â³n y editoriales',               '#718096', 8, 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `name` = VALUES(`name`),
      `slug` = VALUES(`slug`),
      `description` = VALUES(`description`),
      `color` = VALUES(`color`),
      `sort_order` = VALUES(`sort_order`),
      `active` = VALUES(`active`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- Tags
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `tags` (`id`, `name`, `slug`, `active`, `created_at`, `updated_at`) VALUES
    ('t0000000-0000-0000-0000-000000000001', 'Elecciones',     'elecciones',     1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000002', 'InflaciÃƒÆ’Ã‚Â³n',      'inflacion',      1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000003', 'FÃƒÆ’Ã‚Âºtbol',         'futbol',         1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000004', 'Inteligencia Artificial', 'inteligencia-artificial', 1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000005', 'Medio Ambiente', 'medio-ambiente', 1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000006', 'Reforma Laboral','reforma-laboral',1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000007', 'Criptomonedas',  'criptomonedas',  1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000008', 'Salud',          'salud',          1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000009', 'EducaciÃƒÆ’Ã‚Â³n',      'educacion',      1, NOW(), NOW()),
    ('t0000000-0000-0000-0000-000000000010', 'Urgente',        'urgente',        1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `name` = VALUES(`name`),
      `slug` = VALUES(`slug`),
      `active` = VALUES(`active`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- News: Writer 1 (Ana) - 6 articles
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `news` (`id`, `title`, `slug`, `subtitle`, `excerpt`, `body`, `author_id`, `status`, `featured`, `breaking`, `created_by`, `published_at`, `view_count`, `created_at`, `updated_at`) VALUES
    ('n0000000-0000-0000-0000-000000000001', 'El gobierno anuncia nuevas medidas econÃƒÆ’Ã‚Â³micas para el segundo semestre', 'gobierno-anuncia-nuevas-medidas-economicas-segundo-semestre', 'Plan de estabilizaciÃƒÆ’Ã‚Â³n incluye recorte del gasto pÃƒÆ’Ã‚Âºblico', 'El Ministerio de EconomÃƒÆ’Ã‚Â­a presentÃƒÆ’Ã‚Â³ un paquete de medidas que busca reducir el dÃƒÆ’Ã‚Â©ficit fiscal en un 2% durante los prÃƒÆ’Ã‚Â³ximos 6 meses.', '<p>El Ministerio de EconomÃƒÆ’Ã‚Â­a presentÃƒÆ’Ã‚Â³ esta maÃƒÆ’Ã‚Â±ana un paquete integral de medidas que busca reducir el dÃƒÆ’Ã‚Â©ficit fiscal en un 2% durante los prÃƒÆ’Ã‚Â³ximos 6 meses. Entre las principales iniciativas se destacan la reducciÃƒÆ’Ã‚Â³n del gasto corriente, la implementaciÃƒÆ’Ã‚Â³n de nuevos incentivos para la inversiÃƒÆ’Ã‚Â³n extranjera y un plan de regularizaciÃƒÆ’Ã‚Â³n impositiva.</p><p>El ministro destacÃƒÆ’Ã‚Â³ que estas medidas son necesarias para mantener la estabilidad macroeconÃƒÆ’Ã‚Â³mica lograda en el primer trimestre. "Estamos trabajando para consolidar un camino de crecimiento sostenible", afirmÃƒÆ’Ã‚Â³ en conferencia de prensa.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 2 DAY), 1450, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000002', 'Debate legislativo por la reforma del sistema previsional', 'debate-legislativo-reforma-sistema-previsional', 'Diputados inician sesiones especiales para tratar el proyecto', 'La CÃƒÆ’Ã‚Â¡mara de Diputados convocÃƒÆ’Ã‚Â³ a sesiones extraordinarias para debatir la reforma del sistema previsional que afecta a mÃƒÆ’Ã‚Â¡s de 8 millones de jubilados.', '<p>La CÃƒÆ’Ã‚Â¡mara de Diputados convocÃƒÆ’Ã‚Â³ a sesiones extraordinarias para debatir la reforma del sistema previsional que afecta a mÃƒÆ’Ã‚Â¡s de 8 millones de jubilados. El proyecto, que cuenta con dictamen de comisiÃƒÆ’Ã‚Â³n, propone una nueva fÃƒÆ’Ã‚Â³rmula de movilidad indexada a la inflaciÃƒÆ’Ã‚Â³n y al crecimiento salarial.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 3 DAY), 890, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000003', 'Acuerdo histÃƒÆ’Ã‚Â³rico en la cumbre climÃƒÆ’Ã‚Â¡tica regional', 'acuerdo-historico-cumbre-climatica-regional', '15 paÃƒÆ’Ã‚Â­ses firman compromiso de reducciÃƒÆ’Ã‚Â³n de emisiones', 'En la Cumbre ClimÃƒÆ’Ã‚Â¡tica Regional celebrada en Santiago de Chile, 15 paÃƒÆ’Ã‚Â­ses del continente firmaron un acuerdo vinculante para reducir emisiones de carbono.', '<p>En la Cumbre ClimÃƒÆ’Ã‚Â¡tica Regional celebrada en Santiago de Chile, 15 paÃƒÆ’Ã‚Â­ses del continente firmaron un acuerdo vinculante para reducir emisiones de carbono en un 35% para 2035. El acuerdo incluye mecanismos de financiamiento verde y un fondo solidario para naciones en desarrollo.</p>', 'a0000000-0000-0000-0000-000000000005', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 5 DAY), 670, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000004', 'La selecciÃƒÆ’Ã‚Â³n se prepara para las eliminatorias', 'seleccion-prepara-eliminatorias', 'El DT convoca a 26 jugadores para la doble fecha', 'El director tÃƒÆ’Ã‚Â©cnico de la selecciÃƒÆ’Ã‚Â³n nacional anunciÃƒÆ’Ã‚Â³ la lista de convocados para la prÃƒÆ’Ã‚Â³xima doble fecha de eliminatorias sudamericanas.', '<p>El director tÃƒÆ’Ã‚Â©cnico de la selecciÃƒÆ’Ã‚Â³n nacional anunciÃƒÆ’Ã‚Â³ la lista de 26 convocados para la prÃƒÆ’Ã‚Â³xima doble fecha de eliminatorias sudamericanas. La principal novedad es la inclusiÃƒÆ’Ã‚Â³n de dos juveniles del fÃƒÆ’Ã‚Âºtbol europeo que podrÃƒÆ’Ã‚Â­an debutar ante Colombia y Venezuela.</p>', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 1 DAY), 2200, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000005', 'Nuevo avance en inteligencia artificial aplicada a la medicina', 'nuevo-avance-inteligencia-artificial-medicina', 'Investigadores desarrollan sistema de detecciÃƒÆ’Ã‚Â³n temprana de cÃƒÆ’Ã‚Â¡ncer', 'Un equipo de investigadores argentinos desarrollÃƒÆ’Ã‚Â³ un sistema basado en IA capaz de detectar tumores malignos con un 97% de precisiÃƒÆ’Ã‚Â³n.', '<p>Un equipo de investigadores argentinos desarrollÃƒÆ’Ã‚Â³ un sistema basado en inteligencia artificial capaz de detectar tumores malignos con un 97% de precisiÃƒÆ’Ã‚Â³n usando ÃƒÆ’Ã‚Âºnicamente imÃƒÆ’Ã‚Â¡genes de tomografÃƒÆ’Ã‚Â­a. El proyecto, financiado por el CONICET, ya se encuentra en fase de pruebas en tres hospitales pÃƒÆ’Ã‚Âºblicos.</p>', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 4 DAY), 1800, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000006', 'Crisis hÃƒÆ’Ã‚Â­drica: advierten sobre la baja del rÃƒÆ’Ã‚Â­o ParanÃƒÆ’Ã‚Â¡', 'crisis-hidrica-baja-rio-parana', 'Los niveles se encuentran en mÃƒÆ’Ã‚Â­nimos histÃƒÆ’Ã‚Â³ricos', 'Especialistas alertan que el rÃƒÆ’Ã‚Â­o ParanÃƒÆ’Ã‚Â¡ registra niveles por debajo de los mÃƒÆ’Ã‚Â­nimos histÃƒÆ’Ã‚Â³ricos, afectando el transporte fluvial y la provisiÃƒÆ’Ã‚Â³n de agua potable.', '<p>Especialistas alertan que el rÃƒÆ’Ã‚Â­o ParanÃƒÆ’Ã‚Â¡ registra niveles por debajo de los mÃƒÆ’Ã‚Â­nimos histÃƒÆ’Ã‚Â³ricos por tercer mes consecutivo. La situaciÃƒÆ’Ã‚Â³n impacta en el transporte de granos, la generaciÃƒÆ’Ã‚Â³n hidroelÃƒÆ’Ã‚Â©ctrica y la provisiÃƒÆ’Ã‚Â³n de agua potable para millones de habitantes de la cuenca.</p>', 'a0000000-0000-0000-0000-000000000005', 'in_review', 0, 0, '10000000-0000-0000-0000-000000000005', NULL, 0, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `title` = VALUES(`title`),
      `slug` = VALUES(`slug`),
      `subtitle` = VALUES(`subtitle`),
      `excerpt` = VALUES(`excerpt`),
      `body` = VALUES(`body`),
      `author_id` = VALUES(`author_id`),
      `status` = VALUES(`status`),
      `featured` = VALUES(`featured`),
      `breaking` = VALUES(`breaking`),
      `created_by` = VALUES(`created_by`),
      `published_at` = VALUES(`published_at`),
      `view_count` = VALUES(`view_count`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- News: Writer 2 (Diego) - 6 articles
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `news` (`id`, `title`, `slug`, `subtitle`, `excerpt`, `body`, `author_id`, `status`, `featured`, `breaking`, `created_by`, `published_at`, `view_count`, `created_at`, `updated_at`) VALUES
    ('n0000000-0000-0000-0000-000000000007', 'Mercados: el dÃƒÆ’Ã‚Â³lar se estabiliza tras semanas de volatilidad', 'mercados-dolar-estabiliza-semanas-volatilidad', 'Analistas esperan que la tendencia se mantenga', 'Tras cuatro semanas de fuertes oscilaciones, el tipo de cambio paralelo se estabiliza en la zona de $1.250.', '<p>Tras cuatro semanas de fuertes oscilaciones, el tipo de cambio paralelo se estabiliza en la zona de $1.250. Los analistas consultados coinciden en que las recientes medidas del Banco Central lograron contener la presiÃƒÆ’Ã‚Â³n cambiaria, aunque advierten que la estabilidad depende del cierre exitoso de las negociaciones con organismos internacionales.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 1 DAY), 1350, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000008', 'River y Boca definen la final del campeonato local', 'river-boca-definen-final-campeonato-local', 'El superclÃƒÆ’Ã‚Â¡sico mÃƒÆ’Ã‚Â¡s esperado de la dÃƒÆ’Ã‚Â©cada', 'River Plate y Boca Juniors se enfrentarÃƒÆ’Ã‚Â¡n en una final a ida y vuelta que definirÃƒÆ’Ã‚Â¡ al campeÃƒÆ’Ã‚Â³n del torneo local.', '<p>River Plate y Boca Juniors se enfrentarÃƒÆ’Ã‚Â¡n en una final a ida y vuelta que definirÃƒÆ’Ã‚Â¡ al campeÃƒÆ’Ã‚Â³n del torneo local. La primera fecha se disputarÃƒÆ’Ã‚Â¡ en el Monumental y la vuelta en La Bombonera. Se espera una movilizaciÃƒÆ’Ã‚Â³n de mÃƒÆ’Ã‚Â¡s de 150.000 hinchas entre ambos partidos.</p>', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 1, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 6 HOUR), 5400, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000009', 'Festival internacional de cine: Argentina gana tres premios', 'festival-internacional-cine-argentina-gana-tres-premios', 'Las producciones nacionales brillan en el escenario global', 'Tres pelÃƒÆ’Ã‚Â­culas argentinas fueron premiadas en el Festival Internacional de Cine de BerlÃƒÆ’Ã‚Â­n, consolidando el crecimiento de la industria audiovisual nacional.', '<p>Tres pelÃƒÆ’Ã‚Â­culas argentinas fueron premiadas en el Festival Internacional de Cine de BerlÃƒÆ’Ã‚Â­n, incluyendo el codiciado Oso de Plata al mejor director. Las producciones nacionales fueron elogiadas por la crÃƒÆ’Ã‚Â­tica internacional por su originalidad narrativa y su compromiso social.</p>', 'a0000000-0000-0000-0000-000000000004', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 7 DAY), 920, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000010', 'Lanzamiento del primer satÃƒÆ’Ã‚Â©lite argentino de comunicaciones 5G', 'lanzamiento-primer-satelite-argentino-comunicaciones-5g', 'CONAE confirma ÃƒÆ’Ã‚Â³rbita exitosa', 'La ComisiÃƒÆ’Ã‚Â³n Nacional de Actividades Espaciales celebra el lanzamiento exitoso del ARSAT-4, diseÃƒÆ’Ã‚Â±ado para proveer cobertura 5G en zonas rurales.', '<p>La ComisiÃƒÆ’Ã‚Â³n Nacional de Actividades Espaciales (CONAE) celebra el lanzamiento exitoso del ARSAT-4, un satÃƒÆ’Ã‚Â©lite diseÃƒÆ’Ã‚Â±ado para proveer cobertura de comunicaciones 5G en zonas rurales del paÃƒÆ’Ã‚Â­s. El satÃƒÆ’Ã‚Â©lite fue puesto en ÃƒÆ’Ã‚Â³rbita desde la base de Kourou, en la Guayana Francesa.</p>', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 2 DAY), 3100, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000011', 'Reforma educativa: provincias acuerdan nuevo diseÃƒÆ’Ã‚Â±o curricular', 'reforma-educativa-provincias-acuerdan-nuevo-diseno-curricular', 'El plan incluye programaciÃƒÆ’Ã‚Â³n desde el nivel primario', 'El Consejo Federal de EducaciÃƒÆ’Ã‚Â³n aprobÃƒÆ’Ã‚Â³ por unanimidad un nuevo diseÃƒÆ’Ã‚Â±o curricular que incluye programaciÃƒÆ’Ã‚Â³n como materia obligatoria desde cuarto grado.', '<p>El Consejo Federal de EducaciÃƒÆ’Ã‚Â³n aprobÃƒÆ’Ã‚Â³ por unanimidad un nuevo diseÃƒÆ’Ã‚Â±o curricular que incluye programaciÃƒÆ’Ã‚Â³n como materia obligatoria desde cuarto grado. La implementaciÃƒÆ’Ã‚Â³n comenzarÃƒÆ’Ã‚Â¡ en 2027 y demandarÃƒÆ’Ã‚Â¡ la capacitaciÃƒÆ’Ã‚Â³n de mÃƒÆ’Ã‚Â¡s de 50.000 docentes en todo el paÃƒÆ’Ã‚Â­s.</p>', 'a0000000-0000-0000-0000-000000000005', 'draft', 0, 0, '10000000-0000-0000-0000-000000000006', NULL, 0, NOW(), NOW()),
    
    ('n0000000-0000-0000-0000-000000000012', 'OpiniÃƒÆ’Ã‚Â³n: El futuro de las criptomonedas en la regiÃƒÆ’Ã‚Â³n', 'opinion-futuro-criptomonedas-region', 'AnÃƒÆ’Ã‚Â¡lisis de las tendencias del mercado crypto latinoamericano', 'Las criptomonedas siguen ganando terreno en AmÃƒÆ’Ã‚Â©rica Latina. Analizamos las regulaciones emergentes y las oportunidades de inversiÃƒÆ’Ã‚Â³n.', '<p>Las criptomonedas siguen ganando terreno en AmÃƒÆ’Ã‚Â©rica Latina. Con la aprobaciÃƒÆ’Ã‚Â³n de marcos regulatorios en Brasil y Colombia, y el avance de proyectos de moneda digital en Argentina, la regiÃƒÆ’Ã‚Â³n se posiciona como uno de los mercados crypto de mayor crecimiento global.</p>', 'a0000000-0000-0000-0000-000000000004', 'approved', 0, 0, '10000000-0000-0000-0000-000000000006', NULL, 0, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `title` = VALUES(`title`),
      `slug` = VALUES(`slug`),
      `subtitle` = VALUES(`subtitle`),
      `excerpt` = VALUES(`excerpt`),
      `body` = VALUES(`body`),
      `author_id` = VALUES(`author_id`),
      `status` = VALUES(`status`),
      `featured` = VALUES(`featured`),
      `breaking` = VALUES(`breaking`),
      `created_by` = VALUES(`created_by`),
      `published_at` = VALUES(`published_at`),
      `view_count` = VALUES(`view_count`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- News-Categories associations
-- -----------------------------------------------------------
INSERT IGNORE INTO `news_categories` (`id`, `news_id`, `category_id`) VALUES
(UUID(), 'n0000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000001'),
(UUID(), 'n0000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000002'),
(UUID(), 'n0000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000001'),
(UUID(), 'n0000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000007'),
(UUID(), 'n0000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000006'),
(UUID(), 'n0000000-0000-0000-0000-000000000004', 'c0000000-0000-0000-0000-000000000003'),
(UUID(), 'n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000004'),
(UUID(), 'n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000008'),
(UUID(), 'n0000000-0000-0000-0000-000000000006', 'c0000000-0000-0000-0000-000000000006'),
(UUID(), 'n0000000-0000-0000-0000-000000000007', 'c0000000-0000-0000-0000-000000000002'),
(UUID(), 'n0000000-0000-0000-0000-000000000008', 'c0000000-0000-0000-0000-000000000003'),
(UUID(), 'n0000000-0000-0000-0000-000000000009', 'c0000000-0000-0000-0000-000000000005'),
(UUID(), 'n0000000-0000-0000-0000-000000000010', 'c0000000-0000-0000-0000-000000000004'),
(UUID(), 'n0000000-0000-0000-0000-000000000011', 'c0000000-0000-0000-0000-000000000006'),
(UUID(), 'n0000000-0000-0000-0000-000000000012', 'c0000000-0000-0000-0000-000000000008'),
(UUID(), 'n0000000-0000-0000-0000-000000000012', 'c0000000-0000-0000-0000-000000000002');

-- -----------------------------------------------------------
-- News-Tags associations
-- -----------------------------------------------------------
INSERT IGNORE INTO `news_tags` (`id`, `news_id`, `tag_id`) VALUES
(UUID(), 'n0000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000002'),
(UUID(), 'n0000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000006'),
(UUID(), 'n0000000-0000-0000-0000-000000000002', 't0000000-0000-0000-0000-000000000001'),
(UUID(), 'n0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000005'),
(UUID(), 'n0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000003'),
(UUID(), 'n0000000-0000-0000-0000-000000000005', 't0000000-0000-0000-0000-000000000004'),
(UUID(), 'n0000000-0000-0000-0000-000000000005', 't0000000-0000-0000-0000-000000000008'),
(UUID(), 'n0000000-0000-0000-0000-000000000006', 't0000000-0000-0000-0000-000000000005'),
(UUID(), 'n0000000-0000-0000-0000-000000000006', 't0000000-0000-0000-0000-000000000010'),
(UUID(), 'n0000000-0000-0000-0000-000000000007', 't0000000-0000-0000-0000-000000000002'),
(UUID(), 'n0000000-0000-0000-0000-000000000008', 't0000000-0000-0000-0000-000000000003'),
(UUID(), 'n0000000-0000-0000-0000-000000000008', 't0000000-0000-0000-0000-000000000010'),
(UUID(), 'n0000000-0000-0000-0000-000000000009', 't0000000-0000-0000-0000-000000000005'),
(UUID(), 'n0000000-0000-0000-0000-000000000010', 't0000000-0000-0000-0000-000000000004'),
(UUID(), 'n0000000-0000-0000-0000-000000000011', 't0000000-0000-0000-0000-000000000009'),
(UUID(), 'n0000000-0000-0000-0000-000000000012', 't0000000-0000-0000-0000-000000000007');

-- -----------------------------------------------------------
-- Ad Slots
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `ad_slots` LIMIT 1) THEN
    INSERT INTO `ad_slots` (`id`, `name`, `placement`, `type`, `content`, `target_url`, `active`, `created_at`, `updated_at`) VALUES
    (UUID(), 'Banner principal superior',   'home_main',      'external', '{"imageUrl":"/uploads/ads/banner-header.jpg","headline":"Cobertura especial","body":"Auspicia este espacio"}',   'https://example.com/promo1', 1, NOW(), NOW()),
    (UUID(), 'Banner lateral derecho',      'sidebar',        'external', '{"imageUrl":"/uploads/ads/banner-sidebar.jpg","headline":"Anuncio lateral","body":"Contenido patrocinado"}',        'https://example.com/promo2', 1, NOW(), NOW()),
    (UUID(), 'Banner entre artÃƒÆ’Ã‚Â­culos',      'article_inline', 'internal', '{"imageUrl":"/uploads/ads/banner-inline.jpg","headline":"Publicidad","body":"Espacio comercial"}',                    NULL,                         1, NOW(), NOW()),
    (UUID(), 'Banner pie de pÃƒÆ’Ã‚Â¡gina',        'list_inline',    'external', '{"imageUrl":"/uploads/ads/banner-footer.jpg","headline":"Promocion","body":"No te pierdas esta propuesta"}',          'https://example.com/promo3', 1, NOW(), NOW());
  END IF;

-- -----------------------------------------------------------
-- Newsletter Subscribers
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `newsletter_subscribers` LIMIT 1) THEN
    INSERT INTO `newsletter_subscribers` (`id`, `email`, `name`, `status`, `confirmed_at`, `created_at`, `updated_at`) VALUES
    (UUID(), 'lector1@gmail.com',    'Juan GarcÃƒÆ’Ã‚Â­a',      'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector2@gmail.com',    'MarÃƒÆ’Ã‚Â­a PÃƒÆ’Ã‚Â©rez',      'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector3@outlook.com',  'Pedro MartÃƒÆ’Ã‚Â­nez',   'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector4@yahoo.com',    'LucÃƒÆ’Ã‚Â­a GÃƒÆ’Ã‚Â³mez',      'pending',      NULL,  NOW(), NOW()),
    (UUID(), 'lector5@gmail.com',    'AndrÃƒÆ’Ã‚Â©s LÃƒÆ’Ã‚Â³pez',     'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector6@hotmail.com',  'Carolina DÃƒÆ’Ã‚Â­az',    'unsubscribed', NOW(), NOW(), NOW()),
    (UUID(), 'lector7@gmail.com',    'Fernando Ruiz',    'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector8@outlook.com',  'Gabriela Torres',  'active',       NOW(), NOW(), NOW()),
    (UUID(), 'lector9@gmail.com',    'Ricardo Herrera',  'pending',      NULL,  NOW(), NOW()),
    (UUID(), 'lector10@gmail.com',   'SofÃƒÆ’Ã‚Â­a Romero',     'active',       NOW(), NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `name` = VALUES(`name`),
      `status` = VALUES(`status`),
      `confirmed_at` = VALUES(`confirmed_at`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

-- -----------------------------------------------------------
-- Poll: Sample
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `polls` LIMIT 1) THEN
    INSERT INTO `polls` (`id`, `title`, `description`, `active`, `starts_at`, `ends_at`, `created_by`, `created_at`, `updated_at`) VALUES
    ('p0000000-0000-0000-0000-000000000001', 'Ãƒâ€šÃ‚Â¿CuÃƒÆ’Ã‚Â¡l es la temÃƒÆ’Ã‚Â¡tica que mÃƒÆ’Ã‚Â¡s te interesa?', 'Ayudanos a mejorar nuestro contenido seleccionando tus temas favoritos.', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), '10000000-0000-0000-0000-000000000001', NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `title` = VALUES(`title`),
      `description` = VALUES(`description`),
      `active` = VALUES(`active`),
      `starts_at` = VALUES(`starts_at`),
      `ends_at` = VALUES(`ends_at`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `poll_questions` LIMIT 1) THEN
    INSERT INTO `poll_questions` (`id`, `poll_id`, `text`, `type`, `sort_order`, `required`, `created_at`) VALUES
    ('pq000000-0000-0000-0000-000000000001', 'p0000000-0000-0000-0000-000000000001', 'Ãƒâ€šÃ‚Â¿QuÃƒÆ’Ã‚Â© secciÃƒÆ’Ã‚Â³n visitÃƒÆ’Ã‚Â¡s con mÃƒÆ’Ã‚Â¡s frecuencia?', 'single', 1, 1, NOW()),
    ('pq000000-0000-0000-0000-000000000002', 'p0000000-0000-0000-0000-000000000001', 'Ãƒâ€šÃ‚Â¿QuÃƒÆ’Ã‚Â© tipo de contenido preferÃƒÆ’Ã‚Â­s?', 'multiple', 2, 1, NOW())
    ON DUPLICATE KEY UPDATE
      `poll_id` = VALUES(`poll_id`),
      `text` = VALUES(`text`),
      `type` = VALUES(`type`),
      `sort_order` = VALUES(`sort_order`),
      `required` = VALUES(`required`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `poll_options` LIMIT 1) THEN
    INSERT INTO `poll_options` (`id`, `question_id`, `text`, `sort_order`, `created_at`) VALUES
    (UUID(), 'pq000000-0000-0000-0000-000000000001', 'PolÃƒÆ’Ã‚Â­tica',     1, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000001', 'EconomÃƒÆ’Ã‚Â­a',     2, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000001', 'Deportes',     3, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000001', 'TecnologÃƒÆ’Ã‚Â­a',   4, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000001', 'Cultura',      5, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000002', 'Noticias breves',  1, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000002', 'AnÃƒÆ’Ã‚Â¡lisis extenso', 2, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000002', 'Videos',           3, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000002', 'InfografÃƒÆ’Ã‚Â­as',      4, NOW()),
    (UUID(), 'pq000000-0000-0000-0000-000000000002', 'Entrevistas',      5, NOW());
  END IF;

-- -----------------------------------------------------------
-- Engagement Events (sample data)
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `engagement_events` LIMIT 1) THEN
    INSERT INTO `engagement_events` (`id`, `entity_id`, `entity_type`, `event_type`, `ip_address`, `created_at`) VALUES
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'news', 'view',  '192.168.1.10', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'news', 'view',  '192.168.1.11', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'news', 'share', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000004', 'news', 'view',  '10.0.0.5',     DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000004', 'news', 'view',  '10.0.0.6',     DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000008', 'news', 'view',  '172.16.0.1',   NOW()),
    (UUID(), 'n0000000-0000-0000-0000-000000000008', 'news', 'share', '172.16.0.1',   NOW()),
    (UUID(), 'n0000000-0000-0000-0000-000000000008', 'news', 'view',  '172.16.0.2',   NOW()),
    (UUID(), 'n0000000-0000-0000-0000-000000000010', 'news', 'view',  '192.168.1.20', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000005', 'news', 'view',  '10.0.0.15',    DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000005', 'news', 'click', '10.0.0.15',    DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000007', 'news', 'view',  '192.168.5.1',  DATE_SUB(NOW(), INTERVAL 1 DAY));
  END IF;

-- -----------------------------------------------------------
-- Post Status History (sample)
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `post_status_history` LIMIT 1) THEN
    INSERT INTO `post_status_history` (`id`, `news_id`, `from_status`, `to_status`, `changed_by`, `created_at`) VALUES
    (UUID(), 'n0000000-0000-0000-0000-000000000001', NULL,          'draft',     '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 5 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'draft',       'in_review', '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'in_review',   'approved',  '10000000-0000-0000-0000-000000000003', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000001', 'approved',    'published', '10000000-0000-0000-0000-000000000003', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000008', NULL,          'draft',     '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (UUID(), 'n0000000-0000-0000-0000-000000000008', 'draft',       'published', '10000000-0000-0000-0000-000000000001', DATE_SUB(NOW(), INTERVAL 6 HOUR));
  END IF;

-- -----------------------------------------------------------
-- Portal User Feature Test Data
-- Password for portal users: Portal123!
-- -----------------------------------------------------------
  IF TRUE THEN
    INSERT INTO `portal_users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
    ('50000000-0000-0000-0000-000000000001', 'lector.a@netxus.com', '$2y$10$Cttx4dAQDzPwviksi/BeAeYGSSai5425Alh/WbUL.pb4UoM0RAJFO', 'Lectora', 'A', 'Lectora A', 1, NOW(), NOW(), NOW()),
    ('50000000-0000-0000-0000-000000000002', 'lector.b@netxus.com', '$2y$10$Cttx4dAQDzPwviksi/BeAeYGSSai5425Alh/WbUL.pb4UoM0RAJFO', 'Lector', 'B', 'Lector B', 1, NOW(), NOW(), NOW()),
    ('50000000-0000-0000-0000-000000000003', 'lector.c@netxus.com', '$2y$10$Cttx4dAQDzPwviksi/BeAeYGSSai5425Alh/WbUL.pb4UoM0RAJFO', 'Lectora', 'C', 'Lectora C', 1, NOW(), NOW(), NOW()),
    ('50000000-0000-0000-0000-000000000004', 'lector.d@netxus.com', '$2y$10$Cttx4dAQDzPwviksi/BeAeYGSSai5425Alh/WbUL.pb4UoM0RAJFO', 'Lector', 'D', 'Lector D', 1, NOW(), NOW(), NOW())
    ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_preferences` LIMIT 1) THEN
    INSERT INTO `portal_user_preferences` (`id`, `portal_user_id`, `timezone`, `language`, `digest_frequency`, `personalization_opt_in`, `created_at`, `updated_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 'America/Argentina/Buenos_Aires', 'es', 'daily', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 'America/Argentina/Buenos_Aires', 'es', 'daily', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', 'America/Argentina/Buenos_Aires', 'es', 'weekly', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000004', 'America/Argentina/Buenos_Aires', 'es', 'none', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `timezone` = VALUES(`timezone`),
      `language` = VALUES(`language`),
      `digest_frequency` = VALUES(`digest_frequency`),
      `personalization_opt_in` = VALUES(`personalization_opt_in`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_favorite_categories` LIMIT 1) THEN
    INSERT INTO `portal_user_favorite_categories` (`id`, `portal_user_id`, `category_id`, `weight`, `created_at`, `updated_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000004', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000002', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000001', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `weight` = VALUES(`weight`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_favorite_tags` LIMIT 1) THEN
    INSERT INTO `portal_user_favorite_tags` (`id`, `portal_user_id`, `tag_id`, `weight`, `created_at`, `updated_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000004', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000007', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 't0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000001', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `weight` = VALUES(`weight`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_favorite_authors` LIMIT 1) THEN
    INSERT INTO `portal_user_favorite_authors` (`id`, `portal_user_id`, `author_id`, `weight`, `created_at`, `updated_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 'a0000000-0000-0000-0000-000000000002', 1, NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000001', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `weight` = VALUES(`weight`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_saved_posts` LIMIT 1) THEN
    INSERT INTO `portal_user_saved_posts` (`id`, `portal_user_id`, `news_id`, `saved_at`, `created_at`, `updated_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', NOW(), NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', NOW(), NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', NOW(), NOW(), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000001', NOW(), NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `saved_at` = VALUES(`saved_at`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `portal_user_interactions` LIMIT 1) THEN
    INSERT INTO `portal_user_interactions` (`id`, `portal_user_id`, `news_id`, `category_id`, `tag_id`, `author_id`, `action`, `context`, `time_spent_seconds`, `score_delta`, `metadata`, `created_at`) VALUES
    (UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'view_post', 'seed', 180, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'save_post', 'seed', 0, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', 'c0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000002', 'view_post', 'seed', 260, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
    (UUID(), '50000000-0000-0000-0000-000000000003', NULL, 'c0000000-0000-0000-0000-000000000001', NULL, NULL, 'click_category', 'seed', 0, 0, JSON_OBJECT('source','test_data_sql'), NOW());
  END IF;

-- -----------------------------------------------------------
-- Surveys
-- -----------------------------------------------------------
  IF NOT EXISTS (SELECT 1 FROM `surveys` LIMIT 1) THEN
    INSERT INTO `surveys` (`id`, `title`, `slug`, `description`, `initial_message`, `final_message`, `status`, `starts_at`, `ends_at`, `requires_login`, `allow_back_navigation`, `questions_per_view`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
    ('s0000000-0000-0000-0000-000000000001', 'Satisfaccion general del lector', 'satisfaccion-general-del-lector', 'Encuesta publica para medir experiencia de lectura.', 'Queremos saber como te sentis con la experiencia actual del portal.', 'Gracias por completar la encuesta. Tu feedback nos ayuda a mejorar.', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), 0, 1, 3, '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', NOW(), NOW()),
    ('s0000000-0000-0000-0000-000000000002', 'Preferencias editoriales del usuario', 'preferencias-editoriales-del-usuario', 'Encuesta publica con login para usuarios registrados.', 'Tu perfil nos ayuda a personalizar el contenido que ves.', 'Listo. Guardamos tus preferencias para futuras recomendaciones.', 'published', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 45 DAY), 1, 1, 2, '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', NOW(), NOW()),
    ('s0000000-0000-0000-0000-000000000003', 'Encuesta de prueba cerrada', 'encuesta-de-prueba-cerrada', 'Muestra de una encuesta ya finalizada.', 'Esta encuesta quedo cerrada para nuevas respuestas.', 'Gracias por tu participacion.', 'closed', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0, 0, 1, '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `title` = VALUES(`title`),
      `slug` = VALUES(`slug`),
      `description` = VALUES(`description`),
      `initial_message` = VALUES(`initial_message`),
      `final_message` = VALUES(`final_message`),
      `status` = VALUES(`status`),
      `starts_at` = VALUES(`starts_at`),
      `ends_at` = VALUES(`ends_at`),
      `requires_login` = VALUES(`requires_login`),
      `allow_back_navigation` = VALUES(`allow_back_navigation`),
      `questions_per_view` = VALUES(`questions_per_view`),
      `updated_by` = VALUES(`updated_by`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `survey_sections` LIMIT 1) THEN
    INSERT INTO `survey_sections` (`id`, `survey_id`, `title`, `description`, `sort_order`, `created_at`, `updated_at`) VALUES
    ('ss000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'Uso y costumbres', 'Primer bloque de experiencia general.', 1, NOW(), NOW()),
    ('ss000000-0000-0000-0000-000000000002', 's0000000-0000-0000-0000-000000000001', 'Cierre', 'Evaluacion final del portal.', 2, NOW(), NOW()),
    ('ss000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000002', 'Preferencias personales', 'Bloque unico con perfil editorial.', 1, NOW(), NOW()),
    ('ss000000-0000-0000-0000-000000000004', 's0000000-0000-0000-0000-000000000003', 'Encuesta cerrada', 'Seccion de referencia.', 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `survey_id` = VALUES(`survey_id`),
      `title` = VALUES(`title`),
      `description` = VALUES(`description`),
      `sort_order` = VALUES(`sort_order`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `survey_questions` LIMIT 1) THEN
    INSERT INTO `survey_questions` (`id`, `survey_id`, `section_id`, `question_text`, `help_text`, `type`, `is_required`, `sort_order`, `config`, `created_at`, `updated_at`) VALUES
    ('sq000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'Con que frecuencia lees Netxus?', 'Eleginos la opcion que mejor te describa.', 'single_choice', 1, 1, JSON_OBJECT('layout', 'vertical'), NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000002', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'Que tema te interesa mas?', NULL, 'multiple_choice', 0, 2, NULL, NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'Que tanto te gusta la navegacion actual?', '1 es poco y 5 es excelente.', 'numeric_scale', 1, 3, JSON_OBJECT('min', 1, 'max', 5), NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000004', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000002', 'Dejarias un comentario final?', NULL, 'long_text', 0, 1, NULL, NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000005', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'Cual es tu seccion favorita?', NULL, 'dropdown', 1, 1, NULL, NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000006', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'Que formato consumis mas?', NULL, 'long_text', 0, 2, NULL, NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000007', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'Cuantas noticias lees por dia?', NULL, 'numeric_scale', 1, 3, JSON_OBJECT('min', 1, 'max', 10), NOW(), NOW()),
    ('sq000000-0000-0000-0000-000000000008', 's0000000-0000-0000-0000-000000000003', 'ss000000-0000-0000-0000-000000000004', 'Seguira visible?', NULL, 'date', 0, 1, NULL, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `survey_id` = VALUES(`survey_id`),
      `section_id` = VALUES(`section_id`),
      `question_text` = VALUES(`question_text`),
      `help_text` = VALUES(`help_text`),
      `type` = VALUES(`type`),
      `is_required` = VALUES(`is_required`),
      `sort_order` = VALUES(`sort_order`),
      `config` = VALUES(`config`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `survey_question_options` LIMIT 1) THEN
    INSERT INTO `survey_question_options` (`id`, `question_id`, `label`, `value`, `sort_order`, `created_at`, `updated_at`) VALUES
    ('so000000-0000-0000-0000-000000000001', 'sq000000-0000-0000-0000-000000000001', 'Todos los dias', 'todos-los-dias', 1, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000002', 'sq000000-0000-0000-0000-000000000001', 'Varias veces por semana', 'varias-veces-por-semana', 2, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000003', 'sq000000-0000-0000-0000-000000000001', 'Una vez por semana', 'una-vez-por-semana', 3, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000004', 'sq000000-0000-0000-0000-000000000002', 'Noticias', 'noticias', 1, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000005', 'sq000000-0000-0000-0000-000000000002', 'Analisis', 'analisis', 2, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000006', 'sq000000-0000-0000-0000-000000000002', 'Entrevistas', 'entrevistas', 3, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000007', 'sq000000-0000-0000-0000-000000000005', 'Actualidad', 'actualidad', 1, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000008', 'sq000000-0000-0000-0000-000000000005', 'Economia', 'economia', 2, NOW(), NOW()),
    ('so000000-0000-0000-0000-000000000009', 'sq000000-0000-0000-0000-000000000005', 'Tecnologia', 'tecnologia', 3, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      `question_id` = VALUES(`question_id`),
      `label` = VALUES(`label`),
      `value` = VALUES(`value`),
      `sort_order` = VALUES(`sort_order`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `survey_responses` LIMIT 1) THEN
    INSERT INTO `survey_responses` (`id`, `survey_id`, `user_id`, `anonymous_key`, `status`, `current_section_id`, `completed_at`, `ip_hash`, `user_agent_hash`, `created_at`, `updated_at`) VALUES
    ('sr000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', NULL, 'survey-anon-lector-a', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), SHA1('192.168.1.10'), SHA1('Mozilla/5.0 Netxus Survey'), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    ('sr000000-0000-0000-0000-000000000002', 's0000000-0000-0000-0000-000000000001', NULL, 'survey-anon-lector-b', 'in_progress', 'ss000000-0000-0000-0000-000000000002', NULL, SHA1('192.168.1.11'), SHA1('Mozilla/5.0 Netxus Survey'), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('sr000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000002', '50000000-0000-0000-0000-000000000001', NULL, 'completed', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), SHA1('192.168.1.12'), SHA1('Mozilla/5.0 Netxus Survey'), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('sr000000-0000-0000-0000-000000000004', 's0000000-0000-0000-0000-000000000002', '50000000-0000-0000-0000-000000000002', NULL, 'in_progress', 'ss000000-0000-0000-0000-000000000003', NULL, SHA1('192.168.1.13'), SHA1('Mozilla/5.0 Netxus Survey'), DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR))
    ON DUPLICATE KEY UPDATE
      `survey_id` = VALUES(`survey_id`),
      `user_id` = VALUES(`user_id`),
      `anonymous_key` = VALUES(`anonymous_key`),
      `status` = VALUES(`status`),
      `current_section_id` = VALUES(`current_section_id`),
      `completed_at` = VALUES(`completed_at`),
      `ip_hash` = VALUES(`ip_hash`),
      `user_agent_hash` = VALUES(`user_agent_hash`),
      `updated_at` = VALUES(`updated_at`);
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `survey_answers` LIMIT 1) THEN
    INSERT INTO `survey_answers` (`id`, `survey_response_id`, `survey_id`, `section_id`, `question_id`, `value_text`, `value_json`, `created_at`, `updated_at`) VALUES
    (UUID(), 'sr000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'sq000000-0000-0000-0000-000000000001', 'varias-veces-por-semana', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'sq000000-0000-0000-0000-000000000002', NULL, JSON_ARRAY('noticias','tecnologia'), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000001', 'sq000000-0000-0000-0000-000000000003', '4', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000001', 's0000000-0000-0000-0000-000000000001', 'ss000000-0000-0000-0000-000000000002', 'sq000000-0000-0000-0000-000000000004', 'Me gusta la propuesta, aunque podria sumar mas filtros.', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'sq000000-0000-0000-0000-000000000005', 'actualidad', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'sq000000-0000-0000-0000-000000000006', 'Videos y analisis cortos', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (UUID(), 'sr000000-0000-0000-0000-000000000003', 's0000000-0000-0000-0000-000000000002', 'ss000000-0000-0000-0000-000000000003', 'sq000000-0000-0000-0000-000000000007', '6', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY))
    ON DUPLICATE KEY UPDATE
      `value_text` = VALUES(`value_text`),
      `value_json` = VALUES(`value_json`),
      `updated_at` = VALUES(`updated_at`);
  END IF;
END //
DELIMITER ;

CALL `seed_test_data`();
DROP PROCEDURE IF EXISTS `seed_test_data`;
