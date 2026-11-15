<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Services\ExpenseRateio;
use InvalidArgumentException;
use PDO;
use Throwable;

final class ExpenseController
{
    // Identifica o usuário e sua república
    private function contexto(): array
    {
        $user = Session::get('user');

        if (!is_array($user) || empty($user['id'])) {
            header('Location: /login');
            exit;
        }

        $db = Database::connection();

        $consulta = $db->prepare(
            'SELECT r.id, r.name
             FROM republic_members rm
             JOIN republics r
               ON r.id = rm.republic_id
             WHERE rm.user_id = :usuario
               AND rm.active = 1
               AND r.active = 1
             ORDER BY r.id
             LIMIT 1'
        );

        $consulta->execute([
            'usuario' => (int) $user['id']
        ]);

        $republica = $consulta->fetch() ?: null;

        return [$db, $user, $republica];
    }

    // TELA 5 - LISTAR DESPESAS
    public function index(): void
    {
        [$db, $user, $republica] = $this->contexto();

        $despesas = [];

        if ($republica !== null) {

            $consulta = $db->prepare(
                "SELECT
                    e.id,
                    e.title,
                    e.total_amount,
                    e.expense_date,
                    e.due_date,
                    c.name AS category_name,

                    COALESCE(SUM(
                        CASE
                            WHEN sh.id IS NULL THEN 0
                            ELSE GREATEST(
                                0,
                                LEAST(
                                    sh.amount_due,
                                    CASE
                                        WHEN sh.status = 'paid'
                                        THEN sh.amount_due
                                        ELSE COALESCE(
                                            p.confirmed_amount, 0
                                        )
                                    END
                                )
                            )
                        END
                    ), 0) AS paid_amount

                FROM expenses e

                LEFT JOIN expense_categories c
                    ON c.id = e.category_id

                LEFT JOIN expense_shares sh
                    ON sh.expense_id = e.id

                LEFT JOIN (
                    SELECT
                        expense_share_id,
                        SUM(amount) AS confirmed_amount
                    FROM payments
                    WHERE status = 'confirmed'
                    GROUP BY expense_share_id
                ) p ON p.expense_share_id = sh.id

                WHERE e.republic_id = :republica

                GROUP BY
                    e.id,
                    e.title,
                    e.total_amount,
                    e.expense_date,
                    e.due_date,
                    c.name

                ORDER BY e.expense_date DESC, e.id DESC"
            );

            $consulta->execute([
                'republica' => (int) $republica['id']
            ]);

            $despesas = $consulta->fetchAll();
        }

        require dirname(__DIR__)
            . '/Views/expenses/index.php';
    }

    // Busca categorias e moradores
    private function prepararFormulario(
        PDO $db,
        int $republicaId
    ): array {

        $categorias = $db->query(
            'SELECT id, name
             FROM expense_categories
             WHERE active = 1
             ORDER BY name'
        )->fetchAll();

        $consulta = $db->prepare(
            'SELECT rm.id, u.name
             FROM republic_members rm
             JOIN users u ON u.id = rm.user_id
             WHERE rm.republic_id = :republica
               AND rm.active = 1
               AND u.active = 1
             ORDER BY u.name'
        );

        $consulta->execute([
            'republica' => $republicaId
        ]);

        $moradores = $consulta->fetchAll();

        return [$categorias, $moradores];
    }

    // Token de segurança
    private function token(): string
    {
        $token = Session::get('csrf_despesas');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_despesas', $token);
        }

        return $token;
    }

    // TELA 6 - FORMULÁRIO DE CADASTRO
    public function create(): void
    {
        [$db, $user, $republica] = $this->contexto();

        if ($republica === null) {
            header('Location: /despesas');
            exit;
        }

        [$categorias, $moradores] =
            $this->prepararFormulario(
                $db,
                (int) $republica['id']
            );

        $dados = [];

        $selecionados = array_map(
            'intval',
            array_column($moradores, 'id')
        );

        $erros = [];
        $csrf = $this->token();

        require dirname(__DIR__)
            . '/Views/expenses/create.php';
    }

    // SALVAR DESPESA COM OS 3 TIPOS DE RATEIO
    public function store(): void
    {
        [$db, $user, $republica] = $this->contexto();

        if ($republica === null) {
            header('Location: /despesas');
            exit;
        }

        [$categorias, $moradores] =
            $this->prepararFormulario(
                $db,
                (int) $republica['id']
            );

        $csrf = $this->token();
        $dados = $_POST;
        $erros = [];

        $selecionados = array_values(array_unique(
            array_map(
                'intval',
                (array) ($_POST['members'] ?? [])
            )
        ));

        // Verifica o token do formulário
        if (
            !is_string($_POST['_csrf'] ?? null) ||
            !hash_equals($csrf, $_POST['_csrf'])
        ) {
            http_response_code(403);
            exit('Formulário inválido.');
        }

        try {

            // DADOS DA DESPESA
            $titulo = trim(
                (string) ($_POST['title'] ?? '')
            );

            $descricao = trim(
                (string) ($_POST['description'] ?? '')
            );

            $categoria = (int) (
                $_POST['category_id'] ?? 0
            );

            $valorTexto = (string) (
                $_POST['total_amount'] ?? ''
            );

            $data = (string) (
                $_POST['expense_date'] ?? ''
            );

            $vencimento = (string) (
                $_POST['due_date'] ?? ''
            );

            // Tipo escolhido no formulário
            $tipo = (string) (
                $_POST['split_type'] ?? 'equal'
            );

            $valores = $_POST['values'] ?? [];

            if (!is_array($valores)) {
                throw new InvalidArgumentException(
                    'Valores do rateio inválidos.'
                );
            }

            // VALIDAR O TÍTULO
            $tamanho = preg_match_all('/./us', $titulo);

            if ($tamanho === false ||
                $tamanho < 2 ||
                $tamanho > 150
            ) {
                throw new InvalidArgumentException(
                    'Informe um nome de 2 a 150 caracteres.'
                );
            }

            if (strlen($descricao) > 5000) {
                throw new InvalidArgumentException(
                    'A descrição está muito longa.'
                );
            }

            // VALIDAR O VALOR TOTAL
            $totalCentavos = ExpenseRateio::centavos(
                $valorTexto
            );

            if ($totalCentavos <= 0) {
                throw new InvalidArgumentException(
                    'O valor deve ser maior que zero.'
                );
            }

            // VALIDAR A CATEGORIA
            $categoriasValidas = array_map(
                'intval',
                array_column($categorias, 'id')
            );

            if (!in_array(
                $categoria,
                $categoriasValidas,
                true
            )) {
                throw new InvalidArgumentException(
                    'Escolha uma categoria válida.'
                );
            }

            // VALIDAR AS DATAS
            foreach ([$data, $vencimento] as $indice => $campo) {

                if ($indice === 1 && $campo === '') {
                    continue;
                }

                if (!preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/D',
                    $campo
                )) {
                    throw new InvalidArgumentException(
                        'Informe datas válidas.'
                    );
                }

                [$ano, $mes, $dia] = array_map(
                    'intval',
                    explode('-', $campo)
                );

                if (!checkdate($mes, $dia, $ano)) {
                    throw new InvalidArgumentException(
                        'Informe datas válidas.'
                    );
                }
            }

            // VALIDAR OS MORADORES
            $idsPermitidos = array_map(
                'intval',
                array_column($moradores, 'id')
            );

            if (
                !$selecionados ||
                array_diff($selecionados, $idsPermitidos)
            ) {
                throw new InvalidArgumentException(
                    'Selecione moradores válidos.'
                );
            }

            // VALIDAR O TIPO DE RATEIO
            if (!in_array(
                $tipo,
                ['equal', 'percentage', 'custom'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Tipo de rateio inválido.'
                );
            }

            // Verifica os campos de cada participante
            if ($tipo !== 'equal') {
                foreach ($selecionados as $moradorId) {
                    if (
                        !isset($valores[$moradorId]) ||
                        !is_scalar($valores[$moradorId])
                    ) {
                        throw new InvalidArgumentException(
                            'Preencha os valores do rateio.'
                        );
                    }
                }
            }

            // CALCULAR O RATEIO
            // Esta classe trata os três tipos
            $partes = ExpenseRateio::calcular(
                $tipo,
                $totalCentavos,
                $selecionados,
                $valores
            );

            // INICIAR A TRANSAÇÃO MYSQL
            $db->beginTransaction();

            // SALVAR A DESPESA
            $inserir = $db->prepare(
                'INSERT INTO expenses
                (
                    republic_id,
                    created_by,
                    category_id,
                    title,
                    description,
                    total_amount,
                    expense_date,
                    due_date,
                    split_type
                )
                VALUES
                (
                    :republica,
                    :usuario,
                    :categoria,
                    :titulo,
                    :descricao,
                    :valor,
                    :data,
                    :vencimento,
                    :tipo
                )'
            );

            $inserir->execute([
                'republica' => (int) $republica['id'],
                'usuario' => (int) $user['id'],
                'categoria' => $categoria,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'valor' => number_format(
                    $totalCentavos / 100, 2, '.', ''
                ),
                'data' => $data,
                'vencimento' => $vencimento !== ''
                    ? $vencimento
                    : null,
                'tipo' => $tipo
            ]);

            // ID DA DESPESA CRIADA
            $despesaId = (int) $db->lastInsertId();

            // SALVAR O RATEIO DOS MORADORES
            $inserirParte = $db->prepare(
                'INSERT INTO expense_shares
                (
                    expense_id,
                    member_id,
                    amount_due,
                    percentage
                )
                VALUES
                (
                    :despesa,
                    :morador,
                    :valor,
                    :percentual
                )'
            );

            foreach ($partes as $moradorId => $parte) {

                $inserirParte->execute([
                    'despesa' => $despesaId,
                    'morador' => (int) $moradorId,

                    'valor' => number_format(
                        $parte['centavos'] / 100,
                        2,
                        '.',
                        ''
                    ),

                    'percentual' => $parte['percentual']
                ]);
            }

            // CONFIRMAR O CADASTRO
            $db->commit();

            // Voltar para a Tela 5
            header('Location: /despesas', true, 303);
            exit;

        } catch (InvalidArgumentException $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $erros[] = $erro->getMessage();

        } catch (Throwable $erro) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log((string) $erro);

            $erros[] =
                'Não foi possível salvar a despesa.';
        }

        // Retorna ao formulário caso haja erro
        require dirname(__DIR__)
            . '/Views/expenses/create.php';
    }
}
