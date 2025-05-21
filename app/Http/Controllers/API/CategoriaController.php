<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Oferta;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Categoria::query();

        // Filtros
        if ($request->has('tipo_categoria')) {
            $query->where('tipo_categoria', $request->tipo_categoria);
        }

        // Incluir relacionamentos
        if ($request->has('includeSubcategorias') && $request->includeSubcategorias === 'true') {
            $query->with('sub_categorias');
        }

        // Ordenação
        $orderBy = $request->input('order_by', 'nome_categoria');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 50);

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_categoria' => 'required|string|max:255|unique:categorias,nome_categoria',
            'tipo_categoria' => 'nullable|string',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json($categoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $categoria = Categoria::with('sub_categorias')->findOrFail($id);
        return response()->json($categoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $categoria = Categoria::findOrFail($id);

        $validated = $request->validate([
            'nome_categoria' => 'sometimes|string|max:255|unique:categorias,nome_categoria,' . $id . ',id_categoria',
            'tipo_categoria' => 'nullable|string',
        ]);

        $categoria->update($validated);

        return response()->json($categoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $categoria = Categoria::findOrFail($id);

        // Armazenar informações da categoria antes de deletar
        $nomeCategoria = $categoria->nome_categoria;
        $tipoCategoria = $categoria->tipo_categoria;

        // Verificar se há sub_categorias associadas
        if ($categoria->sub_categorias()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir esta categoria pois existem subcategorias associadas',
                'categoria' => [
                    'id' => $categoria->id_categoria,
                    'nome' => $nomeCategoria,
                    'tipo' => $tipoCategoria
                ],
                'subcategorias_count' => $categoria->sub_categorias()->count()
            ], 400);
        }

        // Verificar se há ofertas associadas
        if ($categoria->ofertas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir esta categoria pois existem ofertas associadas',
                'categoria' => [
                    'id' => $categoria->id_categoria,
                    'nome' => $nomeCategoria,
                    'tipo' => $tipoCategoria
                ],
                'ofertas_count' => $categoria->ofertas()->count()
            ], 400);
        }

        // Verificar se há usuários associados
        if ($categoria->usuarios()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir esta categoria pois existem usuários associados',
                'categoria' => [
                    'id' => $categoria->id_categoria,
                    'nome' => $nomeCategoria,
                    'tipo' => $tipoCategoria
                ],
                'usuarios_count' => $categoria->usuarios()->count()
            ], 400);
        }

        // Deletar a categoria
        $categoria->delete();

        // Retornar mensagem de sucesso com informações da categoria deletada
        return response()->json([
            'success' => true,
            'message' => "Categoria '{$nomeCategoria}' foi excluída com sucesso",
            'deleted_categoria' => [
                'id' => $id,
                'nome' => $nomeCategoria,
                'tipo' => $tipoCategoria,
                'deleted_at' => now()->toISOString()
            ]
        ], 200);
    }

    /**
     * Get categorias with count of ofertas
     */
    public function withOfertasCount(Request $request)
    {
        $categorias = Categoria::withCount([
            'ofertas' => function ($query) {
                $query->where('status', true);
            }
        ])
            ->orderBy('ofertas_count', 'desc')
            ->get();

        return response()->json($categorias);
    }

    /**
     * Get ofertas by categoria
     */
    public function ofertas(int $id, Request $request)
    {
        $categoria = Categoria::findOrFail($id);

        $query = Oferta::where('categoria_id', $categoria->id_categoria)
            ->where('status', true);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);

        $ofertas = $query->with(['usuario', 'sub_categoria', 'imagensUp'])
            ->paginate($perPage);

        return response()->json($ofertas);
    }
}
