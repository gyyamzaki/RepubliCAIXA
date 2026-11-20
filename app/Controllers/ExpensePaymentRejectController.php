<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use InvalidArgumentException;
use PDO;
use Throwable;

final class ExpensePaymentRejectController
{
    public function reject(): void
    {
        // VERIFICAR LOGIN
        $usuario = Session::get('user');

        if (
            !is_array($usuario) ||
            empty($usuario['id'])
        ) {
            header('Location: /login');
            exit;
        }

        // VALIDAR TOKEN CSRF
        $token = Session::get('csrf_despesas');
        $recebido = $_POST['_csrf'] ?? null;

        if (
            !is_string($token) ||
            !is_string($recebido) ||
            !hash_equals($token, $recebido)
        ) {
            http_response_code(403);
            exit('Operação não autorizada.');
        }

        // IDENTIFICAR PAGAMENTO
        $pagamentoId = filter_var(
            $_POST['payment_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$pagamentoId) {
            http_response_code(400);
            exit('Pagamento inválido.');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // LOCALIZAR O PAGAMENTO
            $consulta = $db->prepare(
                'SELECT
                    p.id,
                    s.expense_id,
                    e.republic_id
                 FROM payments p
                 INNER JOIN expense_shares s
                    ON s.id = p.expense_share_id
                 INNER JOIN expenses e
                    ON e.id = s.expense_id
                 WHERE p.id = :pagamento
                 LIMIT 1'
            );

            $consulta->execute([
                'pagamento' => $pagamentoId
            ]);

            $pagamento = $consulta->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$pagamento) {
                throw new InvalidArgumentException(
                    'Pagamento não encontrado.'
                );
            }

            $despesaId = (int) $pagamento['expense_id'];
            $republicaId = (int) $pagamento['republic_id'];

            // BLOQUEAR A DESPESA DURANTE A OPERAÇÃO
            $consulta = $db->prepare(
                'SELECT id
                 FROM expenses
                 WHERE id = :id
                 FOR UPDATE'
            );

            $consulta->execute([
                'id' => $despesaId
            ]);

            if (!$consulta->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Despesa não encontrada.'
                );
            }

            // VERIFICAR PERMISSÃO DE ADMINISTRADOR
            $consulta = $db->prepare(
                "SELECT rm.id
                 FROM republic_members rm
                 INNER JOIN republics r
                    ON r.id = rm.republic_id
                 INNER JOIN users u
                    ON u.id = rm.user_id
                 WHERE rm.republic_id = :republica
                   AND rm.user_id = :usuario
                   AND rm.role = 'admin'
                   AND rm.active = 1
                   AND r.active = 1
                   AND u.active = 1
                 LIMIT 1"
            );

            $consulta->execute([
                'republica' => $republicaId,
                'usuario' => (int) $usuario['id']
            ]);

            if (!$consulta->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Somente administradores podem rejeitar pagamentos.'
                );
            }

            // VERIFICAR O STATUS ATUAL
            $consulta = $db->prepare(
                'SELECT status
                 FROM payments
                 WHERE id = :id
                 FOR UPDATE'
            );

            $consulta->execute([
                'id' => $pagamentoId
            ]);

            $status = $consulta->fetchColumn();

            if ($status !== 'pending') {
                throw new InvalidArgumentException(
                    'Somente pagamentos pendentes podem ser rejeitados.'
                );
            }

            // REJEITAR PAGAMENTO
            $consulta = $db->prepare(
                "UPDATE payments
                 SET status = 'rejected'
                 WHERE id = :id
                   AND status = 'pending'"
            );

            $consulta->execute([
                'id' => $pagamentoId
            ]);

            if ($consulta->rowCount() !== 1) {
                throw new InvalidArgumentException(
                    'O pagamento não está mais pendente.'
                );
            }

            // CONFIRMAR ALTERAÇÃO NO MYSQL
            $db->commit();

            header(
                'Location: /despesas/detalhes?id='
                . $despesaId
                . '&pagamento=rejeitado',
                true,
                303
            );

            exit;

        } catch (InvalidArgumentException $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            http_response_code(422);

            header('Content-Type: text/plain; charset=utf-8');

            exit($erro->getMessage());

        } catch (Throwable $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log((string) $erro);

            http_response_code(500);

            exit('Erro interno ao rejeitar pagamento.');
        }
    }
}
