@props(['logo' => 'SF', 'appName' => null])

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-white to-gray-50 border-r border-gray-200 shadow-xl transform transition-all duration-300 lg:translate-x-0 lg:static lg:inset-0 -translate-x-full">
    <div class="flex flex-col h-full">
        <!-- Logo & Collapse Button -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 bg-white/80 backdrop-blur-sm">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-3 sidebar-logo transition-all duration-300">
                <div class="w-10 h-10 flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('icon.png') }}" alt="{{ $appName ?? config('app.name') }}" class="w-full h-full object-contain rounded-xl">
                </div>
                <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent sidebar-text whitespace-nowrap">
                    {{ $appName ?? config('app.name') }}
                </span>
            </a>
            <div class="flex items-center space-x-2">
                <!-- Collapse Button (Desktop) -->
                <button id="collapseSidebar" class="hidden lg:block text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition-all" title="Réduire/Étendre">
                    <svg class="w-5 h-5 transform transition-transform duration-300" id="collapseIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
                <!-- Close Button (Mobile) -->
                <button id="closeSidebar" class="lg:hidden text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto overflow-x-hidden custom-scrollbar">
            {{ $slot }}
        </nav>

        <!-- User Profile -->
        @isset($footer)
            <div class="border-t border-gray-200 p-4 bg-white/50 backdrop-blur-sm">
                {{ $footer }}
            </div>
        @else
            <div class="border-t border-gray-200 p-4 bg-white/50 backdrop-blur-sm" x-data="{ showLogoutConfirm: false }">
                <div class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-indigo-50 transition-all cursor-pointer group relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold shadow-lg flex-shrink-0 ring-2 ring-white group-hover:ring-indigo-200 transition-all">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="flex-1 min-w-0 sidebar-text">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="sidebar-text" id="logout-form">
                        @csrf
                        <button type="button" @click="showLogoutConfirm = true" class="text-gray-400 hover:text-red-600 transition-colors p-1.5 rounded-lg hover:bg-red-50" title="Déconnexion">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Logout Confirmation Modal (Alpine.js only - no Livewire) -->
                <template x-teleport="body">
                    <div x-show="showLogoutConfirm"
                         x-cloak
                         class="fixed inset-0 z-[100] overflow-y-auto"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @keydown.escape.window="showLogoutConfirm = false">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="showLogoutConfirm = false"></div>

                        <!-- Modal -->
                        <div class="fixed inset-0 z-10 overflow-y-auto">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div x-show="showLogoutConfirm"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative bg-white rounded-2xl shadow-xl transform transition-all sm:max-w-md w-full"
                                     @click.stop>
                                    <div class="p-6">
                                        <!-- Icon -->
                                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-5">
                                            <svg class="h-7 w-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                        </div>

                                        <!-- Content -->
                                        <div class="text-center">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                Confirmer la déconnexion
                                            </h3>
                                            <p class="text-sm text-gray-600 mb-3">
                                                Êtes-vous sûr de vouloir vous déconnecter ? Vous devrez vous reconnecter pour accéder à votre compte.
                                            </p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex gap-3 justify-center mt-6">
                                            <button type="button"
                                                    @click="showLogoutConfirm = false"
                                                    class="px-5 py-2.5 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                                                Annuler
                                            </button>
                                            <button type="button"
                                                    @click="document.getElementById('logout-form').submit()"
                                                    class="px-5 py-2.5 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 bg-red-600 hover:bg-red-700 focus:ring-red-500">
                                                Se déconnecter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endisset
    </div>
</aside>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden hidden"></div>

@once
    @push('styles')
        <style>
            /* Custom scrollbar */
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #e5e7eb;
                border-radius: 3px;
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: #d1d5db;
            }

            /* Sidebar collapsed state */
            #sidebar.collapsed {
                width: 80px !important;
            }
            #sidebar.collapsed .sidebar-text {
                display: none !important;
            }
            #sidebar.collapsed .sidebar-logo span.sidebar-text {
                display: none !important;
            }
            /* Hide dropdowns in collapsed state */
            #sidebar.collapsed [data-dropdown-container] .dropdown-content {
                display: none !important;
            }

            /* Tooltip - hidden by default */
            .sidebar-tooltip {
                display: none;
            }
            /* Show tooltip only in collapsed state on hover */
            #sidebar.collapsed .sidebar-item:hover .sidebar-tooltip {
                display: block;
                position: absolute;
                left: 100%;
                top: 50%;
                transform: translateY(-50%);
                background: #1f2937;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                white-space: nowrap;
                margin-left: 0.75rem;
                z-index: 100;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            // Wrap all sidebar functionality in DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {
                // Sidebar toggle for mobile
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                const openSidebar = document.getElementById('openSidebar');
                const closeSidebar = document.getElementById('closeSidebar');
                const collapseSidebar = document.getElementById('collapseSidebar');
                const collapseIcon = document.getElementById('collapseIcon');

                // Load saved state
                if (sidebar && collapseSidebar && collapseIcon) {
                    console.log('Sidebar collapse initialized');
                    const savedCollapsedState = localStorage.getItem('sidebar-collapsed');
                    if (savedCollapsedState === 'true') {
                        sidebar.classList.add('collapsed');
                        collapseIcon.style.transform = 'rotate(180deg)';
                    }

                    // Desktop collapse toggle
                    collapseSidebar.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Collapse button clicked');

                        sidebar.classList.toggle('collapsed');
                        const isCollapsed = sidebar.classList.contains('collapsed');
                        console.log('Sidebar collapsed state:', isCollapsed);

                        // Rotate icon
                        collapseIcon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';

                        // Save state
                        localStorage.setItem('sidebar-collapsed', isCollapsed);
                    });
                } else {
                    console.error('Sidebar elements not found:', {
                        sidebar: !!sidebar,
                        collapseSidebar: !!collapseSidebar,
                        collapseIcon: !!collapseIcon
                    });
                }

                // Mobile open
                openSidebar?.addEventListener('click', () => {
                    sidebar.classList.remove('-translate-x-full');
                    sidebarOverlay.classList.remove('hidden');
                });

                // Mobile close
                closeSidebar?.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                });

                // Overlay close
                sidebarOverlay?.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                });

                // Dropdown Menu Functionality
                // Initialize all dropdown toggles
                const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');

                dropdownToggles.forEach(toggle => {
                    const dropdownId = toggle.getAttribute('data-dropdown-toggle');
                    const dropdownContent = document.getElementById(dropdownId);
                    const dropdownArrow = document.querySelector(`[data-dropdown-arrow="${dropdownId}"]`);

                    if (!dropdownContent || !dropdownArrow) return;

                    // Check if dropdown contains an active item (link with active state)
                    const hasActiveChild = dropdownContent.querySelector('a.bg-indigo-50, a.from-indigo-600');
                    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

                    // Auto-open if contains active item or initially expanded
                    if (hasActiveChild || isExpanded) {
                        openDropdown(dropdownContent, dropdownArrow, toggle, true);
                    } else {
                        // Otherwise check localStorage
                        const savedState = localStorage.getItem(`dropdown-${dropdownId}`);
                        if (savedState === 'open') {
                            openDropdown(dropdownContent, dropdownArrow, toggle, false);
                        }
                    }

                    // Toggle on click
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

                        if (isOpen) {
                            closeDropdown(dropdownContent, dropdownArrow, toggle);
                        } else {
                            openDropdown(dropdownContent, dropdownArrow, toggle, false);
                        }
                    });
                });

                function openDropdown(content, arrow, toggle, skipLocalStorage = false) {
                    // Set the actual height for transition
                    content.style.maxHeight = content.scrollHeight + 'px';
                    content.classList.remove('opacity-0', 'max-h-0');
                    content.classList.add('opacity-100', 'max-h-screen');
                    arrow.style.transform = 'rotate(180deg)';
                    toggle.setAttribute('aria-expanded', 'true');

                    // Store state in localStorage (unless it's auto-opened due to active child)
                    if (!skipLocalStorage) {
                        const dropdownId = content.id;
                        localStorage.setItem(`dropdown-${dropdownId}`, 'open');
                    }
                }

                function closeDropdown(content, arrow, toggle) {
                    content.style.maxHeight = '0px';
                    content.classList.remove('opacity-100', 'max-h-screen');
                    content.classList.add('opacity-0', 'max-h-0');
                    arrow.style.transform = 'rotate(0deg)';
                    toggle.setAttribute('aria-expanded', 'false');

                    // Store state in localStorage
                    const dropdownId = content.id;
                    localStorage.setItem(`dropdown-${dropdownId}`, 'closed');
                }
            });
        </script>
    @endpush
@endonce
