<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => 'Melihat dashboard admin',
            'doctors.view' => 'Melihat data dokter',
            'doctors.create' => 'Menambah dokter',
            'doctors.update' => 'Mengubah data dokter',
            'doctors.delete' => 'Menghapus dokter',
            'doctors.toggle_active' => 'Mengubah status aktif dokter',
            'news.view' => 'Melihat berita',
            'news.create' => 'Membuat berita',
            'news.update' => 'Mengubah berita',
            'news.delete' => 'Menghapus berita',
            'news.publish' => 'Mempublikasikan berita',
            'news.images.manage' => 'Mengelola gambar berita',
            'pkrs_categories.view' => 'Melihat kategori PKRS',
            'pkrs_categories.create' => 'Membuat kategori PKRS',
            'pkrs_categories.update' => 'Mengubah kategori PKRS',
            'pkrs_categories.delete' => 'Menghapus kategori PKRS',
            'pkrs_posts.view' => 'Melihat post PKRS',
            'pkrs_posts.create' => 'Membuat post PKRS',
            'pkrs_posts.update' => 'Mengubah post PKRS',
            'pkrs_posts.delete' => 'Menghapus post PKRS',
            'pkrs_posts.publish' => 'Mempublikasikan post PKRS',
            'pkrs_galleries.view' => 'Melihat galeri PKRS',
            'pkrs_galleries.create' => 'Menambah galeri PKRS',
            'pkrs_galleries.update' => 'Mengubah galeri PKRS',
            'pkrs_galleries.delete' => 'Menghapus galeri PKRS',
            'settings.view' => 'Melihat pengaturan situs',
            'settings.update' => 'Mengubah pengaturan situs',
            'users.view' => 'Melihat manajemen user',
            'users.create' => 'Membuat user lokal',
            'users.update' => 'Mengubah user lokal',
            'users.delete' => 'Menghapus user lokal',
            'users.assign_roles' => 'Menetapkan role user',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $roles = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Akses penuh terhadap sistem.',
                'is_system' => true,
                'permissions' => array_keys($permissions),
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Mengelola seluruh konten dan pengaturan, tanpa manajemen user.',
                'permissions' => [
                    'dashboard.view',
                    'doctors.view', 'doctors.create', 'doctors.update', 'doctors.delete', 'doctors.toggle_active',
                    'news.view', 'news.create', 'news.update', 'news.delete', 'news.publish', 'news.images.manage',
                    'pkrs_categories.view', 'pkrs_categories.create', 'pkrs_categories.update', 'pkrs_categories.delete',
                    'pkrs_posts.view', 'pkrs_posts.create', 'pkrs_posts.update', 'pkrs_posts.delete', 'pkrs_posts.publish',
                    'pkrs_galleries.view', 'pkrs_galleries.create', 'pkrs_galleries.update', 'pkrs_galleries.delete',
                    'settings.view',
                ],
            ],
            'editor' => [
                'name' => 'Editor',
                'description' => 'Membuat dan mengubah konten, tanpa publikasi.',
                'permissions' => [
                    'dashboard.view',
                    'news.view', 'news.create', 'news.update', 'news.images.manage',
                    'pkrs_posts.view', 'pkrs_posts.create', 'pkrs_posts.update',
                    'pkrs_galleries.view', 'pkrs_galleries.create', 'pkrs_galleries.update',
                ],
            ],
            'publisher' => [
                'name' => 'Publisher',
                'description' => 'Memverifikasi dan mempublikasikan konten.',
                'permissions' => [
                    'dashboard.view',
                    'news.view', 'news.publish',
                    'pkrs_posts.view', 'pkrs_posts.publish',
                    'doctors.view', 'doctors.toggle_active',
                ],
            ],
            'operator_dokter' => [
                'name' => 'Operator Dokter',
                'description' => 'Mengelola data dokter dan jadwal.',
                'permissions' => [
                    'dashboard.view',
                    'doctors.view', 'doctors.create', 'doctors.update', 'doctors.delete', 'doctors.toggle_active',
                ],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Hanya melihat data.',
                'permissions' => [
                    'dashboard.view',
                    'doctors.view', 'news.view', 'pkrs_posts.view', 'pkrs_categories.view', 'pkrs_galleries.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'description' => $data['description'], 'is_system' => $data['is_system'] ?? false]
            );

            $permissionIds = Permission::whereIn('slug', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
