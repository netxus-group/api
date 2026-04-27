<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table         = 'user_roles';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['id', 'user_id', 'role_profile_id', 'created_at'];

    /**
     * Get roles for a user.
     */
    public function getUserRoles(string $userId): array
    {
        $rows = $this->db->table('user_roles ur')
            ->select('rp.name')
            ->join('role_profiles rp', 'rp.id = ur.role_profile_id', 'inner')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_column($rows, 'name')));
    }

    /**
     * Sync roles for a user (delete old, insert new).
     */
    public function syncRoles(string $userId, array $roles): void
    {
        $this->where('user_id', $userId)->delete();

        if (empty($roles)) {
            return;
        }

        $inputRoles = array_values(array_unique(array_filter(array_map('strval', $roles))));
        $profiles = $this->db->table('role_profiles')
            ->select('id, name')
            ->groupStart()
                ->whereIn('name', $inputRoles)
                ->orWhereIn('id', $inputRoles)
            ->groupEnd()
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($profiles as $profile) {
            $this->insert([
                'id'              => $this->uuid(),
                'user_id'         => $userId,
                'role_profile_id' => $profile['id'],
                'created_at'      => $now,
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
