@extends('layouts.app')

@section('title', 'Sessions Management - Warnet Management System')
@section('page-title', 'Sessions Management')

@section('content')
<div x-data="sessionsManagement()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Active Sessions</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.active"></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-play-circle text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-800" x-text="formatRupiah(stats.totalRevenue)"></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Avg Duration</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.avgDuration + 'h'"></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-yellow-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Completed Today</p>
                    <p class="text-3xl font-bold text-gray-800" x-text="stats.completedToday"></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Actions -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <select x-model="statusFilter" @change="filterSessions()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all">All Sessions</option>
                <option value="ACTIVE">Active</option>
                <option value="PAUSED">Paused</option>
            </select>

            <select x-model="tierFilter" @change="filterSessions()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all">All Tiers</option>
                <option value="REGULER">Regular</option>
                <option value="VIP">VIP</option>
            </select>
        </div>

        <div class="flex items-center space-x-2">
            <button @click="refreshData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="session in filteredSessions" :key="session.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-desktop text-blue-600 mr-2"></i>
                                <span class="font-semibold" x-text="session.pc ? session.pc.name : 'PC-' + session.pc_id"></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900" x-text="session.user_name"></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                  :class="session.tier === 'VIP' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
                                  x-text="session.tier"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div x-text="Math.floor(session.duration / 60) + 'h ' + (session.duration % 60) + 'm'" class="text-xs text-gray-500">Booked</div>
                                <template x-if="session.status === 'PAUSED'">
                                    <div class="text-base font-semibold font-mono text-yellow-600" x-text="formatRemainingTime(session.remainingSeconds)"></div>
                                    <div class="text-xs text-yellow-600 mt-1 flex items-center font-semibold">
                                        <i class="fas fa-pause-circle mr-1"></i>
                                        <span>Session Paused</span>
                                    </div>
                                </template>
                                <template x-if="session.status !== 'PAUSED'">
                                    <div class="text-base font-semibold font-mono" :class="{
                                        'text-red-600 animate-pulse': session.remainingSeconds <= 300,
                                        'text-orange-600': session.remainingSeconds > 300 && session.remainingSeconds <= 600,
                                        'text-green-600': session.remainingSeconds > 600
                                    }" x-text="formatRemainingTime(session.remainingSeconds)"></div>
                                    <template x-if="session.remainingSeconds <= 300 && session.remainingSeconds > 0">
                                        <div class="text-xs text-red-500 mt-1">⚠️ Waktu Terbatas!</div>
                                    </template>
                                </template>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-green-600" x-text="formatRupiah(session.total_cost)"></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs rounded-full font-semibold"
                                  :class="{
                                      'bg-green-100 text-green-800': session.status === 'ACTIVE',
                                      'bg-yellow-100 text-yellow-800': session.status === 'PAUSED',
                                      'bg-gray-100 text-gray-800': session.status === 'COMPLETED'
                                  }"
                                  x-text="session.status"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex space-x-2">
                                <template x-if="session.status === 'ACTIVE'">
                                    <button @click="showExtendModal(session)" 
                                            class="text-blue-600 hover:text-blue-900" title="Extend">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                </template>
                                <template x-if="session.status === 'ACTIVE'">
                                    <button @click="pauseSession(session.id)" 
                                            class="text-yellow-600 hover:text-yellow-900" title="Pause">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                </template>
                                <template x-if="session.status === 'PAUSED'">
                                    <button @click="resumeSession(session.id)" 
                                            class="text-green-600 hover:text-green-900" title="Resume">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </template>
                                <template x-if="session.status === 'ACTIVE' || session.status === 'PAUSED'">
                                    <button @click="completeSession(session.id)" 
                                            class="text-red-600 hover:text-red-900" title="Complete">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </template>
                                <button @click="viewSession(session)" 
                                        class="text-gray-600 hover:text-gray-900" title="Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Create Session Modal -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showCreateModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <h2 class="text-2xl font-bold mb-4">New Session</h2>
            <form @submit.prevent="createSession()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PC</label>
                        <select x-model="newSession.pc_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select PC</option>
                            <template x-for="pc in availablePCs" :key="pc.id">
                                <option :value="pc.id" x-text="pc.pc_code + ' (' + pc.type + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                        <input type="text" x-model="newSession.user_name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                        <input type="number" x-model="newSession.duration" required min="30" step="30"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="newSession.payment_method = 'CASH'" 
                                    :class="newSession.payment_method === 'CASH' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                                <i class="fas fa-money-bill-wave mr-2"></i>Cash
                            </button>
                            <button type="button" @click="newSession.payment_method = 'QRIS'" 
                                    :class="newSession.payment_method === 'QRIS' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                                    class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                                <i class="fas fa-qrcode mr-2"></i>QRIS
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex space-x-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Create Session
                    </button>
                    <button type="button" @click="showCreateModal = false" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Extend Session Modal -->
    <div x-show="showExtendSessionModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showExtendSessionModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <h2 class="text-2xl font-bold mb-4">Extend Session</h2>
            <template x-if="selectedSession">
                <div>
                    <p class="mb-4 text-gray-600">Extend session for <strong x-text="selectedSession.user_name"></strong></p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Minutes</label>
                        <input type="number" x-model="extendMinutes" min="30" step="30" value="60"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex space-x-3">
                        <button @click="extendSession()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Extend
                        </button>
                        <button @click="showExtendSessionModal = false" 
                                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                            Cancel
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Session Details Modal -->
    <div x-show="showDetailsModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showDetailsModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Session Details</h2>
                <button @click="showDetailsModal = false" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <template x-if="selectedSession">
                <div class="space-y-4">
                    <!-- Session Info Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Session ID</p>
                            <p class="text-lg font-semibold" x-text="selectedSession.id"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">PC</p>
                            <p class="text-lg font-semibold">
                                <i class="fas fa-desktop text-blue-600 mr-2"></i>
                                <span x-text="selectedSession.pc ? selectedSession.pc.name : 'PC-' + selectedSession.pc_id"></span>
                            </p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Customer Name</p>
                            <p class="text-lg font-semibold" x-text="selectedSession.user_name"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Tier</p>
                            <p class="text-lg font-semibold">
                                <span :class="selectedSession.tier === 'VIP' ? 'text-yellow-600' : 'text-blue-600'" 
                                      x-text="selectedSession.tier"></span>
                            </p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Duration</p>
                            <p class="text-lg font-semibold" x-text="formatDuration(selectedSession.duration)"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Total Cost</p>
                            <p class="text-lg font-semibold text-green-600" x-text="formatRupiah(selectedSession.total_cost)"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Status</p>
                            <p class="text-lg font-semibold">
                                <span :class="{
                                    'px-3 py-1 rounded-full text-sm': true,
                                    'bg-green-100 text-green-800': selectedSession.status === 'ACTIVE',
                                    'bg-yellow-100 text-yellow-800': selectedSession.status === 'PAUSED',
                                    'bg-gray-100 text-gray-800': selectedSession.status === 'COMPLETED'
                                }" x-text="selectedSession.status"></span>
                            </p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Remaining Time</p>
                            <p class="text-lg font-semibold text-blue-600" 
                               x-text="selectedSession.status !== 'COMPLETED' ? formatTime(selectedSession.remainingSeconds) : '-'"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Start Time</p>
                            <p class="text-lg font-semibold" x-text="formatDateTime(selectedSession.start_time)"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">End Time</p>
                            <p class="text-lg font-semibold" 
                               x-text="selectedSession.end_time ? formatDateTime(selectedSession.end_time) : '-'"></p>
                        </div>
                    </div>
                    
                    <!-- Close Button -->
                    <div class="flex justify-end mt-6">
                        <button @click="showDetailsModal = false" 
                                class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            Close
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function sessionsManagement() {
    return {
        sessions: [],
        filteredSessions: [],
        availablePCs: [],
        selectedSession: null,
        statusFilter: 'all',
        tierFilter: 'all',
        showCreateModal: false,
        showExtendSessionModal: false,
        showDetailsModal: false,
        extendMinutes: 60,
        newSession: {
            pc_id: '',
            user_name: '',
            duration: 120,
            payment_method: 'CASH'
        },
        stats: {
            active: 0,
            totalRevenue: 0,
            avgDuration: 0,
            completedToday: 0
        },
        countdownInterval: null,
        serverSyncInterval: null,
        sessionTimestamps: {}, // Store timestamp untuk each session
        completingSessionIds: new Set(), // Track sessions being auto-completed to prevent duplicates

        async init() {
            console.log('Sessions Management initialized');
            await this.loadSessions();
            await this.loadAvailablePCs();
            
            // Start countdown timer untuk real-time update
            this.startCountdownTimer();
            
            // Server sync setiap 10 detik (bukan 5 detik) untuk validasi
            this.serverSyncInterval = setInterval(() => this.syncWithServer(), 10000);
        },

        startCountdownTimer() {
            if (this.countdownInterval) clearInterval(this.countdownInterval);
            
            this.countdownInterval = setInterval(() => {
                // Decrease remaining time for each active session
                this.sessions.forEach(session => {
                    // CRITICAL: Skip PAUSED and COMPLETED sessions - don't modify their time
                    if (session.status === 'PAUSED') {
                        // For paused sessions, time should be frozen
                        console.log(`Session ${session.id} is PAUSED, time frozen at ${session.remainingSeconds}s`);
                        return;
                    }
                    
                    if (session.status !== 'ACTIVE') {
                        return;
                    }
                    
                    
                    // For ACTIVE sessions: check if time expired, then decrement
                    if (session.remainingSeconds !== undefined && session.remainingSeconds !== null) {
                        // Auto-complete if time already expired (0 or negative)
                        if (session.remainingSeconds <= 0 && !this.completingSessionIds.has(session.id)) {
                            console.log(`Auto-completing expired session ${session.id} (remaining: ${session.remainingSeconds}s)`);
                            this.completingSessionIds.add(session.id); // Mark as being completed
                            this.completeSession(session.id, true);
                            return; // Skip decrement for this session
                        }
                        
                        // Decrement time for active sessions with time remaining
                        if (session.remainingSeconds > 0) {
                            session.remainingSeconds--;
                            
                            // Check again after decrement
                            if (session.remainingSeconds <= 0 && !this.completingSessionIds.has(session.id)) {
                                console.log(`Auto-completing session ${session.id} (time just expired)`);
                                this.completingSessionIds.add(session.id); // Mark as being completed
                                this.completeSession(session.id, true);
                            }
                        }
                    }
                });
                
                // Update filtered sessions juga
                this.filterSessions();
            }, 1000); // Update setiap 1 detik
        },

        async loadSessions() {
            try {
                console.log('Loading sessions...');
                // Include completed sessions untuk menghitung total revenue
                const response = await apiCall('/sessions?include_completed=true');
                console.log('Sessions API response:', response);
                if (response.success) {
                    // Hitung remaining time berdasarkan start_time dan duration
                    const now = Math.floor(Date.now() / 1000);
                    
                    this.sessions = response.data.map(session => {
                        // 🔥 CRITICAL: For PAUSED sessions, ALWAYS use remaining_seconds from server
                        // Never recalculate for paused sessions to prevent time drift on refresh
                        if (session.status === 'PAUSED') {
                            if (session.remaining_seconds !== null && session.remaining_seconds !== undefined) {
                                console.log(`PAUSED session ${session.id}: using saved remaining_seconds = ${session.remaining_seconds}s`);
                                return {
                                    ...session,
                                    remainingSeconds: session.remaining_seconds
                                };
                            } else {
                                // WARNING: PAUSED session has no remaining_seconds saved!
                                // This should not happen - calculate as fallback but log error
                                console.error(`CRITICAL: PAUSED session ${session.id} has no remaining_seconds saved! Calculating fallback...`);
                                
                                // Calculate remaining time as fallback
                                const now = Math.floor(Date.now() / 1000);
                                const startTime = Math.floor(new Date(session.start_time).getTime() / 1000);
                                const durationSeconds = session.duration * 60;
                                const pausedDurationSeconds = (session.paused_duration || 0) * 60;
                                const totalElapsed = now - startTime;
                                const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                                const remainingSeconds = Math.max(0, durationSeconds - effectiveElapsed);
                                
                                console.warn(`Calculated fallback for PAUSED session ${session.id}: ${remainingSeconds}s`);
                                
                                return {
                                    ...session,
                                    remainingSeconds: remainingSeconds
                                };
                            }
                        }
                        
                        // For ACTIVE sessions: ALWAYS calculate from start_time + paused_duration
                        // NEVER use remaining_seconds from database (it should be null anyway)
                        const startTime = Math.floor(new Date(session.start_time).getTime() / 1000);
                        const durationSeconds = session.duration * 60;
                        
                        // Account for paused duration
                        const pausedDurationSeconds = (session.paused_duration || 0) * 60;
                        const totalElapsed = now - startTime;
                        const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                        const remainingSeconds = Math.max(0, durationSeconds - effectiveElapsed);
                        
                        console.log(`Calculating remaining time for ACTIVE session ${session.id}: ${remainingSeconds}s`);
                        
                        // Store timestamp untuk validasi saat sync
                        this.sessionTimestamps[session.id] = {
                            remainingSeconds: remainingSeconds,
                            serverTime: now
                        };
                        
                        return {
                            ...session,
                            remainingSeconds: remainingSeconds
                        };
                    });
                    
                    console.log('Sessions loaded:', this.sessions.length, 'records');
                    this.calculateStats();
                    this.filterSessions();
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        },

        async syncWithServer() {
            try {
                console.log('Syncing with server...');
                const response = await apiCall('/sessions?include_completed=true');
                if (response.success) {
                    const now = Math.floor(Date.now() / 1000);
                    
                    response.data.forEach(serverSession => {
                        const localSession = this.sessions.find(s => s.id === serverSession.id);
                        if (localSession) {
                            // If session status changed on server, update locally
                            if (localSession.status !== serverSession.status) {
                                console.log(`Session ${serverSession.id} status changed on server: ${serverSession.status}`);
                                localSession.status = serverSession.status;
                            }
                            
                            // 🔥 CRITICAL: For PAUSED sessions, NEVER recalculate time
                            // Always use the saved remaining_seconds from server
                            if (serverSession.status === 'PAUSED') {
                                if (serverSession.remaining_seconds !== null && serverSession.remaining_seconds !== undefined) {
                                    // Only update if different from local (to prevent unnecessary updates)
                                    if (localSession.remainingSeconds !== serverSession.remaining_seconds) {
                                        console.log(`PAUSED session ${serverSession.id}: syncing to saved remaining_seconds = ${serverSession.remaining_seconds}s`);
                                        localSession.remainingSeconds = serverSession.remaining_seconds;
                                    }
                                }
                                // Skip time sync for paused sessions - they should be frozen
                                return;
                            }
                            
                            // For ACTIVE sessions: ALWAYS calculate time from start_time + paused_duration
                            // NEVER use remaining_seconds (it should be null for ACTIVE sessions)
                            const startTime = Math.floor(new Date(serverSession.start_time).getTime() / 1000);
                            const durationSeconds = serverSession.duration * 60;
                            
                            // Account for paused duration
                            const pausedDurationSeconds = (serverSession.paused_duration || 0) * 60;
                            const totalElapsed = now - startTime;
                            const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                            const serverRemainingSeconds = Math.max(0, durationSeconds - effectiveElapsed);
                            
                            // Jika perbedaan lebih dari 2 detik, sinkronisasi
                            const diff = Math.abs(localSession.remainingSeconds - serverRemainingSeconds);
                            if (diff > 2) {
                                console.log(`ACTIVE session ${serverSession.id} time difference: ${diff}s, syncing to calculated time: ${serverRemainingSeconds}s`);
                                localSession.remainingSeconds = serverRemainingSeconds;
                            }
                        }
                    });
                    
                    // Check for completed sessions from server
                    const completedSessions = response.data.filter(s => s.status === 'COMPLETED');
                    completedSessions.forEach(completed => {
                        const existing = this.sessions.find(s => s.id === completed.id && s.status !== 'COMPLETED');
                        if (existing) {
                            existing.status = 'COMPLETED';
                            existing.end_time = completed.end_time;
                        }
                    });
                    
                    this.calculateStats();
                    this.filterSessions();
                }
            } catch (error) {
                console.error('Error syncing with server:', error);
            }
        },

        formatRemainingTime(seconds) {
            if (seconds <= 0) return '00:00';
            
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
        },

        async loadAvailablePCs() {
            try {
                console.log('Loading available PCs...');
                // Get IDLE PCs only
                const response = await apiCall('/pcs?status=IDLE');
                console.log('PCs API response:', response);
                if (response.success) {
                    this.availablePCs = response.data;
                    console.log('Available PCs loaded:', this.availablePCs.length, 'records');
                } else {
                    console.warn('Failed to load PCs:', response.message);
                    // Fallback: load all PCs
                    const allPcs = await apiCall('/pcs');
                    if (allPcs.success) {
                        this.availablePCs = allPcs.data.filter(pc => pc.status === 'IDLE');
                    }
                }
            } catch (error) {
                console.error('Error loading PCs:', error);
                // Fallback: try to load all PCs and filter
                try {
                    const response = await apiCall('/pcs');
                    if (response.success) {
                        this.availablePCs = response.data.filter(pc => pc.status === 'IDLE');
                    }
                } catch (err) {
                    console.error('Error in fallback:', err);
                }
            }
        },

        calculateStats() {
            this.stats.active = this.sessions.filter(s => s.status === 'ACTIVE').length;
            
            const today = new Date().toDateString();
            
            // Total Revenue: sum dari COMPLETED sessions HARI INI saja (reset setiap hari)
            const completedToday = this.sessions.filter(s => 
                s.status === 'COMPLETED' && 
                s.end_time && 
                new Date(s.end_time).toDateString() === today
            );
            
            console.log('Completed sessions TODAY for revenue:', completedToday);
            
            this.stats.totalRevenue = completedToday.reduce((sum, s) => {
                const cost = parseFloat(s.total_cost || 0);
                console.log(`Session ${s.id}: cost = ${cost}`);
                return sum + cost;
            }, 0);
            
            console.log('Total Revenue TODAY calculated:', this.stats.totalRevenue);
            
            // Avg Duration: dari semua sessions (completed dan active)
            const allSessions = this.sessions.filter(s => s.status === 'COMPLETED' || s.status === 'ACTIVE');
            this.stats.avgDuration = allSessions.length > 0 
                ? Math.round(allSessions.reduce((sum, s) => sum + s.duration, 0) / allSessions.length / 60) 
                : 0;
            
            // Completed Today: jumlah sessions yang completed hari ini
            this.stats.completedToday = completedToday.length;
            
            console.log('Stats:', this.stats);
        },

        filterSessions() {
            this.filteredSessions = this.sessions.filter(session => {
                // ALWAYS hide COMPLETED sessions from table view
                // (but keep them in this.sessions for stats calculation)
                if (session.status === 'COMPLETED') {
                    return false;
                }
                
                const statusMatch = this.statusFilter === 'all' || session.status === this.statusFilter;
                const tierMatch = this.tierFilter === 'all' || session.tier === this.tierFilter;
                return statusMatch && tierMatch;
            });
        },

        async createSession() {
            try {
                // Find selected PC to get tier
                const selectedPC = this.availablePCs.find(pc => pc.id == this.newSession.pc_id);
                if (!selectedPC) {
                    alert('Please select a valid PC');
                    return;
                }

                // Prepare data with tier from selected PC
                const sessionData = {
                    pc_id: parseInt(this.newSession.pc_id),
                    user_name: this.newSession.user_name,
                    duration: parseInt(this.newSession.duration),
                    tier: selectedPC.type,  // ✅ Add tier from PC
                    payment_method: this.newSession.payment_method
                };

                console.log('Creating session with data:', sessionData);
                const response = await apiCall('/sessions', 'POST', sessionData);
                console.log('Create session response:', response);
                
                if (response.success) {
                    alert('Session created successfully!');
                    this.showCreateModal = false;
                    this.newSession = { pc_id: '', user_name: '', duration: 120, payment_method: 'CASH' };
                    await this.loadSessions();
                    await this.loadAvailablePCs();
                } else {
                    alert('Error: ' + (response.message || 'Failed to create session'));
                }
            } catch (error) {
                console.error('Error creating session:', error);
                alert('Error creating session: ' + error.message);
            }
        },

        showExtendModal(session) {
            this.selectedSession = session;
            this.extendMinutes = 60;
            this.showExtendSessionModal = true;
        },

        async extendSession() {
            try {
                const response = await apiCall(`/sessions/${this.selectedSession.id}/extend`, 'PATCH', {
                    additional_duration: parseInt(this.extendMinutes)
                });
                if (response.success) {
                    alert('Session diperpanjang! Biaya tambahan: ' + formatRupiah(response.data.total_cost - this.selectedSession.total_cost));
                    
                    // Update remaining time lokal tanpa reload full
                    const session = this.sessions.find(s => s.id === this.selectedSession.id);
                    if (session) {
                        const additionalSeconds = parseInt(this.extendMinutes) * 60;
                        session.remainingSeconds += additionalSeconds;
                        session.duration += parseInt(this.extendMinutes);
                        session.total_cost = response.data.total_cost;
                    }
                    
                    this.showExtendSessionModal = false;
                    this.calculateStats();
                    this.filterSessions();
                }
            } catch (error) {
                alert('Error extending session');
                console.error(error);
            }
        },

        async pauseSession(sessionId) {
            if (!confirm('Pause this session?')) return;
            try {
                // Find session to get current remainingSeconds
                const session = this.sessions.find(s => s.id === sessionId);
                if (!session) {
                    alert('Session not found');
                    return;
                }
                
                // DEBUG: Log session data before pause
                console.log('=== PAUSE DEBUG ===');
                console.log('Session ID:', sessionId);
                console.log('Session object:', session);
                console.log('remainingSeconds:', session.remainingSeconds);
                console.log('Type of remainingSeconds:', typeof session.remainingSeconds);
                console.log('Is null?', session.remainingSeconds === null);
                console.log('Is undefined?', session.remainingSeconds === undefined);
                
                // Validate remainingSeconds before sending
                if (session.remainingSeconds === null || session.remainingSeconds === undefined) {
                    console.error('ERROR: remainingSeconds is null or undefined!');
                    alert('Error: Cannot pause - remaining time is not set');
                    return;
                }
                
                // Send remainingSeconds to backend for accurate tracking
                const payload = {
                    remaining_seconds: session.remainingSeconds
                };
                console.log('Sending payload:', payload);
                
                const response = await apiCall(`/sessions/${sessionId}/pause`, 'POST', payload);
                
                console.log('Pause response:', response);
                
                if (response.success) {
                    // Update status lokal tanpa reload
                    if (session) {
                        session.status = 'PAUSED';
                        // Keep remainingSeconds as-is (frozen)
                    }
                    this.calculateStats();
                    this.filterSessions();
                }
            } catch (error) {
                console.error('Error pausing session:', error);
                alert('Error pausing session');
            }
        },

        async resumeSession(sessionId) {
            if (!confirm('Resume this session?')) return;
            try {
                const session = this.sessions.find(s => s.id === sessionId);
                console.log(`Resuming session ${sessionId}`, {
                    remaining_seconds_before: session?.remainingSeconds,
                    status_before: session?.status
                });
                
                const response = await apiCall(`/sessions/${sessionId}/resume`, 'POST');
                if (response.success) {
                    console.log(`Session ${sessionId} resumed successfully`, {
                        remaining_seconds_after: response.data.remaining_seconds,
                        status_after: response.data.status
                    });
                    
                    // Update status lokal tanpa reload
                    if (session) {
                        session.status = 'ACTIVE';
                        
                        // CRITICAL: For ACTIVE sessions, recalculate time from start_time + paused_duration
                        // Do NOT use remaining_seconds from server (it should be null for ACTIVE sessions)
                        const now = Math.floor(Date.now() / 1000);
                        const startTime = Math.floor(new Date(response.data.start_time).getTime() / 1000);
                        const durationSeconds = response.data.duration * 60;
                        const pausedDurationSeconds = (response.data.paused_duration || 0) * 60;
                        const totalElapsed = now - startTime;
                        const effectiveElapsed = totalElapsed - pausedDurationSeconds;
                        const remainingSeconds = Math.max(0, durationSeconds - effectiveElapsed);
                        
                        session.remainingSeconds = remainingSeconds;
                        console.log(`Recalculated remainingSeconds for resumed session: ${remainingSeconds}s`);
                    }
                    this.calculateStats();
                    this.filterSessions();
                }
            } catch (error) {
                console.error('Error resuming session:', error);
                alert('Error resuming session');
            }
        },

        viewSession(session) {
            this.selectedSession = session;
            this.showDetailsModal = true;
        },

        async completeSession(sessionId, autoComplete = false) {
            if (!autoComplete && !confirm('Complete this session?')) {
                // Remove from tracking if user cancels
                this.completingSessionIds.delete(sessionId);
                return;
            }
            try {
                const response = await apiCall(`/sessions/${sessionId}/complete`, 'POST');
                if (response.success) {
                    // Remove session from array immediately for real-time update
                    const sessionIndex = this.sessions.findIndex(s => s.id === sessionId);
                    if (sessionIndex !== -1) {
                        this.sessions.splice(sessionIndex, 1);
                        console.log(`Session ${sessionId} removed from UI`);
                    }
                    
                    if (!autoComplete) {
                        alert('Session completed!');
                    }
                    
                    this.calculateStats();
                    this.filterSessions();
                    
                    // Reload PCs setelah completed
                    await this.loadAvailablePCs();
                    
                    // Remove from tracking set after successful completion
                    this.completingSessionIds.delete(sessionId);
                } else {
                    // Remove from tracking if completion failed
                    this.completingSessionIds.delete(sessionId);
                }
            } catch (error) {
                console.error('Error completing session:', error);
                alert('Error completing session');
                // Remove from tracking set on error so it can retry
                this.completingSessionIds.delete(sessionId);
            }
        },

        viewSession(session) {
            this.selectedSession = session;
            this.showDetailsModal = true;
        },

        formatTime(seconds) {
            if (!seconds && seconds !== 0) return '-';
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
        },

        formatDateTime(timestamp) {
            if (!timestamp) return '-';
            const date = new Date(timestamp);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            return date.toLocaleString('en-US', options);
        },

        async refreshData() {
            await this.loadSessions();
            await this.loadAvailablePCs();
        }
    }
}
</script>
@endpush
@endsection
