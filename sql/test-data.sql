-- ============================================================
-- Netxus Portal News - Test Data
-- 2 usuarios por rol, 5+ artículos por redactor,
-- categorías, tags, autores, ads, encuestas, suscriptores
-- ============================================================

-- Prerequisite: Run schema.sql and seed-initial.sql first

-- -----------------------------------------------------------
-- Users: 2 editors + 2 writers (admin already seeded)
-- Password for all: Test1234!
-- -----------------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `email_verified`, `created_at`, `updated_at`) VALUES
('10000000-0000-0000-0000-000000000002', 'admin2@netxus.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos',  'Méndez',   'Carlos Méndez',   1, 1, NOW(), NOW()),
('10000000-0000-0000-0000-000000000003', 'editor1@netxus.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Laura',   'Giménez',  'Laura Giménez',   1, 1, NOW(), NOW()),
('10000000-0000-0000-0000-000000000004', 'editor2@netxus.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martín',  'López',    'Martín López',    1, 1, NOW(), NOW()),
('10000000-0000-0000-0000-000000000005', 'writer1@netxus.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana',     'Rodríguez','Ana Rodríguez',   1, 1, NOW(), NOW()),
('10000000-0000-0000-0000-000000000006', 'writer2@netxus.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diego',   'Fernández','Diego Fernández', 1, 1, NOW(), NOW());

-- Assign roles
INSERT INTO `user_roles` (`id`, `user_id`, `role_profile_id`, `created_at`) VALUES
('20000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000001', NOW()),
('20000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000003', '00000000-0000-0000-0000-000000000002', NOW()),
('20000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000002', NOW()),
('20000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000005', '00000000-0000-0000-0000-000000000003', NOW()),
('20000000-0000-0000-0000-000000000006', '10000000-0000-0000-0000-000000000006', '00000000-0000-0000-0000-000000000003', NOW());

-- -----------------------------------------------------------
-- Authors
-- -----------------------------------------------------------
INSERT INTO `authors` (`id`, `name`, `slug`, `bio`, `email`, `active`, `created_at`, `updated_at`) VALUES
('a0000000-0000-0000-0000-000000000001', 'María Belén Torres',   'maria-belen-torres',   'Periodista especializada en política y economía.',        'maria@netxus.com',  1, NOW(), NOW()),
('a0000000-0000-0000-0000-000000000002', 'Joaquín Pérez',        'joaquin-perez',         'Corresponsal de deportes y actualidad.',                  'joaquin@netxus.com',1, NOW(), NOW()),
('a0000000-0000-0000-0000-000000000003', 'Valentina Ruiz',       'valentina-ruiz',        'Especialista en tecnología y cultura digital.',           'vale@netxus.com',   1, NOW(), NOW()),
('a0000000-0000-0000-0000-000000000004', 'Roberto Sánchez',      'roberto-sanchez',       'Editor de opinión y análisis internacional.',             'roberto@netxus.com',1, NOW(), NOW()),
('a0000000-0000-0000-0000-000000000005', 'Camila Herrera',       'camila-herrera',        'Periodista de investigación y sociedad.',                 'camila@netxus.com', 1, NOW(), NOW());

-- -----------------------------------------------------------
-- Categories
-- -----------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `color`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
('c0000000-0000-0000-0000-000000000001', 'Política',     'politica',     'Noticias políticas nacionales e internacionales', '#E53E3E', 1, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000002', 'Economía',     'economia',     'Indicadores económicos, finanzas y mercados',     '#DD6B20', 2, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000003', 'Deportes',     'deportes',     'Fútbol, tenis, automovilismo y más',              '#38A169', 3, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000004', 'Tecnología',   'tecnologia',   'Innovación, gadgets y transformación digital',    '#3182CE', 4, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000005', 'Cultura',      'cultura',      'Arte, música, cine y espectáculos',               '#805AD5', 5, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000006', 'Sociedad',     'sociedad',     'Educación, salud, medio ambiente',                '#D69E2E', 6, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000007', 'Internacional','internacional','Noticias del mundo',                              '#2D3748', 7, 1, NOW(), NOW()),
('c0000000-0000-0000-0000-000000000008', 'Opinión',      'opinion',      'Columnas de opinión y editoriales',               '#718096', 8, 1, NOW(), NOW());

-- -----------------------------------------------------------
-- Tags
-- -----------------------------------------------------------
INSERT INTO `tags` (`id`, `name`, `slug`, `active`, `created_at`, `updated_at`) VALUES
('t0000000-0000-0000-0000-000000000001', 'Elecciones',     'elecciones',     1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000002', 'Inflación',      'inflacion',      1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000003', 'Fútbol',         'futbol',         1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000004', 'Inteligencia Artificial', 'inteligencia-artificial', 1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000005', 'Medio Ambiente', 'medio-ambiente', 1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000006', 'Reforma Laboral','reforma-laboral',1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000007', 'Criptomonedas',  'criptomonedas',  1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000008', 'Salud',          'salud',          1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000009', 'Educación',      'educacion',      1, NOW(), NOW()),
('t0000000-0000-0000-0000-000000000010', 'Urgente',        'urgente',        1, NOW(), NOW());

-- -----------------------------------------------------------
-- News: Writer 1 (Ana) - 6 articles
-- -----------------------------------------------------------
INSERT INTO `news` (`id`, `title`, `slug`, `subtitle`, `excerpt`, `body`, `author_id`, `status`, `featured`, `breaking`, `created_by`, `published_at`, `view_count`, `created_at`, `updated_at`) VALUES
('n0000000-0000-0000-0000-000000000001', 'El gobierno anuncia nuevas medidas económicas para el segundo semestre', 'gobierno-anuncia-nuevas-medidas-economicas-segundo-semestre', 'Plan de estabilización incluye recorte del gasto público', 'El Ministerio de Economía presentó un paquete de medidas que busca reducir el déficit fiscal en un 2% durante los próximos 6 meses.', '<p>El Ministerio de Economía presentó esta mañana un paquete integral de medidas que busca reducir el déficit fiscal en un 2% durante los próximos 6 meses. Entre las principales iniciativas se destacan la reducción del gasto corriente, la implementación de nuevos incentivos para la inversión extranjera y un plan de regularización impositiva.</p><p>El ministro destacó que estas medidas son necesarias para mantener la estabilidad macroeconómica lograda en el primer trimestre. "Estamos trabajando para consolidar un camino de crecimiento sostenible", afirmó en conferencia de prensa.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 2 DAY), 1450, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000002', 'Debate legislativo por la reforma del sistema previsional', 'debate-legislativo-reforma-sistema-previsional', 'Diputados inician sesiones especiales para tratar el proyecto', 'La Cámara de Diputados convocó a sesiones extraordinarias para debatir la reforma del sistema previsional que afecta a más de 8 millones de jubilados.', '<p>La Cámara de Diputados convocó a sesiones extraordinarias para debatir la reforma del sistema previsional que afecta a más de 8 millones de jubilados. El proyecto, que cuenta con dictamen de comisión, propone una nueva fórmula de movilidad indexada a la inflación y al crecimiento salarial.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 3 DAY), 890, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000003', 'Acuerdo histórico en la cumbre climática regional', 'acuerdo-historico-cumbre-climatica-regional', '15 países firman compromiso de reducción de emisiones', 'En la Cumbre Climática Regional celebrada en Santiago de Chile, 15 países del continente firmaron un acuerdo vinculante para reducir emisiones de carbono.', '<p>En la Cumbre Climática Regional celebrada en Santiago de Chile, 15 países del continente firmaron un acuerdo vinculante para reducir emisiones de carbono en un 35% para 2035. El acuerdo incluye mecanismos de financiamiento verde y un fondo solidario para naciones en desarrollo.</p>', 'a0000000-0000-0000-0000-000000000005', 'published', 0, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 5 DAY), 670, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000004', 'La selección se prepara para las eliminatorias', 'seleccion-prepara-eliminatorias', 'El DT convoca a 26 jugadores para la doble fecha', 'El director técnico de la selección nacional anunció la lista de convocados para la próxima doble fecha de eliminatorias sudamericanas.', '<p>El director técnico de la selección nacional anunció la lista de 26 convocados para la próxima doble fecha de eliminatorias sudamericanas. La principal novedad es la inclusión de dos juveniles del fútbol europeo que podrían debutar ante Colombia y Venezuela.</p>', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 1 DAY), 2200, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000005', 'Nuevo avance en inteligencia artificial aplicada a la medicina', 'nuevo-avance-inteligencia-artificial-medicina', 'Investigadores desarrollan sistema de detección temprana de cáncer', 'Un equipo de investigadores argentinos desarrolló un sistema basado en IA capaz de detectar tumores malignos con un 97% de precisión.', '<p>Un equipo de investigadores argentinos desarrolló un sistema basado en inteligencia artificial capaz de detectar tumores malignos con un 97% de precisión usando únicamente imágenes de tomografía. El proyecto, financiado por el CONICET, ya se encuentra en fase de pruebas en tres hospitales públicos.</p>', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 4 DAY), 1800, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000006', 'Crisis hídrica: advierten sobre la baja del río Paraná', 'crisis-hidrica-baja-rio-parana', 'Los niveles se encuentran en mínimos históricos', 'Especialistas alertan que el río Paraná registra niveles por debajo de los mínimos históricos, afectando el transporte fluvial y la provisión de agua potable.', '<p>Especialistas alertan que el río Paraná registra niveles por debajo de los mínimos históricos por tercer mes consecutivo. La situación impacta en el transporte de granos, la generación hidroeléctrica y la provisión de agua potable para millones de habitantes de la cuenca.</p>', 'a0000000-0000-0000-0000-000000000005', 'in_review', 0, 0, '10000000-0000-0000-0000-000000000005', NULL, 0, NOW(), NOW());

-- -----------------------------------------------------------
-- News: Writer 2 (Diego) - 6 articles
-- -----------------------------------------------------------
INSERT INTO `news` (`id`, `title`, `slug`, `subtitle`, `excerpt`, `body`, `author_id`, `status`, `featured`, `breaking`, `created_by`, `published_at`, `view_count`, `created_at`, `updated_at`) VALUES
('n0000000-0000-0000-0000-000000000007', 'Mercados: el dólar se estabiliza tras semanas de volatilidad', 'mercados-dolar-estabiliza-semanas-volatilidad', 'Analistas esperan que la tendencia se mantenga', 'Tras cuatro semanas de fuertes oscilaciones, el tipo de cambio paralelo se estabiliza en la zona de $1.250.', '<p>Tras cuatro semanas de fuertes oscilaciones, el tipo de cambio paralelo se estabiliza en la zona de $1.250. Los analistas consultados coinciden en que las recientes medidas del Banco Central lograron contener la presión cambiaria, aunque advierten que la estabilidad depende del cierre exitoso de las negociaciones con organismos internacionales.</p>', 'a0000000-0000-0000-0000-000000000001', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 1 DAY), 1350, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000008', 'River y Boca definen la final del campeonato local', 'river-boca-definen-final-campeonato-local', 'El superclásico más esperado de la década', 'River Plate y Boca Juniors se enfrentarán en una final a ida y vuelta que definirá al campeón del torneo local.', '<p>River Plate y Boca Juniors se enfrentarán en una final a ida y vuelta que definirá al campeón del torneo local. La primera fecha se disputará en el Monumental y la vuelta en La Bombonera. Se espera una movilización de más de 150.000 hinchas entre ambos partidos.</p>', 'a0000000-0000-0000-0000-000000000002', 'published', 1, 1, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 6 HOUR), 5400, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000009', 'Festival internacional de cine: Argentina gana tres premios', 'festival-internacional-cine-argentina-gana-tres-premios', 'Las producciones nacionales brillan en el escenario global', 'Tres películas argentinas fueron premiadas en el Festival Internacional de Cine de Berlín, consolidando el crecimiento de la industria audiovisual nacional.', '<p>Tres películas argentinas fueron premiadas en el Festival Internacional de Cine de Berlín, incluyendo el codiciado Oso de Plata al mejor director. Las producciones nacionales fueron elogiadas por la crítica internacional por su originalidad narrativa y su compromiso social.</p>', 'a0000000-0000-0000-0000-000000000004', 'published', 0, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 7 DAY), 920, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000010', 'Lanzamiento del primer satélite argentino de comunicaciones 5G', 'lanzamiento-primer-satelite-argentino-comunicaciones-5g', 'CONAE confirma órbita exitosa', 'La Comisión Nacional de Actividades Espaciales celebra el lanzamiento exitoso del ARSAT-4, diseñado para proveer cobertura 5G en zonas rurales.', '<p>La Comisión Nacional de Actividades Espaciales (CONAE) celebra el lanzamiento exitoso del ARSAT-4, un satélite diseñado para proveer cobertura de comunicaciones 5G en zonas rurales del país. El satélite fue puesto en órbita desde la base de Kourou, en la Guayana Francesa.</p>', 'a0000000-0000-0000-0000-000000000003', 'published', 1, 0, '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 2 DAY), 3100, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000011', 'Reforma educativa: provincias acuerdan nuevo diseño curricular', 'reforma-educativa-provincias-acuerdan-nuevo-diseno-curricular', 'El plan incluye programación desde el nivel primario', 'El Consejo Federal de Educación aprobó por unanimidad un nuevo diseño curricular que incluye programación como materia obligatoria desde cuarto grado.', '<p>El Consejo Federal de Educación aprobó por unanimidad un nuevo diseño curricular que incluye programación como materia obligatoria desde cuarto grado. La implementación comenzará en 2027 y demandará la capacitación de más de 50.000 docentes en todo el país.</p>', 'a0000000-0000-0000-0000-000000000005', 'draft', 0, 0, '10000000-0000-0000-0000-000000000006', NULL, 0, NOW(), NOW()),

('n0000000-0000-0000-0000-000000000012', 'Opinión: El futuro de las criptomonedas en la región', 'opinion-futuro-criptomonedas-region', 'Análisis de las tendencias del mercado crypto latinoamericano', 'Las criptomonedas siguen ganando terreno en América Latina. Analizamos las regulaciones emergentes y las oportunidades de inversión.', '<p>Las criptomonedas siguen ganando terreno en América Latina. Con la aprobación de marcos regulatorios en Brasil y Colombia, y el avance de proyectos de moneda digital en Argentina, la región se posiciona como uno de los mercados crypto de mayor crecimiento global.</p>', 'a0000000-0000-0000-0000-000000000004', 'approved', 0, 0, '10000000-0000-0000-0000-000000000006', NULL, 0, NOW(), NOW());

-- -----------------------------------------------------------
-- News-Categories associations
-- -----------------------------------------------------------
INSERT INTO `news_categories` (`id`, `news_id`, `category_id`) VALUES
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
INSERT INTO `news_tags` (`id`, `news_id`, `tag_id`) VALUES
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
INSERT INTO `ad_slots` (`id`, `name`, `placement`, `type`, `content`, `target_url`, `active`, `created_at`, `updated_at`) VALUES
(UUID(), 'Banner principal superior',   'header',  'image', '{"imageUrl":"/uploads/ads/banner-header.jpg","width":728,"height":90}',   'https://example.com/promo1', 1, NOW(), NOW()),
(UUID(), 'Banner lateral derecho',      'sidebar', 'image', '{"imageUrl":"/uploads/ads/banner-sidebar.jpg","width":300,"height":250}', 'https://example.com/promo2', 1, NOW(), NOW()),
(UUID(), 'Banner entre artículos',      'inline',  'html',  '{"html":"<div class=\"ad-inline\">Publicidad aquí</div>"}',                NULL,                         1, NOW(), NOW()),
(UUID(), 'Banner pie de página',        'footer',  'image', '{"imageUrl":"/uploads/ads/banner-footer.jpg","width":728,"height":90}',   'https://example.com/promo3', 1, NOW(), NOW());

-- -----------------------------------------------------------
-- Newsletter Subscribers
-- -----------------------------------------------------------
INSERT INTO `newsletter_subscribers` (`id`, `email`, `name`, `status`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(UUID(), 'lector1@gmail.com',    'Juan García',      'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector2@gmail.com',    'María Pérez',      'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector3@outlook.com',  'Pedro Martínez',   'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector4@yahoo.com',    'Lucía Gómez',      'pending',      NULL,  NOW(), NOW()),
(UUID(), 'lector5@gmail.com',    'Andrés López',     'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector6@hotmail.com',  'Carolina Díaz',    'unsubscribed', NOW(), NOW(), NOW()),
(UUID(), 'lector7@gmail.com',    'Fernando Ruiz',    'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector8@outlook.com',  'Gabriela Torres',  'active',       NOW(), NOW(), NOW()),
(UUID(), 'lector9@gmail.com',    'Ricardo Herrera',  'pending',      NULL,  NOW(), NOW()),
(UUID(), 'lector10@gmail.com',   'Sofía Romero',     'active',       NOW(), NOW(), NOW());

-- -----------------------------------------------------------
-- Poll: Sample
-- -----------------------------------------------------------
INSERT INTO `polls` (`id`, `title`, `description`, `active`, `starts_at`, `ends_at`, `created_by`, `created_at`, `updated_at`) VALUES
('p0000000-0000-0000-0000-000000000001', '¿Cuál es la temática que más te interesa?', 'Ayudanos a mejorar nuestro contenido seleccionando tus temas favoritos.', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), '10000000-0000-0000-0000-000000000001', NOW(), NOW());

INSERT INTO `poll_questions` (`id`, `poll_id`, `text`, `type`, `sort_order`, `required`, `created_at`) VALUES
('pq000000-0000-0000-0000-000000000001', 'p0000000-0000-0000-0000-000000000001', '¿Qué sección visitás con más frecuencia?', 'single', 1, 1, NOW()),
('pq000000-0000-0000-0000-000000000002', 'p0000000-0000-0000-0000-000000000001', '¿Qué tipo de contenido preferís?', 'multiple', 2, 1, NOW());

INSERT INTO `poll_options` (`id`, `question_id`, `text`, `sort_order`, `created_at`) VALUES
(UUID(), 'pq000000-0000-0000-0000-000000000001', 'Política',     1, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000001', 'Economía',     2, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000001', 'Deportes',     3, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000001', 'Tecnología',   4, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000001', 'Cultura',      5, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000002', 'Noticias breves',  1, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000002', 'Análisis extenso', 2, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000002', 'Videos',           3, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000002', 'Infografías',      4, NOW()),
(UUID(), 'pq000000-0000-0000-0000-000000000002', 'Entrevistas',      5, NOW());

-- -----------------------------------------------------------
-- Engagement Events (sample data)
-- -----------------------------------------------------------
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

-- -----------------------------------------------------------
-- Post Status History (sample)
-- -----------------------------------------------------------
INSERT INTO `post_status_history` (`id`, `news_id`, `from_status`, `to_status`, `changed_by`, `created_at`) VALUES
(UUID(), 'n0000000-0000-0000-0000-000000000001', NULL,          'draft',     '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(UUID(), 'n0000000-0000-0000-0000-000000000001', 'draft',       'in_review', '10000000-0000-0000-0000-000000000005', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(UUID(), 'n0000000-0000-0000-0000-000000000001', 'in_review',   'approved',  '10000000-0000-0000-0000-000000000003', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(UUID(), 'n0000000-0000-0000-0000-000000000001', 'approved',    'published', '10000000-0000-0000-0000-000000000003', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(UUID(), 'n0000000-0000-0000-0000-000000000008', NULL,          'draft',     '10000000-0000-0000-0000-000000000006', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(UUID(), 'n0000000-0000-0000-0000-000000000008', 'draft',       'published', '10000000-0000-0000-0000-000000000001', DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- -----------------------------------------------------------
-- Portal User Feature Test Data
-- Password for portal users: Portal123!
-- -----------------------------------------------------------
INSERT INTO `portal_users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `last_login_at`, `created_at`, `updated_at`) VALUES
('50000000-0000-0000-0000-000000000001', 'lector.a@netxus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lectora', 'A', 'Lectora A', 1, NOW(), NOW(), NOW()),
('50000000-0000-0000-0000-000000000002', 'lector.b@netxus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lector', 'B', 'Lector B', 1, NOW(), NOW(), NOW()),
('50000000-0000-0000-0000-000000000003', 'lector.c@netxus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lectora', 'C', 'Lectora C', 1, NOW(), NOW(), NOW()),
('50000000-0000-0000-0000-000000000004', 'lector.d@netxus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lector', 'D', 'Lector D', 1, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `portal_user_preferences` (`id`, `portal_user_id`, `timezone`, `language`, `digest_frequency`, `personalization_opt_in`, `created_at`, `updated_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 'America/Argentina/Buenos_Aires', 'es', 'daily', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 'America/Argentina/Buenos_Aires', 'es', 'daily', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', 'America/Argentina/Buenos_Aires', 'es', 'weekly', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000004', 'America/Argentina/Buenos_Aires', 'es', 'none', 1, NOW(), NOW());

INSERT INTO `portal_user_favorite_categories` (`id`, `portal_user_id`, `category_id`, `weight`, `created_at`, `updated_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000004', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000001', 'c0000000-0000-0000-0000-000000000002', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 'c0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', 'c0000000-0000-0000-0000-000000000001', 1, NOW(), NOW());

INSERT INTO `portal_user_favorite_tags` (`id`, `portal_user_id`, `tag_id`, `weight`, `created_at`, `updated_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000004', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000001', 't0000000-0000-0000-0000-000000000007', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 't0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000001', 1, NOW(), NOW());

INSERT INTO `portal_user_favorite_authors` (`id`, `portal_user_id`, `author_id`, `weight`, `created_at`, `updated_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 'a0000000-0000-0000-0000-000000000003', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 'a0000000-0000-0000-0000-000000000002', 1, NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000001', 1, NOW(), NOW());

INSERT INTO `portal_user_saved_posts` (`id`, `portal_user_id`, `news_id`, `saved_at`, `created_at`, `updated_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', NOW(), NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', NOW(), NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', NOW(), NOW(), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', 'n0000000-0000-0000-0000-000000000001', NOW(), NOW(), NOW());

INSERT INTO `portal_user_interactions` (`id`, `portal_user_id`, `news_id`, `category_id`, `tag_id`, `author_id`, `action`, `context`, `time_spent_seconds`, `score_delta`, `metadata`, `created_at`) VALUES
(UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000005', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'view_post', 'seed', 180, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000001', 'n0000000-0000-0000-0000-000000000010', 'c0000000-0000-0000-0000-000000000004', 't0000000-0000-0000-0000-000000000004', 'a0000000-0000-0000-0000-000000000003', 'save_post', 'seed', 0, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000002', 'n0000000-0000-0000-0000-000000000008', 'c0000000-0000-0000-0000-000000000003', 't0000000-0000-0000-0000-000000000003', 'a0000000-0000-0000-0000-000000000002', 'view_post', 'seed', 260, 0, JSON_OBJECT('source','test_data_sql'), NOW()),
(UUID(), '50000000-0000-0000-0000-000000000003', NULL, 'c0000000-0000-0000-0000-000000000001', NULL, NULL, 'click_category', 'seed', 0, 0, JSON_OBJECT('source','test_data_sql'), NOW());
