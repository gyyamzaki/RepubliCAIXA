<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Services\ExpenseRateio;
use InvalidArgumentException;
use PDO;
use Throwable;

final class ExpenseEditController
{
    public function edit(): void
    {
        // VERIFICAR LOGIN
        $usuario = Session::get('user');

        if (!is_array($usuario) || empty($usuario['id'])) {
            header('Location: /login');
            exit;
        }

        // IDENTIFICAR A DESPESA
        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            http_response_code(404);
            exit('Despesa não encontrada.');
        }

        $db = Database::connection();

        // VERIFICAR A REPÚBLICA DO USUÁRIO
        $consulta = $db->prepare(
            'SELECT e.*, rm.role
             FROM expenses e
             JOIN republic_members rm
               ON rm.republic_id = e.republic_id
             JOIN republics r
               ON r.id = e.republic_id
             WHERE e.id = :id
               AND rm.user_id = :usuario
               AND rm.active = 1
               AND r.active = 1
             LIMIT 1'
        );

        $consulta->execute([
            'id' => $id,
            'usuario' => (int) $usuario['id']
        ]);

        $despesa = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$despesa) {
            http_response_code(404);
            exit('Despesa não encontrada nesta república.');
        }

        // VERIFICAR PERMISSÃO DE EDIÇÃO
        if (
            (int) $despesa['created_by'] !==
                (int) $usuario['id']
            &&
            $despesa['role'] !== 'admin'
        ) {
            http_response_code(403);
            exit('Você não tem permissão para editar.');
        }

        // BUSCAR CATEGORIAS
        $categorias = $db->query(
            'SELECT id, name
             FROM expense_categories
             WHERE active = 1
             ORDER BY name'
        )->fetchAll(PDO::FETCH_ASSOC);

        // BUSCAR MORADORES
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
            'republica' => (int) $despesa['republic_id']
        ]);

        $moradores = $consulta->fetchAll(
            PDO::FETCH_ASSOC
        );

        // BUSCAR RATEIO ATUAL
        $consulta = $db->prepare(
            'SELECT member_id, amount_due, percentage, status
             FROM expense_shares
             WHERE expense_id = :id
             ORDER BY member_id'
        );

        $consulta->execute(['id' => $id]);

        $partesAtuais = $consulta->fetchAll(
            PDO::FETCH_ASSOC
        );

        // PREPARAR CAMPOS DO FORMULÁRIO
        $dados = $despesa;
        $selecionados = [];
        $valoresAtuais = [];

        foreach ($partesAtuais as $parte) {

            $moradorId = (int) $parte['member_id'];

            $selecionados[] = $moradorId;

            if ($despesa['split_type'] === 'percentage') {

                $valoresAtuais[$moradorId] =
                    $parte['percentage'];

            } elseif ($despesa['split_type'] === 'custom') {

                $valoresAtuais[$moradorId] =
                    $parte['amount_due'];
            }
        }

        // VERIFICAR SE EXISTEM PAGAMENTOS
        $consulta = $db->prepare(
            'SELECT COUNT(*)
             FROM payments p
             JOIN expense_shares s
               ON s.id = p.expense_share_id
             WHERE s.expense_id = :id'
        );

        $consulta->execute(['id' => $id]);

        $temPagamentos =
            (int) $consulta->fetchColumn() > 0;

        $temPartePaga = false;

        foreach ($partesAtuais as $parte) {

            if ($parte['status'] === 'paid') {
                $temPartePaga = true;
                break;
            }
        }

        $bloqueada = $temPagamentos || $temPartePaga;

        // TOKEN DE SEGURANÇA
        $csrf = Session::get('csrf_despesas');

        if (!is_string($csrf) || $csrf === '') {

            $csrf = bin2hex(random_bytes(32));

            Session::set('csrf_despesas', $csrf);
        }

        $erros = [];

        // PROCESSAR ALTERAÇÕES
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (
                !is_string($_POST['_csrf'] ?? null)
                ||
                !hash_equals($csrf, $_POST['_csrf'])
            ) {
                http_response_code(403);
                exit('Formulário inválido.');
            }

            $dados = $_POST;

            $valoresAtuais = is_array(
                $_POST['values'] ?? null
            ) ? $_POST['values'] : [];

            try {

                // BLOQUEAR EDIÇÃO COM PAGAMENTOS
                if ($bloqueada) {

                    throw new InvalidArgumentException(
                        'Esta despesa possui pagamentos e não pode ser editada.'
                    );
                }

                // MORADORES SELECIONADOS
                $lista = $_POST['members'] ?? [];

                if (!is_array($lista)) {
                    throw new InvalidArgumentException(
                        'Moradores inválidos.'
                    );
                }

                $selecionados = array_values(
                    array_unique(
                        array_map('intval', $lista)
                    )
                );

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

                $tipo = (string) (
                    $_POST['split_type'] ?? 'equal'
                );

                // VALIDAR NOME
                $tamanho = preg_match_all(
                    '/./us',
                    $titulo
                );

                if (
                    $tamanho === false ||
                    $tamanho < 2 ||
                    $tamanho > 150
                ) {
                    throw new InvalidArgumentException(
                        'Informe um nome de 2 a 150 caracteres.'
                    );
                }

                if (strlen($descricao) > 5000) {
                    throw new InvalidArgumentException(
                        'Descrição muito longa.'
                    );
                }

                // CONVERTER VALOR
                $centavos = ExpenseRateio::centavos(
                    $valorTexto
                );

                if ($centavos <= 0) {
                    throw new InvalidArgumentException(
                        'O valor deve ser maior que zero.'
                    );
                }

                // VALIDAR CATEGORIA
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

                // VALIDAR DATAS
                foreach (
                    [$data, $vencimento]
                    as $indice => $campo
                ) {

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

                // VALIDAR MORADORES
                $permitidos = array_map(
                    'intval',
                    array_column($moradores, 'id')
                );

                if (
                    !$selecionados ||
                    array_diff($selecionados, $permitidos)
                ) {
                    throw new InvalidArgumentException(
                        'Selecione moradores válidos.'
                    );
                }

                // VALIDAR TIPO DE RATEIO
                if (!in_array(
                    $tipo,
                    ['equal', 'percentage', 'custom'],
                    true
                )) {
                    throw new InvalidArgumentException(
                        'Tipo de rateio inválido.'
                    );
                }

                // VALIDAR VALORES DOS MORADORES
                if ($tipo !== 'equal') {

                    foreach ($selecionados as $moradorId) {

                        if (
                            !isset($valoresAtuais[$moradorId])
                            ||
                            !is_scalar(
                                $valoresAtuais[$moradorId]
                            )
                        ) {
                            throw new InvalidArgumentException(
                                'Preencha os valores do rateio.'
                            );
                        }
                    }
                }

                // RECALCULAR RATEIO
                $partes = ExpenseRateio::calcular(
                    $tipo,
                    $centavos,
                    $selecionados,
                    $valoresAtuais
                );

                // INICIAR TRANSAÇÃO
                $db->beginTransaction();

                // BLOQUEAR A DESPESA DURANTE A EDIÇÃO
                $consulta = $db->prepare(
                    'SELECT id
                     FROM expenses
                     WHERE id = :id
                       AND republic_id = :republica
                     FOR UPDATE'
                );

                $consulta->execute([
                    'id' => $id,
                    'republica' =>
                        (int) $despesa['republic_id']
                ]);

                if (!$consulta->fetchColumn()) {
                    throw new InvalidArgumentException(
                        'Despesa não encontrada.'
                    );
                }

                // CONFERIR AS PARTES DO RATEIO
                $consulta = $db->prepare(
                    'SELECT id, status
                     FROM expense_shares
                     WHERE expense_id = :id
                     FOR UPDATE'
                );

                $consulta->execute(['id' => $id]);

                $partesBloqueadas = $consulta->fetchAll(
                    PDO::FETCH_ASSOC
                );

                foreach ($partesBloqueadas as $parte) {

                    if ($parte['status'] === 'paid') {
                        throw new InvalidArgumentException(
                            'Não é possível editar uma despesa com pagamentos.'
                        );
                    }
                }

                // CONFERIR PAGAMENTOS NOVAMENTE
                $consulta = $db->prepare(
                    'SELECT COUNT(*)
                     FROM payments p
                     JOIN expense_shares s
                       ON s.id = p.expense_share_id
                     WHERE s.expense_id = :id'
                );

                $consulta->execute(['id' => $id]);

                if (
                    (int) $consulta->fetchColumn() > 0
                ) {
                    throw new InvalidArgumentException(
                        'Não é possível editar uma despesa com pagamentos.'
                    );
                }

                // ATUALIZAR A DESPESA
                $consulta = $db->prepare(
                    'UPDATE expenses
                     SET title = :titulo,
                         description = :descricao,
                         category_id = :categoria,
                         total_amount = :valor,
                         expense_date = :data,
                         due_date = :vencimento,
                         split_type = :tipo
                     WHERE id = :id'
                );

                $consulta->execute([
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'categoria' => $categoria,

                    'valor' => number_format(
                        $centavos / 100,
                        2,
                        '.',
                        ''
                    ),

                    'data' => $data,

                    'vencimento' => $vencimento !== ''
                        ? $vencimento
                        : null,

                    'tipo' => $tipo,
                    'id' => $id
                ]);

                // REMOVER RATEIO ANTERIOR
                $consulta = $db->prepare(
                    'DELETE FROM expense_shares
                     WHERE expense_id = :id'
                );

                $consulta->execute(['id' => $id]);

                // SALVAR NOVO RATEIO
                $inserir = $db->prepare(
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

                    $inserir->execute([
                        'despesa' => $id,
                        'morador' => (int) $moradorId,

                        'valor' => number_format(
                            $parte['centavos'] / 100,
                            2,
                            '.',
                            ''
                        ),

                        'percentual' =>
                            $parte['percentual']
                    ]);
                }

                // CONFIRMAR ALTERAÇÕES
                $db->commit();

                header(
                    'Location: /despesas',
                    true,
                    303
                );

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
                    'Não foi possível atualizar a despesa.';
            }
        }

        // ABRIR O FORMULÁRIO DE EDIÇÃO
        require dirname(__DIR__)
            . '/Views/expenses/edit.php';
    }
}
