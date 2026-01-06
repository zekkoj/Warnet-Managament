@extends('layouts.app')

@section('title', 'Orders Management - Warnet Management System')
@section('page-title', 'Orders Management')

@section('content')
<div x-data="ordersManagement()" x-init="init()">
    <!-- Filter Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="flex border-b border-gray-200">
            <button @click="statusFilter = 'all'; filterOrders()"
                    :class="statusFilter === 'all' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
                    class="px-6 py-3 font-semibold">
                All Orders (<span x-text="stats.all"></span>)
            </button>
            <button @click="statusFilter = 'RECEIVED'; filterOrders()"
                    :class="statusFilter === 'RECEIVED' ? 'border-b-2 border-yellow-600 text-yellow-600' : 'text-gray-600'"
                    class="px-6 py-3 font-semibold">
                Received (<span x-text="stats.received"></span>)
            </button>
            <button @click="statusFilter = 'PREPARING'; filterOrders()"
                    :class="statusFilter === 'PREPARING' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
                    class="px-6 py-3 font-semibold">
                Preparing (<span x-text="stats.preparing"></span>)
            </button>
            <button @click="statusFilter = 'COMPLETED'; filterOrders()"
                    :class="statusFilter === 'COMPLETED' ? 'border-b-2 border-gray-600 text-gray-600' : 'text-gray-600'"
                    class="px-6 py-3 font-semibold">
                Completed (<span x-text="stats.completed"></span>)
            </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="order in filteredOrders" :key="order.id">
            <div class="bg-white rounded-lg shadow-lg border-l-4"
                 :class="{
                     'border-yellow-500': order.order_status === 'RECEIVED',
                     'border-blue-500': order.order_status === 'PREPARING',
                     'border-gray-400': order.order_status === 'COMPLETED'
                 }">
                
                <!-- Order Header -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-lg font-bold text-gray-800" x-text="'Order #' + order.id"></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-yellow-100 text-yellow-800': order.order_status === 'RECEIVED',
                                  'bg-blue-100 text-blue-800': order.order_status === 'PREPARING',
                                  'bg-gray-100 text-gray-800': order.order_status === 'COMPLETED'
                              }"
                              x-text="order.order_status"></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span x-text="order.table_id"></span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1" x-text="formatDateTime(order.created_at)"></div>
                </div>

                <!-- Order Items -->
                <div class="p-4 space-y-2">
                    <template x-for="item in order.order_items" :key="item.id">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800" x-text="item.menu.name"></p>
                                <p class="text-sm text-gray-500" x-text="formatRupiah(item.unit_price)"></p>
                            </div>
                            <span class="ml-2 px-2 py-1 bg-gray-100 rounded font-semibold" x-text="'x' + item.quantity"></span>
                        </div>
                    </template>
                </div>

                <!-- Order Footer -->
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-semibold text-gray-700">Total:</span>
                        <span class="text-xl font-bold text-green-600" x-text="formatRupiah(order.total)"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <template x-if="order.order_status === 'RECEIVED'">
                            <button @click="updateOrderStatus(order.id, 'PREPARING')"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                <i class="fas fa-fire mr-2"></i>Start Preparing
                            </button>
                        </template>

                        <template x-if="order.order_status === 'PREPARING'">
                            <button @click="updateOrderStatus(order.id, 'COMPLETED')"
                                    class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                                <i class="fas fa-check-double mr-2"></i>Complete Order
                            </button>
                        </template>

                        <button @click="viewOrderDetails(order)" 
                                class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredOrders.length === 0">
        <div class="text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500">No orders found</p>
        </div>
    </template>

    <!-- Order Detail Modal -->
    <div x-show="selectedOrder" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="selectedOrder = null">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
            <template x-if="selectedOrder">
                <div>
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-2xl font-bold" x-text="'Order #' + selectedOrder.id"></h2>
                        <button @click="selectedOrder = null" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4">
                        <!-- Order Info -->
                        <div>
                            <p class="text-sm text-gray-500">Table/PC</p>
                            <p class="font-semibold text-lg" x-text="selectedOrder.table_id"></p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold mt-1"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': selectedOrder.order_status === 'RECEIVED',
                                      'bg-blue-100 text-blue-800': selectedOrder.order_status === 'PREPARING',
                                      'bg-gray-100 text-gray-800': selectedOrder.order_status === 'COMPLETED'
                                  }"
                                  x-text="selectedOrder.order_status"></span>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Order Time</p>
                            <p class="font-semibold" x-text="formatDateTime(selectedOrder.created_at)"></p>
                        </div>

                        <!-- Items -->
                        <div class="border-t pt-4">
                            <h3 class="font-semibold mb-3">Order Items</h3>
                            <div class="space-y-3">
                                <template x-for="item in selectedOrder.order_items" :key="item.id">
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="font-semibold" x-text="item.menu.name"></p>
                                            <p class="text-sm text-gray-600" x-text="'Qty: ' + item.quantity"></p>
                                        </div>
                                        <p class="font-bold" x-text="formatRupiah(item.subtotal)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center text-xl">
                                <span class="font-semibold">Total:</span>
                                <span class="font-bold text-green-600" x-text="formatRupiah(selectedOrder.total)"></span>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <template x-if="selectedOrder.payment">
                            <div class="border-t pt-4">
                                <h3 class="font-semibold mb-3">Payment Info</h3>
                                <div class="bg-gray-50 p-3 rounded-lg space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Method:</span>
                                        <span class="font-semibold" x-text="selectedOrder.payment.method"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Status:</span>
                                        <span class="font-semibold" x-text="selectedOrder.payment.status"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ordersManagement() {
    return {
        orders: [],
        filteredOrders: [],
        selectedOrder: null,
        statusFilter: 'all',
        stats: {
            all: 0,
            received: 0,
            preparing: 0,
            completed: 0
        },

        async init() {
            await this.loadOrders();
            // 🔥 WebSocket: Listen to real-time order updates
            this.listenToOrderUpdates();
        },

        // 🔥 WebSocket: Listen to real-time order updates
        listenToOrderUpdates() {
            console.log('🔌 Connecting to WebSocket for order updates...');
            
            window.Echo.channel('orders')
                .listen('.order.status.changed', (event) => {
                    console.log('📡 Order updated via WebSocket:', event.order);
                    this.handleOrderUpdate(event.order);
                });
            
            console.log('✅ WebSocket listener registered for orders');
        },

        // 🔥 WebSocket: Handle incoming order updates
        handleOrderUpdate(updatedOrder) {
            const index = this.orders.findIndex(o => o.id === updatedOrder.id);
            
            if (index !== -1) {
                // Update existing order
                this.orders[index] = updatedOrder;
                console.log(`✅ Updated order ${updatedOrder.id} locally`);
            } else {
                // New order, add to list
                this.orders.push(updatedOrder);
                console.log(`✅ Added new order ${updatedOrder.id}`);
            }
            
            this.calculateStats();
            this.filterOrders();
        },

        async loadOrders() {
            try {
                const response = await apiCall('/orders?include=orderItems.menu,payment');
                if (response.success) {
                    this.orders = response.data;
                    this.calculateStats();
                    this.filterOrders();
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        },

        calculateStats() {
            this.stats.all = this.orders.length;
            this.stats.received = this.orders.filter(o => o.order_status === 'RECEIVED').length;
            this.stats.preparing = this.orders.filter(o => o.order_status === 'PREPARING').length;
            this.stats.completed = this.orders.filter(o => o.order_status === 'COMPLETED').length;
        },

        filterOrders() {
            if (this.statusFilter === 'all') {
                this.filteredOrders = this.orders;
            } else {
                this.filteredOrders = this.orders.filter(o => o.order_status === this.statusFilter);
            }
            // Sort by most recent first
            this.filteredOrders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        },

        async updateOrderStatus(orderId, newStatus) {
            try {
                const response = await apiCall(`/orders/${orderId}/status`, 'PUT', { 
                    order_status: newStatus 
                });
                
                if (response.success) {
                    await this.loadOrders();
                }
            } catch (error) {
                alert('Error updating order status');
                console.error(error);
            }
        },

        viewOrderDetails(order) {
            this.selectedOrder = order;
        }
    }
}
</script>
@endpush
@endsection
