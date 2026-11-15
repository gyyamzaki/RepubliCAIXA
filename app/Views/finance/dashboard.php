<?php require __DIR__ . '/start.php'; ?>
<p class="finance-note">A reserva guarda contribuições e gastos próprios. Os pagamentos das despesas rateadas são acompanhados separadamente.</p>
<section class="card-grid finance-cards" aria-label="Indicadores financeiros">
    <article class="card"><h2 class="card-label">Saldo atual da reserva</h2><strong class="card-value"><?= $money($cash['balance']) ?></strong><p class="text-muted">Acumulado de todos os movimentos.</p><a class="finance-link" href="/caixa?month=<?= $e($month) ?>">Consultar reserva →</a></article>
    <article class="card"><h2 class="card-label">Despesas rateadas do mês</h2><strong class="card-value"><?= $money($totals['total']) ?></strong><p class="text-muted">Pela data da despesa informada no formulário.</p><a class="finance-link" href="/despesas">Consultar despesas →</a></article>
    <article class="card"><h2 class="card-label">Pendências dos moradores</h2><strong class="card-value finance-warning"><?= $money($allPending) ?></strong><p class="text-muted">Todas as despesas, inclusive de outros meses. Pagamentos aguardando confirmação continuam pendentes.</p></article>
</section>
<div class="finance-grid">
    <section class="card"><h2>Reserva no mês</h2><dl class="finance-statement">
        <div><dt>Saldo anterior ao mês</dt><dd><?= $money($cash['opening']) ?></dd></div>
        <div><dt>Entradas</dt><dd class="finance-income">+ <?= $money($cash['income']) ?></dd></div>
        <div><dt>Saídas</dt><dd class="finance-expense">− <?= $money($cash['expense']) ?></dd></div>
        <div class="finance-total"><dt>Saldo ao fim do período</dt><dd><?= $money($cash['closing']) ?></dd></div>
    </dl><p class="text-muted">Considera os movimentos já registrados no mês selecionado.</p></section>
    <section class="card"><h2>Despesas rateadas por categoria</h2>
        <?php if (!$categories): ?><p class="text-muted">Nenhuma despesa rateada neste mês.</p><?php else: ?>
        <ul class="finance-bars"><?php $maximum = max(1, ...array_values($categories)); foreach ($categories as $category => $amount): ?>
            <li><div><span><?= $e($category) ?></span><strong><?= $money($amount) ?></strong></div><div class="finance-track" aria-hidden="true"><span style="width: <?= (int) round(100 * $amount / $maximum) ?>%"></span></div></li>
        <?php endforeach; ?></ul><?php endif; ?>
    </section>
</div>
<section class="card finance-section"><h2>Despesas rateadas nos últimos seis meses</h2>
    <p class="text-muted">Até o mês de referência. Os gastos da reserva ficam no extrato separado.</p>
    <ul class="finance-bars finance-history"><?php $maximum = max(1, ...array_values($history)); foreach ($history as $key => $amount): ?>
        <li><div><span><?= $e(substr($key, 5, 2) . '/' . substr($key, 0, 4)) ?></span><strong><?= $money($amount) ?></strong></div><div class="finance-track" aria-hidden="true"><span style="width: <?= (int) round(100 * $amount / $maximum) ?>%"></span></div></li>
    <?php endforeach; ?></ul>
</section>
<section class="card finance-section"><h2>Próximas pendências a conferir</h2>
    <p class="text-muted">Até cinco despesas com parcelas em aberto, começando pelos vencimentos mais antigos.</p>
    <?php if (!$pending): ?><p>Nenhuma pendência de rateio encontrada.</p><?php else: ?>
    <div class="finance-table-wrap" tabindex="0" role="region" aria-label="Despesas com parcelas pendentes"><table><thead><tr><th scope="col">Despesa</th><th scope="col">Vencimento</th><th scope="col">Pendente</th><th scope="col">Consultar</th></tr></thead><tbody>
    <?php foreach (array_slice($pending, 0, 5) as $expense): ?><tr><td><?= $e($expense['title']) ?></td><td><?= $e($date($expense['due_date'])) ?><?php if ($expense['due_date'] && $expense['due_date'] < date('Y-m-d')): ?> <span class="finance-badge">Vencida</span><?php endif; ?></td><td><?= $money($expense['pending']) ?></td><td><a class="finance-link" href="/despesas/detalhes?id=<?= (int) $expense['id'] ?>">Ver detalhes<span class="finance-sr"> de <?= $e($expense['title']) ?></span></a></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/end.php'; ?>
