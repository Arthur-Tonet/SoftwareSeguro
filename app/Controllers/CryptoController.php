<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\CryptoService;

/**
 * CryptoController — Demonstração do módulo AES-256-GCM (Req. 5/6/7 Criptografia)
 */
final class CryptoController extends Controller
{
    public function __construct()
    {
        AuthService::exigirLogin();
    }

    public function index(): void
    {
        $this->view('crypto/demo', [
            'titulo'     => 'Módulo de Criptografia AES-256-GCM',
            'resultado'  => null,
            'operacao'   => null,
        ]);
    }

    public function processar(): void
    {
        $operacao  = $_POST['operacao'] ?? '';
        $texto     = trim($_POST['texto'] ?? '');
        $resultado = null;
        $erro      = null;

        $key = getenv('APP_KEY') ?: '';

        if ($texto === '') {
            flash('erro', 'Informe um texto para processar.');
            redirect('/crypto');
        }

        try {
            if ($operacao === 'encrypt') {
                $resultado = CryptoService::encrypt($texto, $key);
                flash('sucesso', 'Texto criptografado com AES-256-GCM.');
            } elseif ($operacao === 'decrypt') {
                $resultado = CryptoService::decrypt($texto, $key);
                if ($resultado === null) {
                    flash('erro', 'Falha na descriptografia — payload inválido ou chave incorreta.');
                } else {
                    flash('sucesso', 'Texto descriptografado com sucesso.');
                }
            } else {
                flash('erro', 'Operação inválida.');
                redirect('/crypto');
            }
        } catch (\Throwable $e) {
            flash('erro', 'Erro interno na operação criptográfica.');
            redirect('/crypto');
        }

        $this->view('crypto/demo', [
            'titulo'    => 'Módulo de Criptografia AES-256-GCM',
            'resultado' => $resultado,
            'operacao'  => $operacao,
            'entrada'   => $texto,
        ]);
    }
}
