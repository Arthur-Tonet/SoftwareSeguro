<?php
namespace App\Models;

final class Chamado
{
    public function __construct(
        public ?int $id,
        public int $usuarioId,
        public string $titulo,
        public string $descricao,
        public string $prioridade = 'Media',
        public string $status = 'Aberto'
    ) {}
}
