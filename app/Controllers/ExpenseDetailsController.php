<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use PDO;

final class ExpenseDetailsController
{
    public function show(): void
    {
        // VERIFICAR LOGIN
        $usuario = Session::get('user');

        if (!is_array($usuario) || empty($usuario['id'])) {
            header('Location: /login');
            exit;
        }

        // IDENTIFICAR DESPESA
        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            http_response_code(404);
            exit('Despesa não encontrada.');
        }

        $db = Database::connection();

        // BUSCAR DESPESA E VERIFICAR REPÚBLICA
        $consulta = $db->prepare(
            'SELECT
                e.*,
                c.name AS category_name,
                u.name AS creator_name,
                r.name AS republic_name
             FROM expenses e
             INNER JOIN republics r
                ON r.id = e.republic_id
             INNER JOIN republic_members acesso
                ON acesso.republic_id = e.republic_id
               AND acesso.user_id = :usuario
               AND acesso.active = 1
             INNER JOIN users u
                ON u.id = e.created_by
             LEFT JOIN expense_categories c
                ON c.id = e.category_id
             WHERE e.id = :despesa
               AND r.active = 1
             LIMIT 1'
        );

        $consulta->execute([
            'usuario' => (int) $usuario['id'],
            'despesa' => $id
        ]);

        $despesa = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$despesa) {
            http_response_code(404);
            exit('Despesa não encontrada nesta república.');
        }

        // BUSCAR RATEIO E PAGAMENTOS
        $consulta = $db->prepare(
            "SELECT
                s.id AS share_id,
                s.member_id,
                s.amount_due,
                s.percentage,
                s.status AS share_status,
                u.name AS member_name,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'confirmed'
                            THEN p.amount
                            ELSE 0
                        END
                    ), 0
                ) AS confirmed_amount,

                COALESCE(
                    SUM(
                        CASE
                            WHEN p.status = 'pending'
                            THEN p.amount
                            ELSE 0
                        END
                    ), 0
                ) AS pending_amount

             FROM expense_shares s

             INNER JOIN republic_members rm
                ON rm.id = s.member_id

             INNER JOIN users u
                ON u.id = rm.user_id

             LEFT JOIN payments p
                ON p.expense_share_id = s.id

             WHERE s.expense_id = :despesa
               AND rm.republic_id = :republica

             GROUP BY
                s.id,
                s.member_id,
                s.amount_due,
                s.percentage,
                s.status,
                u.name

             ORDER BY u.name, s.id"
        );

        $consulta->execute([
            'despesa' => $id,
            'republica' => (int) $despesa['republic_id']
        ]);

        $rateios = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // CALCULAR QUANTO JÁ FOI PAGO
        $totalPago = 0.0;

        foreach ($rateios as &$parte) {

            $devido = (float) $parte['amount_due'];

            $confirmado = (float)
                $parte['confirmed_amount'];

            $pago = $parte['share_status'] === 'paid'
                ? $devido
                : min(
                    $devido,
                    max(0.0, $confirmado)
                );

            $parte['paid_amount'] = round($pago, 2);

            $parte['remaining_amount'] = round(
                max(0.0, $devido - $pago),
                2
            );

            $totalPago += $parte['paid_amount'];
        }

        unset($parte);

        $totalPago = round(
            min(
                (float) $despesa['total_amount'],
                $totalPago
            ),
            2
        );

        // BUSCAR HISTÓRICO DE PAGAMENTOS
        $consulta = $db->prepare(
            'SELECT
                p.id,
                p.expense_share_id,
                p.amount,
                p.payment_date,
                p.status,
                p.proof_path,
                u.name AS member_name

             FROM payments p

             INNER JOIN expense_shares s
                ON s.id = p.expense_share_id

             INNER JOIN republic_members rm
                ON rm.id = s.member_id

             INNER JOIN users u
                ON u.id = rm.user_id

             WHERE s.expense_id = :despesa
               AND rm.republic_id = :republica

             ORDER BY p.payment_date DESC, p.id DESC'
        );

        $consulta->execute([
            'despesa' => $id,
            'republica' => (int) $despesa['republic_id']
        ]);

        $pagamentos = $consulta->fetchAll(
            PDO::FETCH_ASSOC
        );

        // ABRIR TELA 7
        require dirname(__DIR__)
            . '/Views/expenses/show.php';
    }
}
