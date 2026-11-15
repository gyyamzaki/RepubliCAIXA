<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Services\FinanceInput;
use App\Services\FinanceService;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class FinanceController
{
    private FinanceService $service;
    private array $user;
    private ?array $republic;

    public function handle(string $action): void
    {
        $user = Session::get('user');
        if (!is_array($user) || empty($user['id'])) {
            header('Location: /login', true, 302);
            return;
        }
        $this->user = $user;
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        try {
            $this->service = new FinanceService(Database::connection());
            $this->republic = $this->service->context((int) $user['id']);
            if ($this->republic === null) {
                $this->error('Sua conta não tem vínculo ativo com uma república. Peça ao administrador do grupo para conferir seu cadastro.', 403);
                return;
            }
            if ($action === 'store') {
                $this->store();
            } elseif ($action === 'export') {
                $this->export();
            } else {
                $this->screen($action);
            }
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage(), 422);
        } catch (Throwable $error) {
            error_log('[Financeiro] ' . (string) $error);
            $this->error('Não foi possível concluir a operação. Tente novamente. Se o problema persistir, avise o responsável pelo sistema.', 500);
        }
    }

    private function period(): array
    {
        return FinanceInput::month($_GET['month'] ?? date('Y-m'));
    }

    private function screen(string $screen, array $errors = [], array $old = []): void
    {
        [$month, $start, $end] = $this->period();
        $republic = $this->republic;
        $id = (int) $republic['id'];
        $cash = $this->service->cashSummary($id, $start, $end);
        $expenses = $this->service->expenses($id, $start, $end);
        $totals = ['total' => array_sum(array_column($expenses, 'total')), 'paid' => array_sum(array_column($expenses, 'paid')),
            'pending' => array_sum(array_column($expenses, 'pending'))];
        $categories = [];
        foreach ($expenses as $expense) {
            $categories[$expense['category']] = ($categories[$expense['category']] ?? 0) + $expense['total'];
        }
        arsort($categories);
        $cashRows = [];
        $residents = [];
        $pending = [];
        $history = [];
        if ($screen === 'dashboard') {
            $all = $this->service->expenses($id);
            $pending = array_values(array_filter($all, fn($expense) => $expense['pending'] > 0));
            usort($pending, fn($a, $b) => ($a['due_date'] ?? '9999-12-31') <=> ($b['due_date'] ?? '9999-12-31'));
            $allPending = array_sum(array_column($pending, 'pending'));
            $cursor = new DateTimeImmutable($start);
            for ($i = 5; $i >= 0; $i--) {
                $history[$cursor->modify("-$i months")->format('Y-m')] = 0;
            }
            foreach ($all as $expense) {
                $key = substr($expense['expense_date'], 0, 7);
                if (array_key_exists($key, $history)) {
                    $history[$key] += $expense['total'];
                }
            }
        } else {
            $cashRows = $this->service->cashRows($id, $start, $end);
            $running = $cash['opening'];
            foreach ($cashRows as &$cashRow) {
                $running += $cashRow['type'] === 'income' ? $cashRow['cents'] : -$cashRow['cents'];
                $cashRow['balance'] = $running;
            }
            unset($cashRow);
            if ($screen === 'reports') {
                $residents = $this->service->residentTotals($id, $start, $end);
            }
        }
        if (!Session::has('finance_csrf')) {
            Session::set('finance_csrf', bin2hex(random_bytes(32)));
        }
        if (!Session::has('finance_submission')) {
            Session::set('finance_submission', bin2hex(random_bytes(32)));
        }
        $csrf = Session::get('finance_csrf');
        $submission = Session::get('finance_submission');
        $currentPage = ['dashboard' => 'dashboard', 'cash' => 'cash', 'reports' => 'reports'][$screen];
        $title = ['dashboard' => 'Dashboard', 'cash' => 'Caixa da reserva', 'reports' => 'Relatórios'][$screen];
        $pageTitle = $title . ' - RepubliCAIXA';
        $user = $this->user;
        $isAdmin = $republic['role'] === 'admin';
        $notice = Session::get('finance_notice');
        Session::remove('finance_notice');
        $e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $money = [FinanceInput::class, 'money'];
        $date = static fn($day) => $day ? implode('/', array_reverse(explode('-', $day))) : 'Sem vencimento';
        require dirname(__DIR__) . '/Views/finance/' . $screen . '.php';
    }

    private function store(): void
    {
        if ($this->republic['role'] !== 'admin') {
            $this->error('Apenas administradores podem registrar movimentos na reserva.', 403);
            return;
        }
        $token = $_POST['_csrf'] ?? null;
        if (!is_string($token) || !Session::has('finance_csrf') || !hash_equals(Session::get('finance_csrf'), $token)) {
            $this->error('Formulário expirado ou inválido. Abra o Caixa novamente e tente outra vez.', 403);
            return;
        }
        $submission = $_POST['_submission'] ?? null;
        if (!is_string($submission) || !Session::has('finance_submission') || !hash_equals(Session::get('finance_submission'), $submission)) {
            $this->error('Este formulário já foi utilizado ou ficou desatualizado. Confira o histórico antes de fazer outro lançamento.', 409);
            return;
        }
        try {
            $movement = FinanceInput::transaction($_POST);
            $this->service->addCash((int) $this->republic['id'], (int) $this->user['id'], $movement);
        } catch (InvalidArgumentException $error) {
            http_response_code(422);
            $old = array_filter($_POST, 'is_string');
            $this->screen('cash', [$error->getMessage()], $old);
            return;
        }
        Session::set('finance_submission', bin2hex(random_bytes(32)));
        Session::set('finance_notice', 'Movimento registrado na reserva.');
        header('Location: /caixa?month=' . substr($movement['day'], 0, 7), true, 303);
    }

    private function export(): void
    {
        [$month, $start, $end] = $this->period();
        $type = FinanceInput::text($_GET['tipo'] ?? '');
        if (!in_array($type, ['caixa', 'despesas'], true)) {
            throw new InvalidArgumentException('Selecione um relatório válido.');
        }
        $id = (int) $this->republic['id'];
        $rows = $type === 'caixa' ? $this->service->cashRows($id, $start, $end) : $this->service->expenses($id, $start, $end);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="republicaixa-' . $type . '-' . $month . '.csv"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        $write = static function (array $row) use ($output): void {
            fputcsv($output, array_map(fn($cell) => FinanceInput::csvText((string) $cell), $row), ';', '"', '');
        };
        if ($type === 'caixa') {
            $write(['Data', 'Tipo', 'Descrição', 'Valor (R$)', 'Registrado por']);
            foreach ($rows as $row) {
                $write([$row['transaction_date'], $row['type'] === 'income' ? 'Entrada' : 'Saída', $row['description'],
                    str_replace('.', ',', FinanceInput::decimal($row['cents'])), $row['author']]);
            }
        } else {
            $write(['Data da despesa', 'Vencimento', 'Título', 'Categoria', 'Total (R$)', 'Quitado das parcelas (R$)', 'Pendente (R$)']);
            foreach ($rows as $row) {
                $write([$row['expense_date'], $row['due_date'] ?? '', $row['title'], $row['category'],
                    str_replace('.', ',', FinanceInput::decimal($row['total'])), str_replace('.', ',', FinanceInput::decimal($row['paid'])),
                    str_replace('.', ',', FinanceInput::decimal($row['pending']))]);
            }
        }
        fclose($output);
    }

    private function error(string $message, int $status): void
    {
        http_response_code($status);
        $pageTitle = 'Aviso - RepubliCAIXA';
        require dirname(__DIR__) . '/Views/finance/error.php';
    }
}
