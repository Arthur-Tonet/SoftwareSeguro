<?php
namespace Tests;

use App\Services\SecurityService;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para SecurityService
 * Cobre: validação de senha forte, email, nome e hash — Req. C2 / segurança geral
 */
class SecurityServiceTest extends TestCase
{
    // ── Senha forte ─────────────────────────────────────────────────────────

    public function test_senha_forte_valida(): void
    {
        $this->assertTrue(SecurityService::validarSenhaForte('Senha@123'));
    }

    public function test_senha_sem_maiuscula_invalida(): void
    {
        $this->assertFalse(SecurityService::validarSenhaForte('senha@123'));
    }

    public function test_senha_sem_minuscula_invalida(): void
    {
        $this->assertFalse(SecurityService::validarSenhaForte('SENHA@123'));
    }

    public function test_senha_sem_numero_invalida(): void
    {
        $this->assertFalse(SecurityService::validarSenhaForte('Senha@abc'));
    }

    public function test_senha_sem_simbolo_invalida(): void
    {
        $this->assertFalse(SecurityService::validarSenhaForte('Senha1234'));
    }

    public function test_senha_curta_invalida(): void
    {
        $this->assertFalse(SecurityService::validarSenhaForte('S@1a'));
    }

    // ── Email ────────────────────────────────────────────────────────────────

    public function test_email_valido(): void
    {
        $this->assertTrue(SecurityService::validarEmail('usuario@empresa.com'));
    }

    public function test_email_invalido_sem_arroba(): void
    {
        $this->assertFalse(SecurityService::validarEmail('usuarioempresa.com'));
    }

    public function test_email_invalido_vazio(): void
    {
        $this->assertFalse(SecurityService::validarEmail(''));
    }

    public function test_email_muito_longo_invalido(): void
    {
        $email = str_repeat('a', 175) . '@b.com';
        $this->assertFalse(SecurityService::validarEmail($email));
    }

    // ── Nome ─────────────────────────────────────────────────────────────────

    public function test_nome_valido(): void
    {
        $this->assertTrue(SecurityService::validarNome('Arthur Tonet'));
    }

    public function test_nome_muito_curto_invalido(): void
    {
        $this->assertFalse(SecurityService::validarNome('Ab'));
    }

    public function test_nome_com_numeros_invalido(): void
    {
        $this->assertFalse(SecurityService::validarNome('Arthur123'));
    }

    // ── Hash de senha ────────────────────────────────────────────────────────

    public function test_hash_e_verificacao_corretos(): void
    {
        $senha = 'MinhaSenh@2026';
        $hash = SecurityService::senhaHash($senha);
        $this->assertTrue(SecurityService::verificarSenha($senha, $hash));
    }

    public function test_senha_errada_nao_passa_verificacao(): void
    {
        $hash = SecurityService::senhaHash('SenhaCorreta@1');
        $this->assertFalse(SecurityService::verificarSenha('SenhaErrada@2', $hash));
    }

    public function test_hash_diferente_a_cada_chamada(): void
    {
        $senha = 'Senha@123';
        $h1 = SecurityService::senhaHash($senha);
        $h2 = SecurityService::senhaHash($senha);
        $this->assertNotSame($h1, $h2, 'bcrypt/Argon2 usa salt aleatório — hashes devem ser diferentes');
    }

    // ── Validar texto ────────────────────────────────────────────────────────

    public function test_texto_dentro_dos_limites_valido(): void
    {
        $this->assertTrue(SecurityService::validarTexto('Olá mundo', 5, 100));
    }

    public function test_texto_abaixo_do_minimo_invalido(): void
    {
        $this->assertFalse(SecurityService::validarTexto('abc', 5, 100));
    }

    public function test_texto_acima_do_maximo_invalido(): void
    {
        $this->assertFalse(SecurityService::validarTexto(str_repeat('a', 101), 5, 100));
    }
}
