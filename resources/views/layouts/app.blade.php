<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Warnet Management System')</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vite Assets (Echo.js + WebSocket) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: true }" class="flex h-screen">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-gray-900 text-white transition-all duration-300 flex flex-col">
            <!-- Logo & Toggle -->
            <div class="p-4 flex items-center justify-between border-b border-gray-700">
                <div x-show="sidebarOpen" class="flex items-center space-x-2">
                    <i class="fas fa-desktop text-2xl text-blue-400"></i>
                    <span class="font-bold text-xl">Warnet MS</span>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 hover:bg-gray-800 rounded">
                    <i :class="sidebarOpen ? 'fa-angles-left' : 'fa-angles-right'" class="fas"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('dashboard') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-chart-line w-6"></i>
                    <span x-show="sidebarOpen">Dashboard</span>
                </a>
                
                <a href="{{ route('pcs.index') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('pcs.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-desktop w-6"></i>
                    <span x-show="sidebarOpen">PC Monitoring</span>
                </a>
                
                <a href="{{ route('sessions.index') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('sessions.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-clock w-6"></i>
                    <span x-show="sidebarOpen">Sessions</span>
                </a>
                
                <a href="{{ route('rental-order') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('rental-order') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-cash-register w-6"></i>
                    <span x-show="sidebarOpen">Rental & Order</span>
                </a>
                
                <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('orders.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-shopping-cart w-6"></i>
                    <span x-show="sidebarOpen">Orders</span>
                </a>
                
                <a href="{{ route('menu.index') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('menu.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-utensils w-6"></i>
                    <span x-show="sidebarOpen">Menu</span>
                </a>
                
                <a href="{{ route('analytics') }}" class="flex items-center space-x-3 p-3 rounded hover:bg-gray-800 {{ Request::routeIs('analytics') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-chart-pie w-6"></i>
                    <span x-show="sidebarOpen">Analytics</span>
                </a>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-user"></i>
                        </div>
                        <div x-show="sidebarOpen">
                            <p class="font-semibold" id="userName">Loading...</p>
                            <p class="text-xs text-gray-400">Administrator</p>
                        </div>
                    </div>
                    <button x-show="sidebarOpen" onclick="handleLogout()" class="p-2 hover:bg-red-600 rounded-lg transition" title="Logout">
                        <i class="fas fa-sign-out-alt text-red-400 hover:text-white"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600 text-sm" id="headerUserName"></span>
                        <button onclick="handleLogout()" class="flex items-center space-x-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Custom Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70" onclick="closeLogoutModal()"></div>
        
        <!-- Modal -->
        <div class="relative z-10 w-full max-w-md mx-4">
            <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-600 overflow-hidden">
                <!-- Header -->
                <div class="p-8 text-center">
                    <div class="w-20 h-20 mx-auto mb-5 bg-red-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-sign-out-alt text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">Konfirmasi Logout</h3>
                    <p class="text-gray-300 text-base">Apakah Anda yakin ingin keluar dari sistem?</p>
                </div>
                
                <!-- Buttons -->
                <div class="flex border-t border-gray-600">
                    <button onclick="closeLogoutModal()" 
                            class="flex-1 px-6 py-4 bg-gray-700 text-white hover:bg-gray-600 transition font-medium text-lg">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button onclick="confirmLogout()" 
                            class="flex-1 px-6 py-4 bg-red-600 text-white hover:bg-red-700 transition font-medium text-lg">
                        <i class="fas fa-check mr-2"></i>Ya, Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Scripts -->
    <script>
        // API Helper
        const API_BASE = '/api';
        
        // ============== AUTH GUARD - HARUS LOGIN DULU ==============
        (function checkAuth() {
            const token = localStorage.getItem('api_token');
            const user = localStorage.getItem('user');
            
            // Jika tidak ada token, redirect ke login
            if (!token) {
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
                return;
            }
            
            // Set user name di sidebar dan header
            if (user) {
                try {
                    const userData = JSON.parse(user);
                    document.getElementById('userName').textContent = userData.name || 'User';
                    document.getElementById('headerUserName').textContent = 'Welcome, ' + (userData.name || 'User');
                } catch (e) {
                    console.error('Error parsing user data:', e);
                }
            }
            
            // Set API_TOKEN untuk digunakan di apiCall
            window.API_TOKEN = token;
        })();
        
        // ============== LOGOUT MODAL FUNCTIONS ==============
        function handleLogout() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
        async function confirmLogout() {
            try {
                // Call logout API
                await fetch(`${API_BASE}/logout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
            
            // Clear local storage
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            
            // Redirect ke login
            window.location.href = '/login';
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });

        // Define apiCall globally
        window.apiCall = async function(endpoint, method = 'GET', data = null) {
            const token = localStorage.getItem('api_token');
            
            // Check if token exists
            if (!token) {
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
                return null;
            }
            
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(`${API_BASE}${endpoint}`, options);
                
                // Jika unauthorized (401), redirect ke login
                if (response.status === 401) {
                    localStorage.removeItem('api_token');
                    localStorage.removeItem('user');
                    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
                    return null;
                }
                
                return await response.json();
            } catch (error) {
                console.error('API call error:', error);
                throw error;
            }
        };

        // Currency Formatter
        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Date Formatter
        function formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('id-ID');
        }

        // Remaining Time Formatter
        function formatRemainingTime(seconds) {
            if (seconds <= 0) return '00:00';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
            } else if (minutes > 0) {
                return `${String(minutes).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
            } else {
                return `${String(secs).padStart(2, '0')}s`;
            }
        }
    </script>

    <!-- Page-specific scripts -->
    @stack('scripts')
</body>
</html>
