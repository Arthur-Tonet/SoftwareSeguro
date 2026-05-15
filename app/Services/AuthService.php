<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;

final class AuthService
{
    private UsuarioRepository $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioRepository();
    }

    public function cadastrar(array $dados): array
    {
        $erros = [];
        $nome           = trim($dados['nome'] ?? '');
        $email          = mb_strtolower(trim($dados['email'] ?? ''));
        $senha          = $dados['senha'] ?? '';
        $confirmarSenha = $dados['confirmar_senha'] ?? '';
        $codigoEmpresa  = trim($dados['codigo_empresa'] ?? '');

        if (!SecurityService::validarNome($nome))         $erros[] = 'Informe um nome válido com pelo menos 3 caracteres.';
        if (!SecurityService::validarEmail($email))       $erros[] = 'Informe um e-mail válido.';
        if (!SecurityService::validarSenhaForte($senha))  $erros[] = 'A senha deve ter no mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.';
        if ($senha !== $confirmarSenha)                   $erros[] = 'A confirmação de senha não confere.';

        $empresa = $this->usuarios->empresaPorCodigo($codigoEmpresa);
        if (!$empresa)                                    $erros[] = 'Código da empresa inválido ou inativo.';
        if ($this->usuarios->buscarPorEmail($email))      $erros[] = 'Já existe uma conta com este e-mail.';

        if ($erros) return [false, $erros];

        $usuarioId = $this->usuarios->criar([
            'empresa_id' => (int) $empresa['id'],
            'nome'       => $nome,
            'email'      => $email,
            'senha_hash' => SecurityService::senhaHash($senha),
        ]);
        AuditService::registrar($usuarioId, 'CADASTRO_USUARIO', 'Novo usuário cadastrado');

        return [true, []];
    }

    public function login(string $email, string $senha): array
    {
        $email = mb_strtolower(trim($email));
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($this->usuarios->tentativasInvalidasRecentes($email, $ip) >= 5) {
            return [false, 'Muitas tentativas inválidas. Aguarde 15 minutos e tente novamente.'];
        }

        $usuario = $this->usuarios->buscarPorEmail($email);
        if (!$usuario || !$usuario['ativo'] || !SecurityService::verificarSenha($senha, $usuario['senha_hash'])) {
            $this->usuarios->registrarTentativa($email, $ip, false);
            return [false, 'E-mail ou senha inválidos.'];
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'           => (int) $usuario['id'],
            'nome'         => $usuario['nome'],
            'email'        => $usuario['email'],
            'perfil'       => $usuario['perfil'],
            'empresa_nome' => $usuario['empresa_nome'],
        ];
        $this->usuarios->registrarTentativa($email, $ip, true);
        AuditService::registrar((int) $usuario['id'], 'LOGIN_SUCESSO', 'Usuário autenticado');
        return [true, 'Login realizado com sucesso.'];
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['usuario']['id']) ? (int) $_SESSION['usuario']['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['usuario']['perfil'] ?? '') === 'admin';
    }

    public static function exigirLogin(): void
    {
        if (!self::usuario()) {
            flash('erro', 'Faça login para acessar esta área.');
            redirect('/login');
        }
    }

    public static function exigirAdmin(): void
    {
        self::exigirLogin();
        if (!self::isAdmin()) {
            flash('erro', 'Acesso restrito ao administrador.');
            redirect('/chamados');
        }
    }

    public static function logout(): void
    {
        if (self::id()) {
            AuditService::registrar(self::id(), 'LOGOUT', 'Sessão encerrada');
        }
        unset($_SESSION['usuario'], $_SESSION['csrf_token']);
        session_regenerate_id(true);
    }
}
