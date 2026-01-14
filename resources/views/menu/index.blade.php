@extends('layouts.app')

@section('title', 'Menu Management - Warnet Management System')
@section('page-title', 'Menu Management')

@section('content')
<div x-data="menuManagement()" x-init="init()">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Total Items</p>
            <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Foods</p>
            <p class="text-2xl font-bold text-orange-600" x-text="stats.foods"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Beverages</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.beverages"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Snacks</p>
            <p class="text-2xl font-bold text-yellow-600" x-text="stats.snacks"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Available</p>
            <p class="text-2xl font-bold text-green-600" x-text="stats.available"></p>
        </div>
    </div>

    <!-- Filter & Actions -->
    <div class="bg-white rounded-lg shadow p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <select x-model="categoryFilter" @change="filterMenu()" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="all">All Categories</option>
                <option value="MAKANAN_BERAT">Makanan Berat</option>
                <option value="MAKANAN_RINGAN">Makanan Ringan</option>
                <option value="MINUMAN_DINGIN">Minuman Dingin</option>
                <option value="MINUMAN_PANAS">Minuman Panas</option>
                <option value="MINUMAN_SACHET">Minuman Sachet</option>
            </select>

            <select x-model="availabilityFilter" @change="filterMenu()" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="all">All Status</option>
                <option value="1">Available</option>
                <option value="0">Not Available</option>
            </select>
        </div>

        <button @click="showCreateModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Add Menu Item
        </button>
    </div>

    <!-- Menu Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="item in filteredMenu" :key="item.id">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                <!-- Image -->
                <div class="h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center overflow-hidden">
                    <template x-if="item.image_url">
                        <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!item.image_url">
                        <i class="fas fa-utensils text-6xl text-gray-400"></i>
                    </template>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-bold text-lg text-gray-800" x-text="item.name"></h3>
                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                              :class="{
                                  'bg-orange-100 text-orange-800': item.category === 'MAKANAN_BERAT',
                                  'bg-yellow-100 text-yellow-800': item.category === 'MAKANAN_RINGAN',
                                  'bg-blue-100 text-blue-800': item.category === 'MINUMAN_DINGIN',
                                  'bg-red-100 text-red-800': item.category === 'MINUMAN_PANAS',
                                  'bg-purple-100 text-purple-800': item.category === 'MINUMAN_SACHET'
                              }"
                              x-text="item.category"></span>
                    </div>

                    <p class="text-sm text-gray-600 mb-3 line-clamp-2" x-text="item.description || 'No description'"></p>

                    <div class="flex items-center justify-between mb-3">
                        <span class="text-2xl font-bold text-green-600" x-text="formatRupiah(item.price)"></span>
                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                              :class="item.available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                              x-text="item.available ? 'Available' : 'Not Available'"></span>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button @click="editItem(item)" 
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button @click="toggleAvailability(item)" 
                                class="px-3 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">
                            <i :class="item.available ? 'fa-eye-slash' : 'fa-eye'" class="fas"></i>
                        </button>
                        <button @click="deleteItem(item.id)" 
                                class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showCreateModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4" x-text="editMode ? 'Edit Menu Item' : 'Add Menu Item'"></h2>
            <form @submit.prevent="saveItem()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" x-model="formData.name" required 
                               placeholder="e.g., Nasi Goreng Spesial"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select x-model="formData.category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select Category</option>
                            <option value="MAKANAN_BERAT">Makanan Berat</option>
                            <option value="MAKANAN_RINGAN">Makanan Ringan</option>
                            <option value="MINUMAN_DINGIN">Minuman Dingin</option>
                            <option value="MINUMAN_PANAS">Minuman Panas</option>
                            <option value="MINUMAN_SACHET">Minuman Sachet</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rupiah) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">Rp</span>
                            <input type="number" x-model="formData.price" required min="0" step="1000"
                                   placeholder="0"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Format: 5000, 10000, 15000</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea x-model="formData.description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                        <input type="text" x-model="formData.image_url" 
                               placeholder="e.g., /images/menus/Nasi Goreng.png"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Path to menu image</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" x-model="formData.available" id="available"
                               class="w-4 h-4 text-blue-600 rounded">
                        <label for="available" class="ml-2 text-sm text-gray-700">Available for order</label>
                    </div>
                </div>

                <div class="mt-6 flex space-x-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i><span x-text="editMode ? 'Update' : 'Create'"></span>
                    </button>
                    <button type="button" @click="closeModal()" 
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
function menuManagement() {
    return {
        menu: [],
        filteredMenu: [],
        showCreateModal: false,
        editMode: false,
        categoryFilter: 'all',
        availabilityFilter: 'all',
        formData: {
            name: '',
            category: '',
            price: 0,
            description: '',
            image_url: '',
            available: true
        },
        stats: {
            total: 0,
            foods: 0,
            beverages: 0,
            snacks: 0,
            available: 0
        },

        async init() {
            // Wait for app to be ready (auto-login complete)
            await window.appReady;
            await this.loadMenu();
        },

        async loadMenu() {
            try {
                const response = await apiCall('/menu');
                if (response.success) {
                    this.menu = response.data;
                    this.calculateStats();
                    this.filterMenu();
                }
            } catch (error) {
                console.error('Error loading menu:', error);
            }
        },

        calculateStats() {
            this.stats.total = this.menu.length;
            this.stats.foods = this.menu.filter(m => m.category === 'MAKANAN_BERAT' || m.category === 'MAKANAN_RINGAN').length;
            this.stats.beverages = this.menu.filter(m => m.category === 'MINUMAN_DINGIN' || m.category === 'MINUMAN_PANAS' || m.category === 'MINUMAN_SACHET').length;
            this.stats.snacks = this.menu.filter(m => m.category === 'MAKANAN_RINGAN').length;
            this.stats.available = this.menu.filter(m => m.available).length;
        },

        filterMenu() {
            this.filteredMenu = this.menu.filter(item => {
                const categoryMatch = this.categoryFilter === 'all' || item.category === this.categoryFilter;
                const availabilityMatch = this.availabilityFilter === 'all' || 
                                        item.available == this.availabilityFilter;
                return categoryMatch && availabilityMatch;
            });
        },

        editItem(item) {
            this.editMode = true;
            this.formData = { ...item };
            this.showCreateModal = true;
        },

        async saveItem() {
            try {
                let response;
                if (this.editMode) {
                    response = await apiCall(`/menu/${this.formData.id}`, 'PUT', this.formData);
                } else {
                    response = await apiCall('/menu', 'POST', this.formData);
                }

                if (response.success) {
                    alert(this.editMode ? 'Menu item updated!' : 'Menu item created!');
                    this.closeModal();
                    await this.loadMenu();
                }
            } catch (error) {
                alert('Error saving menu item');
            }
        },

        async toggleAvailability(item) {
            try {
                const response = await apiCall(`/menu/${item.id}`, 'PUT', {
                    ...item,
                    available: !item.available
                });
                if (response.success) {
                    await this.loadMenu();
                }
            } catch (error) {
                alert('Error updating availability');
            }
        },

        async deleteItem(id) {
            if (!confirm('Delete this menu item?')) return;
            
            try {
                const response = await apiCall(`/menu/${id}`, 'DELETE');
                if (response.success) {
                    alert('Menu item deleted!');
                    await this.loadMenu();
                }
            } catch (error) {
                alert('Error deleting menu item');
            }
        },

        closeModal() {
            this.showCreateModal = false;
            this.editMode = false;
            this.formData = {
                name: '',
                category: '',
                price: 0,
                description: '',
                image_url: '',
                available: true
            };
        }
    }
}
</script>
@endpush
@endsection
