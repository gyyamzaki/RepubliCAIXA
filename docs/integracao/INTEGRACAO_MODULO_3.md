# Integração do Módulo 3 — Caixa, Dashboard e Relatórios

## Regra adotada
Os arquivos autorais de execução do Módulo 3 foram copiados sem alteração. Os SHA-256 do pacote recebido e da versão integrada são idênticos.

## Arquivos adicionados sem nenhuma alteração
- app/Controllers/FinanceController.php
- app/Services/FinanceInput.php
- app/Services/FinanceService.php
- app/Views/finance/cash-table.php
- app/Views/finance/cash.php
- app/Views/finance/dashboard.php
- app/Views/finance/end.php
- app/Views/finance/error.php
- app/Views/finance/reports.php
- app/Views/finance/start.php
- public/assets/css/finance.css
- public/assets/js/finance.js

A documentação e evidências originais do Rodrigo também foram copiadas, sem alteração, para `docs/modulo3/`.

## Arquivos compartilhados alterados pela integração
### routes/web.php
1. Adicionado `use App\\Controllers\\FinanceController;`.
2. Adicionado `use App\\Middleware\\AuthMiddleware;` para preservar a revalidação de sessão da nossa base.
3. A rota `GET /dashboard` deixou de chamar o antigo `DashboardController` e agora:
   - chama `AuthMiddleware::requireLogin()`;
   - chama `(new FinanceController())->handle('dashboard')`.
4. Adicionadas:
   - GET /caixa -> `handle('cash')`;
   - POST /caixa -> `handle('store')`;
   - GET /relatorios -> `handle('reports')`;
   - GET /relatorios/exportar -> `handle('export')`.
5. Nas quatro rotas financeiras foi mantida a validação prévia `AuthMiddleware::requireLogin()` da nossa base.

O `FinanceController.php` do Rodrigo NÃO foi modificado.

### app/Views/partials/sidebar.php
1. O href do item Caixa mudou de `#` para `/caixa`.
2. Foi inserido um novo item `Relatórios` apontando para `/relatorios`.

Os links de Dashboard, Despesas, Moradores, Perfil e o formulário de logout/CSRF permaneceram como estavam na nossa base.

### app/Views/partials/header.php
Adicionado exatamente o carregamento condicional usado pelo Módulo 3:
```php
<?php if (!empty($financeStyles)): ?>
<link rel="stylesheet" href="/assets/css/finance.css">
<?php endif; ?>
```

### public/index.php
Adicionada uma única instrução:
```php
date_default_timezone_set('America/Sao_Paulo');
```
Nenhuma outra linha do arquivo foi alterada.

### config/database.example.php
Uma única linha foi alterada, conforme o pacote do Módulo 3:
De:
```php
'dbname' => 'republicaixa',
```
Para:
```php
'dbname' => getenv('REPUBLICAIXA_DB') ?: 'republicaixa',
```
Isso permite ao conjunto de testes do módulo usar banco isolado sem alterar o padrão `republicaixa`.

### .gitignore
Adicionada a linha `/.local/`, usada pelos testes locais do Módulo 3. As regras de comprovantes já haviam sido incluídas na etapa do Módulo 2.

## Decisões de conflito
### Landing pública x dashboard em `/`
O pacote original do Módulo 3 transformava `/` em dashboard. Isso NÃO foi aplicado, porque o projeto atual definiu:
- `/` = landing pública;
- `/dashboard` = área autenticada.

Nenhum arquivo autoral do Rodrigo foi modificado. Apenas conectamos o dashboard financeiro dele à rota `/dashboard`.

### DashboardController anterior
`app/Controllers/DashboardController.php` e `app/Views/dashboard.php` foram mantidos no projeto, mas a rota `/dashboard` agora usa `FinanceController`. Não foram apagados para evitar remoção desnecessária do trabalho anterior.

### Testes originais do Módulo 3
A suíte original NÃO foi incorporada ao ZIP final porque `tests/http.php` assume que `/` é uma rota privada de dashboard. Na arquitetura atual `/` é landing pública, logo o teste original teria falha conhecida mesmo com o módulo funcionando corretamente.

Nenhum teste do Rodrigo foi editado. Eles permanecem intactos no ZIP original entregue por ele. Se o grupo quiser incorporar a suíte ao repositório principal, será necessário adaptar apenas as expectativas de `/` para `/dashboard`; isso deve ser feito conscientemente e explicado ao Rodrigo.

## Requisito de ambiente
`FinanceInput.php` usa `mb_strlen()`. Portanto, a extensão PHP `mbstring` precisa estar habilitada. O código do Rodrigo não foi alterado para contornar isso.

## Banco de dados
Nenhuma migração foi necessária. `schema.sql` e `seed.sql` são byte a byte iguais nos três módulos.
