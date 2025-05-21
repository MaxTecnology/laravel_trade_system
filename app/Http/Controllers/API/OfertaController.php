<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Oferta;
use App\Models\Imagem;
use App\Models\Categoria;
use App\Models\SubCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OfertaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Oferta::query();

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('sub_categoria_id')) {
            $query->where('sub_categoria_id', $request->sub_categoria_id);
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        } else {
            // Se não for especificado um usuário, mostrar apenas ofertas ativas
            $query->where('status', true);
        }

        if ($request->has('cidade')) {
            $query->where('cidade', 'like', '%' . $request->cidade . '%');
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('retirada')) {
            $query->where('retirada', $request->retirada);
        }

        if ($request->has('precoMin') && $request->has('precoMax')) {
            $query->whereBetween('valor', [$request->precoMin, $request->precoMax]);
        } elseif ($request->has('precoMin')) {
            $query->where('valor', '>=', $request->precoMin);
        } elseif ($request->has('precoMax')) {
            $query->where('valor', '<=', $request->precoMax);
        }

        // Relacionamentos
        $query->with(['categoria', 'sub_categoria', 'usuario', 'imagensUp']);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

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
            'id_franquia' => 'nullable|integer',
            'nome_franquia' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|string',
            'status' => 'required|boolean',
            'descricao' => 'required|string',
            'quantidade' => 'required|integer|min:1',
            'valor' => 'required|numeric|min:0',
            'limite_compra' => 'required|numeric|min:0',
            'vencimento' => 'required|date',
            'cidade' => 'required|string|max:255',
            'estado' => 'required|string|max:2',
            'retirada' => 'required|string',
            'obs' => 'required|string',
            'imagens' => 'nullable|array',
            'imagens.*' => 'string',
            'usuario_id' => 'required|exists:usuarios,id_usuario',
            'nome_usuario' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id_categoria',
            'sub_categoria_id' => 'nullable|exists:sub_categorias,id_sub_categoria',
            'sub_conta_id' => 'nullable|exists:sub_contas,id_sub_contas',
        ]);

        // Verificar sub_categoria pertence à categoria
        if (isset($validated['sub_categoria_id']) && isset($validated['categoria_id'])) {
            $subcategoria = SubCategoria::find($validated['sub_categoria_id']);
            if ($subcategoria->categoria_id != $validated['categoria_id']) {
                return response()->json([
                    'message' => 'A sub_categoria selecionada não pertence à categoria',
                ], 400);
            }
        }

        // Criar oferta
        $oferta = Oferta::create($validated);

        return response()->json($oferta, 201);
    }

    /**
     * Display the specified resource.
     */
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Converte implicitamente para inteiro ao usar findOrFail
        $oferta = Oferta::with(['categoria', 'sub_categoria', 'usuario', 'subconta', 'imagensUp'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $oferta
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $oferta = Oferta::findOrFail($id);

        $validated = $request->validate([
            'id_franquia' => 'nullable|integer',
            'nome_franquia' => 'nullable|string|max:255',
            'titulo' => 'sometimes|string|max:255',
            'tipo' => 'sometimes|string',
            'status' => 'sometimes|boolean',
            'descricao' => 'sometimes|string',
            'quantidade' => 'sometimes|integer|min:0',
            'valor' => 'sometimes|numeric|min:0',
            'limite_compra' => 'sometimes|numeric|min:0',
            'vencimento' => 'sometimes|date',
            'cidade' => 'sometimes|string|max:255',
            'estado' => 'sometimes|string|max:2',
            'retirada' => 'sometimes|string',
            'obs' => 'sometimes|string',
            'imagens' => 'nullable|array',
            'imagens.*' => 'string',
            'nome_usuario' => 'sometimes|string|max:255',
            'categoria_id' => 'sometimes|exists:categorias,id_categoria',
            'sub_categoria_id' => 'nullable|exists:sub_categorias,id_sub_categoria',
            'sub_conta_id' => 'nullable|exists:sub_contas,id_sub_contas',
        ]);

        // Verificar sub_categoria pertence à categoria
        if (isset($validated['sub_categoria_id']) && isset($validated['categoria_id'])) {
            $subcategoria = SubCategoria::find($validated['sub_categoria_id']);
            if ($subcategoria->categoria_id != $validated['categoria_id']) {
                return response()->json([
                    'message' => 'A sub_categoria selecionada não pertence à categoria',
                ], 400);
            }
        } elseif (isset($validated['sub_categoria_id'])) {
            $subcategoria = SubCategoria::find($validated['sub_categoria_id']);
            if ($subcategoria->categoria_id != $oferta->categoria_id) {
                return response()->json([
                    'message' => 'A sub_categoria selecionada não pertence à categoria',
                ], 400);
            }
        }

        // Atualizar oferta
        $oferta->update($validated);

        return response()->json($oferta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $oferta = Oferta::findOrFail($id);

        // Verificar se existem transações relacionadas
        if ($oferta->transacoes()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir esta oferta pois existem transações relacionadas',
            ], 400);
        }

        // Salvar informações da oferta para usar na mensagem
        $tituloOferta = $oferta->titulo;
        $idOferta = $oferta->id_oferta;

        // Excluir imagens
        foreach ($oferta->imagensUp as $imagem) {
            Storage::disk('public')->delete($imagem->url);
            $imagem->delete();
        }

        $oferta->delete();

        // Retornar código 200 com mensagem descritiva em vez de 204
        return response()->json([
            'success' => true,
            'message' => "Oferta #$idOferta \"$tituloOferta\" foi excluída com sucesso",
            'id' => $idOferta
        ], 200);
    }

    /**
     * Upload de imagens para a oferta
     */
    public function uploadImages(Request $request, int $id)
    {
        $request->validate([
            'imagens' => 'required|array',
            'imagens.*' => 'image|max:5120', // 5MB max
        ]);

        $oferta = Oferta::findOrFail($id);
        $imagens = [];

        foreach ($request->file('imagens') as $file) {
            // Gerar nome único para o arquivo
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            // Salvar arquivo
            $path = $file->storeAs('ofertas', $filename, 'public');

            // Criar registro de imagem
            $imagem = Imagem::create([
                'public_id' => $filename,
                'url' => $path,
                'oferta_id' => $oferta->id_oferta,
            ]);

            $imagens[] = $imagem;
        }

        return response()->json([
            'message' => 'Imagens enviadas com sucesso',
            'imagens' => $imagens,
        ]);
    }

    /**
     * Remover imagem
     */
    public function removeImage(Request $request, int $id, int $imageId)
    {
        $oferta = Oferta::findOrFail($id);
        $imagem = Imagem::where('id', $imageId)
            ->where('oferta_id', $oferta->id_oferta)
            ->firstOrFail();

        // Remover arquivo
        Storage::disk('public')->delete($imagem->url);

        // Remover registro
        $imagem->delete();

        return response()->json([
            'message' => 'Imagem removida com sucesso',
        ]);
    }

    /**
     * Buscar ofertas por termo
     */
    /**
     * Buscar ofertas por múltiplos critérios
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string',
            'termo' => 'nullable|string',
            'categoria_id' => 'nullable|exists:categorias,id_categoria',
            'min_valor' => 'nullable|numeric|min:0',
            'max_valor' => 'nullable|numeric|min:0',
        ]);

        $query = Oferta::where('status', true);

        // Busca por termo (aceita tanto 'q' quanto 'termo' para compatibilidade)
        $termoBusca = $request->q ?? $request->termo ?? null;

        if ($termoBusca) {
            $query->where(function ($q) use ($termoBusca) {
                $q->where('titulo', 'like', "%{$termoBusca}%")
                    ->orWhere('descricao', 'like', "%{$termoBusca}%")
                    ->orWhere('cidade', 'like', "%{$termoBusca}%")
                    ->orWhere('nome_usuario', 'like', "%{$termoBusca}%");
            });
        }

        // Filtrar por categoria
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtrar por valor (preço)
        if ($request->has('min_valor') && $request->has('max_valor')) {
            $query->whereBetween('valor', [$request->min_valor, $request->max_valor]);
        } elseif ($request->has('min_valor')) {
            $query->where('valor', '>=', $request->min_valor);
        } elseif ($request->has('max_valor')) {
            $query->where('valor', '<=', $request->max_valor);
        }

        // Carrega relacionamentos
        $ofertas = $query->with(['categoria', 'sub_categoria', 'usuario', 'imagensUp'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Busca realizada com sucesso',
            'total' => $ofertas->total(),
            'data' => $ofertas
        ]);
    }

    /**
     * Ativar/desativar oferta
     */
    public function toggleStatus(int $id)
    {
        $oferta = Oferta::findOrFail($id);
        $oferta->status = !$oferta->status;
        $oferta->save();

        return response()->json([
            'message' => $oferta->status ? 'Oferta ativada com sucesso' : 'Oferta desativada com sucesso',
            'oferta' => $oferta
        ]);
    }
}
