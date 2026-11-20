@extends('admin.layouts.app')

@section('title', 'Edit User')
@section('subtitle', 'Pengaturan akses user lokal')

@section('content')
    <div class="admin-card p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label">Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
            </div>

            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
            </div>

            <div>
                <label class="form-label">Username</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-input">
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="text-white/80">Aktif</label>
            </div>

            <div>
                <label class="form-label">Roles</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 text-white/80">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                {{ in_array($role->id, old('roles', $user->roles->pluck('id')->all())) ? 'checked' : '' }}>
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary px-6 py-3 rounded-xl text-white font-semibold">Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-3 rounded-xl text-white/70 hover:text-white">Kembali</a>
            </div>
        </form>
    </div>
@endsection