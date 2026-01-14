@extends('layouts.app')

@section('title', 'PC Monitoring - Warnet Management System')
@section('page-title', 'PC Monitoring')

@section('content')
<div x-data="pcMonitoring()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total PCs</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.total"></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-desktop text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">In Use</p>
                    <p class="text-3xl font-bold text-green-600" x-text="stats.inUse"></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-play-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Available</p>
                    <p class="text-3xl font-bold text-blue-600" x-text="stats.idle"></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Maintenance</p>
                    <p class="text-3xl font-bold text-yellow-600" x-text="stats.maintenance"></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-wrench text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Actions -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <select x-model="filter" @change="filterPCs()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all">All PCs</option>
                <option value="IDLE">Available</option>
                <option value="IN_USE">In Use</option>
                <option value="MAINTENANCE">Maintenance</option>
            </select>
            
            <select x-model="tierFilter" @change="filterPCs()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all">All Tiers</option>
                <option value="REGULER">Regular</option>
                <option value="VIP">VIP</option>
            </select>
        </div>

        <button @click="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>

    <!-- PC Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <template x-for="pc in filteredPCs" :key="pc.id">
            <div @click="selectPC(pc)" 
                 class="bg-white rounded-lg shadow-lg p-6 cursor-pointer transition-all hover:shadow-xl border-2"
                 x-bind:class="getPCCardClass(pc)">
                
                <!-- PC Icon -->
                <div class="flex justify-center mb-3">
                    <i class="fas fa-desktop text-5xl"
                       x-bind:class="getPCIconClass(pc)"></i>
                </div>

                <!-- PC Name -->
                <h3 class="text-center font-bold text-lg mb-2" x-text="pc.pc_code"></h3>

                <!-- Tier Badge -->
                <div class="flex justify-center mb-2">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                          :class="pc.tier === 'VIP' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
                          x-text="pc.tier"></span>
                </div>

                <!-- Status Badge -->
                <div class="flex justify-center">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                          x-bind:class="getPCStatusBadgeClass(pc)"
                          x-text="getPCStatusText(pc)"></span>
                </div>

                <!-- Session Info (if in use) -->
                <template x-if="pc.status === 'IN_USE' && pc.active_session">
                    <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-600 text-center">
                        <p class="font-semibold text-gray-800" x-text="pc.active_session.user_name"></p>
                        <template x-if="pc.active_session.status === 'PAUSED'">
                            <p class="text-yellow-600 mt-1 font-semibold">
                                <i class="fas fa-pause-circle mr-1"></i>
                                Session Paused
                            </p>
                        </template>
                        <p class="text-gray-600 mt-1">
                            <i class="fas fa-clock mr-1"></i>
                            <span x-text="formatTimeRemaining(pc.remaining_time)"></span>
                        </p>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- PC Detail Modal -->
    <div x-show="selectedPC" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="selectedPC = null">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <template x-if="selectedPC">
                <div>
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-2xl font-bold" x-text="selectedPC.pc_code"></h2>
                        <button @click="selectedPC = null" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-6">
                        <!-- PC Info - Top Section -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">PC Information</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2 font-medium">Tier</label>
                                    <select x-model="selectedPC.tier" @change="updateTier()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-semibold">
                                        <option value="REGULER">Regular</option>
                                        <option value="VIP">VIP</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-2 font-medium">Status</label>
                                    <div class="px-4 py-2 bg-gray-100 rounded-lg font-semibold text-gray-800" x-text="selectedPC.status"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Specifications Section -->
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <label class="block text-sm text-gray-700 mb-2 font-medium">Specifications</label>
                            <p class="text-gray-800 font-medium" x-text="selectedPC.specifications || 'Standard Configuration'"></p>
                        </div>

                        <!-- Active Session -->
                        <template x-if="selectedPC.active_session">
                            <div class="border-t pt-4">
                                <h3 class="font-semibold mb-3">Active Session</h3>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Customer:</span>
                                        <span class="font-semibold" x-text="selectedPC.active_session.user_name"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Duration:</span>
                                        <span x-text="Math.floor(selectedPC.active_session.duration / 60) + ' hours ' + (selectedPC.active_session.duration % 60) + ' mins'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Elapsed:</span>
                                        <span x-text="formatTimeRemaining(selectedPC.elapsed_time)"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Remaining:</span>
                                        <span class="font-bold text-lg" 
                                              :class="selectedPC.remaining_time <= 300 ? 'text-red-600' : 'text-green-600'"
                                              x-text="formatTimeRemaining(selectedPC.remaining_time)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Cost:</span>
                                        <span class="font-bold text-green-600" x-text="formatRupiah(selectedPC.active_session.total_cost)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Started:</span>
                                        <span x-text="formatDateTime(selectedPC.active_session.start_time)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Actions -->
                        <div class="border-t pt-4 space-y-2">
                            <!-- Start Session (only if IDLE) -->
                            <template x-if="selectedPC.status === 'IDLE'">
                                <button @click="showStartSessionModal = true" 
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fas fa-play mr-2"></i>Start Session
                                </button>
                            </template>
                            
                            <!-- Pause Session (only if IN_USE and ACTIVE) -->
                            <template x-if="selectedPC.status === 'IN_USE' && selectedPC.active_session && selectedPC.active_session.status === 'ACTIVE'">
                                <button @click="pauseSession()" 
                                        class="w-full px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                                    <i class="fas fa-pause mr-2"></i>Pause Session
                                </button>
                            </template>
                            
                            <!-- Resume Session (only if IN_USE and PAUSED) -->
                            <template x-if="selectedPC.status === 'IN_USE' && selectedPC.active_session && selectedPC.active_session.status === 'PAUSED'">
                                <button @click="resumeSession()" 
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fas fa-play mr-2"></i>Resume Session
                                </button>
                            </template>
                            
                            <!-- Complete Session (only if IN_USE) -->
                            <template x-if="selectedPC.status === 'IN_USE'">
                                <button @click="completeSession()" 
                                        class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                    <i class="fas fa-stop mr-2"></i>Complete Session
                                </button>
                            </template>
                            
                            <!-- Set Maintenance (only if IDLE or already MAINTENANCE) -->
                            <template x-if="selectedPC.status === 'IDLE' || selectedPC.status === 'MAINTENANCE'">
                                <button @click="toggleMaintenance()" 
                                        class="w-full px-4 py-2 text-white rounded-lg"
                                        :class="selectedPC.status === 'MAINTENANCE' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-yellow-600 hover:bg-yellow-700'">
                                    <i class="fas mr-2" :class="selectedPC.status === 'MAINTENANCE' ? 'fa-check' : 'fa-wrench'"></i>
                                    <span x-text="selectedPC.status === 'MAINTENANCE' ? 'Set Available' : 'Set Maintenance'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Start Session Modal -->
    <div x-show="showStartSessionModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showStartSessionModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold">Start New Session</h2>
                <p class="text-gray-600" x-text="'PC: ' + (selectedPC ? selectedPC.pc_code : '')"></p>
            </div>
            
            <form @submit.prevent="startSession()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Customer Name (Optional)</label>
                    <input type="text" x-model="sessionForm.user_name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="Enter customer name">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Duration (minutes) *</label>
                    <input type="number" x-model="sessionForm.duration" required min="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="e.g., 60">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Estimated Cost</label>
                    <div class="text-2xl font-bold text-green-600" x-text="calculateEstimatedCost()"></div>
                </div>
                
                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-semibold mb-3">Payment Method *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="sessionForm.payment_method = 'CASH'" 
                                :class="sessionForm.payment_method === 'CASH' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                            <i class="fas fa-money-bill-wave mr-2"></i>Cash
                        </button>
                        <button type="button" @click="sessionForm.payment_method = 'QRIS'" 
                                :class="sessionForm.payment_method === 'QRIS' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                            <i class="fas fa-qrcode mr-2"></i>QRIS
                        </button>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i>Start Session
                    </button>
                    <button type="button" @click="showStartSessionModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pcMonitoring() {
    return {
        pcs: [],
        filteredPCs: [],
        selectedPC: null,
        showStartSessionModal: false,
        filter: 'all',
        tierFilter: 'all',
        sessionForm: {
            user_name: '',
            duration: 60,
            payment_method: 'CASH'
        },
        stats: {
            total: 0,
            idle: 0,
            inUse: 0,
            maintenance: 0
        },
        completingSessionIds: new Set(), // Track sessions being auto-completed
        countdownInterval: null, // Track countdown timer interval

        async init() {
            await this.loadPCs();
            // 🔥 WebSocket: Listen to real-time PC updates
            this.listenToPCUpdates();
            // Start countdown timer untuk real-time countdown
            this.startCountdownTimer();
        },

        // Helper function to parse timestamp from Laravel
        parseUTCTimestamp(timestamp) {
            if (!timestamp) return new Date();
            // Laravel now uses Asia/Jakarta timezone, so parse as-is
            return new Date(timestamp);
        },

        startCountdownTimer() {
            // Clear any existing interval to prevent multiple timers
            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
            }
            
            // Update remaining time display every second
            this.countdownInterval = setInterval(() => {
                // Update remaining time for each PC with active session
                this.pcs.forEach(pc => {
                    if (pc.status === 'IN_USE' && pc.active_session) {
                        // CRITICAL: Skip PAUSED sessions - don't update their time
                        if (pc.active_session.status === 'PAUSED') {
                            // For paused sessions, time should be frozen
                            console.log(`PC ${pc.pc_code} session is PAUSED, time frozen at ${pc.remaining_time}s`);
                            return;
                        }
                        
                        // For ACTIVE sessions: simple countdown (decrement by 1 second)
                        if (pc.active_session.status === 'ACTIVE') {
                            // If remaining_time is already set, just decrement it
                            if (pc.remaining_time !== undefined && pc.remaining_time !== null && pc.remaining_time > 0) {
                                pc.remaining_time--;
                                pc.elapsed_time = (pc.elapsed_time || 0) + 1;
                            } else {
                                // Fallback: Calculate from start_time (for first load or after resume)
                                const now = Math.floor(Date.now() / 1000);
                                const startTimeUTC = this.parseUTCTimestamp(pc.active_session.start_time);
                                const startTime = Math.floor(startTimeUTC.getTime() / 1000);
                                const durationSeconds = pc.active_session.duration * 60;
                                const pausedDurationSeconds = (pc.active_session.paused_duration || 0) * 60;
                                
                                // Calculate effective elapsed time (excluding paused time)
                                const totalElapsed = now - startTime;
                                const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                                
                                // Remaining time = duration - effective elapsed time
                                pc.remaining_time = Math.max(0, durationSeconds - effectiveElapsed);
                                pc.elapsed_time = Math.max(0, effectiveElapsed);
                            }
                            
                            // Auto-complete when time expires
                            if (pc.remaining_time <= 0 && !this.completingSessionIds.has(pc.current_session_id)) {
                                this.completingSessionIds.add(pc.current_session_id);
                                
                                console.log(`Auto-completing expired session for PC ${pc.pc_code}`);
                                apiCall(`/sessions/${pc.current_session_id}/complete`, 'POST')
                                    .then(response => {
                                        if (response.success) {
                                            console.log(`Session for PC ${pc.pc_code} auto-completed successfully`);
                                            this.loadPCs();
                                        } else {
                                            console.error('Failed to auto-complete session:', response.message);
                                            this.completingSessionIds.delete(pc.current_session_id);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error auto-completing session:', error);
                                        this.completingSessionIds.delete(pc.current_session_id);
                                    });
                            }
                        }
                    }
                });
            }, 1000); // Update setiap 1 detik
        },

        async loadPCs() {
            try {
                const response = await apiCall('/pcs?include=activeSession');
                if (response.success) {
                    const now = Math.floor(Date.now() / 1000);
                    
                    // Calculate remaining time untuk setiap PC dengan active session
                    this.pcs = response.data.map(pc => {
                        if (pc.status === 'IN_USE' && pc.active_session) {
                            // 🔥 CRITICAL: For PAUSED sessions, ALWAYS use remaining_seconds from server
                            // Never recalculate for paused sessions to prevent time drift on refresh
                            if (pc.active_session.status === 'PAUSED') {
                                if (pc.active_session.remaining_seconds !== null && pc.active_session.remaining_seconds !== undefined) {
                                    console.log(`PAUSED session on ${pc.pc_code}: using saved remaining_seconds = ${pc.active_session.remaining_seconds}s`);
                                    return {
                                        ...pc,
                                        remaining_time: pc.active_session.remaining_seconds
                                    };
                                } else {
                                    console.warn(`PAUSED session on ${pc.pc_code} has no remaining_seconds saved!`);
                                }
                            }
                            
                            // For ACTIVE sessions: ALWAYS calculate from start_time + paused_duration
                            // NEVER use remaining_seconds from database (it should be null anyway)
                            const startTimeUTC = this.parseUTCTimestamp(pc.active_session.start_time);
                            const startTime = Math.floor(startTimeUTC.getTime() / 1000);
                            const durationSeconds = pc.active_session.duration * 60;
                            const pausedDurationSeconds = (pc.active_session.paused_duration || 0) * 60;
                            
                            // Calculate effective elapsed time (excluding paused time)
                            const totalElapsed = now - startTime;
                            const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                            
                            // Remaining time = duration - effective elapsed time
                            const remainingSeconds = Math.max(0, durationSeconds - effectiveElapsed);
                            
                            console.log(`Calculating remaining time for ACTIVE session on ${pc.pc_code}: ${remainingSeconds}s`);
                            
                            return {
                                ...pc,
                                remaining_time: remainingSeconds
                            };
                        }
                        return pc;
                    });
                    
                    this.calculateStats();
                    this.filterPCs();
                }
            } catch (error) {
                console.error('Error loading PCs:', error);
            }
        },

        // 🔥 WebSocket: Listen to real-time PC updates
        listenToPCUpdates() {
            // Check if Echo is available
            if (typeof window.Echo === 'undefined') {
                console.warn('⚠️ Echo not loaded, WebSocket disabled');
                return;
            }
            
            console.log('🔌 Connecting to WebSocket for PC updates...');
            
            window.Echo.channel('pc-monitoring')
                .listen('.pc.status.changed', (event) => {
                    console.log('📡 PC updated via WebSocket:', event.pc);
                    this.handlePCUpdate(event.pc);
                });
            
            // Also listen to session updates to refresh PC data
            window.Echo.channel('sessions')
                .listen('.session.updated', (event) => {
                    console.log('📡 Session updated, refreshing PCs...');
                    // Reload PCs when sessions change
                    this.loadPCs();
                });
            
            console.log('✅ WebSocket listeners registered for PC monitoring');
        },

        // 🔥 WebSocket: Handle incoming PC updates
        handlePCUpdate(updatedPC) {
            const index = this.pcs.findIndex(pc => pc.id === updatedPC.id);
            
            if (index !== -1) {
                // Calculate remaining_time if PC has active session
                let remainingTime = 0;
                if (updatedPC.status === 'IN_USE' && updatedPC.active_session) {
                    // 🔥 PRIORITY: Use remaining_seconds from server if available
                    if (updatedPC.active_session.remaining_seconds !== null && updatedPC.active_session.remaining_seconds !== undefined) {
                        remainingTime = updatedPC.active_session.remaining_seconds;
                    } else {
                        // Fallback: Calculate from start_time
                        const now = Math.floor(Date.now() / 1000);
                        const startTimeUTC = this.parseUTCTimestamp(updatedPC.active_session.start_time);
                        const startTime = Math.floor(startTimeUTC.getTime() / 1000);
                        const durationSeconds = updatedPC.active_session.duration * 60;
                        const pausedDurationSeconds = (updatedPC.active_session.paused_duration || 0) * 60;
                        
                        // Calculate effective elapsed time (excluding paused time)
                        const totalElapsed = now - startTime;
                        const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                        
                        // Remaining time = duration - effective elapsed time
                        remainingTime = Math.max(0, durationSeconds - effectiveElapsed);
                    }
                }
                
                this.pcs[index] = {
                    ...updatedPC,
                    remaining_time: remainingTime
                };
                
                console.log(`✅ Updated PC ${updatedPC.pc_code} locally with remaining_time: ${remainingTime}s`);
            }
            
            this.calculateStats();
            this.filterPCs();
        },


        calculateStats() {
            this.stats.total = this.pcs.length;
            this.stats.idle = this.pcs.filter(pc => pc.status === 'IDLE').length;
            this.stats.inUse = this.pcs.filter(pc => pc.status === 'IN_USE').length;
            this.stats.maintenance = this.pcs.filter(pc => pc.status === 'MAINTENANCE').length;
        },

        filterPCs() {
            this.filteredPCs = this.pcs.filter(pc => {
                const statusMatch = this.filter === 'all' || pc.status === this.filter;
                const tierMatch = this.tierFilter === 'all' || pc.tier === this.tierFilter;
                return statusMatch && tierMatch;
            });
        },

        selectPC(pc) {
            this.selectedPC = pc;
            this.showStartSessionModal = false;
            this.sessionForm = { user_name: '', duration: 60, payment_method: 'CASH' };
        },

        calculateEstimatedCost() {
            if (!this.selectedPC || !this.sessionForm.duration) return 'Rp 0';
            
            // Harga sesuai PricingService.php
            const basePrice = this.selectedPC.type === 'VIP' ? 10000 : 7000;  // Jam pertama
            const tier2Price = this.selectedPC.type === 'VIP' ? 8000 : 6000;  // Jam ke-2+
            
            const hours = Math.ceil(this.sessionForm.duration / 60);
            let cost = 0;
            
            if (hours > 0) {
                cost += basePrice;  // Jam pertama
                if (hours > 1) {
                    cost += (hours - 1) * tier2Price;  // Jam berikutnya
                }
            }
            
            return formatRupiah(cost);
        },

        async startSession() {
            if (!this.selectedPC || !this.sessionForm.duration) {
                alert('Please fill in all required fields');
                return;
            }

            try {
                const response = await apiCall('/sessions', 'POST', {
                    pc_id: this.selectedPC.id,
                    duration: parseInt(this.sessionForm.duration),
                    user_name: this.sessionForm.user_name || 'Guest',
                    tier: this.selectedPC.type,
                    payment_method: this.sessionForm.payment_method
                });

                if (response.success) {
                    alert('Session started successfully!');
                    this.showStartSessionModal = false;
                    this.selectedPC = null;
                    this.sessionForm = { user_name: '', duration: 60, payment_method: 'CASH' };
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to start session'));
                }
            } catch (error) {
                console.error('Error starting session:', error);
                alert('Error starting session. Please try again.');
            }
        },

        async completeSession() {
            if (!this.selectedPC || !this.selectedPC.current_session_id) {
                alert('No active session found');
                return;
            }

            if (!confirm('Are you sure you want to complete this session?')) return;

            try {
                const response = await apiCall(`/sessions/${this.selectedPC.current_session_id}/complete`, 'POST', {
                    payment_method: 'CASH'
                });
                
                if (response.success) {
                    alert('Session completed successfully!\nTotal cost: ' + formatRupiah(response.data.total_cost));
                    this.selectedPC = null;
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to complete session'));
                }
            } catch (error) {
                console.error('Error completing session:', error);
                alert('Error completing session. Please try again.');
            }
        },

        async pauseSession() {
            if (!this.selectedPC || !this.selectedPC.current_session_id) {
                alert('No active session found');
                return;
            }

            try {
                // Send remaining_time to backend for accurate tracking
                const response = await apiCall(`/sessions/${this.selectedPC.current_session_id}/pause`, 'POST', {
                    remaining_seconds: this.selectedPC.remaining_time
                });
                
                if (response.success) {
                    alert('Session paused successfully!');
                    this.selectedPC = null;
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to pause session'));
                }
            } catch (error) {
                console.error('Error pausing session:', error);
                alert('Error pausing session. Please try again.');
            }
        },

        async resumeSession() {
            if (!this.selectedPC || !this.selectedPC.current_session_id) {
                alert('No active session found');
                return;
            }

            try {
                console.log(`Resuming session for PC ${this.selectedPC.pc_code}`, {
                    session_id: this.selectedPC.current_session_id,
                    remaining_time_before: this.selectedPC.remaining_time
                });
                
                const response = await apiCall(`/sessions/${this.selectedPC.current_session_id}/resume`, 'POST');
                
                if (response.success) {
                    console.log(`Session resumed successfully for PC ${this.selectedPC.pc_code}`, {
                        remaining_seconds: response.data.remaining_seconds,
                        status: response.data.status
                    });
                    
                    alert('Session resumed successfully!');
                    this.selectedPC = null;
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to resume session'));
                }
            } catch (error) {
                console.error('Error resuming session:', error);
                alert('Error resuming session. Please try again.');
            }
        },

        async updateStatus(status) {
            if (!this.selectedPC) return;

            try {
                const response = await apiCall(`/pcs/${this.selectedPC.id}`, 'PUT', { status });
                if (response.success) {
                    alert('PC status updated successfully!');
                    this.selectedPC = null;
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to update PC status'));
                }
            } catch (error) {
                console.error('Error updating PC status:', error);
                alert('Error updating PC status');
            }
        },

        async toggleMaintenance() {
            if (!this.selectedPC) return;

            const newStatus = this.selectedPC.status === 'MAINTENANCE' ? 'IDLE' : 'MAINTENANCE';
            const confirmMessage = newStatus === 'MAINTENANCE' 
                ? 'Set this PC to maintenance mode?' 
                : 'Set this PC back to available?';

            if (!confirm(confirmMessage)) return;

            try {
                const response = await apiCall(`/pcs/${this.selectedPC.id}`, 'PUT', { status: newStatus });
                if (response.success) {
                    alert(`PC status updated to ${newStatus}!`);
                    this.selectedPC = null;
                    await this.loadPCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to update PC status'));
                }
            } catch (error) {
                console.error('Error updating PC status:', error);
                alert('Error updating PC status');
            }
        },

        async updateTier() {
            if (!this.selectedPC) return;

            try {
                const response = await apiCall(`/pcs/${this.selectedPC.id}`, 'PUT', { type: this.selectedPC.tier });
                if (response.success) {
                    alert('Tier updated successfully!');
                    await this.loadPCs();
                    // Re-select the PC to show updated data
                    const updatedPC = this.pcs.find(pc => pc.id === this.selectedPC.id);
                    if (updatedPC) {
                        this.selectedPC = updatedPC;
                    }
                } else {
                    alert('Error: ' + (response.message || 'Failed to update tier'));
                }
            } catch (error) {
                console.error('Error updating tier:', error);
                alert('Error updating tier');
            }
        },

        async refreshData() {
            await this.loadPCs();
        },

        // Helper functions for PC card styling
        getPCCardClass(pc) {
            const isPaused = pc.status === 'IN_USE' && pc.active_session && pc.active_session.status === 'PAUSED';
            if (isPaused || pc.status === 'MAINTENANCE') {
                return 'border-yellow-500 bg-yellow-50';
            } else if (pc.status === 'IN_USE') {
                return 'border-green-500 bg-green-50';
            } else if (pc.status === 'IDLE') {
                return 'border-blue-500 bg-blue-50';
            }
            return '';
        },

        getPCIconClass(pc) {
            const isPaused = pc.status === 'IN_USE' && pc.active_session && pc.active_session.status === 'PAUSED';
            if (isPaused || pc.status === 'MAINTENANCE') {
                return 'text-yellow-600';
            } else if (pc.status === 'IN_USE') {
                return 'text-green-600';
            } else if (pc.status === 'IDLE') {
                return 'text-blue-600';
            }
            return '';
        },

        getPCStatusBadgeClass(pc) {
            const isPaused = pc.status === 'IN_USE' && pc.active_session && pc.active_session.status === 'PAUSED';
            if (isPaused || pc.status === 'MAINTENANCE') {
                return 'bg-yellow-100 text-yellow-800';
            } else if (pc.status === 'IN_USE') {
                return 'bg-green-100 text-green-800';
            } else if (pc.status === 'IDLE') {
                return 'bg-blue-100 text-blue-800';
            }
            return '';
        },

        getPCStatusText(pc) {
            if (pc.status === 'IN_USE' && pc.active_session && pc.active_session.status === 'PAUSED') {
                return 'PAUSED';
            }
            return pc.status;
        },

        formatTimeRemaining(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        }
    }
}
</script>
@endpush
@endsection
