# Caixa, Dashboard e Relatórios — módulo de Rodrigo

Implementação local de 14/11/2026, a partir do ZIP `RepubliCAIXA_FINAL.zip` fornecido pelo grupo. O código dos módulos de autenticação e despesas pertence à base recebida. O ZIP e o SQL originais não foram modificados.

## Regra definida com Rodrigo

O Caixa representa um **fundo de reserva separado** das despesas rateadas. Contribuições recebidas são entradas; gastos efetivamente realizados com a reserva são saídas. Confirmar o pagamento de uma parcela reduz a pendência do morador e não aumenta a reserva.

Exemplo: três contribuições de R$ 50 geram R$ 150 de reserva. Um conserto de R$ 90 deixa R$ 60. Esse conserto não deve virar uma nova cobrança aos moradores. Nesta versão, essa distinção depende do registro correto pelo administrador; o sistema não identifica automaticamente se dois lançamentos em módulos diferentes representam o mesmo gasto real.

Administradores registram movimentos; moradores ativos consultam. A república é obtida da sessão e da associação ativa no banco, usando a primeira república ativa por ID, como no módulo de despesas. A aplicação ainda não oferece seleção entre várias repúblicas.

Não há edição nem exclusão de movimentos na interface. Correções são movimentos compensatórios, com referência ao número original e motivo na descrição. Não é um sistema contábil nem uma conciliação automática com banco ou Pix. O usuário registra os recebimentos e pagamentos realizados.

## Telas e funcionamento

| Tela | Rota | Conteúdo |
|---|---|---|
| 4 — Dashboard | `/` | Saldo atual da reserva; despesas do mês; pendências de todos os meses; reserva do mês; categorias; evolução de seis meses; cinco pendências por vencimento |
| 8 — Caixa | `/caixa` | Entradas e saídas; formulário para administrador; extrato com autor, data, descrição e saldo após cada movimento |
| 10 — Relatórios | `/relatorios` | Despesas e parcelas do mês; rateio por morador; extrato e saldos da reserva; impressão e CSV |

O mês das despesas é determinado por `expenses.expense_date`, não pela data em que o registro foi criado. A situação de quitação é atual: consultar novembro inclui pagamentos dessas parcelas confirmados em dezembro. Portanto, o relatório de parcelas não é uma fotografia histórica da inadimplência ao fechamento do mês. O extrato da reserva usa a data efetiva de cada movimento e calcula o saldo anterior e o final do período. O saldo atual inclui todos os movimentos, independentemente do mês filtrado.

Pagamentos pendentes ou rejeitados não contam como quitação. Para compatibilidade com os dados recebidos, uma parcela marcada `paid` continua considerada quitada mesmo quando o histórico de pagamentos não possui o valor completo. A mesma regra já é usada pelo módulo dos colegas.

## Caminho da tela até o banco

1. O navegador solicita a rota; `public/index.php` inicia a sessão e consulta `routes/web.php`.
2. `FinanceController` verifica o login, resolve a república, valida campos e prepara a tela ou o CSV.
3. `FinanceService` consulta MariaDB/MySQL via PDO e executa as regras de movimentação.
4. `app/Views/finance/` produz HTML com os dados escapados. CSS e JavaScript próprios ficam em `public/assets/`.

É uma aplicação PHP organizada em controlador, serviço e visualização, com páginas renderizadas no servidor. Preserva a arquitetura existente do grupo. Não introduz framework, API externa ou dependência de gráficos; os gráficos usam HTML/CSS com os mesmos valores disponíveis em texto. Não há alteração estrutural nas tabelas, e `database/schema.sql` continua sendo a referência do grupo.

O dinheiro é calculado em centavos inteiros no PHP e armazenado como DECIMAL no banco. Os pagamentos são agregados por parcela antes de juntar às despesas, evitando multiplicar valores quando uma parcela possui vários pagamentos.

## Controles e tratamento de erros

- PDO com consultas preparadas: valores não são concatenados ao SQL. Identificadores de república e autor vêm do contexto autorizado no servidor.
- `htmlspecialchars` nas saídas HTML, inclusive quando o formulário reaparece com erro. Texto perigoso é exibido como texto.
- Token CSRF ligado à sessão nas gravações do Caixa. Um segundo token de envio impede repetir o mesmo formulário depois de uma gravação bem-sucedida.
- Conta, vínculo e república devem estar ativos. A permissão de administrador é revalidada dentro da transação de gravação.
- Valores positivos com até duas casas decimais; descrição de 3 a 255 caracteres; datas reais entre 2000 e o dia atual. Mês de filtro entre 2000 e 2100.
- Transação de banco, bloqueio da república com `FOR UPDATE` e validação do histórico impedem saldo negativo, inclusive em lançamentos retroativos e simultâneos.
- Falhas de validação: HTTP 422 e mensagem legível; falta de permissão/CSRF: 403; formulário repetido: 409; sessão ausente: redirecionamento para login.
- Falha inesperada: mensagem genérica e HTTP 500; detalhes apenas no log do servidor. Não mostrar erros PHP em produção.
- CSV neutraliza textos que poderiam ser interpretados como fórmulas e aplica a mesma autorização da tela.

Esses controles foram implementados e testados no módulo 3. Isso não constitui uma auditoria completa da autenticação, dos uploads ou de todos os módulos dos colegas.

## Evidências e testes

Ambiente: PHP 8.2.12 e MariaDB 10.4.32 locais. Dados sintéticos em banco isolado com prefixo `republicaixa_finance_test_`. Cada execução cria um banco novo; não limpa ou sobrescreve um banco existente. Não executar os testes apontando para dados de produção.

No Windows, configure `config/database.php` seguindo o exemplo (incluindo a variável `REPUBLICAIXA_DB`), tenha MySQL/MariaDB iniciado e execute na raiz:

```powershell
powershell -ExecutionPolicy Bypass -File tests/run.ps1
```

| Verificação | Evidência |
|---|---|
| 34 verificações de precisão, validação, saldos, rateio, isolamento, rollback e estados de pagamento | `tests/finance.php` |
| Duas saídas simultâneas de R$ 75 sobre saldo de R$ 100: uma aceita, uma recusada, saldo R$ 25 | `tests/concurrency.php` |
| 37 verificações HTTP de login, permissões, CSRF, repetição de formulário, SQL injection, XSS, CSV e integração real com pagamento de parcela | `tests/http.php` |
| Telas em 1360 × 900 e 390 × 844, sem transbordamento lateral da página; menu móvel e campos identificados | `docs/evidencias/ui-result.json` e capturas PNG |

Os testes de SQL injection enviam um texto semelhante a uma instrução SQL no campo descrição e verificam que foi armazenado como dado, mantendo a tabela intacta. Os de XSS usam uma tag com manipulador de evento e verificam seu escape no extrato e no formulário com erro. São testes direcionados, não uma garantia de ausência de qualquer vulnerabilidade.

O teste de integração confirma uma parcela pelo endpoint existente dos colegas, verifica a atualização do relatório e confirma que a reserva permanece igual. Os dados desse teste não aparecem no banco de demonstração.

## Integração com o trabalho do grupo

Novos arquivos principais: `FinanceController.php`, `FinanceService.php`, `FinanceInput.php`, `app/Views/finance/`, `finance.css`, `finance.js`, `tests/` e este guia.

Arquivos compartilhados alterados: `routes/web.php` (rotas financeiras e dashboard), `sidebar.php` (links de Caixa e Relatórios), `header.php` (CSS apenas nas telas financeiras), `public/index.php` (fuso de São Paulo), `config/database.example.php` (nome do banco configurável por ambiente), `.gitignore` (dados locais e comprovantes privados). A antiga `home.php` permanece na base, mas a rota `/` agora usa `FinanceController` e `finance/dashboard.php`.

Ao integrar em uma versão mais recente do projeto, comparar esses arquivos compartilhados em vez de sobrescrevê-los inteiros. O código recebido já continha alterações não commitadas dos colegas; um `git diff` contra o commit antigo mistura essas alterações com o módulo 3. O arquivo `docs/ALTERACOES-MODULO-3.json` identifica as diferenças em relação ao ZIP recebido.

Não enviar `config/database.php`, `.local/`, o banco local nem comprovantes privados ao repositório. O esquema do banco não mudou. Nenhum commit, push ou publicação foi feito por esta implementação.

Pontos ainda pertencentes à integração do grupo: telas de Moradores e Perfil continuam com links provisórios na base recebida; cadastro/seleção de república precisa estar integrado pelo responsável; revisar os dados de exemplo com datas irreais; revisar a exposição de `public/test-db.php` antes de publicar; definir hospedagem PHP/MySQL, HTTPS e configuração segura de sessões. Não foram implementados como parte do módulo 3.

Limitações desta versão: extratos e relatórios mensais são carregados integralmente; volumes grandes pedirão paginação/consultas agregadas adicionais. Não há pagamento bancário real, integração Pix, conciliação automática, comprovante específico de reserva, exportação PDF pelo servidor ou seleção de múltiplas repúblicas. O botão de PDF usa a impressão do navegador.

## Uso de IA e autoria

Rodrigo escolheu, em discussão com ChatGPT/Codex, o modelo de reserva separada. ChatGPT/Codex auxiliou na análise da base recebida, implementação do módulo 3, testes automatizados e documentação. O código e os dados anteriores foram fornecidos pelo grupo e preservados como base; não devem ser atribuídos ao módulo 3. Esta declaração é um registro para compor a seção de uso de IA do relatório do grupo. Rodrigo e colegas ainda precisam revisar, compreender e validar o resultado para a entrega e a defesa.
