<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => null,
                'pin_hash' => Hash::make('000000'),
                'status' => 'active',
                'theme' => 'system',
                'pin_must_change' => true,
            ]
        );

        $superRole = Role::where('name', Role::SUPER_ADMIN)->first();
        if ($superRole && ! $admin->roles->contains($superRole->id)) {
            $admin->roles()->syncWithoutDetaching([$superRole->id]);
        }

        UserSetting::updateOrCreate(['user_id' => $admin->id], []);

        foreach (NotificationPreference::EVENT_TYPES as $event) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $admin->id, 'event_type' => $event],
                ['enabled' => true]
            );
        }
    }
}
