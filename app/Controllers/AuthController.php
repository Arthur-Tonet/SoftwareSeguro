<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\CsrfService;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth/login', ['titulo' => 'Entrar']);
    }

    public function login(): void
    {
        if (!CsrfService::validate($_POST['csrf_token'] ?? null)) {
            flash('erro', 'Token de segurança inválido. Atualize a página e tente novamente.');
            redirect('/login');
        }

        $auth = new AuthService();
        [$ok, $mensagem] = $auth->login($_POST['email'] ?? '', $_POST['senha'] ?? '');
        if (!$ok) {
            flash('erro', $mensagem);
            $_SESSION['old'] = ['email' => $_POST['email'] ?? ''];
            redirect('/login');
        }
        flash('sucesso', $mensagem);
        redirect('/chamados');
    }

    public function cadastroForm(): void
    {
        $this->view('auth/cadastro', ['titulo' => 'Criar conta']);
    }

    public function cadastrar(): void
    {
        if (!CsrfService::validate($_POST['csrf_token'] ?? null)) {
            flash('erro', 'Token de segurança inválido. Atualize a página e tente novamente.');
            redirect('/cadastro');
        }

        $auth = new AuthService();
        [$ok, $erros] = $auth->cadastrar($_POST);
        if (!$ok) {
            foreach ($erros as $erro) {
                flash('erro', $erro);
            }
            $_SESSION['old'] = [
                'nome' => $_POST['nome'] ?? '',
                'email' => $_POST['email'] ?? '',
                'codigo_empresa' => $_POST['codigo_empresa'] ?? '',
            ];
            redirect('/cadastro');
        }
        flash('sucesso', 'Conta criada com sucesso. Agora faça login.');
        redirect('/login');
    }

    public function logout(): void
    {
        if (!CsrfService::validate($_POST['csrf_token'] ?? null)) {
            flash('erro', 'Token de segurança inválido.');
            redirect('/chamados');
        }

        AuthService::logout();
        flash('sucesso', 'Você saiu do sistema.');
        redirect('/');
    }
}
