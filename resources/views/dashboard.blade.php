<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-0" x-data="{ activeTab: 'notes' }">

        {{-- Tab Switcher --}}
        <div class="flex items-center gap-1 px-4 pt-4 pb-0">
            <div class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-800/60 rounded-2xl">
                <button @click="activeTab = 'notes'"
                        :class="activeTab === 'notes'
                            ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-50 shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-bold transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Notes
                </button>
                <button @click="activeTab = 'todos'"
                        :class="activeTab === 'todos'
                            ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-50 shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-sm font-bold transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Todos
                </button>
            </div>
        </div>

        {{-- Panel --}}
        <div class="flex-1 min-h-0 p-4">
            <div x-show="activeTab === 'notes'" class="h-full">
                <livewire:notes />
            </div>
            <div x-show="activeTab === 'todos'" class="h-full">
                <div class="flex h-full w-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                    <livewire:todos />
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
