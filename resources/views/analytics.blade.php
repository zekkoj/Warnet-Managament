@extends('layouts.app')

@section('title', 'Analytics - Warnet Management System')
@section('page-title', 'Analytics & Reports')

@section('content')
<div x-data="analytics()" x-init="init()">
    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-col md:flex-row items-end justify-start gap-4">
            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Start Date</label>
                    <input type="date" x-model="dateRange.start" @change="loadData()"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <span class="text-gray-400">to</span>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">End Date</label>
                    <input type="date" x-model="dateRange.end" @change="loadData()"
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="setQuickRange('today')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
                    Today
                </button>
                <button @click="setQuickRange('week')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
                    This Week
                </button>
                <button @click="setQuickRange('month')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
                    This Month
                </button>
            </div>
        </div>
    </div>

    <!-- Revenue Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-blue-600 rounded-lg shadow-lg p-6 text-white">
            <h3 class="text-blue-100 text-sm mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold mb-2" x-text="formatRupiah(revenue.total)">Rp 0</p>
            <div class="flex items-center text-sm">
                <i class="fas fa-chart-line mr-2"></i>
                <span>All Sources</span>
            </div>
        </div>

        <div class="bg-green-600 rounded-lg shadow-lg p-6 text-white">
            <h3 class="text-green-100 text-sm mb-2">PC Rental Revenue</h3>
            <p class="text-3xl font-bold mb-2" x-text="formatRupiah(revenue.pcRental)">Rp 0</p>
            <div class="flex items-center text-sm">
                <span x-text="revenue.total > 0 ? Math.round((revenue.pcRental / revenue.total) * 100) + '%' : '0%'">0%</span>
                <span class="ml-1">of total</span>
            </div>
        </div>

        <div class="bg-yellow-500 rounded-lg shadow-lg p-6 text-white">
            <h3 class="text-yellow-100 text-sm mb-2">F&B Revenue</h3>
            <p class="text-3xl font-bold mb-2" x-text="formatRupiah(revenue.fnb)">Rp 0</p>
            <div class="flex items-center text-sm">
                <span x-text="revenue.total > 0 ? Math.round((revenue.fnb / revenue.total) * 100) + '%' : '0%'">0%</span>
                <span class="ml-1">of total</span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Trend -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue Trend</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Category -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue Distribution</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="revenueDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- PC Usage Statistics -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">PC Usage Statistics</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="text-center p-4 bg-blue-600 text-white rounded-lg shadow-md">
                <p class="text-sm text-blue-100 mb-2">Total Sessions</p>
                <p class="text-3xl font-bold" x-text="pcUsage.totalSessions">0</p>
            </div>
            <div class="text-center p-4 bg-green-600 text-white rounded-lg shadow-md">
                <p class="text-sm text-green-100 mb-2">Completed Sessions</p>
                <p class="text-3xl font-bold" x-text="pcUsage.completedSessions">0</p>
            </div>
            <div class="text-center p-4 bg-orange-600 text-white rounded-lg shadow-md">
                <p class="text-sm text-orange-100 mb-2">Completion Rate</p>
                <p class="text-3xl font-bold" x-text="pcUsage.completionRate.toFixed(1) + '%'">0%</p>
            </div>
            <div class="text-center p-4 bg-purple-600 text-white rounded-lg shadow-md">
                <p class="text-sm text-purple-100 mb-2">Avg Duration</p>
                <p class="text-3xl font-bold" x-text="pcUsage.avgDuration + 'h'">0h</p>
            </div>
        </div>

        <!-- Peak Hours Chart -->
        <div style="height: 250px; position: relative;">
            <canvas id="peakHoursChart"></canvas>
        </div>
    </div>

    <!-- Most Used PCs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top 10 Most Used PCs</h3>
            <div class="space-y-3">
                <template x-for="(pc, index) in pcUsage.mostUsedPCs" :key="pc.pc_id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold"
                                  x-text="index + 1"></span>
                            <div>
                                <p class="font-semibold text-gray-800" x-text="pc.pc ? pc.pc.name : 'PC-' + pc.pc_id">PC Name</p>
                                <p class="text-sm text-gray-600" x-text="Math.floor(pc.total_minutes / 60) + ' hours'"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600" x-text="pc.usage_count + ' sessions'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Selling F&B -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Selling F&B Items</h3>
            <div class="space-y-3">
                <template x-for="(item, index) in fnbAnalytics.topItems" :key="item.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 bg-yellow-600 text-white rounded-full flex items-center justify-center font-bold"
                                  x-text="index + 1"></span>
                            <div>
                                <p class="font-semibold text-gray-800" x-text="item.name">Item Name</p>
                                <p class="text-sm text-gray-600" x-text="item.category"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600" x-text="formatRupiah(item.total_revenue)"></p>
                            <p class="text-sm text-gray-600" x-text="item.total_quantity + ' sold'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- F&B Category Breakdown -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">F&B Category Performance</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <template x-for="cat in fnbAnalytics.categoryBreakdown" :key="cat.category">
                <div class="text-center p-4 bg-rose-600 text-white rounded-lg shadow-md">
                    <p class="text-sm text-white font-semibold mb-2" x-text="cat.category"></p>
                    <p class="text-2xl font-bold mb-1" x-text="formatRupiah(cat.total_revenue)"></p>
                    <p class="text-sm text-white" x-text="cat.total_quantity + ' items sold'"></p>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function analytics() {
    return {
        dateRange: {
            start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
            end: new Date().toISOString().split('T')[0]
        },
        revenue: {
            total: 0,
            pcRental: 0,
            fnb: 0
        },
        pcUsage: {
            totalSessions: 0,
            completedSessions: 0,
            completionRate: 0,
            avgDuration: 0,
            mostUsedPCs: [],
            peakHours: []
        },
        fnbAnalytics: {
            topItems: [],
            categoryBreakdown: []
        },
        trendData: {
            dates: [],
            revenues: []
        },
        revenueTrendChart: null,
        revenueDistributionChart: null,
        peakHoursChart: null,
        loading: true,
        chartsInitialized: false,

        async init() {
            // Wait for API token to be ready
            await window.appReady;
            
            // Wait for DOM to be fully loaded
            await this.$nextTick();
            
            try {
                await this.loadData();
                
                // Add a small delay to ensure canvas elements are fully rendered
                setTimeout(() => {
                    this.initCharts();
                }, 100);
            } catch (error) {
                console.error('Error initializing analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadData() {
            try {
                // Reset charts flag to allow reinit on date change
                this.chartsInitialized = false;
                
                await Promise.all([
                    this.loadRevenue(),
                    this.loadPCUsage(),
                    this.loadFnBAnalytics()
                ]);
                
                // Update charts after data is loaded
                this.$nextTick(() => {
                    if (!this.chartsInitialized) {
                        this.initCharts();
                    } else {
                        this.updateCharts();
                    }
                });
            } catch (error) {
                console.error('Error loading data:', error);
            }
        },

        async loadRevenue() {
            try {
                const response = await apiCall(`/analytics/revenue?start_date=${this.dateRange.start}&end_date=${this.dateRange.end}`);
                console.log('Revenue API Response:', response);
                if (response.success) {
                    this.revenue.total = parseFloat(response.summary.total_revenue || 0);
                    this.revenue.pcRental = parseFloat(response.summary.pc_rental_revenue || 0);
                    this.revenue.fnb = parseFloat(response.summary['f&b_revenue'] || 0);
                    console.log('Revenue Data:', this.revenue);
                    
                    // Process trend data from pc_revenue and f&b_revenue
                    const trendMap = {};
                    
                    // Process PC revenue
                    if (response.data && response.data.pc_revenue) {
                        response.data.pc_revenue.forEach(item => {
                            if (!trendMap[item.date]) {
                                trendMap[item.date] = 0;
                            }
                            trendMap[item.date] += parseFloat(item.amount || 0);
                        });
                    }
                    
                    // Process F&B revenue
                    if (response.data && response.data['f&b_revenue']) {
                        response.data['f&b_revenue'].forEach(item => {
                            if (!trendMap[item.date]) {
                                trendMap[item.date] = 0;
                            }
                            trendMap[item.date] += parseFloat(item.amount || 0);
                        });
                    }
                    
                    // Convert to arrays and sort by date
                    const sortedDates = Object.keys(trendMap).sort();
                    this.trendData.dates = sortedDates;
                    this.trendData.revenues = sortedDates.map(date => trendMap[date]);
                    
                    console.log('Revenue trend data:', { dates: this.trendData.dates, revenues: this.trendData.revenues });
                }
            } catch (error) {
                console.error('Error loading revenue:', error);
            }
        },

        async loadPCUsage() {
            try {
                const response = await apiCall(`/analytics/pc-usage?start_date=${this.dateRange.start}&end_date=${this.dateRange.end}`);
                if (response.success) {
                    this.pcUsage.totalSessions = response.summary.total_sessions || 0;
                    this.pcUsage.completedSessions = response.summary.completed_sessions || 0;
                    this.pcUsage.completionRate = response.summary.completion_rate || 0;
                    this.pcUsage.mostUsedPCs = response.data.most_used_pcs || [];
                    
                    // Process peak hours - ensure it has sessions property
                    this.pcUsage.peakHours = (response.data.peak_hours || []).map(h => ({
                        hour: h.hour,
                        sessions: h.count || 0,
                        avg_duration: h.avg_duration || 0
                    })).sort((a, b) => a.hour - b.hour);
                    
                    const avgMinutes = this.pcUsage.peakHours.length > 0 
                        ? this.pcUsage.peakHours.reduce((sum, h) => sum + parseFloat(h.avg_duration || 0), 0) / this.pcUsage.peakHours.length
                        : 0;
                    this.pcUsage.avgDuration = Math.round(avgMinutes / 60);
                    
                    console.log('Peak hours data:', this.pcUsage.peakHours);
                }
            } catch (error) {
                console.error('Error loading PC usage:', error);
            }
        },

        async loadFnBAnalytics() {
            try {
                const response = await apiCall(`/analytics/fnb?start_date=${this.dateRange.start}&end_date=${this.dateRange.end}`);
                if (response.success) {
                    this.fnbAnalytics.topItems = response.data.top_items || [];
                    this.fnbAnalytics.categoryBreakdown = response.data.category_breakdown || [];
                }
            } catch (error) {
                console.error('Error loading F&B analytics:', error);
            }
        },

        setQuickRange(range) {
            const today = new Date();
            
            if (range === 'today') {
                this.dateRange.start = today.toISOString().split('T')[0];
                this.dateRange.end = today.toISOString().split('T')[0];
            } else if (range === 'week') {
                const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                this.dateRange.start = weekStart.toISOString().split('T')[0];
                this.dateRange.end = new Date().toISOString().split('T')[0];
            } else if (range === 'month') {
                this.dateRange.start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                this.dateRange.end = new Date().toISOString().split('T')[0];
            }
            
            // Reset charts flag to allow reinit
            this.chartsInitialized = false;
            this.loadData();
        },

        formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount || 0);
        },

        initCharts() {
            try {
                // Skip if already initialized
                if (this.chartsInitialized) {
                    return;
                }

                // Destroy existing charts if they exist
                if (this.revenueTrendChart) {
                    this.revenueTrendChart.destroy();
                    this.revenueTrendChart = null;
                }
                if (this.revenueDistributionChart) {
                    this.revenueDistributionChart.destroy();
                    this.revenueDistributionChart = null;
                }
                if (this.peakHoursChart) {
                    this.peakHoursChart.destroy();
                    this.peakHoursChart = null;
                }

                // Revenue Trend Chart
                const trendCtx = document.getElementById('revenueTrendChart');
                if (!trendCtx) {
                    console.error('Revenue trend chart canvas not found');
                    return;
                }
                
                // Validate canvas context
                const trendContext = trendCtx.getContext('2d');
                if (!trendContext) {
                    console.error('Cannot get 2d context for revenue trend chart');
                    return;
                }
                
                this.revenueTrendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: this.trendData.dates.length > 0 ? this.trendData.dates : ['No Data'],
                        datasets: [{
                            label: 'Revenue',
                            data: this.trendData.revenues.length > 0 ? this.trendData.revenues : [0],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value)
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => 'Pendapatan: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y)
                                }
                            }
                        }
                    }
                });

                // Revenue Distribution Chart
                const distCtx = document.getElementById('revenueDistributionChart');
                if (!distCtx) {
                    console.error('Revenue distribution chart canvas not found');
                    return;
                }
                
                // Validate canvas context
                const distContext = distCtx.getContext('2d');
                if (!distContext) {
                    console.error('Cannot get 2d context for revenue distribution chart');
                    return;
                }
                
                this.revenueDistributionChart = new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['PC Rental', 'F&B'],
                        datasets: [{
                            data: [parseFloat(this.revenue.pcRental) || 0, parseFloat(this.revenue.fnb) || 0],
                            backgroundColor: ['#10b981', '#f59e0b']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: (context) => context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed)
                                }
                            }
                        }
                    }
                });

                // Peak Hours Chart
                const peakCtx = document.getElementById('peakHoursChart');
                if (!peakCtx) {
                    console.error('Peak hours chart canvas not found');
                    return;
                }
                
                // Validate canvas context
                const peakContext = peakCtx.getContext('2d');
                if (!peakContext) {
                    console.error('Cannot get 2d context for peak hours chart');
                    return;
                }
                
                this.peakHoursChart = new Chart(peakCtx, {
                    type: 'bar',
                    data: {
                        labels: this.pcUsage.peakHours.length > 0 ? this.pcUsage.peakHours.map(h => h.hour.toString().padStart(2, '0') + ':00') : ['No Data'],
                        datasets: [{
                            label: 'Sessions',
                            data: this.pcUsage.peakHours.length > 0 ? this.pcUsage.peakHours.map(h => h.sessions || 0) : [0],
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                
                // Mark charts as initialized
                this.chartsInitialized = true;
                
                console.log('Charts initialized successfully');
            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        },

        updateCharts() {
            try {
                // Simply destroy all charts and reinit
                // This is more reliable than trying to update data
                if (this.revenueTrendChart) {
                    this.revenueTrendChart.destroy();
                    this.revenueTrendChart = null;
                }
                if (this.revenueDistributionChart) {
                    this.revenueDistributionChart.destroy();
                    this.revenueDistributionChart = null;
                }
                if (this.peakHoursChart) {
                    this.peakHoursChart.destroy();
                    this.peakHoursChart = null;
                }
                
                // Mark as not initialized to trigger reinit
                this.chartsInitialized = false;
                
                // Reinitialize with new data
                this.$nextTick(() => {
                    this.initCharts();
                });
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }
    }
}
</script>
@endpush
@endsection
