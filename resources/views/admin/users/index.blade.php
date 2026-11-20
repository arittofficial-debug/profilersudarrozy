@extends('admin.layouts.app')

@section('title', 'Users')
@section('subtitle', 'Manajemen user lokal')

@section('content')
    <div class="table-container p-4">
        <table class="w-full text-sm text-white/80">
            <thead class="table-header">
                <tr>
                    <th class="text-left p-3">Nama</th>
                    <th class="text-left p-3">Email</th>
                    <th class="text-left p-3">Username</th>
                    <th class="text-left p-3">Roles</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Login Terakhir</th>
                    <th class="text-left p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-t border-white/10">
                        <td class="p-3 text-white">{{ $user->name }}</td>
                        <td class="p-3">{{ $user->email }}</td>
                        <td class="p-3">{{ $user->username }}</td>
                        <td class="p-3">
                            @foreach ($user->roles as $role)
                                <span class="px-2 py-1 rounded bg-indigo-500/20 text-indigo-300 text-xs">{{ $role->name }}</span>
                            @endforeach
                            @if ($user->roles->isEmpty())
                                <span class="text-white/40">-</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($user->is_active && $user->roles->isNotEmpty())
                                <span class="text-green-400">Aktif</span>
                            @else
                                <span class="text-red-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="p-3">{{ optional($user->last_login_at)->format('d M Y H:i') ?: '-' }}</td>
                        <td class="p-3">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-400 hover:text-blue-300">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 ml-3">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
@endsection