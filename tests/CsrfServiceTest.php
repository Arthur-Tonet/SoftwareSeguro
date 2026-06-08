<?php
namespace Tests;

use App\Services\CsrfService;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para CsrfService
 * Cobre: Req. C1 — Token CSRF com proteção a timing attacks
 */
class CsrfServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Simula sessão para os testes
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['csrf_token']);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['csrf_token']);
    }

    public function test_token_gera_string_nao_vazia(): void
    {
        $token = CsrfService::token();
        $this->assertNotEmpty($token);
    }

    public function test_token_tem_64_caracteres_hex(): void
    {
        $token = CsrfService::token();
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_token_e_consistente_na_mesma_sessao(): void
    {
        $t1 = CsrfService::token();
        $t2 = CsrfService::token();
        $this->assertSame($t1, $t2, 'Token deve ser o mesmo dentro da mesma sessão');
    }

    public function test_validate_aceita_token_correto(): void
    {
        $token = CsrfService::token();
        $this->assertTrue(CsrfService::validate($token));
    }

    public function test_validate_rejeita_token_errado(): void
    {
        CsrfService::token();
        $this->assertFalse(CsrfService::validate('token_errado_qualquer'));
    }

    public function test_validate_rejeita_null(): void
    {
        CsrfService::token();
        $this->assertFalse(CsrfService::validate(null));
    }

    public function test_validate_rejeita_string_vazia(): void
    {
        CsrfService::token();
        $this->assertFalse(CsrfService::validate(''));
    }

    public function test_validate_rejeita_token_quase_correto(): void
    {
        $token = CsrfService::token();
        // Altera último caractere — simula ataque de timing
        $tokenAlterado = substr($token, 0, -1) . ($token[-1] === 'a' ? 'b' : 'a');
        $this->assertFalse(CsrfService::validate($tokenAlterado));
    }
}
