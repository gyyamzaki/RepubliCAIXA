# RepubliCAIXA

Base inicial compartilhada do projeto.

## Stack
- HTML
- CSS
- JavaScript
- PHP
- MySQL / MariaDB

## Configuração inicial
1. Crie um banco chamado `republicaixa`.
2. Execute `database/schema.sql`.
3. Execute `database/seed.sql`.
4. Copie `config/database.example.php` para `config/database.php`.
5. Ajuste usuário e senha do MySQL no arquivo local `config/database.php`.
6. Inicie o servidor PHP apontando para a pasta `public`.

```bash
php -S localhost:8000 -t public
```

Acesse `http://localhost:8000`.

## Regra do grupo
O arquivo `database/schema.sql` é a referência oficial da estrutura do banco.
Se alguém alterar uma tabela, deve atualizar esse arquivo antes do merge.

Não enviar `config/database.php` ao GitHub. Cada integrante pode ter credenciais locais diferentes.
