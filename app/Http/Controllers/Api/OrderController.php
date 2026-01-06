<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use App\Services\RevenueService;
use App\Events\OrderStatusChanged;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     * 
     * Default: Show ALL orders (for Orders Management page)
     * With ?analytics_only: Show PAID/COMPLETED only (for Analytics)
     */
    public function index(Request $request)
    {
        // For Orders Management: Show ALL orders by default (including PENDING)
        // For Analytics: Use ?analytics_only=true to get only PAID/COMPLETED
        
        if ($request->has('analytics_only') && $request->query('analytics_only') === 'true') {
            // Analytics mode: Only PAID/COMPLETED orders (synced with Analytics)
            $query = RevenueService::getPaidOrders()->toBase();
        } else {
            // Orders Management mode: Show ALL orders (default)
            $query = Order::with('orderItems.menu', 'payment');
        }

        // Apply additional filters
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->with('orderItems.menu', 'payment')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|string',
            'rental_session_id' => 'nullable|exists:rental_sessions,id',
            'items' => 'required|array',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:QRIS,CASH',
            'notes' => 'nullable|string',
        ]);

        // Calculate totals
        $subtotal = 0;
        $items = [];

        foreach ($validated['items'] as $item) {
            $menu = Menu::findOrFail($item['menu_id']);
            $itemSubtotal = $menu->price * $item['quantity'];
            $subtotal += $itemSubtotal;

            $items[] = [
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $menu->price,
                'subtotal' => $itemSubtotal,
            ];
        }

        // Create order
        $order = Order::create([
            'table_id' => $validated['table_id'],
            'rental_session_id' => $validated['rental_session_id'] ?? null,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'PENDING',
            'order_status' => 'RECEIVED',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create order items
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        $order->load('orderItems.menu');

        // Broadcast new order
        broadcast(new OrderStatusChanged($order));

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load('orderItems.menu', 'payment');

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Order updated',
            'data' => $order,
        ]);
    }

    /**
     * Remove the specified order
     */
    public function destroy(Order $order)
    {
        // Delete order items first
        OrderItem::where('order_id', $order->id)->delete();
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted',
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required_without:order_status|in:RECEIVED,PREPARING,READY,COMPLETED,DELIVERED',
            'order_status' => 'required_without:status|in:RECEIVED,PREPARING,READY,COMPLETED,DELIVERED',
        ]);

        $newStatus = $validated['status'] ?? $validated['order_status'];
        
        $order->update(['order_status' => $newStatus]);

        if ($newStatus === 'DELIVERED' || $newStatus === 'COMPLETED') {
            $order->update(['delivered_at' => now()]);
        }

        // Reload order with relationships
        $order->load('orderItems.menu');

        // Broadcast order status change
        broadcast(new OrderStatusChanged($order));

        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'data' => $order,
        ]);
    }
}
