<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ChamadoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    // ── Usuário comum ────────────────────────────────────────────────────────

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM chamados WHERE usuario_id = ? ORDER BY atualizado_em DESC'
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function buscarDoUsuario(int $id, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM chamados WHERE id = ? AND usuario_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chamados (usuario_id, titulo, descricao, prioridade, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$usuarioId, $dados['titulo'], $dados['descricao'], $dados['prioridade'], $dados['status']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE chamados SET titulo = ?, descricao = ?, prioridade = ?, status = ? WHERE id = ? AND usuario_id = ?'
        );
        return $stmt->execute([$dados['titulo'], $dados['descricao'], $dados['prioridade'], $dados['status'], $id, $usuarioId]);
    }

    public function remover(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM chamados WHERE id = ? AND usuario_id = ?');
        return $stmt->execute([$id, $usuarioId]);
    }

    // ── Admin ────────────────────────────────────────────────────────────────

    /** Lista todos os chamados de uma empresa com dados do usuário dono */
    public function listarPorEmpresa(int $empresaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email
             FROM chamados c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE u.empresa_id = ?
             ORDER BY c.atualizado_em DESC'
        );
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    /** Busca qualquer chamado pelo id (sem restrição de usuário — só admin usa) */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nome AS usuario_nome, u.email AS usuario_email, e.nome AS empresa_nome
             FROM chamados c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             INNER JOIN empresas e ON e.id = u.empresa_id
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Atualiza só o status de um chamado (ação do admin) */
    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE chamados SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }
}
