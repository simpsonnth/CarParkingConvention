<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Users</flux:heading>
        <flux:button variant="primary" wire:click="create">Add User</flux:button>
    </div>

    <div class="flex items-center justify-between gap-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search users..."
            class="max-w-xs" />
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Joined</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                {{ $user->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <flux:badge color="{{ $user->role === 'admin' ? 'purple' : 'blue' }}">
                                {{ ucfirst($user->role ?? 'attendant') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $user->id }})" icon="pencil">Edit</flux:menu.item>
                                    @if($user->id !== auth()->id())
                                        <flux:menu.item wire:click="delete({{ $user->id }})" icon="trash" variant="danger"
                                            wire:confirm="Are you sure you want to delete this user?">Delete</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-500">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $users->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $userId ? 'Edit User' : 'Add User' }}</flux:heading>
                <flux:subheading>Manage user details and roles.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" label="Name" placeholder="John Doe" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="john@example.com" />

                <div class="space-y-2">
                    <label for="role" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
                    <select wire:model="role" id="role"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <option value="attendant">Attendant</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <flux:input wire:model="password" label="Password" type="password"
                    placeholder="{{ $userId ? 'Leave blank to keep current' : 'Enter password' }}" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>