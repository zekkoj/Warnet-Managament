<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = Menu::all();
        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:MAKANAN_BERAT,MAKANAN_RINGAN,MINUMAN_DINGIN,MINUMAN_PANAS,MINUMAN_SACHET',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'available' => 'boolean',
        ]);

        $menu = Menu::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'category' => 'in:MAKANAN_BERAT,MAKANAN_RINGAN,MINUMAN_DINGIN,MINUMAN_PANAS,MINUMAN_SACHET',
            'price' => 'numeric|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'available' => 'boolean',
        ]);

        $menu->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully',
        ]);
    }
}
