<?php
namespace App\Services;

use App\Core\Database;
use Throwable;

final class AuditService
{
    /**
     * Registra uma ação de auditoria.
     * Falhas são silenciadas para não interromper o fluxo principal da aplicação.
     */
    public static function registrar(?int $usuarioId, string $acao, ?string $detalhes = null): void
    {
        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO auditoria (usuario_id, acao, detalhes, ip) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $usuarioId,
                $acao,
                mb_substr($detalhes ?? '', 0, 255),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]);
        } catch (Throwable) {
            // Auditoria não deve derrubar a operação principal
        }
    }
}
