<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SystemController extends Controller
{
    public function index()
    {
        $payload = [
            'name'      => 'Netxus API',
            'version'   => 'v1',
            'status'    => 'online',
            'timestamp' => date(DATE_ATOM),
        ];

        if (ENVIRONMENT !== 'production') {
            $payload['environment'] = ENVIRONMENT;
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON($payload);
    }

    public function health()
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON(['status' => 'ok']);
    }

    public function methodNotAllowed()
    {
        $path = trim($this->request->getPath(), '/');

        $allow = match ($path) {
            'health' => 'GET, OPTIONS',
            default  => 'GET, OPTIONS',
        };

        return $this->response
            ->setStatusCode(405)
            ->setHeader('Allow', $allow)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Method Not Allowed',
            ]);
    }
}
