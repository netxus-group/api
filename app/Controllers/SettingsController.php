<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\UserSettingModel;
use App\Models\SettingModel;

class SettingsController extends BaseApiController
{
    /** GET /api/v1/settings/me — user-specific settings */
    public function mySettings()
    {
        $model    = new UserSettingModel();
        $settings = $model->where('user_id', $this->userId())->findAll();

        $result = [];
        foreach ($settings as $s) {
            $value = json_decode($s->value, true);
            $result[$s->key] = $value ?? $s->value;
        }

        return ApiResponse::ok($result);
    }

    /** PUT /api/v1/settings/me — update user settings */
    public function updateMySettings()
    {
        $data  = $this->getJsonInput();
        $model = new UserSettingModel();

        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $encoded = is_string($value) ? $value : json_encode($value);
            $model->upsert($this->userId(), $key, $encoded);
        }

        return ApiResponse::ok(null, 'Settings saved');
    }

    /** GET /api/v1/settings/global — system-wide settings (super_admin) */
    public function globalSettings()
    {
        $model    = new SettingModel();
        $settings = $model->getAll();
        return ApiResponse::ok($settings);
    }

    /** PUT /api/v1/settings/global — update system settings (super_admin) */
    public function updateGlobalSettings()
    {
        $data  = $this->getJsonInput();
        $model = new SettingModel();

        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $encoded = is_string($value) ? $value : json_encode($value);
            $model->set($key, $encoded);
        }

        return ApiResponse::ok(null, 'Global settings updated');
    }
}
