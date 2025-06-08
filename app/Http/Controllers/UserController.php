<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('user.index', [
            'title' => 'User',
        ]);
    }

    public function create()
    {
        return view('user.create', [
            'title' => 'Buat User Baru',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'role'     => ['required', 'in:owner,admin'],
            'status'   => ['required', 'in:active,inactive'],
        ]);

        $data['password'] = Hash::make('12345678');

        $user = User::create($data);

        activity('user')
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'id_user'     => $user->id,
                'nama'        => $user->name,
                'username'    => $user->username,
                'role'        => $user->role,
                'status'      => $user->status,
            ])
            ->log("User #{$user->id} berhasil dibuat");

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dibuat dengan password default.');
    }

    public function edit(User $user)
    {
        return view('user.edit', [
            'user'  => $user,
            'title' => 'Edit User',
        ]);
    }

    public function update(Request $request, User $user)
    {
        // 1. Validasi input:
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'role'     => ['required', 'in:owner,admin'],
            'status'   => ['required', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8'],
        ];

        $data = $request->validate($rules);

        $lama = [
            'nama'     => $user->getOriginal('name'),
            'username' => $user->getOriginal('username'),
            'role'     => $user->getOriginal('role'),
            'status'   => $user->getOriginal('status'),
        ];

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        $baru = [
            'nama'     => $user->name,
            'username' => $user->username,
            'role'     => $user->role,
            'status'   => $user->status,
        ];

        activity('user')
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties([
                'id_user' => $user->id,
                'lama'    => $lama,
                'baru'    => $baru,
            ])
            ->log("User #{$user->id} berhasil diperbarui");

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }
}
