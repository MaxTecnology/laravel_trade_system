<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoConta;
use Illuminate\Http\Request;

class TipoContaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipoConta::query();

        // Ordenação
        $orderBy = $request->input('order_by', 'tipo_da_conta');
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
            'tipo_da_conta' => 'required|string|max:255|unique:tipo_contas,tipo_da_conta',
            'prefixo_conta' => 'required|string|max:10|unique:tipo_contas,prefixo_conta',
            'descricao' => 'required|string',
            'permissoes' => 'nullable|json',
        ]);

        $tipoConta = TipoConta::create($validated);

        return response()->json($tipoConta, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $tipoConta = TipoConta::findOrFail($id);
        return response()->json($tipoConta);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $tipoConta = TipoConta::findOrFail($id);

        $validated = $request->validate([
            'tipo_da_conta' => 'sometimes|string|max:255|unique:tipo_contas,tipo_da_conta,' . $id . ',id_tipo_conta',
            'prefixo_conta' => 'sometimes|string|max:10|unique:tipo_contas,prefixo_conta,' . $id . ',id_tipo_conta',
            'descricao' => 'sometimes|string',
            'permissoes' => 'nullable|json',
        ]);

        $tipoConta->update($validated);

        return response()->json($tipoConta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $tipoConta = TipoConta::findOrFail($id);

        // Verificar se há contas associadas
        if ($tipoConta->contasAssociadas()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir este tipo de conta pois existem contas associadas',
            ], 400);
        }

        // Armazenar o nome antes de excluir
        $nomeTipoConta = $tipoConta->tipo_da_conta;

        $tipoConta->delete();

        return response()->json([
            'message' => "Tipo de conta '$nomeTipoConta' excluído com sucesso",
            'tipo_conta' => $tipoConta,
            'sucess' => true,
        ], 200);
    }
}
