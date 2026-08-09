<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetAdministratorPasswordRequest;
use App\Http\Requests\Admin\StoreAdministratorRequest;
use App\Http\Requests\Admin\UpdateAdministratorRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AdministratorCreated;
use App\Notifications\PasswordChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    /**
     * Guidance Administrator directory — list, search, filter, paginate.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', UserRole::Counselor)
            ->with('counselorProfile');

        $status = $request->query('status');

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $administrators = $query->orderBy('name')->paginate(10)->withQueryString();

        $stats = [
            'total' => User::where('role', UserRole::Counselor)->count(),
            'active' => User::where('role', UserRole::Counselor)->where('status', UserStatus::Active)->count(),
            'inactive' => User::where('role', UserRole::Counselor)->where('status', UserStatus::Inactive)->count(),
        ];

        return view('admin.administrators.index', [
            'administrators' => $administrators,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('admin.administrators.create');
    }

    /**
     * Store a new guidance administrator (User + CounselorProfile).
     */
    public function store(StoreAdministratorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $admin = DB::transaction(function () use ($data) {
            $admin = User::create([
                'school_id' => $data['school_id'],
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => UserRole::Counselor,
                'status' => UserStatus::Active,
            ]);

            $admin->counselorProfile()->create([
                'position_title' => $data['position_title'] ?? null,
            ]);

            return $admin;
        });

        $this->logAudit('administrator.created', $admin, "Created guidance administrator account for {$admin->name} ({$admin->school_id}).");

        User::where('role', UserRole::SuperAdmin)
            ->where('id', '!=', auth()->id())
            ->get()
            ->each->notify(new AdministratorCreated($admin));

        return redirect()
            ->route('admin.administrators.index')
            ->with('status', "{$admin->name} was added successfully.");
    }

    /**
     * Show the edit form.
     */
    public function edit(User $administrator): View
    {
        $this->ensureIsAdministrator($administrator);

        $administrator->load('counselorProfile');

        return view('admin.administrators.edit', [
            'administrator' => $administrator,
        ]);
    }

    /**
     * Update an existing guidance administrator.
     */
    public function update(UpdateAdministratorRequest $request, User $administrator): RedirectResponse
    {
        $this->ensureIsAdministrator($administrator);

        $data = $request->validated();

        DB::transaction(function () use ($data, $administrator) {
            $administrator->fill([
                'name' => $data['name'],
                'school_id' => $data['school_id'],
                'email' => $data['email'] ?? null,
            ])->save();

            $administrator->counselorProfile()->updateOrCreate(
                ['user_id' => $administrator->id],
                ['position_title' => $data['position_title'] ?? null]
            );
        });

        $this->logAudit('administrator.updated', $administrator, "Updated account details for {$administrator->name} ({$administrator->school_id}).");

        return redirect()
            ->route('admin.administrators.index')
            ->with('status', "{$administrator->name}'s account was updated.");
    }

    /**
     * Activate an administrator account (re-enables login).
     */
    public function activate(User $administrator): RedirectResponse
    {
        $this->ensureIsAdministrator($administrator);

        $administrator->update(['status' => UserStatus::Active]);

        $this->logAudit('administrator.activated', $administrator, "Activated account for {$administrator->name} ({$administrator->school_id}).");

        return redirect()
            ->route('admin.administrators.index')
            ->with('status', "{$administrator->name}'s account was activated.");
    }

    /**
     * Deactivate an administrator account (blocks login).
     */
    public function deactivate(User $administrator): RedirectResponse
    {
        $this->ensureIsAdministrator($administrator);

        $administrator->update(['status' => UserStatus::Inactive]);

        $this->logAudit('administrator.deactivated', $administrator, "Deactivated account for {$administrator->name} ({$administrator->school_id}).");

        return redirect()
            ->route('admin.administrators.index')
            ->with('status', "{$administrator->name}'s account was deactivated.");
    }

    /**
     * Reset an administrator's password.
     */
    public function resetPassword(ResetAdministratorPasswordRequest $request, User $administrator): RedirectResponse
    {
        $this->ensureIsAdministrator($administrator);

        $administrator->forceFill([
            'password' => Hash::make($request->validated()['password']),
            'password_changed_at' => now(),
            'must_change_password' => true,
        ])->save();

        $this->logAudit('administrator.password_reset', $administrator, "Reset password for {$administrator->name} ({$administrator->school_id}).");

        $administrator->notify(new PasswordChanged());

        return redirect()
            ->route('admin.administrators.index')
            ->with('status', "{$administrator->name}'s password was reset. They'll be asked to set a new one on their next login.");
    }

    /**
     * Guard against operating on a non-counselor user via this controller.
     */
    protected function ensureIsAdministrator(User $administrator): void
    {
        abort_unless($administrator->role === UserRole::Counselor, 404);
    }

    protected function logAudit(string $action, User $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => User::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
