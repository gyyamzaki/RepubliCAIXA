<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Services\ExpenseRateio;
use InvalidArgumentException;
use PDO;
use Throwable;

final class ExpensePaymentController
{
    private function usuarioId(): int
    {
        $usuario = Session::get('user');

        if (!is_array($usuario) || empty($usuario['id'])) {
            header('Location: /login');
            exit;
        }

        return (int) $usuario['id'];
    }

    private function validarToken(): void
    {
        $esperado = Session::get('csrf_despesas');
        $recebido = $_POST['_csrf'] ?? null;

        if (
            !is_string($esperado) ||
            !is_string($recebido) ||
            !hash_equals($esperado, $recebido)
        ) {
            http_response_code(403);
            exit(
                'Formulário inválido. Atualize a página e tente novamente.'
            );
        }
    }

    private function erro(
        string $mensagem,
        int $codigo = 422
    ): void {
        http_response_code($codigo);

        echo '<!doctype html>';
        echo '<html lang="pt-BR">';
        echo '<meta charset="UTF-8">';
        echo '<title>Pagamento</title>';

        echo '<body style="font-family:Arial;padding:30px">';
        echo '<h2>Não foi possível concluir a operação</h2>';

        echo '<p>';
        echo htmlspecialchars(
            $mensagem,
            ENT_QUOTES,
            'UTF-8'
        );
        echo '</p>';

        echo '<p><a href="/despesas">';
        echo 'Voltar às despesas';
        echo '</a></p></body></html>';

        exit;
    }

    // REGISTRAR PAGAMENTO
    public function store(): void
    {
        $usuarioId = $this->usuarioId();
        $this->validarToken();

        $shareId = filter_var(
            $_POST['share_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$shareId || $shareId < 1) {
            $this->erro('Selecione um morador válido.');
        }

        try {
            $centavos = ExpenseRateio::centavos(
                (string) ($_POST['amount'] ?? '')
            );

            if ($centavos < 1) {
                throw new InvalidArgumentException(
                    'O pagamento deve ser maior que zero.'
                );
            }
        } catch (InvalidArgumentException $erro) {
            $this->erro($erro->getMessage());
        }

        $db = Database::connection();

        $comprovante = null;
        $arquivoFisico = null;

        try {
            $db->beginTransaction();

            // Verifica se o usuário pode registrar
            // pagamento nesta parte da despesa.
            $consulta = $db->prepare(
                "SELECT
                    s.id,
                    s.expense_id,
                    s.amount_due,
                    s.status,
                    e.republic_id,
                    rm.role
                 FROM expense_shares s
                 JOIN expenses e
                    ON e.id = s.expense_id
                 JOIN republics r
                    ON r.id = e.republic_id
                 JOIN republic_members rm
                    ON rm.republic_id = e.republic_id
                 WHERE s.id = :parte
                   AND rm.user_id = :usuario
                   AND rm.active = 1
                   AND r.active = 1
                   AND (
                       rm.role = 'admin'
                       OR rm.id = s.member_id
                   )
                 LIMIT 1"
            );

            $consulta->execute([
                'parte' => $shareId,
                'usuario' => $usuarioId
            ]);

            $parte = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$parte) {
                throw new InvalidArgumentException(
                    'Você não pode registrar este pagamento.'
                );
            }

            // Protege a despesa durante o pagamento.
            $trava = $db->prepare(
                'SELECT id
                 FROM expenses
                 WHERE id = :id
                 FOR UPDATE'
            );

            $trava->execute([
                'id' => (int) $parte['expense_id']
            ]);

            $trava->fetchColumn();

            $trava = $db->prepare(
                'SELECT amount_due, status
                 FROM expense_shares
                 WHERE id = :id
                 FOR UPDATE'
            );

            $trava->execute(['id' => $shareId]);

            $parteAtual = $trava->fetch(PDO::FETCH_ASSOC);

            if (
                !$parteAtual ||
                $parteAtual['status'] === 'paid'
            ) {
                throw new InvalidArgumentException(
                    'Esta parte já está paga ou não existe.'
                );
            }

            // Soma pagamentos pendentes e confirmados.
            $consulta = $db->prepare(
                "SELECT COALESCE(SUM(amount), 0)
                 FROM payments
                 WHERE expense_share_id = :id
                   AND status IN (
                       'pending',
                       'confirmed'
                   )"
            );

            $consulta->execute(['id' => $shareId]);

            $comprometidos = ExpenseRateio::centavos(
                (string) $consulta->fetchColumn()
            );

            $devido = ExpenseRateio::centavos(
                (string) $parteAtual['amount_due']
            );

            $disponivel = $devido - $comprometidos;

            if ($centavos > $disponivel) {
                throw new InvalidArgumentException(
                    'O valor excede o saldo disponível, considerando pagamentos pendentes.'
                );
            }

            // COMPROVANTE OPCIONAL
            if (isset($_FILES['proof'])) {
                $upload = $_FILES['proof'];

                if (
                    !is_array($upload) ||
                    is_array($upload['error'] ?? null)
                ) {
                    throw new InvalidArgumentException(
                        'Arquivo de comprovante inválido.'
                    );
                }

                if ($upload['error'] !== UPLOAD_ERR_NO_FILE) {

                    if ($upload['error'] !== UPLOAD_ERR_OK) {
                        throw new InvalidArgumentException(
                            'Falha ao enviar o comprovante.'
                        );
                    }

                    if (
                        ($upload['size'] ?? 0) < 1 ||
                        $upload['size'] > 5 * 1024 * 1024
                    ) {
                        throw new InvalidArgumentException(
                            'O comprovante deve ter até 5 MB.'
                        );
                    }

                    $temporario = (string) (
                        $upload['tmp_name'] ?? ''
                    );

                    if (!is_uploaded_file($temporario)) {
                        throw new InvalidArgumentException(
                            'Comprovante inválido.'
                        );
                    }

                    // Somente PDF, JPG e PNG.
                    $tipos = [
                        'application/pdf' => 'pdf',
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png'
                    ];

                    $detector = new \finfo(
                        FILEINFO_MIME_TYPE
                    );

                    $mime = $detector->file($temporario);

                    if (!isset($tipos[$mime])) {
                        throw new InvalidArgumentException(
                            'Envie um PDF, JPG ou PNG.'
                        );
                    }

                    // Pasta fora da área pública do site.
                    $pasta = dirname(__DIR__, 2)
                        . '/storage/payment-proofs';

                    if (
                        !is_dir($pasta) &&
                        !mkdir($pasta, 0750, true) &&
                        !is_dir($pasta)
                    ) {
                        throw new InvalidArgumentException(
                            'Não foi possível criar a pasta de comprovantes.'
                        );
                    }

                    $nome = bin2hex(random_bytes(16))
                        . '.'
                        . $tipos[$mime];

                    $arquivoFisico = $pasta
                        . '/'
                        . $nome;

                    if (!move_uploaded_file(
                        $temporario,
                        $arquivoFisico
                    )) {
                        throw new InvalidArgumentException(
                            'Não foi possível salvar o comprovante.'
                        );
                    }

                    $comprovante = $nome;
                }
            }

            // REGISTRAR NO MYSQL
            $consulta = $db->prepare(
                "INSERT INTO payments
                    (
                        expense_share_id,
                        amount,
                        proof_path,
                        status
                    )
                 VALUES
                    (
                        :parte,
                        :valor,
                        :comprovante,
                        'pending'
                    )"
            );

            $consulta->execute([
                'parte' => $shareId,

                'valor' => number_format(
                    $centavos / 100,
                    2,
                    '.',
                    ''
                ),

                'comprovante' => $comprovante
            ]);

            $db->commit();

            header(
                'Location: /despesas/detalhes?id='
                . (int) $parte['expense_id']
                . '&pagamento=registrado',
                true,
                303
            );

            exit;

        } catch (Throwable $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if (
                $arquivoFisico !== null &&
                is_file($arquivoFisico)
            ) {
                unlink($arquivoFisico);
            }

            if ($erro instanceof InvalidArgumentException) {
                $this->erro($erro->getMessage());
            }

            error_log((string) $erro);

            $this->erro(
                'Erro interno ao registrar pagamento.',
                500
            );
        }
    }

    // CONFIRMAR PAGAMENTO
    public function confirm(): void
    {
        $usuarioId = $this->usuarioId();
        $this->validarToken();

        $paymentId = filter_var(
            $_POST['payment_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$paymentId || $paymentId < 1) {
            $this->erro('Pagamento inválido.');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Somente administrador da república.
            $consulta = $db->prepare(
                "SELECT
                    p.id,
                    p.expense_share_id,
                    s.expense_id
                 FROM payments p
                 JOIN expense_shares s
                    ON s.id = p.expense_share_id
                 JOIN expenses e
                    ON e.id = s.expense_id
                 JOIN republics r
                    ON r.id = e.republic_id
                 JOIN republic_members rm
                    ON rm.republic_id = e.republic_id
                 WHERE p.id = :pagamento
                   AND rm.user_id = :usuario
                   AND rm.active = 1
                   AND rm.role = 'admin'
                   AND r.active = 1
                 LIMIT 1"
            );

            $consulta->execute([
                'pagamento' => $paymentId,
                'usuario' => $usuarioId
            ]);

            $alvo = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$alvo) {
                throw new InvalidArgumentException(
                    'Somente um administrador pode confirmar este pagamento.'
                );
            }

            $trava = $db->prepare(
                'SELECT id
                 FROM expenses
                 WHERE id = :id
                 FOR UPDATE'
            );

            $trava->execute([
                'id' => (int) $alvo['expense_id']
            ]);

            $trava->fetchColumn();

            $trava = $db->prepare(
                'SELECT amount_due
                 FROM expense_shares
                 WHERE id = :id
                 FOR UPDATE'
            );

            $trava->execute([
                'id' => (int) $alvo['expense_share_id']
            ]);

            $devidoTexto = $trava->fetchColumn();

            if ($devidoTexto === false) {
                throw new InvalidArgumentException(
                    'Parte da despesa não encontrada.'
                );
            }

            // Verifica se ainda está pendente.
            $consulta = $db->prepare(
                'SELECT status
                 FROM payments
                 WHERE id = :id
                 FOR UPDATE'
            );

            $consulta->execute(['id' => $paymentId]);

            if ($consulta->fetchColumn() !== 'pending') {
                throw new InvalidArgumentException(
                    'Este pagamento não está aguardando confirmação.'
                );
            }

            // Confirma o pagamento.
            $consulta = $db->prepare(
                "UPDATE payments
                 SET status = 'confirmed'
                 WHERE id = :id"
            );

            $consulta->execute(['id' => $paymentId]);

            // Calcula o total confirmado.
            $consulta = $db->prepare(
                "SELECT COALESCE(SUM(amount), 0)
                 FROM payments
                 WHERE expense_share_id = :parte
                   AND status = 'confirmed'"
            );

            $consulta->execute([
                'parte' => (int) $alvo['expense_share_id']
            ]);

            $totalPago = ExpenseRateio::centavos(
                (string) $consulta->fetchColumn()
            );

            $valorDevido = ExpenseRateio::centavos(
                (string) $devidoTexto
            );

            // Marca o rateio como pago quando quitado.
            if ($totalPago >= $valorDevido) {
                $consulta = $db->prepare(
                    "UPDATE expense_shares
                     SET status = 'paid',
                         paid_at = NOW()
                     WHERE id = :id"
                );

                $consulta->execute([
                    'id' => (int) $alvo['expense_share_id']
                ]);
            }

            $db->commit();

            header(
                'Location: /despesas/detalhes?id='
                . (int) $alvo['expense_id']
                . '&pagamento=confirmado',
                true,
                303
            );

            exit;

        } catch (Throwable $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($erro instanceof InvalidArgumentException) {
                $this->erro($erro->getMessage());
            }

            error_log((string) $erro);

            $this->erro(
                'Erro interno ao confirmar pagamento.',
                500
            );
        }
    }

    // BAIXAR COMPROVANTE COM PERMISSÃO
    public function proof(): void
    {
        $usuarioId = $this->usuarioId();

        $paymentId = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$paymentId || $paymentId < 1) {
            $this->erro(
                'Comprovante não encontrado.',
                404
            );
        }

        $db = Database::connection();

        $consulta = $db->prepare(
            'SELECT p.proof_path
             FROM payments p

             JOIN expense_shares s
                ON s.id = p.expense_share_id

             JOIN expenses e
                ON e.id = s.expense_id

             JOIN republics r
                ON r.id = e.republic_id

             JOIN republic_members rm
                ON rm.republic_id = e.republic_id

             WHERE p.id = :pagamento
               AND rm.user_id = :usuario
               AND rm.active = 1
               AND r.active = 1

             LIMIT 1'
        );

        $consulta->execute([
            'pagamento' => $paymentId,
            'usuario' => $usuarioId
        ]);

        $nome = $consulta->fetchColumn();

        if (
            !$nome ||
            !preg_match(
                '/^[a-f0-9]{32}\.(pdf|jpg|png)$/D',
                (string) $nome
            )
        ) {
            $this->erro(
                'Comprovante não encontrado.',
                404
            );
        }

        $arquivo = dirname(__DIR__, 2)
            . '/storage/payment-proofs/'
            . $nome;

        if (!is_file($arquivo)) {
            $this->erro(
                'Arquivo de comprovante indisponível.',
                404
            );
        }

        $extensao = pathinfo(
            $nome,
            PATHINFO_EXTENSION
        );

        $tipos = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        header('Content-Type: ' . $tipos[$extensao]);
        header('X-Content-Type-Options: nosniff');

        header(
            'Content-Disposition: attachment; filename="comprovante.'
            . $extensao
            . '"'
        );

        header('Cache-Control: private, no-store');
        header('Content-Length: ' . filesize($arquivo));

        readfile($arquivo);
        exit;
    }
}
