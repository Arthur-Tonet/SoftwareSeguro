<?php
namespace App\Services;

final class CsrfService
{
    /**
     * Retorna (ou gera) o token CSRF da sessão atual.
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida o token enviado no formulário de forma segura contra timing attacks.
     * Gera o token automaticamente se a sessão ainda não tiver um.
     */
    public static function validate(?string $token): bool
    {
        // Garante que a sessão tenha um token antes de comparar
        $tokenSessao = self::token();
        return is_string($token) && hash_equals($tokenSessao, $token);
    }
}
