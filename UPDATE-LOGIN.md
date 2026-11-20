# Panduan Update Login UserGate

Dokumen ini menjelaskan cara memperbarui aplikasi ke versi yang sudah terintegrasi dengan UserGate dan RBAC lokal.

## 1. Backup

Sebelum update, backup database dan folder upload:

```bash
mysqldump -u profile -p db_ar_profile > backup-db-$(date +%F).sql
cp -r storage/app/public storage-backup-$(date +%F)
```

## 2. Update Kode

```bash
git pull
composer install --no-dev --optimize-autoloader
```

## 3. Konfigurasi `.env`

Tambahkan variabel berikut:

```env
USERGATE_ENABLED=true
USERGATE_BASE_URL=https://usergate.sandalgurun.web.id
USERGATE_API_URL=${USERGATE_BASE_URL}/api/v1
USERGATE_API_KEY=ug_xxxxxxxxxxxxxxxx
USERGATE_TIMEOUT=10
USERGATE_SYNC_INTERVAL=5
USERGATE_SUPER_ADMIN_ROLE=SUPER_ADMIN

LOCAL_SUPERADMIN_EMAIL=admin@company.com
LOCAL_SUPERADMIN_PASSWORD=password-yang-kuat
LOCAL_SUPERADMIN_ENABLED=true
```

Lalu hapus cache konfigurasi:

```bash
php artisan config:clear
```

## 4. Migrasi Database

Jalankan migrasi tanpa menghapus data lama:

```bash
php artisan migrate
```

Jangan gunakan `migrate:fresh` di production.

## 5. Seed Role dan Permission

```bash
php artisan db:seed --class=RolePermissionSeeder
```

## 6. Backfill User Lama

User lama akan menjadi `is_active = false` dan tanpa role. Aktifkan kembali user admin yang seharusnya tetap punya akses, misalnya:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'admin@company.com')->first();
$user->is_active = true;
$user->save();

$role = \App\Models\Role::where('slug', 'super_admin')->first();
$user->roles()->sync([$role->id]);
```

Ulangi untuk admin lain yang diperlukan.

## 7. Cek Akun Super Admin Lokal

Akun `admin@company.com` dibuat/diperbarui oleh seeder sesuai env:

- `LOCAL_SUPERADMIN_ENABLED=true` → aktif dan role `super_admin`.
- `LOCAL_SUPERADMIN_ENABLED=false` → nonaktif dan tanpa role.

## 8. Uji Login

1. Login dengan akun UserGate yang valid.
2. User pertama yang login akan menjadi super admin lokal jika belum ada super admin aktif.
3. User UserGate biasa akan dibuat nonaktif dan harus diaktifkan serta diberi role oleh super admin.
4. Uji bypass super admin UserGate lewat `POST /admin/usergate/sso` dengan `access_token`.
5. Uji fallback password lokal hanya untuk super admin saat UserGate tidak dapat dihubungi.

## 9. Rollback

Jika terjadi masalah:

```bash
php artisan migrate:rollback --step=5
```

Kembalikan database dari backup jika diperlukan.

## 10. Catatan Keamanan

- Ganti `LOCAL_SUPERADMIN_PASSWORD` sebelum production.
- Jangan commit API key UserGate ke repository.
- Pastikan HTTPS aktif di production.
- Endpoint login dan SSO diberi rate limit; jangan dinonaktifkan.
