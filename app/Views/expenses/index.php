
<?php

$pageTitle = 'Despesas - RepubliCAIXA';
$currentPage = 'expenses';

$despesas = $despesas ?? [];
$republica = $republica ?? null;

$esc = static fn ($valor): string =>
    htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );

$dinheiro = static fn (float $valor): string =>
    'R$ ' . number_format($valor, 2, ',', '.');

// Totais financeiros
$total = 0.0;
$pago = 0.0;

foreach ($despesas as $despesa) {
    $valor = (float) $despesa['total_amount'];
    $total += $valor;

    $pago += max(
        0,
        min($valor, (float) $despesa['paid_amount'])
    );
}

$pendente = max(0, $total - $pago);

require __DIR__ . '/../partials/header.php';

?>

<style>
    .expenses-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .expenses-new-button {
        display: inline-flex;
        padding: 12px 20px;
        color: white;
        background: #0f766e;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
    }

    .expenses-new-button:hover {
        background: #115e59;
        color: white;
    }

    .expenses-list {
        margin-top: 24px;
    }

    .expenses-list h2 {
        margin-bottom: 20px;
    }

    .expenses-filters {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .expenses-filters input,
    .expenses-filters select {
        flex: 1;
        min-width: 190px;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        font-size: 14px;
    }

    .expenses-table-container {
        width: 100%;
        overflow-x: auto;
    }

    .expenses-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .expenses-table th,
    .expenses-table td {
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .expenses-table th {
        color: #64748b;
        font-weight: 600;
    }

    .expenses-table tbody tr:hover {
        background: #f8fafc;
    }

    .expenses-empty {
        padding: 20px 0;
        color: #64748b;
    }

    .expenses-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .expenses-actions a {
        color: #0f766e;
        font-weight: 600;
        text-decoration: none;
    }

    .expenses-actions a:hover {
        text-decoration: underline;
    }
</style>

<div class="app-layout">

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content">

        <!-- CABEÇALHO -->
        <div class="page-header expenses-page-header">

            <div>
                <h1>Despesas</h1>

                <p class="text-muted">
                    Controle as despesas da sua república.
                </p>

                <?php if ($republica !== null): ?>

                    <p class="text-muted">
                        República:
                        <?= $esc($republica['name']) ?>
                    </p>

                <?php endif; ?>

            </div>

            <a
                class="expenses-new-button"
                href="/despesas/nova"
            >
                + Nova despesa
            </a>

        </div>

        <?php if ($republica === null): ?>

            <p class="expenses-empty">
                Seu usuário ainda não está vinculado
                a uma república.
            </p>

        <?php endif; ?>

        <!-- RESUMO FINANCEIRO -->
        <section class="card-grid">

            <article class="card">
                <span class="card-label">
                    Total cadastrado
                </span>

                <strong class="card-value">
                    <?= $dinheiro($total) ?>
                </strong>
            </article>

            <article class="card">
                <span class="card-label">
                    Total pago
                </span>

                <strong class="card-value status-success">
                    <?= $dinheiro($pago) ?>
                </strong>
            </article>

            <article class="card">
                <span class="card-label">
                    Pendente
                </span>

                <strong class="card-value status-danger">
                    <?= $dinheiro($pendente) ?>
                </strong>
            </article>

        </section>

        <!-- LISTA DE DESPESAS -->
        <section class="card expenses-list">

            <h2>Lista de despesas</h2>

            <!-- FILTROS -->
            <div class="expenses-filters">

                <input
                    type="search"
                    id="searchExpense"
                    placeholder="Pesquisar despesa..."
                    aria-label="Pesquisar despesa"
                >

                <select
                    id="filterStatus"
                    aria-label="Filtrar por status"
                >

                    <option value="todos">
                        Todos os status
                    </option>

                    <option value="pago">
                        Pago
                    </option>

                    <option value="pendente">
                        Pendente
                    </option>

                    <option value="parcial">
                        Parcial
                    </option>

                </select>

            </div>

            <div class="expenses-table-container">

                <table class="expenses-table">

                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($despesas as $despesa): ?>

                        <?php

                        $valor = (float)
                            $despesa['total_amount'];

                        $valorPago = max(
                            0,
                            min(
                                $valor,
                                (float) $despesa['paid_amount']
                            )
                        );

                        if ($valorPago >= $valor - 0.005) {

                            $status = 'pago';
                            $classe = 'status-success';

                        } elseif ($valorPago > 0) {

                            $status = 'parcial';
                            $classe = 'status-warning';

                        } else {

                            $status = 'pendente';
                            $classe = 'status-danger';
                        }

                        $vencimento = $despesa['due_date']
                            ? date(
                                'd/m/Y',
                                strtotime($despesa['due_date'])
                            )
                            : 'Não informado';

                        ?>

                        <tr data-status="<?= $status ?>">

                            <td>
                                <?= $esc($despesa['title']) ?>
                            </td>

                            <td>
                                <?= $esc(
                                    $despesa['category_name']
                                    ?? 'Sem categoria'
                                ) ?>
                            </td>

                            <td>
                                <?= $dinheiro($valor) ?>
                            </td>

                            <td>
                                <?= $esc($vencimento) ?>
                            </td>

                            <td>
                                <span class="<?= $classe ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>

                            <!-- AÇÕES DA DESPESA -->
                            <td>

                                <div class="expenses-actions">

                                    <a
                                        href="/despesas/editar?id=<?= (int) $despesa['id'] ?>"
                                    >
                                        Editar
                                    </a>

                                    <a
                                        href="/despesas/detalhes?id=<?= (int) $despesa['id'] ?>"
                                    >
                                        Detalhes
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if (!$despesas): ?>

                <p class="expenses-empty">
                    Nenhuma despesa cadastrada nesta república.
                </p>

            <?php endif; ?>

            <p
                id="emptyMessage"
                class="expenses-empty"
                style="display: none;"
            >
                Nenhuma despesa encontrada com esses filtros.
            </p>

        </section>

    </main>

</div>

<!-- FILTROS JAVASCRIPT -->
<script>

    const pesquisa = document.getElementById(
        'searchExpense'
    );

    const filtro = document.getElementById(
        'filterStatus'
    );

    const linhas = document.querySelectorAll(
        '.expenses-table tbody tr'
    );

    const vazio = document.getElementById(
        'emptyMessage'
    );

    function filtrarDespesas() {

        const texto = pesquisa.value
            .trim()
            .toLocaleLowerCase('pt-BR');

        const status = filtro.value;

        let encontrados = 0;

        linhas.forEach(linha => {

            const descricao = linha.cells[0]
                .textContent
                .toLocaleLowerCase('pt-BR');

            const exibir =
                descricao.includes(texto) &&
                (
                    status === 'todos' ||
                    linha.dataset.status === status
                );

            linha.style.display = exibir ? '' : 'none';

            if (exibir) {
                encontrados++;
            }

        });

        vazio.style.display =
            linhas.length && encontrados === 0
                ? 'block'
                : 'none';
    }

    pesquisa.addEventListener(
        'input',
        filtrarDespesas
    );

    filtro.addEventListener(
        'change',
        filtrarDespesas
    );

</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
