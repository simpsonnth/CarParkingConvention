<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\PermissionRegistry;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public $search = '';

    public $userId = null;

    public $name = '';

    public $email = '';

    public $role = 'attendant';

    public $password = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public bool $modalOpen = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(string $value): void
    {
        if ($value === 'admin') {
            $this->selectedPermissions = [];
        }
    }

    public function render()
    {
        $query = User::query()->with('roles', 'permissions');

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.admin.users', [
            'users' => $query->latest()->paginate(10),
            'permissionGroups' => PermissionRegistry::groups(),
            'assignablePermissions' => PermissionRegistry::assignableToAttendant(),
        ]);
    }

    public function create(): void
    {
        $this->reset('userId', 'name', 'email', 'role', 'password', 'selectedPermissions');
        $this->role = 'attendant';
        $this->modalOpen = true;
    }

    public function edit(User $user): void
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->primaryRoleName();
        $this->password = '';
        $this->selectedPermissions = $user->getDirectPermissions()->pluck('name')->all();
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->userId)],
            'role' => 'required|in:admin,attendant',
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => ['string', Rule::in(PermissionRegistry::assignableToAttendant())],
        ];

        if (! $this->userId) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        $user->syncRoles([$this->role]);

        if ($this->role === 'admin') {
            $user->syncPermissions([]);
        } else {
            $user->syncPermissions($this->selectedPermissions);
        }

        $this->modalOpen = false;
        Flux::toast($this->userId ? 'User updated successfully.' : 'User created successfully.');
        $this->reset('userId', 'name', 'email', 'role', 'password', 'selectedPermissions');
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            Flux::toast('You cannot delete your own account.', variant: 'danger');

            return;
        }

        $user->delete();
        Flux::toast('User deleted successfully.');
    }
}
