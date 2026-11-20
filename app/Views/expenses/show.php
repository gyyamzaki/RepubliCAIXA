<?php

$pageTitle = 'Detalhes da despesa - RepubliCAIXA';
$currentPage = 'expenses';

$esc = static fn ($valor): string => htmlspecialchars(
    (string) ($valor ?? ''),
    ENT_QUOTES,
    'UTF-8'
);

$moeda = static fn ($valor): string => 'R$ ' . number_format(
    (float) $valor,
    2,
    ',',
    '.'
);

$dataBR = static function ($valor): string {
    if (!$valor) {
        return 'Não informado';
    }

    $tempo = strtotime((string) $valor);

    return $tempo === false
        ? 'Não informado'
        : date('d/m/Y', $tempo);
};

$tipos = [
    'equal' => 'Igualitário',
    'percentage' => 'Percentual',
    'custom' => 'Personalizado',
];

// IDENTIFICAR USUÁRIO LOGADO
$usuario = \App\Core\Session::get('user');

$usuarioId = is_array($usuario)
    ? (int) ($usuario['id'] ?? 0)
    : 0;

// CONSULTAR PAPEL DO USUÁRIO NA REPÚBLICA
$consultaMembro = \App\Core\Database::connection()->prepare(
    'SELECT id, role
     FROM republic_members
     WHERE republic_id = :republica
       AND user_id = :usuario
       AND active = 1
     LIMIT 1'
);

$consultaMembro->execute([
    'republica' => (int) $despesa['republic_id'],
    'usuario' => $usuarioId
]);

$membroAtual = $consultaMembro->fetch(
    \PDO::FETCH_ASSOC
) ?: [];

$membroId = (int) ($membroAtual['id'] ?? 0);
$ehAdmin = ($membroAtual['role'] ?? '') === 'admin';

// TOKEN CSRF
$csrf = \App\Core\Session::get('csrf_despesas');

if (!is_string($csrf) || $csrf === '') {
    $csrf = bin2hex(random_bytes(32));

    \App\Core\Session::set(
        'csrf_despesas',
        $csrf
    );
}

// VALORES PENDENTES OU CONFIRMADOS
$comprometidoPorParte = [];

foreach ($pagamentos as $pagamento) {
    if (
        in_array(
            $pagamento['status'],
            ['pending', 'confirmed'],
            true
        )
    ) {
        $idParte = (int) $pagamento['expense_share_id'];

        $comprometidoPorParte[$idParte] =
            ($comprometidoPorParte[$idParte] ?? 0)
            + \App\Services\ExpenseRateio::centavos(
                (string) $pagamento['amount']
            );
    }
}

// RESUMO FINANCEIRO
$total = (float) $despesa['total_amount'];

$restante = max(
    0.0,
    $total - $totalPago
);

$statusGeral = $restante < 0.005
    ? 'Pago'
    : ($totalPago > 0 ? 'Parcial' : 'Pendente');

require __DIR__ . '/../partials/header.php';

?>

<style>
    .details-page {
        max-width: 1100px;
    }

    .details-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .details-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .details-actions a,
    .details-link {
        color: #0f766e;
        text-decoration: none;
        font-weight: 600;
    }

    .details-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin: 24px 0;
    }

    .details-cards .card,
    .details-section {
        padding: 22px;
    }

    .details-section {
        margin-top: 20px;
    }

    .details-section h2 {
        margin-bottom: 18px;
    }

    .details-label {
        display: block;
        color: #64748b;
        font-size: 14px;
        margin-bottom: 7px;
    }

    .details-number {
        font-size: 24px;
        font-weight: 700;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .details-grid strong {
        display: block;
        overflow-wrap: anywhere;
    }

    .details-table-wrap {
        overflow-x: auto;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .details-table th,
    .details-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .details-table th {
        color: #64748b;
        font-weight: 600;
    }

    .details-description {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .details-note {
        color: #64748b;
        margin-top: 12px;
    }

    .details-notice {
        margin: 20px 0;
        padding: 14px 16px;
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        border-radius: 8px;
        color: #115e59;
    }

    .details-payment-list {
        display: grid;
        gap: 16px;
    }

    .details-payment-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px;
    }

    .details-payment-item h3 {
        margin: 0 0 12px;
    }

    .details-payment-item p {
        margin: 6px 0;
    }

    .details-form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        margin-top: 14px;
    }

    .details-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .details-field label {
        font-weight: 600;
    }

    .details-field input[type="number"],
    .details-field input[type="file"] {
        font: inherit;
        max-width: 100%;
        padding: 10px;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
    }

    .details-btn {
        background: #0f766e;
        color: white;
        border: 0;
        padding: 11px 16px;
        border-radius: 8px;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    .details-btn:hover {
        background: #115e59;
    }

    .details-btn-small {
        padding: 8px 12px;
    }

    .details-btn-reject {
        background: #b91c1c;
    }

    .details-btn-reject:hover {
        background: #991b1b;
    }

    .details-admin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .details-pending {
        color: #a16207;
        font-weight: 600;
    }

    .details-confirmed {
        color: #15803d;
        font-weight: 600;
    }

    .details-rejected {
        color: #b91c1c;
        font-weight: 600;
    }

    @media (max-width: 700px) {
        .details-cards,
        .details-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="app-layout">

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content details-page">

        <!-- CABEÇALHO -->
        <div class="page-header details-header">

            <div>
                <h1>
                    <?= $esc($despesa['title']) ?>
                </h1>

                <p class="text-muted">
                    Detalhes da despesa
                    #<?= (int) $despesa['id'] ?>
                </p>
            </div>

            <div class="details-actions">

                <a href="/despesas">
                    Voltar às despesas
                </a>

                <a href="/despesas/editar?id=<?= (int) $despesa['id'] ?>">
                    Editar despesa
                </a>

            </div>

        </div>

        <!-- MENSAGENS -->
        <?php if (
            ($_GET['pagamento'] ?? '') === 'registrado'
        ): ?>

            <div class="details-notice" role="status">
                Pagamento registrado com sucesso!
                Aguarda confirmação do administrador.
            </div>

        <?php elseif (
            ($_GET['pagamento'] ?? '') === 'confirmado'
        ): ?>

            <div class="details-notice" role="status">
                Pagamento confirmado com sucesso!
                Os totais foram atualizados.
            </div>

        <?php elseif (
            ($_GET['pagamento'] ?? '') === 'rejeitado'
        ): ?>

            <div class="details-notice" role="status">
                Pagamento rejeitado com sucesso!
                O valor está disponível para um novo registro.
            </div>

        <?php endif; ?>

        <!-- RESUMO FINANCEIRO -->
        <section
            class="details-cards"
            aria-label="Resumo financeiro"
        >

            <article class="card">

                <span class="details-label">
                    Valor total
                </span>

                <strong class="details-number">
                    <?= $moeda($total) ?>
                </strong>

            </article>

            <article class="card">

                <span class="details-label">
                    Total pago
                </span>

                <strong class="details-number status-success">
                    <?= $moeda($totalPago) ?>
                </strong>

            </article>

            <article class="card">

                <span class="details-label">
                    Falta pagar
                </span>

                <strong class="details-number status-danger">
                    <?= $moeda($restante) ?>
                </strong>

            </article>

        </section>

        <!-- INFORMAÇÕES DA DESPESA -->
        <section class="card details-section">

            <h2>Informações da despesa</h2>

            <div class="details-grid">

                <div>
                    <span class="details-label">
                        Categoria
                    </span>

                    <strong>
                        <?= $esc(
                            $despesa['category_name']
                            ?? 'Sem categoria'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span class="details-label">
                        Situação
                    </span>

                    <strong>
                        <?= $esc($statusGeral) ?>
                    </strong>
                </div>

                <div>
                    <span class="details-label">
                        Data da despesa
                    </span>

                    <strong>
                        <?= $esc(
                            $dataBR($despesa['expense_date'])
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span class="details-label">
                        Vencimento
                    </span>

                    <strong>
                        <?= $esc(
                            $dataBR($despesa['due_date'])
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span class="details-label">
                        Tipo de rateio
                    </span>

                    <strong>
                        <?= $esc(
                            $tipos[$despesa['split_type']]
                            ?? 'Não informado'
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span class="details-label">
                        Cadastrada por
                    </span>

                    <strong>
                        <?= $esc($despesa['creator_name']) ?>
                    </strong>
                </div>

            </div>

            <?php if (
                trim(
                    (string) ($despesa['description'] ?? '')
                ) !== ''
            ): ?>

                <div style="margin-top: 20px;">

                    <span class="details-label">
                        Descrição
                    </span>

                    <p class="details-description">
                        <?= $esc($despesa['description']) ?>
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <!-- RATEIO -->
        <section class="card details-section">

            <h2>Quanto cada morador deve</h2>

            <div class="details-table-wrap">

                <table class="details-table">

                    <thead>
                        <tr>
                            <th>Morador</th>
                            <th>Parte</th>
                            <th>Valor devido</th>
                            <th>Pago</th>
                            <th>Falta</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($rateios as $parte): ?>

                            <tr>

                                <td>
                                    <?= $esc($parte['member_name']) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $despesa['split_type']
                                            === 'percentage'
                                        &&
                                        $parte['percentage'] !== null
                                    ): ?>

                                        <?= $esc(
                                            number_format(
                                                (float) $parte['percentage'],
                                                2,
                                                ',',
                                                '.'
                                            ) . '%'
                                        ) ?>

                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $moeda($parte['amount_due']) ?>
                                </td>

                                <td>
                                    <?= $moeda($parte['paid_amount']) ?>
                                </td>

                                <td>
                                    <?= $moeda($parte['remaining_amount']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <!-- REGISTRAR PAGAMENTO -->
        <section class="card details-section">

            <h2>Registrar pagamento</h2>

            <p class="details-note">
                Informe quanto foi pago.
                O comprovante é opcional e pode ser
                PDF, JPG ou PNG, de até 5 MB.
            </p>

            <p class="details-note">
                Os pagamentos só entram no total pago
                depois da confirmação pelo administrador.
            </p>

            <div class="details-payment-list">

                <?php $algumFormulario = false; ?>

                <?php foreach ($rateios as $parte): ?>

                    <?php

                    $podeRegistrar = $ehAdmin
                        ||
                        (int) $parte['member_id'] === $membroId;

                    $parteId = (int) $parte['share_id'];

                    $devidoCentavos =
                        \App\Services\ExpenseRateio::centavos(
                            (string) $parte['amount_due']
                        );

                    $disponivel = max(
                        0,
                        $devidoCentavos
                        -
                        ($comprometidoPorParte[$parteId] ?? 0)
                    );

                    $estaPago =
                        $parte['share_status'] === 'paid';

                    ?>

                    <?php if ($podeRegistrar): ?>

                        <?php $algumFormulario = true; ?>

                        <div class="details-payment-item">

                            <h3>
                                <?= $esc($parte['member_name']) ?>
                            </h3>

                            <p>
                                Valor devido:
                                <strong>
                                    <?= $moeda($parte['amount_due']) ?>
                                </strong>
                            </p>

                            <p>
                                Já confirmado:
                                <strong>
                                    <?= $moeda($parte['paid_amount']) ?>
                                </strong>
                            </p>

                            <p>
                                Disponível para registrar:
                                <strong>
                                    <?= $moeda($disponivel / 100) ?>
                                </strong>
                            </p>

                            <?php if (
                                !$estaPago &&
                                $disponivel > 0
                            ): ?>

                                <form
                                    class="details-form"
                                    action="/despesas/pagamento"
                                    method="post"
                                    enctype="multipart/form-data"
                                >

                                    <input
                                        type="hidden"
                                        name="_csrf"
                                        value="<?= $esc($csrf) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="share_id"
                                        value="<?= $parteId ?>"
                                    >

                                    <div class="details-field">

                                        <label for="valor-<?= $parteId ?>">
                                            Valor pago (R$)
                                        </label>

                                        <input
                                            id="valor-<?= $parteId ?>"
                                            type="number"
                                            name="amount"
                                            min="0.01"
                                            max="<?= $esc(
                                                number_format(
                                                    $disponivel / 100,
                                                    2,
                                                    '.',
                                                    ''
                                                )
                                            ) ?>"
                                            step="0.01"
                                            value="<?= $esc(
                                                number_format(
                                                    $disponivel / 100,
                                                    2,
                                                    '.',
                                                    ''
                                                )
                                            ) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="details-field">

                                        <label for="prova-<?= $parteId ?>">
                                            Comprovante (opcional)
                                        </label>

                                        <input
                                            id="prova-<?= $parteId ?>"
                                            type="file"
                                            name="proof"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                        >

                                    </div>

                                    <button
                                        class="details-btn"
                                        type="submit"
                                    >
                                        Registrar pagamento
                                    </button>

                                </form>

                            <?php elseif ($estaPago): ?>

                                <p class="details-confirmed">
                                    Parte quitada.
                                </p>

                            <?php else: ?>

                                <p class="details-pending">
                                    O restante já está registrado
                                    e aguarda confirmação.
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

                <?php if (!$algumFormulario): ?>

                    <p class="details-note">
                        Você não possui uma parte desta despesa
                        para registrar pagamentos.
                        Somente o morador responsável
                        ou um administrador pode registrar.
                    </p>

                <?php endif; ?>

            </div>

        </section>

        <!-- HISTÓRICO DE PAGAMENTOS -->
        <section class="card details-section">

            <h2>Histórico de pagamentos</h2>

            <?php if (!$pagamentos): ?>

                <p class="details-note">
                    Nenhum pagamento registrado até o momento.
                </p>

            <?php else: ?>

                <div class="details-table-wrap">

                    <table class="details-table">

                        <thead>
                            <tr>
                                <th>Morador</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Comprovante</th>

                                <?php if ($ehAdmin): ?>
                                    <th>Ações</th>
                                <?php endif; ?>

                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($pagamentos as $pagamento): ?>

                                <?php

                                $statusPagamento = [
                                    'pending' => 'Aguardando confirmação',
                                    'confirmed' => 'Confirmado',
                                    'rejected' => 'Rejeitado',
                                ][$pagamento['status']] ?? 'Desconhecido';

                                ?>

                                <tr>

                                    <td>
                                        <?= $esc(
                                            $pagamento['member_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $moeda(
                                            $pagamento['amount']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $esc(
                                            $dataBR(
                                                $pagamento['payment_date']
                                            )
                                        ) ?>
                                    </td>

                                    <td
                                        class="<?= $pagamento['status'] === 'confirmed'
                                            ? 'details-confirmed'
                                            : (
                                                $pagamento['status'] === 'rejected'
                                                ? 'details-rejected'
                                                : 'details-pending'
                                            ) ?>"
                                    >
                                        <?= $esc($statusPagamento) ?>
                                    </td>

                                    <td>

                                        <?php if (
                                            !empty($pagamento['proof_path'])
                                        ): ?>

                                            <a
                                                class="details-link"
                                                href="/despesas/comprovante?id=<?= (int) $pagamento['id'] ?>"
                                            >
                                                Baixar comprovante
                                            </a>

                                        <?php else: ?>
                                            Não enviado
                                        <?php endif; ?>

                                    </td>

                                    <?php if ($ehAdmin): ?>

                                        <td>

                                            <?php if (
                                                $pagamento['status'] === 'pending'
                                            ): ?>

                                                <div class="details-admin-actions">

                                                    <!-- CONFIRMAR PAGAMENTO -->
                                                    <form
                                                        action="/despesas/pagamento/confirmar"
                                                        method="post"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="_csrf"
                                                            value="<?= $esc($csrf) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="payment_id"
                                                            value="<?= (int) $pagamento['id'] ?>"
                                                        >

                                                        <button
                                                            class="details-btn details-btn-small"
                                                            type="submit"
                                                        >
                                                            Confirmar
                                                        </button>

                                                    </form>

                                                    <!-- REJEITAR PAGAMENTO -->
                                                    <form
                                                        action="/despesas/pagamento/rejeitar"
                                                        method="post"
                                                        onsubmit="return confirm('Tem certeza que deseja rejeitar este pagamento?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="_csrf"
                                                            value="<?= $esc($csrf) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="payment_id"
                                                            value="<?= (int) $pagamento['id'] ?>"
                                                        >

                                                        <button
                                                            class="details-btn details-btn-small details-btn-reject"
                                                            type="submit"
                                                        >
                                                            Rejeitar
                                                        </button>

                                                    </form>

                                                </div>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>

                                    <?php endif; ?>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
