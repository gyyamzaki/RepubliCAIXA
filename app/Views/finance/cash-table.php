<?php if (!$cashRows): ?><p class="text-muted">Nenhum movimento de reserva neste mês.</p><?php else: ?>
<div class="finance-table-wrap" tabindex="0" role="region" aria-label="Extrato de entradas e saídas da reserva">
<table><caption class="finance-sr">Movimentos em ordem de data e número do lançamento.</caption><thead><tr><th scope="col">Nº / Data</th><th scope="col">Descrição</th><th scope="col">Tipo</th><th scope="col">Valor</th><th scope="col">Saldo após movimento</th><th scope="col">Registrado por</th></tr></thead><tbody>
<?php foreach ($cashRows as $row): ?><tr><td>#<?= (int) $row['id'] ?><br><?= $e($date($row['transaction_date'])) ?></td><td><?= $e($row['description']) ?></td><td><?= $row['type'] === 'income' ? 'Entrada' : 'Saída' ?></td><td class="<?= $row['type'] === 'income' ? 'finance-income' : 'finance-expense' ?>"><?= $money($row['cents']) ?></td><td><?= $money($row['balance']) ?></td><td><?= $e($row['author']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
