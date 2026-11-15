<?php require __DIR__ . '/start.php'; ?>
<p class="finance-note">Fundo de reserva para contribuições, consertos e compras coletivas. Pagar uma parcela de despesa não gera entrada automática aqui.</p>
<section class="card-grid finance-cards" aria-label="Resumo da reserva">
    <article class="card"><h2 class="card-label">Saldo atual da reserva</h2><strong class="card-value"><?= $money($cash['balance']) ?></strong><p class="text-muted">Todos os movimentos registrados.</p></article>
    <article class="card"><h2 class="card-label">Entradas no mês</h2><strong class="card-value finance-income"><?= $money($cash['income']) ?></strong></article>
    <article class="card"><h2 class="card-label">Saídas no mês</h2><strong class="card-value finance-expense"><?= $money($cash['expense']) ?></strong></article>
</section>
<?php if ($isAdmin): ?>
<section class="card finance-section"><h2>Registrar movimento</h2>
    <p class="text-muted" id="cash-help">Registre apenas valores já recebidos ou pagos. Um gasto coberto pela reserva não deve ser cobrado novamente no rateio.</p>
    <form class="finance-form" action="/caixa?month=<?= $e($month) ?>" method="post" aria-describedby="cash-help">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="_submission" value="<?= $e($submission) ?>">
        <div class="form-group"><label for="cash-type">Tipo</label><select id="cash-type" name="type" required><option value="income" <?= ($old['type'] ?? '') === 'income' ? 'selected' : '' ?>>Entrada na reserva</option><option value="expense" <?= ($old['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Saída da reserva</option></select></div>
        <div class="form-group"><label for="cash-amount">Valor (R$)</label><input id="cash-amount" name="amount" type="text" inputmode="decimal" maxlength="11" placeholder="Ex.: 50,00" value="<?= $e($old['amount'] ?? '') ?>" required aria-describedby="amount-help"><small id="amount-help">Use vírgula ou ponto para os centavos, sem separador de milhar.</small></div>
        <div class="form-group"><label for="cash-date">Data do movimento</label><input id="cash-date" name="transaction_date" type="date" min="2000-01-01" max="<?= date('Y-m-d') ?>" value="<?= $e($old['transaction_date'] ?? date('Y-m-d')) ?>" required></div>
        <div class="form-group finance-wide"><label for="cash-description">Descrição</label><input id="cash-description" name="description" minlength="3" maxlength="255" placeholder="Ex.: Contribuição de Ana para a reserva" value="<?= $e($old['description'] ?? '') ?>" required></div>
        <div class="finance-wide"><button class="button button-primary" type="submit">Registrar movimento</button></div>
    </form>
    <p class="text-muted finance-help">O histórico preserva os lançamentos. Para corrigir um erro, registre um movimento de valor oposto e identifique o número original e o motivo na descrição.</p>
</section>
<?php else: ?><p class="finance-note">Você pode consultar a reserva. Apenas administradores registram entradas e saídas.</p><?php endif; ?>
<section class="card finance-section"><h2>Extrato da reserva</h2>
    <p>Saldo anterior ao mês: <strong><?= $money($cash['opening']) ?></strong> · Saldo ao fim do período: <strong><?= $money($cash['closing']) ?></strong></p>
    <?php require __DIR__ . '/cash-table.php'; ?>
    <a class="finance-link" href="/relatorios/exportar?tipo=caixa&amp;month=<?= $e($month) ?>">Baixar extrato do mês em CSV</a>
</section>
<?php require __DIR__ . '/end.php'; ?>
