# Como o HTML, CSS, JS e PHP estão integrados

Este projeto não separa as telas em arquivos `.html` soltos, porque o sistema precisa de PHP para autenticação, sessão, CSRF e dados vindos do banco.

Por isso, o HTML fica dentro das **Views PHP**:

- `app/Views/layout.html`: estrutura HTML principal (`<!doctype html>`, `<head>`, menu, footer e importação do CSS/JS).
- `app/Views/home.html`: HTML da tela inicial.
- `app/Views/auth/cadastro.html`: HTML do cadastro.
- `app/Views/auth/login.html`: HTML do login.
- `app/Views/chamados/index.html`: HTML da listagem/CRUD de chamados.
- `app/Views/chamados/form.html`: HTML dos formulários de criar/editar chamado.

Os assets ficam separados:

- `public/assets/css/style.css`: todo o visual responsivo do sistema.
- `public/assets/js/app.js`: validação de senha, confirmação de remoção, filtro da tabela, contador de caracteres, menu mobile e mensagens.
- `public/assets/img/helpit-logo.svg`: logo do sistema.

O fluxo fica assim:

1. O usuário acessa uma rota, por exemplo `/cadastro`.
2. `public/index.html` direciona para o Controller correto.
3. O Controller chama uma View PHP.
4. A View gera HTML e usa CSS/JS do diretório `public/assets`.
5. Quando o formulário é enviado, o PHP valida e grava no MariaDB.


## Observação sobre HTML funcional

As telas do sistema estão em arquivos `.html` dentro de `app/Views`. Elas possuem marcações HTML normais e pequenos trechos PHP para preencher dados dinâmicos, exibir mensagens, inserir token CSRF e renderizar listas do banco. O ponto de entrada continua sendo `public/index.php`, pois login, cadastro e CRUD precisam passar pelo backend PHP e pelo banco MariaDB.
