<?php
require __DIR__ . '/../config/env.php';

if (PHP_SAPI === 'cli-server') {
    $caminhoArquivo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($caminhoArquivo)) return false;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

require __DIR__ . '/../app/Core/helpers.php';

spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    $base     = __DIR__ . '/../app/';
    if (strncmp($prefixo, $classe, strlen($prefixo)) !== 0) return;
    $relativo = substr($classe, strlen($prefixo));
    $arquivo  = $base . str_replace('\\', '/', $relativo) . '.php';
    if (file_exists($arquivo)) require $arquivo;
});

use App\Controllers\AuthController;
use App\Controllers\ChamadoController;
use App\Controllers\CryptoController;
use App\Controllers\DiagnosticController;
use App\Controllers\HomeController;
use App\Controllers\AdminController;

$basePath    = rtrim(getenv('APP_BASE_PATH') ?: '', '/');
$uriCompleta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($basePath !== '' && str_starts_with($uriCompleta, $basePath)) {
    $rota = substr($uriCompleta, strlen($basePath)) ?: '/';
} else {
    $rota = $uriCompleta;
}
if ($rota === '' || $rota[0] !== '/') $rota = '/' . $rota;

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    match (true) {
        $rota === '/'                    && $metodo === 'GET'  => (new HomeController())->index(),
        $rota === '/login'               && $metodo === 'GET'  => (new AuthController())->loginForm(),
        $rota === '/login'               && $metodo === 'POST' => (new AuthController())->login(),
        $rota === '/cadastro'            && $metodo === 'GET'  => (new AuthController())->cadastroForm(),
        $rota === '/cadastro'            && $metodo === 'POST' => (new AuthController())->cadastrar(),
        $rota === '/logout'              && $metodo === 'POST' => (new AuthController())->logout(),
        $rota === '/diagnostico'         && $metodo === 'GET'  => (new DiagnosticController())->index(),
        $rota === '/chamados'            && $metodo === 'GET'  => (new ChamadoController())->index(),
        $rota === '/chamados/novo'       && $metodo === 'GET'  => (new ChamadoController())->criarForm(),
        $rota === '/chamados/criar'      && $metodo === 'POST' => (new ChamadoController())->criar(),
        $rota === '/chamados/editar'     && $metodo === 'GET'  => (new ChamadoController())->editarForm((int)($_GET['id'] ?? 0)),
        $rota === '/chamados/editar'     && $metodo === 'POST' => (new ChamadoController())->atualizar((int)($_GET['id'] ?? 0)),
        $rota === '/chamados/remover'    && $metodo === 'POST' => (new ChamadoController())->remover((int)($_POST['id'] ?? 0)),
        // ── Admin ────────────────────────────────────────────────────────────
        $rota === '/admin'               && $metodo === 'GET'  => (new AdminController())->index(),
        $rota === '/admin/chamados'      && $metodo === 'GET'  => (new AdminController())->chamadosEmpresa(),
        $rota === '/admin/status'        && $metodo === 'POST' => (new AdminController())->atualizarStatus(),
        // ── Criptografia (AES-256-GCM) ───────────────────────────────────────
        $rota === '/crypto'              && $metodo === 'GET'  => (new CryptoController())->index(),
        $rota === '/crypto/processar'    && $metodo === 'POST' => (new CryptoController())->processar(),
        default => (static function (): void {
            http_response_code(404);
            echo '<h1 style="font-family:sans-serif;color:#f8fafc;background:#0c1020;margin:0;padding:40px">404 — Página não encontrada.</h1>';
        })(),
    };
} catch (Throwable $erro) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === 'true';
    $msg   = $debug ? htmlspecialchars($erro->getMessage(), ENT_QUOTES, 'UTF-8') : 'Tente novamente mais tarde.';
    echo '<h1 style="font-family:sans-serif;color:#f8fafc;background:#0c1020;margin:0;padding:40px">Erro interno</h1>'
       . '<p style="font-family:sans-serif;color:#b9c0d4;padding:0 40px">' . $msg . '</p>';
}
