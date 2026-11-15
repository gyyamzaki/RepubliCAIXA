# RepubliCAIXA

Plataforma web para **gestão financeira e organização de repúblicas e moradias estudantis**.

O RepubliCAIXA centraliza usuários, moradores, despesas compartilhadas, rateios, pagamentos, fundo de reserva, dashboard financeiro e relatórios em uma única aplicação responsiva.

## Objetivo

O projeto foi desenvolvido para reduzir problemas comuns em moradias compartilhadas, como:

- dificuldade para saber quem deve o quê;
- divisão manual de despesas;
- falta de histórico dos pagamentos;
- pouca transparência no uso do dinheiro coletivo;
- dificuldade para acompanhar vencimentos e pendências;
- ausência de uma visão geral da situação financeira da república.

A aplicação foi pensada principalmente para estudantes que vivem em repúblicas, pensionatos ou apartamentos compartilhados.

---

## Tecnologias

- **Frontend:** HTML, CSS e JavaScript
- **Backend:** PHP
- **Banco de dados:** MySQL / MariaDB
- **Acesso ao banco:** PDO
- **Versionamento:** Git e GitHub
- **Servidor local utilizado durante o desenvolvimento:** servidor embutido do PHP + MySQL/MariaDB do XAMPP

A interface foi desenvolvida de forma responsiva, com navegação adaptada para desktop e dispositivos móveis.

---

## Arquitetura

O projeto utiliza uma organização baseada em **MVC**, complementada por classes de serviço para regras de negócio específicas.

Estrutura principal:

```text
RepubliCAIXA/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Views/
├── config/
├── database/
├── docs/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── index.php
├── routes/
├── storage/
│   └── payment-proofs/
└── README.md
```

O arquivo `public/index.php` funciona como ponto de entrada da aplicação e utiliza as rotas definidas em `routes/web.php`.

---

# Funcionalidades

## 1. Usuários e autenticação

- cadastro de usuário;
- login e logout;
- armazenamento seguro de senha com `password_hash()`;
- validação de senha com `password_verify()`;
- sessões autenticadas;
- revalidação do usuário da sessão no banco;
- edição de perfil;
- alteração de nome, e-mail e telefone;
- alteração de senha com validação da senha atual.

Rotas principais:

```text
/cadastro
/login
/perfil
```

---

## 2. Repúblicas e moradores

- criação de república;
- geração de código de convite;
- entrada em uma república utilizando o código;
- associação entre usuários e repúblicas;
- níveis de acesso `admin` e `member`;
- listagem dos moradores;
- promoção e rebaixamento de administradores;
- remoção lógica de moradores;
- edição do nome e da descrição da república.

A remoção de um morador não apaga seu histórico. O registro permanece no banco com `active = 0`.

Rotas principais:

```text
/republica
/republica/moradores
/republica/configuracoes
```

---

## 3. Despesas e rateio

O módulo de despesas permite cadastrar e acompanhar os gastos compartilhados da república.

Funcionalidades:

- cadastro de despesas;
- categorias de despesas;
- seleção dos moradores participantes;
- edição de despesas;
- visualização detalhada;
- acompanhamento da situação de cada parcela;
- rateio entre os participantes.

Tipos de rateio disponíveis:

### Igualitário

O valor é dividido automaticamente entre os participantes, considerando também diferenças de centavos.

### Percentual

Cada participante recebe uma porcentagem do valor total. A soma deve corresponder a 100%.

### Personalizado

O usuário informa diretamente quanto cada participante deverá pagar. A soma dos valores deve corresponder ao total da despesa.

Rotas principais:

```text
/despesas
/despesas/nova
/despesas/editar
/despesas/detalhes
```

---

## 4. Pagamentos

Cada parcela de uma despesa pode receber pagamentos registrados pelos moradores.

Funcionalidades:

- pagamento parcial ou total;
- registro de múltiplos pagamentos até a quitação;
- anexação de comprovante de pagamento;
- upload de PDF, JPG e PNG;
- armazenamento dos comprovantes fora da pasta pública;
- confirmação do pagamento por administrador;
- atualização da situação da parcela após confirmação.

Os comprovantes ficam em:

```text
storage/payment-proofs/
```

A pasta é ignorada pelo Git para impedir que arquivos enviados pelos usuários sejam publicados no repositório.

---

## 5. Caixa / fundo de reserva

O Caixa representa o **fundo de reserva da república** e é separado das despesas rateadas.

Exemplo:

```text
Três contribuições de R$ 50
→ reserva = R$ 150

Gasto de R$ 90 com a reserva
→ saldo = R$ 60
```

O pagamento de uma parcela de despesa **não adiciona dinheiro ao fundo de reserva**. Ele apenas reduz a pendência daquele morador.

Administradores podem registrar:

- entradas;
- saídas.

Moradores podem consultar o histórico.

O sistema também impede que uma saída deixe o fundo de reserva com saldo negativo.

Rota principal:

```text
/caixa
```

---

## 6. Dashboard financeiro

A rota autenticada `/dashboard` apresenta uma visão consolidada da situação financeira da república.

Entre os indicadores exibidos estão:

- saldo atual do fundo de reserva;
- entradas do mês;
- saídas do mês;
- despesas do mês;
- valores pendentes;
- distribuição dos gastos por categoria;
- evolução financeira dos últimos meses;
- próximos vencimentos.

A landing page pública permanece disponível em:

```text
/
```

---

## 7. Relatórios

O módulo de relatórios permite consultar informações financeiras por período.

Inclui:

- resumo mensal;
- despesas do período;
- valores quitados e pendentes;
- rateio por morador;
- extrato do fundo de reserva;
- saldo anterior e saldo final do período;
- exportação CSV;
- impressão pelo navegador, permitindo salvar o relatório como PDF.

Rota principal:

```text
/relatorios
```

---

# Banco de dados

O banco utiliza as seguintes tabelas principais:

```text
users
republics
republic_members
expense_categories
expenses
expense_shares
payments
cash_transactions
```

O arquivo oficial da estrutura é:

```text
database/schema.sql
```

Os dados iniciais de categorias estão em:

```text
database/seed.sql
```

Sempre que a estrutura do banco for alterada, `database/schema.sql` deve ser atualizado junto com o código.

---

# Segurança

Entre os controles aplicados no projeto estão:

- `password_hash()` e `password_verify()`;
- prepared statements com PDO;
- proteção CSRF em operações sensíveis;
- regeneração do identificador de sessão após autenticação;
- cookies de sessão com `HttpOnly` e `SameSite`;
- validação de permissões no backend;
- diferenciação entre administrador e morador;
- validação do usuário da sessão no banco;
- escape de conteúdo exibido nas páginas;
- validação do tipo MIME dos comprovantes;
- armazenamento dos comprovantes fora de `public/`;
- proteção contra saldo negativo no fundo de reserva;
- transações de banco em operações financeiras críticas;
- proteção contra reenvio duplicado de determinadas operações financeiras;
- neutralização de conteúdo potencialmente interpretado como fórmula na exportação CSV.

---

# Responsividade

O RepubliCAIXA foi desenvolvido com interface responsiva e pode ser utilizado tanto em desktop quanto em telas menores.

Foram considerados cenários como:

- smartphones;
- tablets;
- notebooks;
- desktops.

O menu lateral utilizado em telas maiores é adaptado para navegação móvel em telas menores.

---

# Requisitos para execução

- PHP 8.x;
- MySQL ou MariaDB;
- extensão PDO MySQL habilitada;
- extensão PHP `mbstring` habilitada.

No XAMPP, mantenha o serviço **MySQL** iniciado antes de executar a aplicação.

---

# Instalação local

## 1. Clone o repositório

```bash
git clone https://github.com/gyyamzaki/RepubliCAIXA.git
cd RepubliCAIXA
```

## 2. Crie o banco

Crie um banco chamado:

```text
republicaixa
```

Em seguida execute, nesta ordem:

```text
database/schema.sql
database/seed.sql
```

## 3. Configure a conexão

Copie:

```text
config/database.example.php
```

para:

```text
config/database.php
```

Depois ajuste as credenciais locais do MySQL/MariaDB.

Exemplo:

```php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'republicaixa',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

> `config/database.php` é local e não deve ser enviado ao GitHub.

## 4. Verifique `mbstring`

No Windows:

```powershell
php -m | findstr mbstring
```

A saída deve conter:

```text
mbstring
```

## 5. Inicie o servidor

Na raiz do projeto:

```bash
php -S localhost:8000 -t public
```

Acesse:

```text
http://localhost:8000
```

---

# Rotas principais

| Rota | Função |
|---|---|
| `/` | Landing page pública |
| `/cadastro` | Cadastro |
| `/login` | Login |
| `/dashboard` | Dashboard financeiro |
| `/republica` | Criar ou entrar em uma república |
| `/republica/moradores` | Moradores e permissões |
| `/republica/configuracoes` | Configurações da república |
| `/perfil` | Perfil do usuário |
| `/despesas` | Lista de despesas |
| `/despesas/nova` | Nova despesa |
| `/caixa` | Fundo de reserva |
| `/relatorios` | Relatórios financeiros |

---

# Organização dos módulos

O desenvolvimento foi dividido entre os integrantes e posteriormente integrado em uma única versão.

### Usuários e Repúblicas

Responsável por:

- autenticação;
- sessões;
- perfil;
- criação e entrada em repúblicas;
- moradores;
- permissões `admin/member`;
- configurações da república.

### Despesas, Rateio e Pagamentos

Responsável por:

- cadastro e edição de despesas;
- seleção de participantes;
- rateio igualitário;
- rateio percentual;
- rateio personalizado;
- parcelas;
- pagamentos;
- comprovantes;
- confirmação de pagamentos.

### Caixa, Dashboard e Relatórios

Responsável por:

- fundo de reserva;
- entradas e saídas;
- validação de saldo;
- indicadores financeiros;
- dashboard;
- relatórios;
- exportação CSV;
- visualização para impressão/PDF.

Os módulos foram integrados preservando os arquivos autorais de cada parte sempre que possível. As alterações necessárias em arquivos compartilhados estão registradas em:

```text
docs/integracao/
```

---

# Testes

Antes da integração final foi realizada uma rodada de testes envolvendo:

- cadastro e autenticação;
- criação e entrada em república;
- permissões administrativas;
- perfil e senha;
- rateios igualitário, percentual e personalizado;
- pagamentos parciais e totais;
- upload e acesso a comprovantes;
- caixa e prevenção de saldo negativo;
- atualização do dashboard;
- relatórios e CSV;
- isolamento de dados entre repúblicas;
- CSRF;
- sessão inválida;
- tentativas básicas de XSS e SQL Injection;
- responsividade;
- navegação entre todos os módulos.

A documentação de integração e evidências adicionais estão disponíveis em `docs/`.

---

# Limitações atuais e trabalhos futuros

Algumas funcionalidades foram deixadas para evolução futura:

- rejeição de pagamentos pela interface;
- anexo do comprovante original da própria despesa;
- cancelamento formal de despesas preservando o histórico;
- notificações push/e-mail de vencimentos;
- integração bancária/Pix;
- conciliação financeira automática;
- exportação PDF diretamente pelo servidor;
- suporte explícito à seleção entre múltiplas repúblicas por usuário;
- paginação e otimizações adicionais para grandes volumes de dados.

---

# Observações importantes

- Não envie `config/database.php` ao repositório.
- Não envie comprovantes reais armazenados em `storage/payment-proofs/`.
- O banco oficial deve sempre seguir `database/schema.sql`.
- Antes de uma apresentação ou demonstração, confirme que o MySQL/MariaDB está iniciado.
- O fundo de reserva é propositalmente separado dos pagamentos das despesas rateadas.

---

## Projeto acadêmico

RepubliCAIXA foi desenvolvido como projeto acadêmico de Engenharia de Software, com foco em organização, segurança, responsividade e aplicação prática em moradias estudantis compartilhadas.
