<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use Throwable;

final class FinanceService
{
    public function __construct(private PDO $db) {}

    public function context(int $userId): ?array
    {
        // Mesma república utilizada pelo módulo de despesas: primeira ativa por ID.
        $query = $this->db->prepare('SELECT r.id, r.name, rm.role, rm.id AS member_id
            FROM republic_members rm JOIN republics r ON r.id = rm.republic_id
            JOIN users u ON u.id = rm.user_id
            WHERE rm.user_id = :user AND rm.active = 1 AND r.active = 1 AND u.active = 1
            ORDER BY r.id LIMIT 1');
        $query->execute(['user' => $userId]);
        return $query->fetch() ?: null;
    }

    public function cashSummary(int $republic, string $start, string $end): array
    {
        $query = $this->db->prepare("SELECT
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) balance,
            COALESCE(SUM(CASE WHEN transaction_date < :opening THEN CASE WHEN type = 'income' THEN amount ELSE -amount END ELSE 0 END), 0) opening,
            COALESCE(SUM(CASE WHEN transaction_date >= :income_start AND transaction_date < :income_end AND type = 'income' THEN amount ELSE 0 END), 0) income,
            COALESCE(SUM(CASE WHEN transaction_date >= :expense_start AND transaction_date < :expense_end AND type = 'expense' THEN amount ELSE 0 END), 0) expense
            FROM cash_transactions WHERE republic_id = :republic");
        $query->execute(['opening' => $start, 'income_start' => $start, 'income_end' => $end,
            'expense_start' => $start, 'expense_end' => $end, 'republic' => $republic]);
        $result = array_map(fn($value) => FinanceInput::cents((string) $value), $query->fetch());
        $result['closing'] = $result['opening'] + $result['income'] - $result['expense'];
        return $result;
    }

    public function cashRows(int $republic, string $start, string $end): array
    {
        $query = $this->db->prepare('SELECT c.id, c.type, c.amount, c.description, c.transaction_date, u.name AS author
            FROM cash_transactions c JOIN users u ON u.id = c.created_by
            WHERE c.republic_id = :republic AND c.transaction_date >= :start AND c.transaction_date < :end
            ORDER BY c.transaction_date, c.id');
        $query->execute(['republic' => $republic, 'start' => $start, 'end' => $end]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            $row['cents'] = FinanceInput::cents($row['amount']);
        }
        return $rows;
    }

    public function addCash(int $republic, int $userId, array $movement): int
    {
        $this->db->beginTransaction();
        try {
            // Serializa os lançamentos da república, inclusive quando o caixa está vazio.
            $lock = $this->db->prepare('SELECT id FROM republics WHERE id = :id AND active = 1 FOR UPDATE');
            $lock->execute(['id' => $republic]);
            if (!$lock->fetchColumn()) {
                throw new InvalidArgumentException('República indisponível.');
            }
            $permission = $this->db->prepare("SELECT rm.id FROM republic_members rm JOIN users u ON u.id = rm.user_id
                WHERE rm.republic_id = :republic AND rm.user_id = :user AND rm.active = 1
                AND rm.role = 'admin' AND u.active = 1 FOR UPDATE");
            $permission->execute(['republic' => $republic, 'user' => $userId]);
            if (!$permission->fetchColumn()) {
                throw new InvalidArgumentException('Apenas administradores ativos podem movimentar a reserva.');
            }
            $insert = $this->db->prepare('INSERT INTO cash_transactions
                (republic_id, created_by, type, amount, description, transaction_date)
                VALUES (:republic, :user, :type, :amount, :description, :day)');
            $insert->execute(['republic' => $republic, 'user' => $userId, 'type' => $movement['type'],
                'amount' => FinanceInput::decimal($movement['amount']), 'description' => $movement['description'], 'day' => $movement['day']]);
            $id = (int) $this->db->lastInsertId();

            // Uma saída retroativa também não pode tornar negativo um saldo histórico.
            $history = $this->db->prepare('SELECT type, amount FROM cash_transactions
                WHERE republic_id = :republic ORDER BY transaction_date, id FOR UPDATE');
            $history->execute(['republic' => $republic]);
            $balance = 0;
            while ($row = $history->fetch()) {
                $amount = FinanceInput::cents($row['amount']);
                $balance += $row['type'] === 'income' ? $amount : -$amount;
                if ($balance < 0) {
                    throw new InvalidArgumentException('Saldo insuficiente. O movimento deixaria a reserva negativa nessa data ou em uma data posterior.');
                }
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function expenses(int $republic, ?string $start = null, ?string $end = null): array
    {
        // Agrega pagamentos por parcela antes de juntar às despesas: evita multiplicar totais.
        // status=paid preserva a compatibilidade com parcelas quitadas pela base recebida.
        $sql = "SELECT e.id, e.title, e.expense_date, e.due_date, e.total_amount,
            COALESCE(c.name, 'Sem categoria') AS category,
            COALESCE(SUM(LEAST(s.amount_due, GREATEST(0,
                CASE WHEN s.status = 'paid' THEN s.amount_due ELSE COALESCE(p.confirmed, 0) END))), 0) AS paid
            FROM expenses e LEFT JOIN expense_categories c ON c.id = e.category_id
            LEFT JOIN expense_shares s ON s.expense_id = e.id
            LEFT JOIN (SELECT expense_share_id, SUM(amount) confirmed FROM payments
                WHERE status = 'confirmed' GROUP BY expense_share_id) p ON p.expense_share_id = s.id
            WHERE e.republic_id = :republic";
        $params = ['republic' => $republic];
        if ($start !== null && $end !== null) {
            $sql .= ' AND e.expense_date >= :start AND e.expense_date < :end';
            $params += ['start' => $start, 'end' => $end];
        }
        $sql .= ' GROUP BY e.id, e.title, e.expense_date, e.due_date, e.total_amount, c.name ORDER BY e.expense_date, e.id';
        $query = $this->db->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            $row['total'] = FinanceInput::cents($row['total_amount']);
            $row['paid'] = min($row['total'], max(0, FinanceInput::cents($row['paid'])));
            $row['pending'] = max(0, $row['total'] - $row['paid']);
        }
        return $rows;
    }

    public function residentTotals(int $republic, string $start, string $end): array
    {
        $query = $this->db->prepare("SELECT u.name, rm.id AS member_id,
            SUM(s.amount_due) total,
            SUM(LEAST(s.amount_due, GREATEST(0, CASE WHEN s.status = 'paid' THEN s.amount_due ELSE COALESCE(p.confirmed, 0) END))) paid
            FROM expense_shares s JOIN expenses e ON e.id = s.expense_id
            JOIN republic_members rm ON rm.id = s.member_id JOIN users u ON u.id = rm.user_id
            LEFT JOIN (SELECT expense_share_id, SUM(amount) confirmed FROM payments
                WHERE status = 'confirmed' GROUP BY expense_share_id) p ON p.expense_share_id = s.id
            WHERE e.republic_id = :republic AND rm.republic_id = :membership_republic
            AND e.expense_date >= :start AND e.expense_date < :end
            GROUP BY rm.id, u.name ORDER BY u.name, rm.id");
        $query->execute(['republic' => $republic, 'membership_republic' => $republic, 'start' => $start, 'end' => $end]);
        $rows = $query->fetchAll();
        foreach ($rows as &$row) {
            $row['total'] = FinanceInput::cents($row['total']);
            $row['paid'] = FinanceInput::cents($row['paid']);
            $row['pending'] = $row['total'] - $row['paid'];
        }
        return $rows;
    }
}
