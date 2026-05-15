<?php
namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $conexao = null;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$conexao === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $porta = getenv('DB_PORT') ?: '3306';
            $banco = getenv('DB_DATABASE') ?: 'helpit';
            $usuario = getenv('DB_USERNAME') ?: 'root';
            $senha = getenv('DB_PASSWORD') ?: '';

            $dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

            try {
                self::$conexao = new PDO($dsn, $usuario, $senha, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $erro) {
                throw new PDOException('Erro ao conectar ao banco de dados. Verifique o Docker/MariaDB e o arquivo .env.');
            }
        }

        return self::$conexao;
    }
}
