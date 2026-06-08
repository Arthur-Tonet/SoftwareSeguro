# HelpIT RA2 — Sistema de Chamados de TI

Sistema web de chamados de suporte com **PHP 8.3 + MariaDB 11 + MVC + HTML + CSS + JavaScript**.

## Tecnologias e segurança

- PHP 8.3 com PDO e prepared statements (sem SQL Injection)
- Hash de senha com `password_hash` / `password_verify`
- Proteção CSRF em todos os formulários
- Bloqueio temporário após 5 tentativas inválidas de login
- Auditoria de ações importantes
- Headers de segurança via `.htaccess`
- Sessão com `httponly`, `samesite=Strict` e regeneração de ID

## Estrutura

```
app/
  Controllers/     Fluxo das telas e ações
  Core/            Base MVC (Controller, Database, helpers)
  Models/          Entidades do domínio
  Repositories/    Acesso ao banco via PDO (DAO)
  Services/        Auth, CSRF, segurança e auditoria
  Views/           HTML das telas (renderizadas pelo PHP)
config/
  env.php          Carregador do .env (somente fora do Docker)
database/
  schema.sql       Criação das tabelas e empresas de teste
  reset.sql        Script para apagar as tabelas
public/
  index.php        Ponto de entrada único
  assets/css/      Estilos
  assets/js/       JavaScript
  assets/img/      Logo SVG
storage/
  logs/            Logs da aplicação (gravável pelo servidor)
```

## Como rodar com Docker (recomendado)

Pré-requisito: **Docker Desktop** instalado.

```bash
# Na pasta do projeto:
docker compose up -d --build
```

Acesse em: http://localhost:8080

### Tela de diagnóstico

Confirme que o banco conectou em: http://localhost:8080/diagnostico

## Como rodar sem Docker (servidor embutido do PHP)

Pré-requisito: PHP 8.3+ com extensão `pdo_mysql` e MariaDB/MySQL local.

1. Copie e ajuste o arquivo de ambiente:

```bash
cp .env.example .env
# Edite .env: troque DB_HOST=db por DB_HOST=127.0.0.1 e DB_PORT=3307
```

2. Importe o schema no seu banco:

```bash
mysql -u helpit_user -p helpit < database/schema.sql
```

3. Suba o servidor embutido:

```bash
php -S localhost:8080 -t public public/index.php
```

Acesse em: http://localhost:8080

## Códigos de empresa para cadastro

| Código      | Empresa        |
|-------------|----------------|
| ALPHA2026   | Empresa Alpha  |
| BETA2026    | Empresa Beta   |
| SIGMA2026   | Empresa Sigma  |
| DELTA2026   | Empresa Delta  |

## Resetar o banco do zero

```bash
docker compose down -v
docker compose up -d --build
```

O `-v` apaga o volume do MariaDB e recria as tabelas pelo `schema.sql`.

## Onde estão o HTML, CSS e JS

- **HTML**: `app/Views/` (arquivos `.html` com pequenos trechos PHP)
- **CSS**: `public/assets/css/style.css` — conectado em `app/Views/layout.html`
- **JS**: `public/assets/js/app.js` — conectado em `app/Views/layout.html`

Como todas as telas passam pelo `layout.html`, o CSS e JS funcionam em todas as páginas.

## Testes unitários (PHPUnit)

Pré-requisito: [Composer](https://getcomposer.org/) instalado.

```bash
composer install
composer test
```

Os testes cobrem:
- `CryptoServiceTest` — AES-256-GCM (cifrar, decifrar, IV aleatório, adulteração, Unicode)
- `CsrfServiceTest` — geração e validação do token CSRF com `hash_equals`
- `SecurityServiceTest` — validação de senha forte, email, nome e hash bcrypt/Argon2

## Módulo de Criptografia AES-256-GCM

Acesse `/crypto` após login para demonstrar o módulo interativamente.

- Algoritmo: AES-256-GCM (autenticado — confidencialidade + integridade)
- IV aleatório de 96 bits por operação (nunca reutilizado)
- Chave derivada via HKDF-SHA256 a partir da `APP_KEY` do `.env`
- Implementado em `app/Services/CryptoService.php`

## CI — GitHub Actions

O workflow `.github/workflows/ci.yml` executa os testes automaticamente em cada push/PR para `main` e `develop`, nas versões PHP 8.2 e 8.3.
