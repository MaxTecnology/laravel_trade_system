<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubCategoria;
use App\Models\Categoria;
use App\Models\Oferta;
use Illuminate\Http\Request;

class SubcategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SubCategoria::query();

        // Filtros
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Incluir relacionamentos
        $query->with('categoria');

        // Ordenação
        $orderBy = $request->input('order_by', 'nome_sub_categoria');
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
            'nome_sub_categoria' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id_categoria',
        ]);

        // Verificar se já existe uma sub_categoria com este nome na mesma categoria
        $existente = SubCategoria::where('nome_sub_categoria', $validated['nome_sub_categoria'])
            ->where('categoria_id', $validated['categoria_id'])
            ->first();

        if ($existente) {
            return response()->json([
                'message' => 'Já existe uma sub_categoria com este nome nesta categoria',
            ], 400);
        }

        $subcategoria = SubCategoria::create($validated);

        return response()->json($subcategoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $subcategoria = SubCategoria::with('categoria')->findOrFail($id);
        return response()->json($subcategoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $subcategoria = SubCategoria::findOrFail($id);

        $validated = $request->validate([
            'nome_sub_categoria' => 'sometimes|string|max:255',
            'categoria_id' => 'sometimes|exists:categorias,id_categoria',
        ]);

        // Verificar se já existe uma sub_categoria com este nome na mesma categoria
        if (isset($validated['nome_sub_categoria']) || isset($validated['categoria_id'])) {
            $nomeSubcategoria = $validated['nome_sub_categoria'] ?? $subcategoria->nome_sub_categoria;
            $categoriaId = $validated['categoria_id'] ?? $subcategoria->categoria_id;

            $existente = SubCategoria::where('nome_sub_categoria', $nomeSubcategoria)
                ->where('categoria_id', $categoriaId)
                ->where('id_sub_categoria', '!=', $id)
                ->first();

            if ($existente) {
                return response()->json([
                    'message' => 'Já existe uma sub_categoria com este nome nesta categoria',
                ], 400);
            }
        }

        $subcategoria->update($validated);

        return response()->json($subcategoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $subcategoria = SubCategoria::with('categoria')->findOrFail($id);

        // Armazenar informações da subcategoria antes de deletar
        $nomeSubcategoria = $subcategoria->nome_sub_categoria;
        $nomeCategoria = $subcategoria->categoria->nome_categoria ?? 'N/A';
        $categoriaId = $subcategoria->categoria_id;

        // Verificar se há ofertas associadas
        if ($subcategoria->ofertas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir esta subcategoria pois existem ofertas associadas',
                'subcategoria' => [
                    'id' => $subcategoria->id_sub_categoria,
                    'nome' => $nomeSubcategoria,
                    'categoria' => [
                        'id' => $categoriaId,
                        'nome' => $nomeCategoria
                    ]
                ],
                'ofertas_count' => $subcategoria->ofertas()->count()
            ], 400);
        }

        // Verificar se há usuários associados
        if ($subcategoria->usuarios()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir esta subcategoria pois existem usuários associados',
                'subcategoria' => [
                    'id' => $subcategoria->id_sub_categoria,
                    'nome' => $nomeSubcategoria,
                    'categoria' => [
                        'id' => $categoriaId,
                        'nome' => $nomeCategoria
                    ]
                ],
                'usuarios_count' => $subcategoria->usuarios()->count()
            ], 400);
        }

        // Deletar a subcategoria
        $subcategoria->delete();

        // Retornar mensagem de sucesso com informações da subcategoria deletada
        return response()->json([
            'success' => true,
            'message' => "Subcategoria '{$nomeSubcategoria}' da categoria '{$nomeCategoria}' foi excluída com sucesso",
            'deleted_subcategoria' => [
                'id' => $id,
                'nome' => $nomeSubcategoria,
                'categoria' => [
                    'id' => $categoriaId,
                    'nome' => $nomeCategoria
                ],
                'deleted_at' => now()->toISOString()
            ]
        ], 200);
    }

    /**
     * Get subcategorias by categoria
     */
    public function getByCategoria(int $categoriaId)
    {
        $categoria = Categoria::findOrFail($categoriaId);

        $subcategorias = SubCategoria::where('categoria_id', $categoriaId)
            ->orderBy('nome_sub_categoria')
            ->get();

        return response()->json($subcategorias);
    }

    /**
     * Get ofertas by sub_categoria
     */
    public function ofertas(int $id, Request $request)
    {
        $subcategoria = SubCategoria::findOrFail($id);

        $query = Oferta::where('sub_categoria_id', $subcategoria->id_sub_categoria)
            ->where('status', true);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);

        $ofertas = $query->with(['usuario', 'categoria', 'imagensUp'])
            ->paginate($perPage);

        return response()->json($ofertas);
    }
}
