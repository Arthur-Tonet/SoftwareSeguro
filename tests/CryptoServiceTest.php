<?php
namespace Tests;

use App\Services\CryptoService;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para CryptoService (AES-256-GCM)
 * Cobre: Req. 5 — Módulo de criptografia; Req. 6 — Uso técnico correto
 */
class CryptoServiceTest extends TestCase
{
    private string $chave = 'chave-de-teste-para-phpunit-2026';

    public function test_encrypt_retorna_string_nao_vazia(): void
    {
        $resultado = CryptoService::encrypt('texto de teste', $this->chave);
        $this->assertNotEmpty($resultado);
    }

    public function test_encrypt_retorna_formato_iv_tag_ciphertext(): void
    {
        $resultado = CryptoService::encrypt('texto de teste', $this->chave);
        $partes = explode(':', $resultado);
        $this->assertCount(3, $partes, 'Payload deve ter formato iv:tag:ciphertext');
    }

    public function test_decrypt_recupera_texto_original(): void
    {
        $textoOriginal = 'Mensagem confidencial do chamado #42';
        $cifrado = CryptoService::encrypt($textoOriginal, $this->chave);
        $decifrado = CryptoService::decrypt($cifrado, $this->chave);
        $this->assertSame($textoOriginal, $decifrado);
    }

    public function test_encrypt_gera_ciphertexts_diferentes_para_mesmo_texto(): void
    {
        $texto = 'mesmo texto';
        $c1 = CryptoService::encrypt($texto, $this->chave);
        $c2 = CryptoService::encrypt($texto, $this->chave);
        $this->assertNotSame($c1, $c2, 'IV aleatório deve gerar ciphertexts diferentes');
    }

    public function test_decrypt_falha_com_chave_errada(): void
    {
        $cifrado = CryptoService::encrypt('texto secreto', $this->chave);
        $resultado = CryptoService::decrypt($cifrado, 'chave-errada-completamente-diferente');
        $this->assertNull($resultado, 'Descriptografia com chave errada deve retornar null');
    }

    public function test_decrypt_falha_com_payload_adulterado(): void
    {
        $cifrado = CryptoService::encrypt('texto original', $this->chave);
        $adulterado = $cifrado . 'XXXXXX';
        $resultado = CryptoService::decrypt($adulterado, $this->chave);
        $this->assertNull($resultado, 'Payload adulterado deve retornar null (GCM detecta adulteração)');
    }

    public function test_decrypt_falha_com_payload_invalido(): void
    {
        $resultado = CryptoService::decrypt('payload_invalido_sem_dois_pontos', $this->chave);
        $this->assertNull($resultado);
    }

    public function test_encrypt_funciona_com_texto_unicode(): void
    {
        $texto = 'Chamado: á é í ó ú ã õ ç — caracteres UTF-8';
        $cifrado = CryptoService::encrypt($texto, $this->chave);
        $decifrado = CryptoService::decrypt($cifrado, $this->chave);
        $this->assertSame($texto, $decifrado);
    }

    public function test_encrypt_funciona_com_texto_longo(): void
    {
        $texto = str_repeat('A', 5000);
        $cifrado = CryptoService::encrypt($texto, $this->chave);
        $decifrado = CryptoService::decrypt($cifrado, $this->chave);
        $this->assertSame($texto, $decifrado);
    }

    public function test_generate_key_retorna_64_caracteres_hex(): void
    {
        $chave = CryptoService::generateKey();
        $this->assertSame(64, strlen($chave));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $chave);
    }

    public function test_generate_key_gera_chaves_unicas(): void
    {
        $k1 = CryptoService::generateKey();
        $k2 = CryptoService::generateKey();
        $this->assertNotSame($k1, $k2);
    }
}
