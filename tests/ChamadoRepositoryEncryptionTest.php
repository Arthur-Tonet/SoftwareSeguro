<?php
namespace Tests;

use App\Services\CryptoService;
use PHPUnit\Framework\TestCase;

/**
 * Testes de integração da criptografia no ChamadoRepository.
 * Testa a lógica de cifrar/decifrar sem precisar de banco de dados,
 * simulando exatamente o comportamento dos métodos privados cifrar() e decifrar().
 */
class ChamadoRepositoryEncryptionTest extends TestCase
{
    private string $chave = '4d6f7e8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';

    // ── Simula os métodos privados cifrar() e decifrar() do Repository ───────

    private function cifrar(string $texto): string
    {
        return 'ENC:' . CryptoService::encrypt($texto, $this->chave);
    }

    private function decifrar(string $valor): string
    {
        if (!str_starts_with($valor, 'ENC:')) {
            return $valor;
        }
        $payload = substr($valor, 4);
        return CryptoService::decrypt($payload, $this->chave) ?? $valor;
    }

    private function decifrarRegistro(?array $row): ?array
    {
        if ($row === null) return null;
        $row['descricao'] = $this->decifrar($row['descricao']);
        return $row;
    }

    private function decifrarLista(array $rows): array
    {
        return array_map([$this, 'decifrarRegistro'], $rows);
    }

    // ── Testes de cifrar ─────────────────────────────────────────────────────

    public function test_cifrar_adiciona_prefixo_ENC(): void
    {
        $resultado = $this->cifrar('descrição do chamado');
        $this->assertTrue(str_starts_with($resultado, 'ENC:'));
    }

    public function test_cifrar_nao_salva_texto_em_claro(): void
    {
        $texto = 'servidor caiu às 03:42';
        $resultado = $this->cifrar($texto);
        $this->assertStringNotContainsString($texto, $resultado);
    }

    public function test_cifrar_gera_payloads_diferentes_para_mesmo_texto(): void
    {
        $texto = 'mesmo problema';
        $c1 = $this->cifrar($texto);
        $c2 = $this->cifrar($texto);
        $this->assertNotSame($c1, $c2, 'IV aleatório deve gerar payloads distintos');
    }

    // ── Testes de decifrar ───────────────────────────────────────────────────

    public function test_decifrar_recupera_texto_original(): void
    {
        $texto = 'Problema crítico no servidor de produção';
        $cifrado = $this->cifrar($texto);
        $decifrado = $this->decifrar($cifrado);
        $this->assertSame($texto, $decifrado);
    }

    public function test_decifrar_texto_puro_retrocompatibilidade(): void
    {
        // Registros antigos (sem ENC:) devem passar sem alteração
        $textoAntigo = 'registro antigo sem criptografia';
        $resultado = $this->decifrar($textoAntigo);
        $this->assertSame($textoAntigo, $resultado);
    }

    public function test_decifrar_texto_com_caracteres_especiais(): void
    {
        $texto = 'Erro: á é í ó ú — servidor #42 (crítico) @ 03:42h';
        $cifrado = $this->cifrar($texto);
        $this->assertSame($texto, $this->decifrar($cifrado));
    }

    public function test_decifrar_texto_longo_5000_chars(): void
    {
        $texto = str_repeat('Descrição detalhada do problema. ', 150); // ~5000 chars
        $texto = mb_substr($texto, 0, 5000);
        $cifrado = $this->cifrar($texto);
        $this->assertSame($texto, $this->decifrar($cifrado));
    }

    // ── Testes de decifrarRegistro ───────────────────────────────────────────

    public function test_decifrar_registro_completo(): void
    {
        $descricao = 'Impressora não funciona no 3º andar';
        $registro = [
            'id'         => 1,
            'titulo'     => 'Impressora quebrada',
            'descricao'  => $this->cifrar($descricao),
            'prioridade' => 'Alta',
            'status'     => 'Aberto',
        ];
        $resultado = $this->decifrarRegistro($registro);
        $this->assertSame($descricao, $resultado['descricao']);
        $this->assertSame('Impressora quebrada', $resultado['titulo']);
        $this->assertSame(1, $resultado['id']);
    }

    public function test_decifrar_registro_nulo_retorna_nulo(): void
    {
        $this->assertNull($this->decifrarRegistro(null));
    }

    public function test_decifrar_registro_antigo_sem_prefixo(): void
    {
        // Retrocompatibilidade: registro sem ENC: não é alterado
        $registro = [
            'id'        => 5,
            'descricao' => 'texto antigo sem criptografia',
        ];
        $resultado = $this->decifrarRegistro($registro);
        $this->assertSame('texto antigo sem criptografia', $resultado['descricao']);
    }

    // ── Testes de decifrarLista ──────────────────────────────────────────────

    public function test_decifrar_lista_de_registros(): void
    {
        $d1 = 'Problema no computador A';
        $d2 = 'Problema no computador B';
        $lista = [
            ['id' => 1, 'descricao' => $this->cifrar($d1)],
            ['id' => 2, 'descricao' => $this->cifrar($d2)],
        ];
        $resultado = $this->decifrarLista($lista);
        $this->assertSame($d1, $resultado[0]['descricao']);
        $this->assertSame($d2, $resultado[1]['descricao']);
    }

    public function test_decifrar_lista_mista_novos_e_antigos(): void
    {
        // Mix de registros cifrados e antigos (texto puro)
        $descNova = 'chamado novo cifrado';
        $descAntiga = 'chamado antigo texto puro';
        $lista = [
            ['id' => 1, 'descricao' => $this->cifrar($descNova)],
            ['id' => 2, 'descricao' => $descAntiga],
        ];
        $resultado = $this->decifrarLista($lista);
        $this->assertSame($descNova, $resultado[0]['descricao']);
        $this->assertSame($descAntiga, $resultado[1]['descricao']);
    }

    public function test_decifrar_lista_vazia(): void
    {
        $this->assertSame([], $this->decifrarLista([]));
    }
}
