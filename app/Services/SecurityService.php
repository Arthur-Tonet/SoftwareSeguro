<?php
namespace App\Services;

final class SecurityService
{
    public static function validarNome(string $nome): bool
    {
        return (bool) preg_match('/^[\p{L}\s\'\-]{3,120}$/u', trim($nome));
    }

    public static function validarEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 180;
    }

    public static function validarSenhaForte(string $senha): bool
    {
        return strlen($senha) >= 8
            && preg_match('/[A-Z]/', $senha)
            && preg_match('/[a-z]/', $senha)
            && preg_match('/[0-9]/', $senha)
            && preg_match('/[^A-Za-z0-9]/', $senha);
    }

    public static function validarTexto(string $texto, int $min, int $max): bool
    {
        $texto = trim($texto);
        return mb_strlen($texto) >= $min && mb_strlen($texto) <= $max;
    }

    /**
     * Gera hash bcrypt da senha com custo padrão do PHP.
     * Uso explícito de PASSWORD_BCRYPT garante o algoritmo independente
     * da versão do PHP, conforme CWE-916 / ASVS V2.1.
     */
    public static function senhaHash(string $senha): string
    {
        return password_hash($senha, PASSWORD_BCRYPT);
    }

    public static function verificarSenha(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }
}

#alteração rebase, mas dessa vez com conflito :)