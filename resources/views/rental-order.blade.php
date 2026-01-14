@extends('layouts.app')

@section('title', 'Rental & Order - Warnet Management System')
@section('page-title', 'PC Rental & Menu Order')

@section('content')
<div x-data="rentalOrderManagement()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- LEFT COLUMN: PC Rental Section -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-desktop mr-3 text-blue-600"></i>
                PC Rental
            </h2>

            <!-- Mode Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-3">Mode</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="setMode('new_rental')" 
                            :class="mode === 'new_rental' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-3 rounded-lg font-semibold hover:shadow-md transition">
                        <i class="fas fa-plus-circle mr-2"></i>New Rental
                    </button>
                    <button @click="setMode('existing_session')" 
                            :class="mode === 'existing_session' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-3 rounded-lg font-semibold hover:shadow-md transition">
                        <i class="fas fa-clock mr-2"></i>Active Session
                    </button>
                </div>
            </div>

            <!-- New Rental Form -->
            <template x-if="mode === 'new_rental'">
                <div class="space-y-4">
                    <!-- PC Selection -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Select PC *</label>
                        <select x-model="rentalForm.pc_id" @change="updatePCSelection()" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Choose PC --</option>
                            <template x-for="pc in availablePCs" :key="pc.id">
                                <option :value="pc.id" x-text="`${pc.pc_code} (${pc.tier})`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Customer Name -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Customer Name</label>
                        <input type="text" x-model="rentalForm.user_name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter customer name (optional)">
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Duration (minutes) *</label>
                        <input type="number" x-model="rentalForm.duration" @input="calculateRentalCost()" 
                               min="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., 60">
                    </div>

                    <!-- Rental Cost Display -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">Rental Cost:</span>
                            <span class="text-2xl font-bold text-blue-600" x-text="formatRupiah(rentalCost)"></span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1" x-show="selectedPC">
                            <span x-text="selectedPC ? selectedPC.type : ''"></span> Tier
                        </p>
                    </div>
                </div>
            </template>

            <!-- Existing Session Selection -->
            <template x-if="mode === 'existing_session'">
                <div class="space-y-4">
                    <!-- Session Selection Dropdown -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">
                            <i class="fas fa-clock mr-2 text-green-600"></i>Select Active Session *
                        </label>
                        <select x-model="rentalForm.session_id" @change="updateSessionSelection()" 
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                            <option value="">-- Choose Active Session --</option>
                            <template x-for="session in activeSessions" :key="session.id">
                                <option :value="session.id" 
                                        x-text="`${session.pc.pc_code} - ${session.user_name} (${formatTimeRemaining(session.remaining_time)})`">
                                </option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Pilih session PC yang sedang aktif untuk order menu
                        </p>
                    </div>

                    <!-- Session Info Display -->
                    <template x-if="selectedSession">
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-5 space-y-3 shadow-sm">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-info-circle text-green-600 text-lg mr-2"></i>
                                <h4 class="font-bold text-green-800">Session Information</h4>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-3 shadow-sm">
                                    <p class="text-xs text-gray-500 mb-1">
                                        <i class="fas fa-desktop mr-1"></i>PC
                                    </p>
                                    <p class="font-bold text-lg text-gray-800" x-text="selectedSession.pc.pc_code"></p>
                                </div>
                                
                                <div class="bg-white rounded-lg p-3 shadow-sm">
                                    <p class="text-xs text-gray-500 mb-1">
                                        <i class="fas fa-user mr-1"></i>Customer
                                    </p>
                                    <p class="font-bold text-lg text-gray-800" x-text="selectedSession.user_name"></p>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">
                                    <i class="fas fa-hourglass-half mr-1"></i>Time Remaining
                                </p>
                                <p class="font-bold text-2xl text-green-600" x-text="formatTimeRemaining(selectedSession.remaining_time)"></p>
                            </div>
                            
                            <div class="bg-green-600 text-white rounded-lg p-3 text-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span class="font-semibold">Ready to order menu for this session</span>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Empty State when no session selected -->
                    <template x-if="!selectedSession">
                        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <i class="fas fa-hand-pointer text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500 font-medium">Please select an active session above</p>
                            <p class="text-xs text-gray-400 mt-1">Choose a PC session to start ordering menu</p>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- RIGHT COLUMN: Menu Order Section -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-utensils mr-3 text-green-600"></i>
                Menu Order
            </h2>

            <!-- Menu Categories -->
            <div class="mb-4">
                <div class="flex space-x-2 overflow-x-auto pb-2">
                    <button @click="categoryFilter = 'all'" 
                            :class="categoryFilter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg font-semibold whitespace-nowrap">
                        All
                    </button>
                    <template x-for="category in categories" :key="category">
                        <button @click="categoryFilter = category" 
                                :class="categoryFilter === category ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-4 py-2 rounded-lg font-semibold whitespace-nowrap"
                                x-text="category">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-2 gap-3 mb-6 max-h-64 overflow-y-auto">
                <template x-for="menu in filteredMenus" :key="menu.id">
                    <div @click="addToCart(menu)" 
                         class="border border-gray-200 rounded-lg p-3 cursor-pointer hover:border-green-500 hover:shadow-md transition">
                        <p class="font-semibold text-sm mb-1" x-text="menu.name"></p>
                        <p class="text-xs text-gray-500 mb-2" x-text="menu.category"></p>
                        <p class="text-green-600 font-bold text-sm" x-text="formatRupiah(menu.price)"></p>
                    </div>
                </template>
            </div>

            <!-- Shopping Cart -->
            <div class="border-t pt-4">
                <h3 class="font-bold mb-3 flex items-center justify-between">
                    <span>Cart</span>
                    <span class="text-sm text-gray-500" x-text="`${cart.length} items`"></span>
                </h3>
                
                <div class="space-y-2 max-h-48 overflow-y-auto mb-4">
                    <template x-if="cart.length === 0">
                        <p class="text-gray-400 text-sm text-center py-4">Cart is empty</p>
                    </template>
                    
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
                            <div class="flex-1">
                                <p class="font-semibold text-sm" x-text="item.name"></p>
                                <p class="text-xs text-gray-500" x-text="formatRupiah(item.price)"></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button @click="decreaseQuantity(index)" 
                                        class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="w-8 text-center font-semibold" x-text="item.quantity"></span>
                                <button @click="increaseQuantity(index)" 
                                        class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                                <button @click="removeFromCart(index)" 
                                        class="ml-2 text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Menu Subtotal -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">Menu Subtotal:</span>
                        <span class="text-xl font-bold text-green-600" x-text="formatRupiah(menuSubtotal)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM SECTION: Summary & Submit -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Rental Summary -->
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Rental Cost</p>
                <p class="text-2xl font-bold text-blue-600" x-text="formatRupiah(rentalCost)"></p>
            </div>

            <!-- Menu Summary -->
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Menu Cost</p>
                <p class="text-2xl font-bold text-green-600" x-text="formatRupiah(menuSubtotal)"></p>
            </div>

            <!-- Total -->
            <div class="bg-gradient-to-r from-blue-600 to-green-600 rounded-lg p-4 text-white">
                <p class="text-sm mb-1">Grand Total</p>
                <p class="text-3xl font-bold" x-text="formatRupiah(grandTotal)"></p>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="mb-6">
            <label class="block text-sm font-semibold mb-3">Payment Method *</label>
            <div class="grid grid-cols-2 gap-3">
                <button @click="paymentMethod = 'CASH'" 
                        :class="paymentMethod === 'CASH' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                    <i class="fas fa-money-bill-wave mr-2"></i>Cash
                </button>
                <button @click="paymentMethod = 'QRIS'" 
                        :class="paymentMethod === 'QRIS' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-6 py-3 rounded-lg font-semibold hover:shadow-md transition">
                    <i class="fas fa-qrcode mr-2"></i>QRIS
                </button>
            </div>
        </div>

        <!-- Submit Button -->
        <button @click="submitOrder()" 
                :disabled="!canSubmit()"
                class="w-full py-4 text-white rounded-lg font-bold text-lg transition-all duration-200"
                :style="canSubmit() ? 'background: linear-gradient(to right, rgb(37, 99, 235), rgb(34, 197, 94)); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);' : 'background-color: rgb(156, 163, 175); cursor: not-allowed; opacity: 0.6;'">
            <i class="fas fa-check-circle mr-2"></i>
            <span x-text="getSubmitButtonText()"></span>
        </button>
        
        <!-- Validation Messages -->
        <div class="mt-3 text-sm" x-show="!canSubmit()">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-2"></i>
                    <div class="text-yellow-800">
                        <p class="font-semibold mb-1">Please complete the following:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li x-show="!paymentMethod">Select a payment method (Cash or QRIS)</li>
                            <li x-show="mode === 'new_rental' && !rentalForm.pc_id">Select a PC</li>
                            <li x-show="mode === 'new_rental' && rentalForm.duration <= 0">Enter rental duration</li>
                            <li x-show="mode === 'existing_session' && !rentalForm.session_id">Select an active session</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function rentalOrderManagement() {
    return {
        mode: 'new_rental', // 'new_rental' or 'existing_session'
        pcs: [],
        availablePCs: [],
        activeSessions: [],
        menus: [],
        categories: [],
        categoryFilter: 'all',
        cart: [],
        selectedPC: null,
        selectedSession: null,
        paymentMethod: 'CASH',
        completingSessionIds: new Set(), // Track sessions being auto-completed
        
        rentalForm: {
            pc_id: '',
            user_name: '',
            duration: 60,
            session_id: ''
        },
        
        rentalCost: 0,

        async init() {
            // Wait for API token to be ready
            if (window.appReady) {
                await window.appReady;
            }
            
            // Ensure paymentMethod is set
            this.paymentMethod = 'CASH';
            
            await this.loadPCs();
            await this.loadActiveSessions();
            await this.loadMenus();
            
            // Start real-time countdown timer
            this.startCountdownTimer();
        },

        startCountdownTimer() {
            // Update remaining time display every second
            setInterval(async () => {
                const now = Math.floor(Date.now() / 1000);
                
                // Update active sessions countdown
                for (let i = this.activeSessions.length - 1; i >= 0; i--) {
                    const session = this.activeSessions[i];
                    
                    if (session.status === 'ACTIVE') {
                        const startTime = Math.floor(new Date(session.start_time).getTime() / 1000);
                        const durationSeconds = session.duration * 60;
                        const endTime = startTime + durationSeconds;
                        const remainingSeconds = Math.max(0, endTime - now);
                        
                        session.remaining_time = remainingSeconds;
                        
                        // Auto-complete session when time expires
                        if (remainingSeconds <= 0 && session.status === 'ACTIVE' && !this.completingSessionIds.has(session.id)) {
                            try {
                                // Mark as being completed to prevent duplicate calls
                                this.completingSessionIds.add(session.id);
                                
                                console.log(`Auto-completing expired session: ${session.id}`);
                                const response = await apiCall(`/sessions/${session.id}/complete`, 'POST');
                                
                                if (response.success) {
                                    // Remove from active sessions
                                    this.activeSessions.splice(i, 1);
                                    
                                    // Clear selected session if it was this one
                                    if (this.selectedSession && this.selectedSession.id === session.id) {
                                        this.selectedSession = null;
                                        this.rentalForm.session_id = '';
                                        alert(`Session for ${session.pc.pc_code} has expired and been completed automatically.`);
                                    }
                                    
                                    // Reload PCs to update availability
                                    await this.loadPCs();
                                    
                                    console.log(`Session ${session.id} auto-completed successfully`);
                                } else {
                                    console.error('Failed to auto-complete session:', response.message);
                                    // Remove from set if failed so it can retry
                                    this.completingSessionIds.delete(session.id);
                                }
                            } catch (error) {
                                console.error('Error auto-completing session:', error);
                                // Remove from set if error so it can retry
                                this.completingSessionIds.delete(session.id);
                            }
                        }
                    }
                }
                
                // Update selected session if exists
                if (this.selectedSession && this.selectedSession.status === 'ACTIVE') {
                    const startTime = Math.floor(new Date(this.selectedSession.start_time).getTime() / 1000);
                    const durationSeconds = this.selectedSession.duration * 60;
                    const endTime = startTime + durationSeconds;
                    const remainingSeconds = Math.max(0, endTime - now);
                    
                    this.selectedSession.remaining_time = remainingSeconds;
                }
            }, 1000); // Update every 1 second
        },

        async loadPCs() {
            try {
                const response = await apiCall('/pcs');
                if (response.success) {
                    this.pcs = response.data;
                    // Filter hanya PC dengan status IDLE
                    this.availablePCs = this.pcs.filter(pc => pc.status === 'IDLE');
                }
            } catch (error) {
                console.error('Error loading PCs:', error);
            }
        },

        async loadActiveSessions() {
            try {
                const response = await apiCall('/sessions');
                if (response.success) {
                    // Calculate remaining time for each session
                    const now = Math.floor(Date.now() / 1000);
                    this.activeSessions = response.data.map(session => {
                        const startTime = Math.floor(new Date(session.start_time).getTime() / 1000);
                        const durationSeconds = session.duration * 60;
                        const endTime = startTime + durationSeconds;
                        const remainingSeconds = Math.max(0, endTime - now);
                        
                        return {
                            ...session,
                            remaining_time: remainingSeconds
                        };
                    });
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        },

        async loadMenus() {
            try {
                const response = await apiCall('/menu');
                console.log('Menu API response:', response);
                if (response.success) {
                    this.menus = response.data;
                    // Extract unique categories
                    this.categories = [...new Set(this.menus.map(m => m.category))];
                    console.log('Loaded menus:', this.menus.length, 'Categories:', this.categories);
                } else {
                    console.error('Menu API returned error:', response);
                }
            } catch (error) {
                console.error('Error loading menus:', error);
            }
        },

        setMode(newMode) {
            this.mode = newMode;
            this.rentalForm = {
                pc_id: '',
                user_name: '',
                duration: 60,
                session_id: ''
            };
            this.selectedPC = null;
            this.selectedSession = null;
            this.rentalCost = 0;
        },

        updatePCSelection() {
            if (!this.rentalForm.pc_id) {
                this.selectedPC = null;
                this.rentalCost = 0;
                return;
            }
            this.selectedPC = this.pcs.find(pc => pc.id == this.rentalForm.pc_id);
            if (!this.selectedPC) {
                console.error('PC not found:', this.rentalForm.pc_id);
                this.rentalCost = 0;
                return;
            }
            this.calculateRentalCost();
        },

        updateSessionSelection() {
            this.selectedSession = this.activeSessions.find(s => s.id == this.rentalForm.session_id);
        },

        calculateRentalCost() {
            if (!this.selectedPC || !this.rentalForm.duration) {
                this.rentalCost = 0;
                return;
            }
            
            // Harga sesuai PricingService.php
            const basePrice = this.selectedPC.type === 'VIP' ? 10000 : 7000;  // Jam pertama
            const tier2Price = this.selectedPC.type === 'VIP' ? 8000 : 6000;  // Jam ke-2+
            
            const hours = Math.ceil(this.rentalForm.duration / 60);
            let cost = 0;
            
            if (hours > 0) {
                cost += basePrice;  // Jam pertama
                if (hours > 1) {
                    cost += (hours - 1) * tier2Price;  // Jam berikutnya
                }
            }
            
            this.rentalCost = cost;
        },

        get filteredMenus() {
            if (this.categoryFilter === 'all') {
                return this.menus;
            }
            return this.menus.filter(m => m.category === this.categoryFilter);
        },

        addToCart(menu) {
            const existingItem = this.cart.find(item => item.id === menu.id);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                this.cart.push({
                    id: menu.id,
                    name: menu.name,
                    price: menu.price,
                    quantity: 1
                });
            }
        },

        increaseQuantity(index) {
            this.cart[index].quantity++;
        },

        decreaseQuantity(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        get menuSubtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        get grandTotal() {
            return this.rentalCost + this.menuSubtotal;
        },

        canSubmit() {
            // Must have payment method
            if (!this.paymentMethod) return false;

            if (this.mode === 'new_rental') {
                // For new rental: must have PC, duration, and optionally menu items
                return this.rentalForm.pc_id && this.rentalForm.duration > 0;
            } else {
                // For existing session: only need to select a session
                // Cart items are optional - user can order menu or just interact with session
                return this.rentalForm.session_id;
            }
        },

        getSubmitButtonText() {
            if (this.mode === 'new_rental') {
                if (!this.rentalForm.pc_id) {
                    return 'Select PC to Continue';
                }
                if (this.cart.length > 0) {
                    return 'Start Rental & Order Menu';
                }
                return 'Start Rental Only';
            } else {
                if (!this.rentalForm.session_id) {
                    return 'Select Session to Continue';
                }
                if (this.cart.length > 0) {
                    return 'Order Menu';
                }
                return 'Continue with Session';
            }
        },

        async submitOrder() {
            if (!this.canSubmit()) {
                alert('Please complete all required fields');
                return;
            }

            try {
                let sessionId = null;

                // Step 1: Create rental session if mode is new_rental
                if (this.mode === 'new_rental') {
                    const sessionResponse = await apiCall('/sessions', 'POST', {
                        pc_id: this.rentalForm.pc_id,
                        duration: parseInt(this.rentalForm.duration),
                        user_name: this.rentalForm.user_name || 'Guest',
                        tier: this.selectedPC.type,
                        payment_method: this.paymentMethod
                    });

                    if (!sessionResponse.success) {
                        alert('Error creating rental session: ' + (sessionResponse.message || 'Unknown error'));
                        return;
                    }

                    sessionId = sessionResponse.data.id;
                } else {
                    sessionId = this.rentalForm.session_id;
                }

                // Step 2: Create order if cart has items
                if (this.cart.length > 0) {
                    const orderItems = this.cart.map(item => ({
                        menu_id: item.id,
                        quantity: item.quantity
                    }));

                    const tableId = this.mode === 'new_rental' 
                        ? this.selectedPC.pc_code 
                        : this.selectedSession.pc.pc_code;

                    const orderResponse = await apiCall('/orders', 'POST', {
                        table_id: tableId,
                        rental_session_id: sessionId,
                        items: orderItems,
                        payment_method: this.paymentMethod,
                        notes: ''
                    });

                    if (!orderResponse.success) {
                        alert('Error creating order: ' + (orderResponse.message || 'Unknown error'));
                        return;
                    }
                }

                // Success!
                if (this.mode === 'new_rental') {
                    const message = this.cart.length > 0 
                        ? `Rental started and menu ordered! Total: ${formatRupiah(this.grandTotal)}`
                        : `Rental started successfully! Total: ${formatRupiah(this.grandTotal)}`;
                    alert(message);
                } else {
                    const message = this.cart.length > 0 
                        ? `Menu ordered successfully! Total: ${formatRupiah(this.menuSubtotal)}`
                        : 'Session updated successfully!';
                    alert(message);
                }
                
                // Reset form
                this.resetForm();
                
                // Reload data
                await this.loadPCs();
                await this.loadActiveSessions();

            } catch (error) {
                console.error('Error submitting order:', error);
                alert('Error processing request. Please try again.');
            }
        },

        resetForm() {
            this.mode = 'new_rental';
            this.rentalForm = {
                pc_id: '',
                user_name: '',
                duration: 60,
                session_id: ''
            };
            this.cart = [];
            this.selectedPC = null;
            this.selectedSession = null;
            this.rentalCost = 0;
            this.paymentMethod = 'CASH';
            this.categoryFilter = 'all';
        },

        formatTimeRemaining(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}h ${minutes}m`;
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
