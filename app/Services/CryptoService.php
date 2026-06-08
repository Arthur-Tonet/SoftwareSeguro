<?php
namespace App\Services;

/**
 * Módulo de criptografia simétrica AES-256-GCM.
 *
 * Usado para proteger dados sensíveis em repouso (ex: detalhes de chamados
 * marcados como confidenciais) ou em trânsito interno entre serviços.
 *
 * Referência: OWASP Cryptographic Storage Cheat Sheet
 * Algoritmo: AES-256-GCM (autenticado — garante confidencialidade + integridade)
 */
final class CryptoService
{
    private const CIPHER    = 'aes-256-gcm';
    private const IV_LENGTH = 12;  // 96 bits — recomendado para GCM
    private const TAG_LENGTH = 16; // 128 bits

    /**
     * Criptografa um texto usando AES-256-GCM.
     *
     * Retorna string no formato base64: iv:tag:ciphertext
     * A chave deve ter exatamente 32 bytes (256 bits).
     */
    public static function encrypt(string $plaintext, string $key): string
    {
        $key = self::deriveKey($key);
        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha na criptografia AES-256-GCM.');
        }

        return base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ciphertext);
    }

    /**
     * Descriptografa um texto cifrado gerado por encrypt().
     * Retorna null se o payload for inválido ou a autenticação falhar.
     */
    public static function decrypt(string $payload, string $key): ?string
    {
        $parts = explode(':', $payload, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [$ivB64, $tagB64, $ciphertextB64] = $parts;

        $iv         = base64_decode($ivB64, true);
        $tag        = base64_decode($tagB64, true);
        $ciphertext = base64_decode($ciphertextB64, true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            return null;
        }

        $key      = self::deriveKey($key);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plaintext === false ? null : $plaintext;
    }

    /**
     * Gera uma chave de 256 bits a partir de uma string arbitrária usando HKDF-SHA256.
     * Garante que a chave tenha sempre o tamanho correto independente da entrada.
     */
    private static function deriveKey(string $key): string
    {
        return hash_hkdf('sha256', $key, 32, 'HelpIT-AES-256-GCM-v1');
    }

    /**
     * Gera uma chave aleatória segura de 256 bits codificada em hex.
     * Útil para gerar a APP_KEY no .env.
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
