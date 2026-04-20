<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'active_flg' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => (int) $request->role,
            'active_flg' => (int) $request->active_flg,
        ]);

        event(new Registered($user));

        return redirect()->route('register.allshow')->with('message', 'ユーザーを作成しました');
    }

    public function index(): View
    {
        return view('auth.user-index', [
            'users' => User::query()->orderByDesc('active_flg')->orderBy('name')->orderBy('id')->get(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function show(string $id): RedirectResponse
    {
        return redirect()->route('register.edit', ['id' => $id]);
    }

    public function edit(string $id): View
    {
        return view('auth.user-edit', [
            'editUser' => User::findOrFail($id),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'active_flg' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = (int) $request->role;
        $user->active_flg = (int) $request->active_flg;
        $user->save();

        return redirect()->route('register.allshow')->with('message', 'ユーザー情報を更新しました');
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ((int) Auth::id() === (int) $user->id) {
            return redirect()->route('register.allshow')->with('error', 'ログイン中のユーザーはここから削除できません');
        }

        $message = '';

        DB::transaction(function () use ($user, &$message) {
            if ($this->hasRelatedRecords($user->id)) {
                $user->active_flg = 0;
                $user->save();
                $message = '関連データがあるため、ユーザーを無効化しました';

                return;
            }

            $user->delete();
            $message = 'ユーザーを削除しました';
        });

        return redirect()->route('register.allshow')->with('message', $message);
    }

    private function roleOptions(): array
    {
        return [
            1 => '一般',
            10 => '管理者',
        ];
    }

    private function hasRelatedRecords(int $userId): bool
    {
        return DB::table('payments')->where('user_id', $userId)->exists()
            || DB::table('batting_orders')->where('userId', $userId)->exists()
            || DB::table('batting_stats')->where('userId', $userId)->exists()
            || DB::table('steals')->where('userId', $userId)->exists()
            || DB::table('pitching_stats')->where('userId', $userId)->exists();
    }
}
