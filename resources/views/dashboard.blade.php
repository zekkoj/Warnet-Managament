@extends('layouts.app')

@section('title', 'Dashboard - Warnet Management System')
@section('page-title', 'Dashboard')

@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Today's Revenue -->
        <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-blue-100 text-sm">Today's Revenue</p>
                    <p class="text-3xl font-bold" x-text="formatRupiah(summary.todayRevenue)"></p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-2xl"></i>
                </div>
            </div>
            <p class="text-blue-100 text-sm">
                <i class="fas fa-arrow-up mr-1"></i>
                <span x-text="summary.revenueGrowth + '%'"></span> from yesterday
            </p>
        </div>

        <!-- Active Sessions -->
        <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-green-100 text-sm">Active Sessions</p>
                    <p class="text-3xl font-bold" x-text="summary.activeSessions"></p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
            <p class="text-green-100 text-sm">
                <span x-text="summary.pcUtilization + '%'"></span> PC Utilization
            </p>
        </div>

        <!-- Complete Orders -->
        <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-yellow-100 text-sm">Complete Orders</p>
                    <p class="text-3xl font-bold" x-text="summary.completeOrders"></p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
            </div>
            <p class="text-yellow-100 text-sm">F&B Orders</p>
        </div>

        <!-- Available PCs -->
        <div class="rounded-lg shadow-lg p-6 text-white" style="background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-purple-100 text-sm">Available PCs</p>
                    <p class="text-3xl font-bold" x-text="summary.availablePCs + ' / ' + summary.totalPCs"></p>
                </div>
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-desktop text-2xl"></i>
                </div>
            </div>
            <p class="text-purple-100 text-sm">
                <span x-text="summary.maintenancePCs"></span> in Maintenance
            </p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Weekly Revenue</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- PC Usage Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">PC Occupancy Rate</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Active Sessions -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold">Active Sessions</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <template x-for="session in activeSessions" :key="session.id">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-desktop text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold" x-text="session.pc.name"></p>
                                    <p class="text-sm text-gray-500" x-text="session.user_name"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600" x-text="formatRupiah(session.total_cost)"></p>
                                <p class="text-sm text-gray-500" x-text="Math.floor(session.duration / 60) + 'h'"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <a href="{{ route('sessions.index') }}" class="block mt-4 text-center text-blue-600 hover:text-blue-700 font-semibold">
                    View All Sessions →
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold">Recent Orders</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <template x-for="order in recentOrders" :key="order.id">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-utensils text-yellow-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold" x-text="'Order #' + order.id"></p>
                                    <p class="text-sm text-gray-500" x-text="order.table_id"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold" x-text="formatRupiah(order.total)"></p>
                                <span class="text-xs px-2 py-1 rounded-full"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800': order.order_status === 'RECEIVED',
                                          'bg-blue-100 text-blue-800': order.order_status === 'PREPARING',
                                          'bg-green-100 text-green-800': order.order_status === 'READY'
                                      }"
                                      x-text="order.order_status"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <a href="{{ route('orders.index') }}" class="block mt-4 text-center text-blue-600 hover:text-blue-700 font-semibold">
                    View All Orders →
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboard() {
    return {
        summary: {
            todayRevenue: 0,
            revenueGrowth: 0,
            activeSessions: 0,
            pcUtilization: 0,
            completeOrders: 0,
            availablePCs: 0,
            totalPCs: 45,
            maintenancePCs: 0
        },
        activeSessions: [],
        recentOrders: [],
        revenueChart: null,
        occupancyChart: null,
        weeklyRevenueData: [],

        async init() {
            // First load data
            await Promise.all([
                this.loadSummary(),
                this.loadActiveSessions(),
                this.loadRecentOrders()
            ]);
            
            // Then initialize charts AFTER data is loaded
            setTimeout(() => {
                this.initCharts();
            }, 100);
            
            // 🔥 WebSocket: Listen to real-time updates
            this.listenToUpdates();
        },

        // 🔥 WebSocket: Listen to all relevant updates
        listenToUpdates() {
            console.log('🔌 Connecting to WebSocket for dashboard updates...');
            
            // Listen to session updates
            window.Echo.channel('sessions')
                .listen('.session.updated', () => {
                    console.log('📡 Session updated, refreshing dashboard...');
                    this.loadSummary();
                    this.loadActiveSessions();
                });
            
            // Listen to order updates
            window.Echo.channel('orders')
                .listen('.order.status.changed', () => {
                    console.log('📡 Order updated, refreshing dashboard...');
                    this.loadSummary();
                    this.loadRecentOrders();
                });
            
            // Listen to PC updates
            window.Echo.channel('pc-monitoring')
                .listen('.pc.status.changed', () => {
                    console.log('📡 PC updated, refreshing dashboard...');
                    this.loadSummary();
                });
            
            console.log('✅ WebSocket listeners registered for dashboard');
        },

        async loadSummary() {
            try {
                // Load PCs
                const pcsResponse = await apiCall('/pcs');
                if (pcsResponse.success) {
                    const pcs = pcsResponse.data;
                    this.summary.totalPCs = pcs.length;
                    this.summary.availablePCs = pcs.filter(pc => pc.status === 'IDLE').length;
                    this.summary.maintenancePCs = pcs.filter(pc => pc.status === 'MAINTENANCE').length;
                    this.summary.activeSessions = pcs.filter(pc => pc.status === 'IN_USE').length;
                    this.summary.pcUtilization = Math.round((this.summary.activeSessions / this.summary.totalPCs) * 100);
                }

                // Load Orders
                const ordersResponse = await apiCall('/orders');
                if (ordersResponse.success) {
                    const orders = ordersResponse.data;
                    this.summary.completeOrders = orders.filter(o => 
                        o.order_status === 'COMPLETED'
                    ).length;
                }

                // Load Revenue from Analytics API
                await this.loadRevenueData();

            } catch (error) {
                console.error('Error loading summary:', error);
            }
        },

        async loadRevenueData() {
            try {
                // Get today's revenue
                const today = new Date();
                const todayStr = today.toISOString().split('T')[0];
                
                const todayResponse = await apiCall(`/analytics/revenue?start_date=${todayStr}&end_date=${todayStr}`);
                if (todayResponse.success) {
                    this.summary.todayRevenue = todayResponse.summary.total_revenue || 0;
                }

                // Get yesterday's revenue for growth calculation
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                const yesterdayStr = yesterday.toISOString().split('T')[0];
                
                const yesterdayResponse = await apiCall(`/analytics/revenue?start_date=${yesterdayStr}&end_date=${yesterdayStr}`);
                if (yesterdayResponse.success) {
                    const yesterdayRevenue = yesterdayResponse.summary.total_revenue || 0;
                    if (yesterdayRevenue > 0) {
                        this.summary.revenueGrowth = (((this.summary.todayRevenue - yesterdayRevenue) / yesterdayRevenue) * 100).toFixed(1);
                    } else {
                        this.summary.revenueGrowth = this.summary.todayRevenue > 0 ? 100 : 0;
                    }
                }

                // Get weekly revenue for chart (last 7 days)
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 6);
                const weekAgoStr = weekAgo.toISOString().split('T')[0];
                
                const weeklyResponse = await apiCall(`/analytics/revenue?start_date=${weekAgoStr}&end_date=${todayStr}`);
                if (weeklyResponse.success) {
                    // Group revenue by date
                    const revenueByDate = {};
                    
                    // Process PC revenue
                    if (weeklyResponse.data.pc_revenue) {
                        weeklyResponse.data.pc_revenue.forEach(item => {
                            if (!revenueByDate[item.date]) {
                                revenueByDate[item.date] = 0;
                            }
                            revenueByDate[item.date] += parseFloat(item.amount || 0);
                        });
                    }
                    
                    // Process F&B revenue
                    if (weeklyResponse.data.f_b_revenue) {
                        weeklyResponse.data.f_b_revenue.forEach(item => {
                            if (!revenueByDate[item.date]) {
                                revenueByDate[item.date] = 0;
                            }
                            revenueByDate[item.date] += parseFloat(item.amount || 0);
                        });
                    }
                    
                    // Create array for last 7 days
                    this.weeklyRevenueData = [];
                    for (let i = 6; i >= 0; i--) {
                        const date = new Date(today);
                        date.setDate(date.getDate() - i);
                        const dateStr = date.toISOString().split('T')[0];
                        this.weeklyRevenueData.push(revenueByDate[dateStr] || 0);
                    }
                }

            } catch (error) {
                console.error('Error loading revenue data:', error);
                // Fallback to 0 if error
                this.summary.todayRevenue = 0;
                this.summary.revenueGrowth = 0;
                this.weeklyRevenueData = [0, 0, 0, 0, 0, 0, 0];
            }
        },

        async loadActiveSessions() {
            try {
                const response = await apiCall('/sessions?status=ACTIVE&include=pc');
                if (response.success) {
                    this.activeSessions = response.data.slice(0, 5);
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        },

        async loadRecentOrders() {
            try {
                const response = await apiCall('/orders?limit=5');
                if (response.success) {
                    this.recentOrders = response.data.slice(0, 5);
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        },

        initCharts() {
            // Destroy existing charts if they exist
            if (this.revenueChart) {
                this.revenueChart.destroy();
            }
            if (this.occupancyChart) {
                this.occupancyChart.destroy();
            }

            // Ensure we have default data if not loaded
            if (!this.weeklyRevenueData || this.weeklyRevenueData.length === 0) {
                this.weeklyRevenueData = [0, 0, 0, 0, 0, 0, 0];
            }

            // Revenue Chart - Using BAR chart for stability
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                // Generate labels for last 7 days
                const labels = [];
                const today = new Date();
                const dayNames = ['Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'];
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(dayNames[date.getDay()]);
                }
                
                this.revenueChart = new Chart(revenueCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue',
                            data: this.weeklyRevenueData,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(59, 130, 246, 0.8)'
                            ],
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: { 
                                display: false 
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return 'Pendapatan: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Occupancy Chart
            const occupancyCanv = document.getElementById('occupancyChart');
            if (occupancyCanv) {
                const occupancyCtx = occupancyCanv.getContext('2d');
                this.occupancyChart = new Chart(occupancyCtx, {
                type: 'doughnut',
                data: {
                    labels: ['In Use', 'Available', 'Maintenance'],
                    datasets: [{
                        data: [this.summary.activeSessions, this.summary.availablePCs, this.summary.maintenancePCs],
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
