<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Plano::query();

        // Filtros
        if ($request->has('tipo_do_plano')) {
            $query->where('tipo_do_plano', $request->tipo_do_plano);
        }

        // Ordenação
        $orderBy = $request->input('order_by', 'nome_plano');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Opção para retornar todos sem paginação
        if ($request->has('all') && $request->all === 'true') {
            return $query->get();
        }

        // Paginação
        $perPage = $request->input('per_page', 15);

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_plano' => 'required|string|max:255|unique:planos,nome_plano',
            'tipo_do_plano' => 'nullable|string|max:255',
            'imagem' => 'nullable|image|max:2048', // 2MB max
            'taxa_inscricao' => 'required|numeric|min:0',
            'taxa_comissao' => 'required|numeric|min:0',
            'taxa_manutencao_anual' => 'required|numeric|min:0',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            $imagemPath = $request->file('imagem')->store('planos', 'public');
            $validated['imagem'] = $imagemPath;
        }

        $plano = Plano::create($validated);

        return response()->json($plano, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $plano = Plano::findOrFail($id);
        return response()->json($plano);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $plano = Plano::findOrFail($id);

        $validated = $request->validate([
            'nome_plano' => 'sometimes|string|max:255|unique:planos,nome_plano,' . $id . ',id_plano',
            'tipo_do_plano' => 'nullable|string|max:255',
            'imagem' => 'nullable|image|max:2048', // 2MB max
            'taxa_inscricao' => 'sometimes|numeric|min:0',
            'taxa_comissao' => 'sometimes|numeric|min:0',
            'taxa_manutencao_anual' => 'sometimes|numeric|min:0',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            // Remover imagem anterior se existir
            if ($plano->imagem) {
                Storage::disk('public')->delete($plano->imagem);
            }

            $imagemPath = $request->file('imagem')->store('planos', 'public');
            $validated['imagem'] = $imagemPath;
        }

        $plano->update($validated);

        return response()->json($plano);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $plano = Plano::findOrFail($id);

        // Verificar se há contas associadas
        if ($plano->contas()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir este plano pois existem contas associadas',
            ], 400);
        }
        // aramazena o nome do plano antes de deletar
        $nomePlano = $plano->nome_plano;

        // Remover imagem se existir
        if ($plano->imagem) {
            Storage::disk('public')->delete($plano->imagem);
        }

        $plano->delete();

        return response()->json([
            'message' => 'Plano "' . $nomePlano . '" excluído com sucesso.',
            'plano' => $plano,
        ], 200);
    }

    /**
     * Get planos by tipo
     */
    public function getByTipo(Request $request)
    {
        $request->validate([
            'tipo_conta_id' => 'nullable|integer',
            'tipo' => 'nullable|string',
        ]);

        $query = Plano::query();

        // Filtrar por ID se fornecido
        if ($request->has('tipo_conta_id')) {
            $query->where('tipo_do_plano', $request->tipo_conta_id);
        }

        // Filtrar por nome do tipo se fornecido
        if ($request->has('tipo')) {
            $query->where('tipo_do_plano', $request->tipo);
        }

        // Se nenhum filtro foi aplicado, retornar erro
        if (!$request->has('tipo_conta_id') && !$request->has('tipo')) {
            return response()->json([
                'message' => 'É necessário fornecer pelo menos um parâmetro: tipo_conta_id ou tipo',
            ], 400);
        }

        $planos = $query->orderBy('nome_plano')->get();

        return response()->json($planos);
    }

    /**
     * Compare planos
     */
    public function compare(Request $request)
    {
        $request->validate([
            'planos' => 'required|array',
            'planos.*' => 'exists:planos,id_plano',
        ]);

        $planos = Plano::whereIn('id_plano', $request->planos)
            ->get();

        return response()->json($planos);
    }
}
