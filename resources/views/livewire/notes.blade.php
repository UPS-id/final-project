<?php

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public ?int $activeNoteId = null;
    public string $title = '';
    public string $content = '';
    public bool $isCreating = false;

    // Toast/Notification state
    public string $toastMessage = '';
    public string $toastType = 'success'; // success, danger

    // Sharing state
    public string $shareEmail = '';
    public string $shareType = 'view';
    public bool $showShareModal = false;

    public function showToast(string $message, string $type = 'success')
    {
        $this->toastMessage = $message;
        $this->toastType = $type;
        $this->dispatch('note-toast-shown');
    }

    public function selectNote(int $id)
    {
        $note = Note::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhereHas('sharedUsers', function ($q) {
                    $q->where('user_id', Auth::id());
                });
        })->find($id);

        if ($note) {
            $this->activeNoteId = $note->id;
            $this->title = $note->title;
            $this->content = $note->content;
            $this->isCreating = false;
            $this->showShareModal = false;
        }
    }

    public function startCreating()
    {
        $this->activeNoteId = null;
        $this->title = '';
        $this->content = '';
        $this->isCreating = true;
        $this->showShareModal = false;
    }

    public function saveNote()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($this->activeNoteId) {
            $note = Note::find($this->activeNoteId);
            if ($note) {
                // Check write permissions
                if ($note->user_id !== Auth::id()) {
                    $isEditable = $note->sharedUsers()
                        ->where('user_id', Auth::id())
                        ->wherePivot('type', 'edit')
                        ->exists();

                    if (!$isEditable) {
                        $this->showToast('You do not have permission to edit this note!', 'danger');
                        return;
                    }
                }

                $note->update([
                    'title' => $this->title,
                    'content' => $this->content,
                ]);
                $this->showToast('Note updated successfully!', 'success');
            }
        } else {
            $note = Auth::user()->notes()->create([
                'title' => $this->title,
                'content' => $this->content,
            ]);
            $this->activeNoteId = $note->id;
            $this->isCreating = false;
            $this->showToast('Note created successfully!', 'success');
        }
    }

    public function deleteNote(int $id)
    {
        $note = Note::find($id);
        if ($note) {
            if ($note->user_id !== Auth::id()) {
                $this->showToast('Only the owner can delete this note!', 'danger');
                return;
            }

            $note->delete();
            if ($this->activeNoteId === $id) {
                $this->activeNoteId = null;
                $this->title = '';
                $this->content = '';
            }
            $this->showToast('Note deleted successfully!', 'danger');
        }
    }

    public function shareNote()
    {
        $this->validate([
            'shareEmail' => 'required|email|exists:users,email',
            'shareType' => 'required|in:view,edit',
        ], [
            'shareEmail.exists' => 'User with this email address was not found.',
        ]);

        $targetUser = User::where('email', $this->shareEmail)->first();
        if ($targetUser->id === Auth::id()) {
            $this->addError('shareEmail', 'You cannot share a note with yourself.');
            return;
        }

        $note = Note::find($this->activeNoteId);
        if (!$note || $note->user_id !== Auth::id()) {
            $this->showToast('Only the note owner can manage sharing!', 'danger');
            return;
        }

        if ($note->sharedUsers()->where('user_id', $targetUser->id)->exists()) {
            $this->addError('shareEmail', 'This note is already shared with this user.');
            return;
        }

        $note->sharedUsers()->attach($targetUser->id, ['type' => $this->shareType]);
        $this->shareEmail = '';
        $this->showToast('Note shared successfully!', 'success');
    }

    public function revokeShare(int $userId)
    {
        $note = Note::find($this->activeNoteId);
        if ($note && $note->user_id === Auth::id()) {
            $note->sharedUsers()->detach($userId);
            $this->showToast('Access revoked successfully!', 'success');
        }
    }

    public function with(): array
    {
        $notesQuery = Note::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('sharedUsers', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })
            ->latest('updated_at');

        if (!empty($this->search)) {
            $notesQuery->where(function($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        $activeNote = null;
        $sharedUsers = collect();
        $isSharedReadOnly = false;

        if ($this->activeNoteId) {
            $activeNote = Note::with('sharedUsers')->find($this->activeNoteId);
            if ($activeNote) {
                $sharedUsers = $activeNote->sharedUsers()->withPivot('type')->get();
                if ($activeNote->user_id !== Auth::id()) {
                    $isSharedReadOnly = !$activeNote->sharedUsers()
                        ->where('user_id', Auth::id())
                        ->wherePivot('type', 'edit')
                        ->exists();
                }
            }
        }

        return [
            'notes' => $notesQuery->get(),
            'activeNote' => $activeNote,
            'sharedUsers' => $sharedUsers,
            'isSharedReadOnly' => $isSharedReadOnly,
        ];
    }
};
?>

<div class="flex h-[calc(100vh-10rem)] w-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900 md:flex-row"
     x-data="{ 
        toastOpen: false, 
        toastMessage: '', 
        toastType: 'success',
        mobileDetailOpen: @entangle('activeNoteId').live !== null || @entangle('isCreating').live
     }"
     @note-toast-shown.window="
        toastMessage = $wire.toastMessage;
        toastType = $wire.toastType;
        toastOpen = true;
        setTimeout(() => { toastOpen = false }, 3000);
     ">

    <!-- Toast Notification -->
    <div x-show="toastOpen" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200 opacity-0"
         class="fixed right-5 top-5 z-50 flex items-center gap-3 rounded-xl px-4 py-3 shadow-2xl transition-all border"
         :class="toastType === 'success' ? 'bg-brand-green border-brand-green text-brand-green dark:text-brand-green' : 'bg-brand-red border-brand-red text-brand-red dark:text-brand-red'"
         style="display: none;">
        <template x-if="toastType === 'success'">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </template>
        <template x-if="toastType !== 'success'">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </template>
        <span class="text-sm font-semibold" x-text="toastMessage"></span>
    </div>

    <!-- Left Sidebar: Notes List -->
    <div class="flex h-full w-full flex-col border-r border-zinc-200 dark:border-zinc-800 md:w-80 lg:w-96"
         :class="mobileDetailOpen ? 'hidden md:flex' : 'flex'">
        
        <!-- Sidebar Header: Search & Create Button -->
        <div class="p-5 space-y-4 bg-zinc-50/50 dark:bg-zinc-950/20 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">My Notes</h2>
                <button wire:click="startCreating" 
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-brand-blue px-4 text-sm font-semibold text-white shadow-lg shadow-brand-blue transition-all hover:bg-brand-blue hover:shadow-brand-blue focus:outline-hidden active:scale-95">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </button>
            </div>

            <!-- Search input -->
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4.5 w-4.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="Search notes title or body..."
                       class="w-full rounded-xl border border-zinc-200 bg-white py-2 pl-9 pr-4 text-sm text-zinc-900 placeholder-zinc-400 shadow-xs transition-all focus:border-brand-blue focus:outline-hidden focus:ring-2 focus:ring-brand-blue dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-150 dark:placeholder-zinc-500" />
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Notes List Scroll Area -->
        <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/50">
            @forelse($notes as $note)
                <div class="group relative flex flex-col gap-1 p-5 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-950/40 cursor-pointer {{ $activeNoteId === $note->id ? 'bg-brand-blue/5 border-l-4 border-brand-blue dark:bg-brand-blue' : '' }}"
                     wire:click="selectNote({{ $note->id }})">
                    
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-50 group-hover:text-brand-blue dark:group-hover:text-brand-blue line-clamp-1">
                            {{ $note->title }}
                        </h3>
                        @if($note->user_id === auth()->id())
                            <!-- Delete Button inside Card -->
                            <button wire:click.stop="deleteNote({{ $note->id }})" 
                                    class="opacity-0 group-hover:opacity-100 p-1 text-zinc-400 hover:text-brand-red rounded-lg hover:bg-brand-red transition-all focus:opacity-100"
                                    title="Delete note">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-400 dark:text-zinc-500 font-medium">
                            {{ $note->updated_at->diffForHumans() }}
                        </span>
                        @if($note->user_id !== auth()->id())
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-blue px-2 py-0.5 text-[10px] font-bold text-brand-blue dark:text-brand-blue">
                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Shared by {{ $note->user?->name ?? 'User' }}
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 mt-1">
                        {{ Str::limit(strip_tags($note->content), 120) }}
                    </p>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center p-8 text-center h-48">
                    <div class="rounded-full bg-zinc-100 p-3 dark:bg-zinc-800">
                        <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-zinc-100">No notes found</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $search ? 'Try adjusting your search criteria' : 'Create your very first note today!' }}
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Side: Note Editor / Reader -->
    <div class="flex-1 flex flex-col h-full bg-zinc-50/30 dark:bg-zinc-950/10"
         :class="mobileDetailOpen ? 'flex' : 'hidden md:flex'">
        
        @if($activeNoteId || $isCreating)
            <form wire:submit.prevent="saveNote" class="flex flex-col h-full">
                <!-- Editor Top Header -->
                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 p-4 bg-white dark:bg-zinc-900">
                    <div class="flex items-center gap-2">
                        <!-- Mobile Back Button -->
                        <button type="button" 
                                @click="mobileDetailOpen = false; $wire.set('activeNoteId', null); $wire.set('isCreating', false)" 
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 md:hidden">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            {{ $activeNoteId ? 'Editing Note' : 'New Note draft' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($activeNoteId && $activeNote && $activeNote->user_id === auth()->id())
                            <!-- Share Button -->
                            <button type="button" 
                                    wire:click="$toggle('showShareModal')"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-600 hover:bg-brand-blue hover:text-brand-blue dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-brand-blue dark:hover:text-brand-blue focus:outline-hidden transition-all animate-pulse">
                                <svg class="h-4 w-4 text-brand-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 10.742a3 3 0 110-5.484m0 5.484a3 3 0 110 5.484m0-5.484h7.586a3 3 0 013 3v2m-6-8a3 3 0 00-3-3V3m0 18v-3" />
                                </svg>
                                Share
                            </button>

                            <!-- Delete Button -->
                            <button type="button" 
                                    wire:click="deleteNote({{ $activeNoteId }})"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-600 hover:bg-brand-red hover:text-brand-red dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-brand-red dark:hover:text-brand-red focus:outline-hidden transition-all">
                                Delete
                            </button>
                        @endif
                        
                        @if(!$isSharedReadOnly)
                            <button type="submit" 
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-brand-blue px-4 text-sm font-semibold text-white shadow-lg shadow-brand-blue transition-all hover:bg-brand-blue focus:outline-hidden active:scale-95">
                                Save Note
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Editor Inputs Area -->
                <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-white dark:bg-zinc-900">
                    
                    @if($isSharedReadOnly)
                        <div class="flex items-center gap-3 rounded-xl border border-brand-blue bg-brand-blue/5 px-4 py-3 text-sm text-brand-blue dark:text-brand-blue">
                            <svg class="h-5 w-5 text-brand-blue shrink-0 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <div>
                                <span class="font-bold">Read-Only Note:</span> Shared with you by <strong class="underline font-bold">{{ $activeNote->user?->name }}</strong>. You do not have permissions to modify this note.
                            </div>
                        </div>
                    @elseif($activeNoteId && $activeNote && $activeNote->user_id !== auth()->id())
                        <div class="flex items-center gap-3 rounded-xl border border-brand-green bg-brand-green/5 px-4 py-3 text-sm text-brand-green dark:text-brand-green">
                            <svg class="h-5 w-5 text-brand-green shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <div>
                                <span class="font-bold">Collaborator Mode:</span> Shared by <strong class="underline font-bold">{{ $activeNote->user?->name }}</strong> with edit access. Any edits you make will be saved instantly.
                            </div>
                        </div>
                    @endif

                    <div>
                        <input wire:model="title" 
                               type="text" 
                               placeholder="Give your note a title..." 
                               class="w-full text-3xl font-extrabold text-zinc-900 dark:text-zinc-50 border-0 p-0 focus:ring-0 focus:outline-hidden placeholder-zinc-300 dark:placeholder-zinc-700 bg-transparent disabled:opacity-75 disabled:cursor-not-allowed"
                               @if($isSharedReadOnly) disabled @endif />
                        @error('title')
                            <p class="mt-1.5 text-sm font-semibold text-brand-red">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="h-full">
                        <textarea wire:model="content" 
                                  placeholder="Start typing your amazing thoughts here..." 
                                  class="w-full min-h-[350px] text-zinc-700 dark:text-zinc-300 border-0 p-0 focus:ring-0 focus:outline-hidden placeholder-zinc-300 dark:placeholder-zinc-700 leading-relaxed text-base bg-transparent resize-none disabled:opacity-75 disabled:cursor-not-allowed"
                                  @if($isSharedReadOnly) disabled @endif></textarea>
                        @error('content')
                            <p class="mt-1.5 text-sm font-semibold text-brand-red">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </form>
        @else
            <!-- Empty state when no note is active -->
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center h-full">
                <!-- Sleek CSS-based illustration -->
                <div class="relative w-40 h-40 mb-6 flex items-center justify-center">
                    <div class="absolute w-28 h-36 bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 rotate-3 transition-transform duration-500 hover:rotate-6"></div>
                    <div class="absolute w-28 h-36 bg-gradient-to-tr from-brand-blue to-indigo-600 rounded-2xl shadow-lg -rotate-6 flex flex-col justify-between p-4 text-white">
                        <div class="space-y-1.5">
                            <div class="h-1 w-8 bg-white/40 rounded-full"></div>
                            <div class="h-1 w-16 bg-white/40 rounded-full"></div>
                            <div class="h-1.5 w-12 bg-white rounded-full"></div>
                        </div>
                        <div class="self-end">
                            <svg class="h-8 w-8 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Capture your thoughts</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 max-w-sm">
                    Select a note from the left sidebar to view/edit it, or create a brand new note to begin your writing journey.
                </p>

                <button wire:click="startCreating" 
                        class="mt-6 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-blue px-5 text-sm font-semibold text-white shadow-lg shadow-brand-blue transition-all hover:bg-brand-blue hover:shadow-brand-blue focus:outline-hidden active:scale-95">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Create a New Note
                </button>
            </div>
        @endif
    </div>

    <!-- Share Modal -->
    @if($showShareModal && $activeNoteId && $activeNote && $activeNote->user_id === auth()->id())
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-zinc-950/75 backdrop-blur-xs transition-opacity" 
                     wire:click="$set('showShareModal', false)"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal box -->
                <div class="relative inline-block transform overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 text-left align-middle shadow-2xl transition-all dark:border-zinc-800 dark:bg-zinc-900 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-zinc-150 dark:border-zinc-800 pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-brand-blue p-2 text-brand-blue dark:text-brand-blue">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 10.742a3 3 0 110-5.484m0 5.484a3 3 0 110 5.484m0-5.484h7.586a3 3 0 013 3v2m-6-8a3 3 0 00-3-3V3m0 18v-3" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-50" id="modal-title">
                                Share Note Access
                            </h3>
                        </div>
                        <button type="button" 
                                wire:click="$set('showShareModal', false)"
                                class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Share Form -->
                    <form wire:submit.prevent="shareNote" class="space-y-4">
                        <div>
                            <label for="shareEmail" class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                                Email Address to Share With
                            </label>
                            <input wire:model="shareEmail" 
                                   type="email" 
                                   id="shareEmail" 
                                   placeholder="collaborator@example.com" 
                                   class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 px-4 text-sm text-zinc-900 placeholder-zinc-400 focus:border-brand-blue focus:outline-hidden focus:ring-2 focus:ring-brand-blue dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-150" />
                            @error('shareEmail')
                                <p class="mt-1.5 text-xs font-semibold text-brand-red">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">
                                Permission Level
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative flex cursor-pointer rounded-xl border p-4 focus:outline-hidden {{ $shareType === 'view' ? 'border-brand-blue bg-brand-blue/5 text-brand-blue dark:text-brand-blue' : 'border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                                    <input type="radio" wire:model="shareType" value="view" class="sr-only" />
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold">View Only</span>
                                        <span class="text-[11px] opacity-75 mt-0.5">Can read but cannot make edits.</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-xl border p-4 focus:outline-hidden {{ $shareType === 'edit' ? 'border-brand-blue bg-brand-blue/5 text-brand-blue dark:text-brand-blue' : 'border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                                    <input type="radio" wire:model="shareType" value="edit" class="sr-only" />
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold">Can Edit</span>
                                        <span class="text-[11px] opacity-75 mt-0.5">Full collaboration and writing.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" 
                                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-brand-blue px-5 text-sm font-semibold text-white shadow-lg shadow-brand-blue hover:bg-brand-blue active:scale-95 transition-all">
                                Share Access
                            </button>
                        </div>
                    </form>

                    <!-- Collaborators List -->
                    <div class="mt-6 border-t border-zinc-150 dark:border-zinc-800 pt-4">
                        <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">
                            People with access
                        </h4>
                        
                        <div class="space-y-3 max-h-40 overflow-y-auto pr-1">
                            <div class="flex items-center justify-between py-1">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                            {{ auth()->user()->name }} (You)
                                        </div>
                                        <div class="text-xs text-zinc-500">{{ auth()->user()->email }}</div>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-zinc-400">Owner</span>
                            </div>

                            @forelse($sharedUsers as $sharedUser)
                                <div class="flex items-center justify-between py-1 border-t border-zinc-100 dark:border-zinc-800/50 pt-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-full bg-brand-blue flex items-center justify-center text-xs font-bold text-brand-blue dark:text-brand-blue">
                                            {{ strtoupper(substr($sharedUser->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                                {{ $sharedUser->name }}
                                            </div>
                                            <div class="text-xs text-zinc-500">{{ $sharedUser->email }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-[10px] font-bold text-zinc-600 dark:text-zinc-400">
                                            {{ $sharedUser->pivot->type === 'edit' ? 'Can Edit' : 'View Only' }}
                                        </span>
                                        <button type="button" 
                                                wire:click="revokeShare({{ $sharedUser->id }})"
                                                class="text-zinc-400 hover:text-brand-red p-1 rounded-lg hover:bg-brand-red"
                                                title="Revoke access">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-xs text-zinc-500 dark:text-zinc-500">
                                    This note is not currently shared with anyone.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
