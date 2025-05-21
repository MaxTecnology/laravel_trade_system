<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\ContaController;
use App\Http\Controllers\API\TipoContaController;
use App\Http\Controllers\API\PlanoController;
use App\Http\Controllers\API\SubContaController;
use App\Http\Controllers\API\OfertaController;
use App\Http\Controllers\API\CategoriaController;
use App\Http\Controllers\API\SubcategoriaController;
use App\Http\Controllers\API\TransacaoController;
use App\Http\Controllers\API\VoucherController;
use App\Http\Controllers\API\CobrancaController;
use App\Http\Controllers\API\SolicitacaoCreditoController;
use App\Http\Controllers\API\FundoPermutaController;
use App\Http\Controllers\API\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware(['auth:sanctum', 'check.blocked'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Usuários
    Route::apiResource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{id}/toggle-bloqueio', [UsuarioController::class, 'toggleBloquear']);
    Route::post('/usuarios/{id}/resetar-senha', [UsuarioController::class, 'resetarSenha']);

    // Contas
    Route::apiResource('contas', ContaController::class);
    Route::get('/minha-conta', [ContaController::class, 'minhaConta']);
    Route::post('/contas/{id}/aumentar-limite', [ContaController::class, 'aumentarLimite']);
    Route::post('/contas/{id}/diminuir-limite', [ContaController::class, 'diminuirLimite']);
    Route::post('/contas/{id}/saldo-permuta', [ContaController::class, 'atualizarSaldoPermuta']);
    Route::post('/contas/{id}/saldo-dinheiro', [ContaController::class, 'atualizarSaldoDinheiro']);

    // Tipos de Conta
    Route::apiResource('tipo-contas', TipoContaController::class);

    // Planos
    Route::get('/planos/by-tipo', [PlanoController::class, 'getByTipo']);
    Route::post('/planos/compare', [PlanoController::class, 'compare']);
    Route::apiResource('planos', PlanoController::class);

    // Sub Contas
    Route::apiResource('sub-contas', SubContaController::class);
    Route::post('/sub-contas/{id}/toggle-status', [SubContaController::class, 'toggleStatus']);
    Route::post('/sub-contas/{id}/resetar-senha', [SubContaController::class, 'resetarSenha']);
    Route::put('/sub-contas/{id}/permissoes', [SubContaController::class, 'atualizarPermissoes']);

    // Ofertas
    Route::post('/ofertas/{oferta}/images', [OfertaController::class, 'uploadImages']);
    Route::delete('/ofertas/{oferta}/images/{image}', [OfertaController::class, 'removeImage']);
    Route::get('/ofertas/search', [OfertaController::class, 'search']);
    Route::post('/ofertas/{oferta}/toggle-status', [OfertaController::class, 'toggleStatus']);
    Route::apiResource('ofertas', OfertaController::class);

    // Categorias
    Route::get('/categorias/with-ofertas-count', [CategoriaController::class, 'withOfertasCount']);
    Route::get('/categorias/{categoria}/ofertas', [CategoriaController::class, 'ofertas']);
    Route::apiResource('categorias', CategoriaController::class);

    // Subcategorias
    Route::get('/categorias/{categoria}/sub-categorias', [SubcategoriaController::class, 'getByCategoria']);
    Route::get('/sub-categorias/{sub_categoria}/ofertas', [SubcategoriaController::class, 'ofertas']);
    Route::apiResource('sub-categorias', SubcategoriaController::class);

    // Transações
    Route::post('/transacoes/{id}/estornar', [TransacaoController::class, 'estornar']);
    Route::post('/transacoes/{id}/avaliar', [TransacaoController::class, 'avaliar']);
    Route::apiResource('transacoes', TransacaoController::class);

    // Vouchers
    Route::get('/transacoes/{transacao}/vouchers', [VoucherController::class, 'getByTransacao']);
    Route::post('/vouchers/validar', [VoucherController::class, 'validar']);
    Route::post('/vouchers/utilizar', [VoucherController::class, 'utilizar']);
    Route::apiResource('vouchers', VoucherController::class);

    // Cobrancas
    Route::post('/cobrancas/gerar-transacao', [CobrancaController::class, 'gerarCobrancaTransacao']);
    Route::put('/cobrancas/{id}/status', [CobrancaController::class, 'atualizarStatus']);
    Route::post('/cobrancas/gerar-mensais', [CobrancaController::class, 'gerarCobrancasMensais']);
    Route::get('/cobrancas/vencidas', [CobrancaController::class, 'cobrancasVencidas']);
    Route::apiResource('cobrancas', CobrancaController::class);

    // Solicitações de Crédito
    Route::put('/solicitacoes-credito/{id}/aprovar', [SolicitacaoCreditoController::class, 'aprovar']);
    Route::put('/solicitacoes-credito/{id}/rejeitar', [SolicitacaoCreditoController::class, 'rejeitar']);
    Route::get('/solicitacoes-credito/matriz', [SolicitacaoCreditoController::class, 'solicitacoesMatriz']);
    Route::put('/solicitacoes-credito/{id}/resposta-matriz', [SolicitacaoCreditoController::class, 'respostaMatriz']);
    Route::apiResource('solicitacoes-credito', SolicitacaoCreditoController::class);

    // Fundo Permuta
    Route::get('/fundo-permuta/usuario/{usuario_id}', [FundoPermutaController::class, 'saldoUsuario']);
    Route::post('/fundo-permuta/transferir', [FundoPermutaController::class, 'transferir']);
    Route::get('/fundo-permuta/usuario/{usuario_id}/movimentacoes', [FundoPermutaController::class, 'movimentacoes']);
    Route::apiResource('fundo-permuta', FundoPermutaController::class);
});
