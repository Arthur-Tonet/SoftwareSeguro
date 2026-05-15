<?php

/**
 * Escapa valor para saída HTML segura (previne XSS).
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Retorna o base path da aplicação (útil quando rodada em subdiretório).
 */
function base_path(): string
{
    $base = getenv('APP_BASE_PATH') ?: '';
    return rtrim($base, '/');
}

/**
 * Gera URL absoluta para uma rota interna.
 */
function url(string $rota = '/'): string
{
    $rota = '/' . ltrim($rota, '/');
    return base_path() . $rota;
}

/**
 * Gera URL absoluta para um asset público (CSS, JS, imagens).
 */
function asset(string $caminho): string
{
    $caminho = '/' . ltrim($caminho, '/');
    return base_path() . $caminho;
}

/**
 * Redireciona para uma rota interna e encerra a execução.
 */
function redirect(string $rota): never
{
    header('Location: ' . url($rota));
    exit;
}

/**
 * Recupera valor antigo de formulário (para repopular campos após erro).
 * Retorna $padrao se o campo não existir ou a sessão não tiver dados antigos.
 */
function old(string $campo, string $padrao = ''): string
{
    return isset($_SESSION['old'][$campo]) ? (string) $_SESSION['old'][$campo] : $padrao;
}

/**
 * Armazena uma mensagem flash na sessão para exibição na próxima requisição.
 * $tipo deve ser 'sucesso' ou 'erro'.
 */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][$tipo][] = $mensagem;
}

/**
 * Retorna e remove todas as mensagens flash da sessão.
 */
function consumeFlash(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensagens;
}
