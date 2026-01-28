<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

class Users extends Component
{
    use WithPagination;

    public $search = '';
    public $userId = null;
    public $name = '';
    public $email = '';
    public $role = 'attendant';
    public $password = '';

    public bool $modalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.users', [
            'users' => $query->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $this->reset('userId', 'name', 'email', 'role', 'password');
        $this->modalOpen = true;
    }

    public function edit(User $user)
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?? 'attendant'; // Default to attendant if null
        $this->password = ''; // Don't show existing password
        $this->modalOpen = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->userId)],
            'role' => 'required|in:admin,attendant',
        ];

        if (!$this->userId) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
        } else {
            User::create($data);
        }

        $this->modalOpen = false;
        Flux::toast($this->userId ? 'User updated successfully.' : 'User created successfully.');
        $this->reset('userId', 'name', 'email', 'role', 'password');
    }

    public function delete(User $user)
    {
        if ($user->id === auth()->id()) {
            Flux::toast('You cannot delete yourself.', variant: 'danger');
            return;
        }

        $user->delete();
        Flux::toast('User deleted successfully.');
    }
}
