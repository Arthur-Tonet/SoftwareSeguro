<?php
namespace App\Models;

final class Usuario
{
    public function __construct(
        public ?int $id,
        public int $empresaId,
        public string $nome,
        public string $email,
        public string $senhaHash,
        public string $perfil = 'usuario',
        public bool $ativo = true
    ) {}
}
