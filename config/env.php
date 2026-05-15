<?php
/**
 * Carrega o arquivo .env da raiz do projeto para o ambiente PHP.
 * Necessário ao rodar com o servidor embutido (php -S), pois o
 * Docker Compose injeta as variáveis automaticamente.
 *
 * Uso no index.php: require __DIR__ . '/../config/env.php';
 */

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    return; // Rodando no Docker — variáveis já vêm do docker-compose.yml
}

$linhas = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($linhas as $linha) {
    $linha = trim($linha);
    // Ignora comentários
    if ($linha === '' || str_starts_with($linha, '#')) {
        continue;
    }
    // Separa chave=valor (apenas na primeira ocorrência de '=')
    $partes = explode('=', $linha, 2);
    if (count($partes) !== 2) {
        continue;
    }
    [$chave, $valor] = $partes;
    $chave = trim($chave);
    $valor = trim($valor, " \t\"'"); // remove aspas opcionais
    if ($chave !== '' && getenv($chave) === false) {
        putenv("{$chave}={$valor}");
        $_ENV[$chave] = $valor;
        $_SERVER[$chave] = $valor;
    }
}
