<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>MyNotepad — A Simple Personal Notes App</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <style>
            body {
                font-family: 'Outfit', 'Instrument Sans', sans-serif;
            }
            .glow-bg {
                background: radial-gradient(circle at 50% -20%, rgba(124, 58, 237, 0.15) 0%, rgba(99, 102, 241, 0.05) 50%, transparent 100%);
            }
            .card-glass {
                background: rgba(17, 17, 19, 0.7);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 selection:bg-violet-500 selection:text-white glow-bg flex flex-col antialiased">
        
        <!-- Header / Navbar -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between border-b border-zinc-900/50">
            <div class="flex items-center gap-2.5">
                <div class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-violet-600 to-indigo-700 text-white shadow-lg shadow-violet-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5.5">
                        <path d="M12 20h9" />
                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">MyNotepad</span>
            </div>

            @if (Route::has('login'))
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-lg shadow-violet-500/20 transition-all hover:bg-violet-500 hover:shadow-violet-500/30 focus:outline-hidden active:scale-95">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-semibold text-zinc-400 hover:text-white transition-all">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-900 hover:bg-zinc-800 px-5 text-sm font-semibold text-white border border-zinc-800 transition-all active:scale-95">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <!-- Hero Section -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-6 py-12 md:py-20 flex flex-col items-center justify-center text-center gap-12">
            
            <!-- Hero Typography -->
            <div class="max-w-3xl space-y-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-4 py-1.5 text-xs font-semibold text-violet-300">
                    <span class="flex h-2 w-2 rounded-full bg-violet-400 animate-pulse"></span>
                    Now Available
                </div>
                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-tight">
                    Your Thoughts, <br class="sm:block hidden" />
                    <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-indigo-400 bg-clip-text text-transparent">Sleekly Organized.</span>
                </h1>
                <p class="text-base sm:text-lg text-zinc-400 font-medium leading-relaxed max-w-2xl mx-auto">
                    A beautiful, secure, and lightning-fast personal notepad built with Laravel and Livewire. Write seamlessly, find notes instantly, and focus on what matters most.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="w-full sm:w-auto inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-violet-600 px-8 text-base font-bold text-white shadow-xl shadow-violet-500/25 transition-all hover:bg-violet-500 hover:shadow-violet-500/35 focus:outline-hidden active:scale-95">
                            Open Notepad
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" 
                           class="w-full sm:w-auto inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-violet-600 px-8 text-base font-bold text-white shadow-xl shadow-violet-500/25 transition-all hover:bg-violet-500 hover:shadow-violet-500/35 focus:outline-hidden active:scale-95">
                            Get Started — It's Free
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="{{ route('login') }}" 
                           class="w-full sm:w-auto inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-zinc-900 border border-zinc-800 px-8 text-base font-bold text-zinc-300 hover:bg-zinc-800 hover:text-white transition-all">
                            Access Your Account
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Premium App Mockup (HTML/CSS Browser Simulation) -->
            <div class="w-full max-w-5xl rounded-2xl border border-zinc-800 bg-zinc-950/40 p-1.5 shadow-2xl shadow-indigo-500/5 transition-transform hover:scale-[1.01] duration-500">
                <div class="rounded-xl overflow-hidden border border-zinc-800 bg-zinc-900 shadow-inner flex flex-col">
                    
                    <!-- Browser Window Header -->
                    <div class="bg-zinc-950 px-4 py-3 flex items-center justify-between border-b border-zinc-800">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                        </div>
                        <div class="bg-zinc-900 text-xs text-zinc-500 px-10 py-1 rounded-md border border-zinc-800/60 max-w-sm truncate">
                            mynotepad.app/dashboard
                        </div>
                        <div class="w-14"></div>
                    </div>

                    <!-- Simulated Workspace -->
                    <div class="flex h-[420px] text-left bg-zinc-900">
                        
                        <!-- Left Panel: Simulated Notes Sidebar -->
                        <div class="w-64 border-r border-zinc-800 flex flex-col bg-zinc-900/50 hidden sm:flex">
                            <div class="p-4 border-b border-zinc-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-white tracking-wide">My Notes</span>
                                    <span class="text-[10px] bg-violet-500/10 border border-violet-500/20 text-violet-300 px-2 py-0.5 rounded-full font-bold">Live</span>
                                </div>
                                <div class="h-8 bg-zinc-950 border border-zinc-800 rounded-lg flex items-center px-3 gap-2">
                                    <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    <span class="text-xs text-zinc-650">Search notes...</span>
                                </div>
                            </div>
                            
                            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                                <!-- Active Card -->
                                <div class="p-3 rounded-xl bg-violet-600/10 border-l-3 border-violet-500 space-y-1">
                                    <h4 class="text-xs font-bold text-white line-clamp-1">💡 Startup Ideas</h4>
                                    <span class="text-[10px] text-violet-400 font-semibold">2 mins ago</span>
                                    <p class="text-[11px] text-zinc-400 line-clamp-1">Personal notepad built on top of Laravel, utilizing Livewire for responsive updates...</p>
                                </div>
                                <!-- Inactive Card -->
                                <div class="p-3 rounded-xl hover:bg-zinc-800/50 space-y-1 transition-all">
                                    <h4 class="text-xs font-bold text-zinc-300 line-clamp-1">📝 Weekly Shopping List</h4>
                                    <span class="text-[10px] text-zinc-500 font-semibold">Yesterday</span>
                                    <p class="text-[11px] text-zinc-500 line-clamp-1">Milk, eggs, organic veggies, coffee beans, fresh apples...</p>
                                </div>
                                <!-- Inactive Card -->
                                <div class="p-3 rounded-xl hover:bg-zinc-800/50 space-y-1 transition-all">
                                    <h4 class="text-xs font-bold text-zinc-300 line-clamp-1">🎨 Design Inspiration links</h4>
                                    <span class="text-[10px] text-zinc-500 font-semibold">3 days ago</span>
                                    <p class="text-[11px] text-zinc-500 line-clamp-1">Linear.app for minimalist forms, Apple notes layout, glassmorphic accents...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Simulated Note Editor -->
                        <div class="flex-1 flex flex-col bg-zinc-950/20">
                            <!-- Editor Header -->
                            <div class="px-6 py-3 border-b border-zinc-800 flex items-center justify-between bg-zinc-900/20">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-300">Editing</span>
                                    <span class="text-[10px] text-zinc-500 font-semibold">Last edited 2m ago</span>
                                </div>
                                <div class="flex gap-2">
                                    <span class="h-7 px-3 rounded-lg border border-zinc-800 bg-zinc-900 text-[11px] text-zinc-400 font-semibold flex items-center cursor-default">Delete</span>
                                    <span class="h-7 px-3 rounded-lg bg-violet-650 text-[11px] text-white font-bold flex items-center shadow-lg shadow-violet-500/25 cursor-default">Save Note</span>
                                </div>
                            </div>
                            <!-- Editor Inputs -->
                            <div class="flex-1 p-6 space-y-4">
                                <h3 class="text-xl font-extrabold text-white">💡 Startup Ideas</h3>
                                <p class="text-xs sm:text-sm text-zinc-300 leading-relaxed">
                                    I am working on building a lightweight and premium notepad application called <strong class="text-violet-400">MyNotepad</strong>. It must look absolutely incredible at first glance—no basic templates allowed. Users will love the responsive dual-pane layout, full SQLite integration, real-time live searches, and seamless transitions.
                                </p>
                                <div class="border-l-2 border-violet-500/50 pl-3 py-1 text-xs text-zinc-400 italic">
                                    Need to finish this Laravel project for the final presentation on Friday! Let's make sure the UI is stunning and easy to explain.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Features Grid -->
            <div class="w-full py-10 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                <!-- Feature 1 -->
                <div class="card-glass border border-zinc-800 p-6 rounded-2xl space-y-4 hover:border-zinc-700/80 transition-all hover:-translate-y-1 duration-300">
                    <div class="size-11 rounded-xl bg-violet-500/10 border border-violet-500/25 flex items-center justify-center text-violet-400 shadow-inner">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">⚡ Lightning Fast</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed font-medium">
                        Search and filter your notes instantly as you type. Real-time Livewire reactivity means zero page reloads and optimal responsiveness.
                    </p>
                </div>
                <!-- Feature 2 -->
                <div class="card-glass border border-zinc-800 p-6 rounded-2xl space-y-4 hover:border-zinc-700/80 transition-all hover:-translate-y-1 duration-300">
                    <div class="size-11 rounded-xl bg-violet-500/10 border border-violet-500/25 flex items-center justify-center text-violet-400 shadow-inner">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">🔒 Secure & Private</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed font-medium">
                        Each note belongs exclusively to you. Advanced user encryption and standard session protection keep your personal notepad entirely isolated and safe.
                    </p>
                </div>
                <!-- Feature 3 -->
                <div class="card-glass border border-zinc-800 p-6 rounded-2xl space-y-4 hover:border-zinc-700/80 transition-all hover:-translate-y-1 duration-300">
                    <div class="size-11 rounded-xl bg-violet-500/10 border border-violet-500/25 flex items-center justify-center text-violet-400 shadow-inner">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">🗂️ Clean CRUD Interface</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed font-medium">
                        A beautiful dual-pane layout designed for absolute focus. Create, edit, preview, and delete notes effortlessly with robust, auto-saving forms.
                    </p>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-8 border-t border-zinc-900/50 flex flex-col sm:flex-row items-center justify-between text-zinc-500 text-xs gap-4 mt-12">
            <span>&copy; {{ date('Y') }} MyNotepad. All rights reserved. Created for the Laravel Final Presentation.</span>
            <div class="flex items-center gap-6">
                <span>Timeline: Monday, May 25 - Friday, May 29</span>
            </div>
        </footer>

    </body>
</html>
