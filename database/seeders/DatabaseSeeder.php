<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        $email = config('usergate.local_super_admin.email');
        $password = config('usergate.local_super_admin.password');
        $enabled = (bool) config('usergate.local_super_admin.enabled');

        if ($email && $password !== '') {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Super Admin',
                    'username' => str_contains($email, '@') ? strstr($email, '@', true) : $email,
                    'password' => $password,
                ]
            );

            if (! $user->usergate_id) {
                $user->is_active = $enabled;
                $user->save();

                $superAdminRole = Role::where('slug', 'super_admin')->first();

                if ($enabled && $superAdminRole) {
                    $user->roles()->sync([$superAdminRole->id]);
                } else {
                    $user->roles()->detach();
                }
            }
        }

        $this->call([
            CompanySettingSeeder::class,
            DoctorSeeder::class,
            NewsSeeder::class,
            NewsImageSeeder::class,
            PkrsCategorySeeder::class,
            PkrsPostSeeder::class,
            PkrsGallerySeeder::class,
        ]);
    }
}
