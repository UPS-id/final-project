<?php

use App\Models\Todo;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    // ─── Form State ───────────────────────────────────────────────
    public string $newTitle = '';
    public string $newPriority = 'medium';
    public string $newDueAt = '';
    public string $newRemindAt = '';

    // ─── Filter State ─────────────────────────────────────────────
    public string $filter = 'all'; // all | active | done

    // ─── Edit State ───────────────────────────────────────────────
    public ?int $editId = null;
    public string $editTitle = '';
    public string $editPriority = 'medium';
    public string $editDueAt = '';
    public string $editRemindAt = '';

    // ─── Toast ────────────────────────────────────────────────────
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function showToast(string $message, string $type = 'success'): void
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->dispatch('todo-toast-shown');
    }

    // ─── Create ───────────────────────────────────────────────────
    public function addTodo(): void
    {
        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newPriority' => 'required|in:low,medium,high',
            'newDueAt' => 'nullable|date',
            'newRemindAt' => 'nullable|date',
        ]);

        Auth::user()
            ->todos()
            ->create([
                'title' => $this->newTitle,
                'priority' => $this->newPriority,
                'due_at' => $this->newDueAt ?: null,
                'reminder_at' => $this->newRemindAt ?: null,
            ]);

        $this->reset('newTitle', 'newPriority', 'newDueAt', 'newRemindAt');
        $this->showToast('Todo added!', 'success');
    }

    // ─── Toggle Done ──────────────────────────────────────────────
    public function toggleDone(int $id): void
    {
        $todo = Todo::where('user_id', Auth::id())->find($id);
        if ($todo) {
            $todo->update(['is_done' => !$todo->is_done]);
            $this->showToast($todo->is_done ? 'Marked as done! 🎉' : 'Marked as active', 'success');
        }
    }

    // ─── Delete ───────────────────────────────────────────────────
    public function deleteTodo(int $id): void
    {
        Todo::where('user_id', Auth::id())->find($id)?->delete();
        $this->showToast('Todo deleted.', 'danger');
    }

    // ─── Edit ─────────────────────────────────────────────────────
    public function startEdit(int $id): void
    {
        $todo = Todo::where('user_id', Auth::id())->find($id);
        if ($todo) {
            $this->editId = $todo->id;
            $this->editTitle = $todo->title;
            $this->editPriority = $todo->priority;
            $this->editDueAt = $todo->due_at ? $todo->due_at->format('Y-m-d\TH:i') : '';
            $this->editRemindAt = $todo->reminder_at ? $todo->reminder_at->format('Y-m-d\TH:i') : '';
        }
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editPriority' => 'required|in:low,medium,high',
            'editDueAt' => 'nullable|date',
            'editRemindAt' => 'nullable|date',
        ]);

        $todo = Todo::where('user_id', Auth::id())->find($this->editId);
        if ($todo) {
            $todo->update([
                'title' => $this->editTitle,
                'priority' => $this->editPriority,
                'due_at' => $this->editDueAt ?: null,
                'reminder_at' => $this->editRemindAt ?: null,
            ]);
            $this->showToast('Todo updated!', 'success');
        }
        $this->reset('editId', 'editTitle', 'editPriority', 'editDueAt', 'editRemindAt');
    }

    public function cancelEdit(): void
    {
        $this->reset('editId', 'editTitle', 'editPriority', 'editDueAt', 'editRemindAt');
    }

    // ─── Data ─────────────────────────────────────────────────────
    public function with(): array
    {
        $query = Todo::where('user_id', Auth::id())->latest();

        if ($this->filter === 'active') {
            $query->where('is_done', false);
        } elseif ($this->filter === 'done') {
            $query->where('is_done', true);
        }

        $todos = $query->get();
        $allCount = Todo::where('user_id', Auth::id())->count();
        $activeCount = Todo::where('user_id', Auth::id())->where('is_done', false)->count();
        $doneCount = Todo::where('user_id', Auth::id())->where('is_done', true)->count();

        // Pass upcoming reminders as JSON for the JS notification engine
        $upcomingReminders = Todo::where('user_id', Auth::id())
            ->where('is_done', false)
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '>=', now())
            ->where('reminder_at', '<=', now()->addHours(24))
            ->get(['id', 'title', 'reminder_at'])
            ->map(
                fn($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'reminder_at' => $t->reminder_at->timestamp * 1000, // JS ms timestamp
                ],
            );

        return compact('todos', 'allCount', 'activeCount', 'doneCount', 'upcomingReminders');
    }
};
?>

<div class="flex flex-col h-full" x-data="{
    toastOpen: false,
    toastMessage: '',
    toastType: 'success',
    showAddForm: false,

    // ── Notification engine ──────────────────────────────────
    reminders: {{ json_encode($upcomingReminders) }},
    scheduledIds: JSON.parse(localStorage.getItem('mn_notified') ?? '[]'),

    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    },

    scheduleReminders() {
        this.reminders.forEach(r => {
            if (this.scheduledIds.includes(r.id)) return;
            const delay = r.reminder_at - Date.now();
            if (delay > 0 && delay < 86400000) {
                setTimeout(() => {
                    if (Notification.permission === 'granted') {
                        new Notification('⏰ Note.ed Reminder', {
                            body: r.title,
                            icon: '/favicon.ico',
                        });
                    }
                    this.scheduledIds.push(r.id);
                    localStorage.setItem('mn_notified', JSON.stringify(this.scheduledIds));
                }, delay);
            }
        });
    },

    init() {
        this.requestNotificationPermission();
        this.scheduleReminders();
    }
}"
    @todo-toast-shown.window="
         toastMessage = $wire.toastMessage;
         toastType    = $wire.toastType;
         toastOpen    = true;
         setTimeout(() => toastOpen = false, 3000);
     ">

    {{-- ── Toast ──────────────────────────────────────────────────── --}}
    <div x-show="toastOpen" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200 opacity-0"
        class="fixed right-5 top-5 z-50 flex items-center gap-3 rounded-xl px-4 py-3 shadow-2xl border"
        :class="toastType === 'success'
            ?
            'bg-white dark:bg-zinc-900 border-brand-green/40 text-brand-green' :
            'bg-white dark:bg-zinc-900 border-brand-red/40 text-brand-red'"
        style="display:none;">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <template x-if="toastType === 'success'">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </template>
            <template x-if="toastType !== 'success'">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </template>
        </svg>
        <span class="text-sm font-semibold" x-text="toastMessage"></span>
    </div>

    {{-- ── Header: Filters + Add button ──────────────────────────── --}}
    <div
        class="px-5 py-4 bg-zinc-50/50 dark:bg-zinc-950/20 border-b border-zinc-200 dark:border-zinc-800 flex flex-col gap-3">

        {{-- Title row --}}
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">My Todos</h2>
            <button @click="showAddForm = !showAddForm"
                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-brand-green px-4 text-sm font-semibold text-white shadow-lg shadow-brand-green/20 transition-all hover:bg-brand-green/90 active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span x-text="showAddForm ? 'Cancel' : 'Add Todo'"></span>
            </button>
        </div>

        {{-- Filter tabs --}}
        <div class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-800/60 rounded-xl w-full">
            <button wire:click="$set('filter','all')"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all
                           {{ $filter === 'all' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-50 shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                All <span class="ml-0.5 opacity-70">({{ $allCount }})</span>
            </button>
            <button wire:click="$set('filter','active')"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all
                           {{ $filter === 'active' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-50 shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                Active <span class="ml-0.5 opacity-70">({{ $activeCount }})</span>
            </button>
            <button wire:click="$set('filter','done')"
                class="flex-1 py-1.5 rounded-lg text-xs font-bold transition-all
                           {{ $filter === 'done' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-50 shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                Done <span class="ml-0.5 opacity-70">({{ $doneCount }})</span>
            </button>
        </div>
    </div>

    {{-- ── Add Todo Form (slide-down) ─────────────────────────────── --}}
    <div x-show="showAddForm" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-brand-green/5" style="display:none;">
        <form wire:submit.prevent="addTodo" class="space-y-3">
            {{-- Title --}}
            <div>
                <input wire:model="newTitle" type="text" placeholder="What needs to be done? ✏️"
                    class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 px-4 text-sm text-zinc-900 placeholder-zinc-400 shadow-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50 dark:placeholder-zinc-500" />
                @error('newTitle')
                    <p class="mt-1 text-xs text-brand-red font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- Priority chips --}}
            <div class="flex gap-2">
                <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 self-center mr-1">Priority:</span>
                @foreach (['low' => ['🟢', 'brand-green'], 'medium' => ['🟡', 'brand-yellow'], 'high' => ['🔴', 'brand-red']] as $p => [$emoji, $color])
                    <label
                        class="flex items-center gap-1 cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold border transition-all
                                  {{ $newPriority === $p ? "border-{$color} bg-{$color}/10 text-{$color}" : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                        <input type="radio" wire:model="newPriority" value="{{ $p }}" class="sr-only" />
                        {{ $emoji }} {{ ucfirst($p) }}
                    </label>
                @endforeach
            </div>

            {{-- Due + Reminder --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wide mb-1">Due
                        date</label>
                    <input wire:model="newDueAt" type="datetime-local"
                        class="w-full rounded-lg border border-zinc-200 bg-white py-2 px-3 text-xs text-zinc-700 focus:border-brand-green focus:outline-none focus:ring-1 focus:ring-brand-green/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-400 uppercase tracking-wide mb-1">🔔 Remind me
                        at</label>
                    <input wire:model="newRemindAt" type="datetime-local"
                        class="w-full rounded-lg border border-zinc-200 bg-white py-2 px-3 text-xs text-zinc-700 focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300" />
                </div>
            </div>

            <button type="submit"
                class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-green px-5 text-sm font-bold text-white shadow-lg shadow-brand-green/25 hover:bg-brand-green/90 active:scale-[.98] transition-all">
                ✅ Add Todo
            </button>
        </form>
    </div>

    {{-- ── Todo List ───────────────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/50">
        @forelse($todos as $todo)
            <div
                class="group p-4 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-900/40
                        {{ $todo->is_done ? 'opacity-60' : '' }}
                        {{ $todo->isOverdue() ? 'border-l-4 border-brand-red' : '' }}">

                @if ($editId === $todo->id)
                    {{-- ── Inline Edit Form ─────────────────────────────── --}}
                    <form wire:submit.prevent="saveEdit" class="space-y-2">
                        <input wire:model="editTitle" type="text"
                            class="w-full rounded-lg border border-zinc-200 bg-white py-2 px-3 text-sm text-zinc-900 focus:border-brand-blue focus:outline-none focus:ring-2 focus:ring-brand-blue/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50" />
                        @error('editTitle')
                            <p class="text-xs text-brand-red font-semibold">{{ $message }}</p>
                        @enderror
                        <div class="flex gap-1">
                            @foreach (['low' => ['🟢', 'brand-green'], 'medium' => ['🟡', 'brand-yellow'], 'high' => ['🔴', 'brand-red']] as $p => [$emoji, $color])
                                <label
                                    class="flex items-center gap-1 cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold border transition-all
                                              {{ $editPriority === $p ? "border-{$color} bg-{$color}/10 text-{$color}" : 'border-zinc-200 dark:border-zinc-700 text-zinc-500' }}">
                                    <input type="radio" wire:model="editPriority" value="{{ $p }}"
                                        class="sr-only" />
                                    {{ $emoji }} {{ ucfirst($p) }}
                                </label>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input wire:model="editDueAt" type="datetime-local"
                                class="w-full rounded-lg border border-zinc-200 bg-white py-1.5 px-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
                            <input wire:model="editRemindAt" type="datetime-local"
                                class="w-full rounded-lg border border-zinc-200 bg-white py-1.5 px-2 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="submit"
                                class="flex-1 h-8 rounded-lg bg-brand-blue text-xs font-bold text-white shadow-md shadow-brand-blue/25 hover:bg-brand-blue/90 active:scale-95 transition-all">
                                Save
                            </button>
                            <button type="button" wire:click="cancelEdit"
                                class="flex-1 h-8 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-semibold text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                @else
                    {{-- ── Normal Row ────────────────────────────────────── --}}
                    <div class="flex items-start gap-3">
                        {{-- Checkbox --}}
                        <button wire:click="toggleDone({{ $todo->id }})"
                            class="mt-0.5 shrink-0 h-5 w-5 rounded-full border-2 flex items-center justify-center transition-all
                                       {{ $todo->is_done
                                           ? 'bg-brand-green border-brand-green text-white'
                                           : 'border-zinc-300 dark:border-zinc-600 hover:border-brand-green' }}">
                            @if ($todo->is_done)
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 leading-snug
                                      {{ $todo->is_done ? 'line-through text-zinc-400 dark:text-zinc-500' : '' }}">
                                {{ $todo->title }}
                            </p>

                            {{-- Meta row --}}
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                {{-- Priority badge --}}
                                @php
                                    $priorityConfig = [
                                        'high' => ['🔴', 'text-brand-red   bg-brand-red/10   border-brand-red/25'],
                                        'medium' => [
                                            '🟡',
                                            'text-brand-yellow bg-brand-yellow/10 border-brand-yellow/25',
                                        ],
                                        'low' => ['🟢', 'text-brand-green  bg-brand-green/10  border-brand-green/25'],
                                    ];
                                    [$pIcon, $pClass] = $priorityConfig[$todo->priority];
                                @endphp
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $pClass }}">
                                    {{ $pIcon }} {{ ucfirst($todo->priority) }}
                                </span>

                                {{-- Due date --}}
                                @if ($todo->due_at)
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold
                                                 {{ $todo->isOverdue()
                                                     ? 'bg-brand-red/10 border-brand-red/25 text-brand-red'
                                                     : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400' }}">
                                        📅 {{ $todo->due_at->format('M j, H:i') }}
                                        @if ($todo->isOverdue())
                                            · Overdue!
                                        @endif
                                    </span>
                                @endif

                                {{-- Reminder --}}
                                @if ($todo->reminder_at && !$todo->is_done)
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-brand-blue/25 bg-brand-blue/10 px-2 py-0.5 text-[10px] font-semibold text-brand-blue">
                                        🔔 {{ $todo->reminder_at->format('M j, H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions (visible on hover) --}}
                        <div
                            class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                            <button wire:click="startEdit({{ $todo->id }})"
                                class="p-1.5 rounded-lg text-zinc-400 hover:text-brand-blue hover:bg-brand-blue/10 transition-all"
                                title="Edit">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button wire:click="deleteTodo({{ $todo->id }})"
                                class="p-1.5 rounded-lg text-zinc-400 hover:text-brand-red hover:bg-brand-red/10 transition-all"
                                title="Delete">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-48 p-8 text-center">
                <div class="rounded-full bg-zinc-100 dark:bg-zinc-800 p-4 mb-3">
                    <svg class="h-7 w-7 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">
                    {{ $filter === 'done' ? 'No completed todos yet' : ($filter === 'active' ? 'All caught up! 🎉' : 'No todos yet') }}
                </h3>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $filter === 'all' ? 'Hit the "Add Todo" button to get started.' : 'Try switching the filter above.' }}
                </p>
            </div>
        @endforelse
    </div>
</div>
