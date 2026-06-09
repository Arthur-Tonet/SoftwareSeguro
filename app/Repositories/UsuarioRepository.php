<?php
// #4 implementa bloqueio de brute-force por email+IP - CWE-307 ASVS V2.2.1
namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UsuarioRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function empresaPorCodigo(string $codigo): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM empresas WHERE codigo_acesso = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([trim($codigo)]);
        return $stmt->fetch() ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, e.nome AS empresa_nome FROM usuarios u
             INNER JOIN empresas e ON e.id = u.empresa_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function criar(array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (empresa_id, nome, email, senha_hash) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['empresa_id'],
            trim($dados['nome']),
            mb_strtolower(trim($dados['email'])),
            $dados['senha_hash'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function registrarTentativa(string $email, string $ip, bool $sucesso): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO login_tentativas (email, ip, sucesso) VALUES (?, ?, ?)');
        $stmt->execute([mb_strtolower(trim($email)), $ip, $sucesso ? 1 : 0]);
    }

    /**
     * Conta tentativas inválidas recentes por email+IP combinados (CWE-307 / ASVS V2.2.1).
     * Limite: 5 tentativas em 15 minutos por par email+IP.
     */
    public function tentativasInvalidasRecentes(string $email, string $ip): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM login_tentativas
             WHERE email = ? AND ip = ? AND sucesso = 0
             AND criado_em >= (NOW() - INTERVAL 15 MINUTE)"
        );
        $stmt->execute([mb_strtolower(trim($email)), $ip]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Conta tentativas inválidas recentes apenas por email, independente do IP.
     * Protege contra ataques distribuídos (múltiplos IPs tentando o mesmo email).
     * Limite: 10 tentativas em 15 minutos por email.
     */
    public function tentativasInvalidasPorEmail(string $email): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM login_tentativas
             WHERE email = ? AND sucesso = 0
             AND criado_em >= (NOW() - INTERVAL 15 MINUTE)"
        );
        $stmt->execute([mb_strtolower(trim($email))]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Conta tentativas inválidas recentes apenas por IP, independente do email.
     * Protege contra ataques de credential stuffing (1 IP testando muitos emails).
     * Limite: 20 tentativas em 15 minutos por IP.
     */
    public function tentativasInvalidasPorIp(string $ip): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM login_tentativas
             WHERE ip = ? AND sucesso = 0
             AND criado_em >= (NOW() - INTERVAL 15 MINUTE)"
        );
        $stmt->execute([$ip]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Remove todas as tentativas inválidas do par email+IP após login bem-sucedido.
     * Evita que tentativas antigas bloqueiem usuário legítimo na próxima sessão.
     */
    public function limparTentativas(string $email, string $ip): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM login_tentativas WHERE email = ? AND ip = ? AND sucesso = 0'
        );
        $stmt->execute([mb_strtolower(trim($email)), $ip]);
    }

    /** Lista todas as empresas (exceto a interna do admin) com contagem de chamados */
    public function listarEmpresasComEstatisticas(): array
    {
        $stmt = $this->pdo->query(
            "SELECT e.id, e.nome, e.codigo_acesso,
                    COUNT(DISTINCT u.id) AS total_usuarios,
                    COUNT(DISTINCT c.id) AS total_chamados,
                    SUM(CASE WHEN c.status = 'Aberto' THEN 1 ELSE 0 END) AS chamados_abertos,
                    SUM(CASE WHEN c.status = 'Em andamento' THEN 1 ELSE 0 END) AS chamados_em_andamento
             FROM empresas e
             LEFT JOIN usuarios u ON u.empresa_id = e.id AND u.perfil = 'usuario'
             LEFT JOIN chamados c ON c.usuario_id = u.id
             WHERE e.codigo_acesso != 'ADMIN2026'
             GROUP BY e.id, e.nome, e.codigo_acesso
             ORDER BY chamados_abertos DESC, e.nome ASC"
        );
        return $stmt->fetchAll();
    }

    /** Busca empresa por id */
    public function empresaPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM empresas WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
