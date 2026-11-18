<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info('Mostrando todos los productos');
        $productos = Producto::all();
        return response()->json($productos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric|min:0',
        ]);

        $producto = Producto::create($request->only(['nombre', 'descripcion', 'precio']));
        Log::info('Producto creado: ', $producto->toArray());
        return response()->json($producto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        Log::info('Mostrando producto con ID: ' . $id);
        $producto = Producto::findOrFail($id);
        return response()->json($producto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($request->only(['nombre', 'descripcion', 'precio']));
        Log::warning('Producto actualizado: ', $producto->toArray());
        return response()->json($producto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Producto::destroy($id);
        Log::error('Producto eliminado con ID: ' . $id);
        return response()->json(null, 204);
    }
}
