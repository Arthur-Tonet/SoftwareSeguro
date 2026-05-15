<?php
namespace App\Core;

abstract class Controller
{
    /**
     * Renderiza uma view dentro do layout principal.
     *
     * @param string $view  Caminho relativo dentro de app/Views/ (sem extensão), ex: 'auth/login'
     * @param array  $dados Variáveis a serem extraídas e disponibilizadas na view e no layout
     */
    protected function view(string $view, array $dados = []): void
    {
        $arquivoView = __DIR__ . '/../Views/' . $view . '.html';

        if (!is_file($arquivoView)) {
            throw new \RuntimeException('View não encontrada: ' . $view);
        }

        // Extrai as variáveis para que o layout e a view as enxerguem
        extract($dados, EXTR_SKIP);

        // O layout faz `require $arquivoView` para incluir a view no lugar correto
        require __DIR__ . '/../Views/layout.html';
    }
}
