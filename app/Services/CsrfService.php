<?php
// #5 implementa proteção CSRF token único por sessão - CWE-352 OWASP ASVS V4.2
namespace App\Services;

final class CsrfService
{
    /**
     * Retorna (ou gera) o token CSRF da sessão atual.
     * O token é gerado com random_bytes(32) para garantir 256 bits de entropia,
     * conforme recomendado pelo OWASP ASVS V4.2 (ASVS 4.2.2 / CWE-352).
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida o token CSRF enviado no formulário e o rotaciona após uso.
     *
     * - Rejeita imediatamente tokens nulos, vazios ou não-string.
     * - Usa hash_equals() para comparação em tempo constante,
     *   prevenindo timing attacks (CWE-208).
     * - Rotaciona o token após validação bem-sucedida, garantindo
     *   que cada token seja válido para apenas uma requisição
     *   (OWASP ASVS V4.2.2 / CWE-352).
     */
    public static function validate(?string $token): bool
    {
        // Rejeita imediatamente se o token enviado for inválido
        if (!is_string($token) || $token === '') {
            return false;
        }

        $tokenSessao = self::token();

        $valido = hash_equals($tokenSessao, $token);

        // Rotaciona o token após cada uso (válido ou não) para
        // impedir reutilização em ataques de replay
        self::regenerar();

        return $valido;
    }

    /**
     * Gera um novo token CSRF e o armazena na sessão,
     * invalidando o anterior imediatamente.
     */
    public static function regenerar(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}