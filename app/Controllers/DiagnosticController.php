<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuthService;
use Throwable;

final class DiagnosticController extends Controller
{
    public function __construct()
    {
        AuthService::exigirLogin();
    }

    public function index(): void
    {
        $status = [
            'conexao' => false,
            'mensagem' => '',
            'tabelas' => [],
            'banco' => getenv('DB_DATABASE') ?: 'helpit',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
        ];

        try {
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1');
            $status['conexao'] = true;
            $status['mensagem'] = 'Conexão com MariaDB funcionando.';

            foreach (['empresas', 'usuarios', 'chamados', 'login_tentativas', 'auditoria'] as $tabela) {
                $stmt = $pdo->query('SELECT COUNT(*) AS total FROM ' . $tabela);
                $status['tabelas'][$tabela] = (int) ($stmt->fetch()['total'] ?? 0);
            }
        } catch (Throwable $erro) {
            $status['mensagem'] = $erro->getMessage();
        }

        $this->view('diagnostico', ['titulo' => 'Diagnóstico do sistema', 'status' => $status]);
    }
}
