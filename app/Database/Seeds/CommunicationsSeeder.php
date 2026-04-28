<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CommunicationsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $templates = [
            [
                'id' => $this->uuid(),
                'key' => 'welcome_user',
                'name' => 'Bienvenida',
                'subject' => 'Bienvenido a {{site_name}}',
                'html_body' => $this->wrapEmail('Bienvenido', '<p>Hola {{user_name}}, gracias por sumarte a {{site_name}}.</p><p>Podés empezar en <a href="{{site_url}}">{{site_url}}</a>.</p>'),
                'text_body' => "Hola {{user_name}}, gracias por sumarte a {{site_name}}.\n\nEmpezar en {{site_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'password_reset',
                'name' => 'Recuperacion de contraseña',
                'subject' => 'Recuperar acceso a {{site_name}}',
                'html_body' => $this->wrapEmail('Recuperar contraseña', '<p>Hola {{user_name}}, recibimos una solicitud para cambiar tu contraseña.</p><p><a href="{{reset_url}}">Crear una nueva contraseña</a></p><p>Este enlace vence el {{expires_at}}.</p>'),
                'text_body' => "Hola {{user_name}},\n\nUsa este enlace para crear una nueva contraseña:\n{{reset_url}}\n\nVence el {{expires_at}}.",
                'variables_json' => json_encode(['user_name', 'user_email', 'reset_url', 'expires_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'newsletter_subscription',
                'name' => 'Suscripcion newsletter',
                'subject' => 'Tu suscripcion a {{site_name}} esta activa',
                'html_body' => $this->wrapEmail('Suscripcion confirmada', '<p>Gracias por suscribirte, {{user_name}}.</p><p>Vas a recibir novedades de {{site_name}} en tu correo.</p><p><a href="{{unsubscribe_url}}">Gestionar baja segura</a></p>'),
                'text_body' => "Gracias por suscribirte, {{user_name}}.\n\nVas a recibir novedades de {{site_name}} en tu correo.\nBaja segura: {{unsubscribe_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'unsubscribe_url', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'newsletter_news_digest',
                'name' => 'Digest de noticias',
                'subject' => 'Las noticias destacadas de {{site_name}}',
                'html_body' => $this->wrapEmail('Resumen de noticias', '<p>Hola {{user_name}}, estas son las noticias seleccionadas.</p>{{news_list}}<p><a href="{{unsubscribe_url}}">Cancelar suscripcion</a></p>'),
                'text_body' => "Hola {{user_name}}, estas son las noticias seleccionadas.\n\n{{news_list}}\n\nBaja segura: {{unsubscribe_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'newsletter_title', 'news_list', 'unsubscribe_url', 'portal_url', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'new_survey_available',
                'name' => 'Nueva encuesta disponible',
                'subject' => 'Nueva encuesta disponible en {{site_name}}',
                'html_body' => $this->wrapEmail('Nueva encuesta', '<p>Hola {{user_name}}, ya está disponible la encuesta <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Responder encuesta</a></p><p>{{survey_initial_message}}</p>'),
                'text_body' => "Hola {{user_name}}, ya está disponible la encuesta {{survey_title}}.\n\nResponder encuesta: {{survey_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'survey_title', 'survey_url', 'survey_initial_message', 'survey_ends_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'survey_incomplete_reminder',
                'name' => 'Recordatorio encuesta incompleta',
                'subject' => 'No dejes incompleta la encuesta {{survey_title}}',
                'html_body' => $this->wrapEmail('Recordatorio', '<p>Hola {{user_name}}, retomá tu avance en <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Continuar encuesta</a></p>'),
                'text_body' => "Hola {{user_name}}, retomá tu avance en {{survey_title}}.\n\nContinuar encuesta: {{survey_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'survey_title', 'survey_url', 'survey_initial_message', 'survey_ends_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'generic_notification',
                'name' => 'Notificacion generica',
                'subject' => '{{site_name}}',
                'html_body' => $this->wrapEmail('Notificacion', '<p>{{message}}</p>'),
                'text_body' => '{{message}}',
                'variables_json' => json_encode(['message', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($templates as $template) {
            $exists = $this->db->table('email_templates')->where('key', $template['key'])->countAllResults() > 0;
            if ($exists) {
                $this->db->table('email_templates')
                    ->where('key', $template['key'])
                    ->update([
                        'name' => $template['name'],
                        'subject' => $template['subject'],
                        'html_body' => $template['html_body'],
                        'text_body' => $template['text_body'],
                        'variables_json' => $template['variables_json'],
                        'is_active' => $template['is_active'],
                        'updated_at' => $now,
                    ]);
                continue;
            }

            $this->db->table('email_templates')->insert($template);
        }

        $configExists = $this->db->table('communication_settings')->where('scope', 'default')->countAllResults() > 0;
        if (!$configExists) {
            $this->db->table('communication_settings')->insert([
                'id' => $this->uuid(),
                'scope' => 'default',
                'public_config' => json_encode([
                    'email' => [
                        'provider' => 'smtp',
                        'fromAddress' => 'no-reply@netxus.com',
                        'fromName' => 'Netxus',
                        'replyTo' => 'soporte@netxus.com',
                        'sendEnabled' => false,
                        'testMode' => false,
                        'testEmail' => '',
                        'smtp' => [
                            'host' => '',
                            'port' => 587,
                            'encryption' => 'tls',
                        ],
                        'envialoSimple' => [
                            'accountId' => '',
                        ],
                    ],
                    'push' => [
                        'provider' => 'none',
                        'sendEnabled' => false,
                        'testMode' => false,
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'secret_config_encrypted' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function wrapEmail(string $title, string $body): string
    {
        return '<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px">'
            . '<h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>'
            . '<div style="font-size:16px;line-height:1.8;color:#374151">' . $body . '</div>'
            . '<p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p>'
            . '</div></div>';
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
