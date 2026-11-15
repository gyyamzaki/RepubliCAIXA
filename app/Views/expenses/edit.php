
<?php

$pageTitle = 'Editar despesa - RepubliCAIXA';
$currentPage = 'expenses';

$esc = static function ($valor): string {
    return htmlspecialchars(
        is_scalar($valor) ? (string) $valor : '',
        ENT_QUOTES,
        'UTF-8'
    );
};

// Identifica o tipo de rateio salvo
$tipoAtual = $dados['split_type'] ?? 'equal';

if (!in_array(
    $tipoAtual,
    ['equal', 'percentage', 'custom'],
    true
)) {
    $tipoAtual = 'equal';
}

// Recupera os valores de cada morador
$valoresAtuais = is_array($valoresAtuais ?? null)
    ? $valoresAtuais
    : [];

require __DIR__ . '/../partials/header.php';

?>

<style>
    .expense-edit {
        max-width: 1050px;
    }

    .expense-edit .edit-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .expense-edit .edit-card {
        margin-top: 22px;
    }

    .expense-edit .edit-card h2 {
        margin-bottom: 18px;
    }

    .expense-edit .edit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .expense-edit .edit-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .expense-edit .edit-field.full {
        grid-column: 1 / -1;
    }

    .expense-edit .edit-field label,
    .expense-edit .edit-select-label {
        font-weight: 600;
    }

    .expense-edit .edit-field input,
    .expense-edit .edit-field select,
    .expense-edit .edit-field textarea,
    .expense-edit .edit-type select {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        color: #1e293b;
        font: inherit;
    }

    .expense-edit .edit-type {
        margin: 20px 0;
    }

    .expense-edit .edit-type label {
        display: block;
        margin-bottom: 8px;
    }

    .expense-edit .edit-member {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .expense-edit .edit-member .member-check {
        width: 18px;
        height: 18px;
    }

    .expense-edit .edit-member label {
        flex: 1;
        min-width: 150px;
    }

    .expense-edit .edit-member .member-value {
        width: 160px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font: inherit;
    }

    .expense-edit .edit-member .member-result {
        min-width: 110px;
        text-align: right;
        font-weight: 600;
        color: #0f766e;
    }

    .expense-edit .edit-feedback {
        margin-top: 16px;
        font-weight: 600;
        color: #0f766e;
    }

    .expense-edit .edit-feedback.error {
        color: #dc2626;
    }

    .expense-edit .edit-alert {
        margin-top: 18px;
        padding: 14px 16px;
        border-radius: 8px;
        color: #991b1b;
        background: #fef2f2;
    }

    .expense-edit .edit-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 24px;
    }

    .expense-edit .edit-save {
        padding: 12px 20px;
        border: 0;
        border-radius: 8px;
        background: #0f766e;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }

    .expense-edit .edit-save:disabled {
        background: #94a3b8;
        opacity: 0.75;
        cursor: not-allowed;
    }

    .expense-edit .edit-link {
        color: #0f766e;
        font-weight: 600;
        text-decoration: none;
    }

    @media (max-width: 650px) {
        .expense-edit .edit-grid {
            grid-template-columns: 1fr;
        }

        .expense-edit .edit-member .member-result {
            text-align: left;
        }
    }
</style>

<div class="app-layout">

    <!-- MENU LATERAL -->
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content expense-edit">

        <!-- CABEÇALHO -->
        <div class="page-header edit-head">

            <div>
                <h1>
                    Editar despesa #<?= (int) $despesa['id'] ?>
                </h1>

                <p class="text-muted">
                    Altere os dados e o tipo de rateio da despesa.
                </p>
            </div>

            <a class="edit-link" href="/despesas">
                Voltar para despesas
            </a>

        </div>

        <!-- AVISO DE PAGAMENTOS -->
        <?php if ($bloqueada): ?>

            <div class="edit-alert" role="alert">
                Esta despesa já possui pagamentos registrados.
                A edição está bloqueada para proteger
                os valores e comprovantes.
            </div>

        <?php endif; ?>

        <!-- ERROS -->
        <?php foreach ($erros as $erro): ?>

            <div class="edit-alert" role="alert">
                <?= $esc($erro) ?>
            </div>

        <?php endforeach; ?>

        <!-- FORMULÁRIO -->
        <form
            id="editExpenseForm"
            method="post"
            action="/despesas/editar?id=<?= (int) $despesa['id'] ?>"
        >

            <input
                type="hidden"
                name="_csrf"
                value="<?= $esc($csrf) ?>"
            >

            <fieldset
                style="border:0;padding:0;margin:0;min-width:0"
                <?= $bloqueada ? 'disabled' : '' ?>
            >

                <!-- INFORMAÇÕES DA DESPESA -->
                <section class="card edit-card">

                    <h2>Informações da despesa</h2>

                    <div class="edit-grid">

                        <div class="edit-field full">

                            <label for="edit-title">
                                Nome da despesa *
                            </label>

                            <input
                                id="edit-title"
                                name="title"
                                maxlength="150"
                                required
                                value="<?= $esc($dados['title'] ?? '') ?>"
                            >

                        </div>

                        <div class="edit-field">

                            <label for="edit-category">
                                Categoria *
                            </label>

                            <select
                                id="edit-category"
                                name="category_id"
                                required
                            >

                                <option value="">
                                    Selecione...
                                </option>

                                <?php foreach ($categorias as $categoria): ?>

                                    <option
                                        value="<?= (int) $categoria['id'] ?>"
                                        <?= (int) ($dados['category_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>
                                    >
                                        <?= $esc($categoria['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="edit-field">

                            <label for="edit-total">
                                Valor total (R$) *
                            </label>

                            <input
                                id="edit-total"
                                type="number"
                                name="total_amount"
                                step="0.01"
                                min="0.01"
                                max="99999999.99"
                                required
                                value="<?= $esc($dados['total_amount'] ?? '') ?>"
                            >

                        </div>

                        <div class="edit-field">

                            <label for="edit-date">
                                Data da despesa *
                            </label>

                            <input
                                id="edit-date"
                                type="date"
                                name="expense_date"
                                required
                                value="<?= $esc($dados['expense_date'] ?? '') ?>"
                            >

                        </div>

                        <div class="edit-field">

                            <label for="edit-due">
                                Vencimento
                            </label>

                            <input
                                id="edit-due"
                                type="date"
                                name="due_date"
                                value="<?= $esc($dados['due_date'] ?? '') ?>"
                            >

                        </div>

                        <div class="edit-field full">

                            <label for="edit-description">
                                Descrição (opcional)
                            </label>

                            <textarea
                                id="edit-description"
                                name="description"
                                rows="3"
                            ><?= $esc($dados['description'] ?? '') ?></textarea>

                        </div>

                    </div>

                </section>

                <!-- RATEIO -->
                <section class="card edit-card">

                    <h2>Rateio entre moradores</h2>

                    <p class="text-muted">
                        Escolha quem participa e como o valor será dividido.
                    </p>

                    <div class="edit-type">

                        <label
                            class="edit-select-label"
                            for="edit-split-type"
                        >
                            Tipo de rateio
                        </label>

                        <select
                            id="edit-split-type"
                            name="split_type"
                        >

                            <option
                                value="equal"
                                <?= $tipoAtual === 'equal' ? 'selected' : '' ?>
                            >
                                Igualitário
                            </option>

                            <option
                                value="percentage"
                                <?= $tipoAtual === 'percentage' ? 'selected' : '' ?>
                            >
                                Percentual
                            </option>

                            <option
                                value="custom"
                                <?= $tipoAtual === 'custom' ? 'selected' : '' ?>
                            >
                                Personalizado
                            </option>

                        </select>

                    </div>

                    <!-- MORADORES -->
                    <?php foreach ($moradores as $morador): ?>

                        <?php $moradorId = (int) $morador['id']; ?>

                        <div class="edit-member">

                            <input
                                class="member-check"
                                type="checkbox"
                                id="edit-member-<?= $moradorId ?>"
                                name="members[]"
                                value="<?= $moradorId ?>"
                                <?= in_array($moradorId, $selecionados, true) ? 'checked' : '' ?>
                            >

                            <label for="edit-member-<?= $moradorId ?>">
                                <?= $esc($morador['name']) ?>
                            </label>

                            <!-- VALOR OU PERCENTUAL -->
                            <input
                                class="member-value"
                                type="number"
                                step="0.01"
                                min="0"
                                name="values[<?= $moradorId ?>]"
                                aria-label="Valor ou porcentagem de <?= $esc($morador['name']) ?>"
                                value="<?= $esc($valoresAtuais[$moradorId] ?? '') ?>"
                            >

                            <!-- PRÉVIA -->
                            <span class="member-result">
                                —
                            </span>

                        </div>

                    <?php endforeach; ?>

                    <p
                        id="edit-feedback"
                        class="edit-feedback"
                        aria-live="polite"
                    ></p>

                </section>

            </fieldset>

            <!-- BOTÕES -->
            <div class="edit-actions">

                <button
                    id="edit-save"
                    type="submit"
                    class="edit-save"
                    disabled
                >
                    Salvar alterações
                </button>

                <a class="edit-link" href="/despesas">
                    Cancelar
                </a>

            </div>

        </form>

    </main>

</div>

<!-- JAVASCRIPT DO RATEIO -->
<script>

(() => {

    const bloqueada = <?= $bloqueada ? 'true' : 'false' ?>;

    const tipo = document.getElementById('edit-split-type');
    const valorTotal = document.getElementById('edit-total');

    const linhas = [
        ...document.querySelectorAll('.expense-edit .edit-member')
    ];

    const mensagem = document.getElementById('edit-feedback');
    const salvar = document.getElementById('edit-save');
    const formulario = document.getElementById('editExpenseForm');

    const moeda = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    // Converte valores para inteiros, evitando erros de centavos
    function unidades(texto, decimais, limiteInteiro) {

        const exp = new RegExp(
            '^\\d{1,' + limiteInteiro +
            '}(?:[.,]\\d{1,' + decimais + '})?$'
        );

        texto = String(texto).trim();

        if (!exp.test(texto)) {
            return null;
        }

        const partes = texto.replace(',', '.').split('.');
        const fator = 10 ** decimais;

        const valor =
            Number(partes[0]) * fator +
            Number(
                (partes[1] || '0').padEnd(decimais, '0')
            );

        return Number.isSafeInteger(valor)
            ? valor
            : null;
    }

    // FUNÇÃO PRINCIPAL
    function calcular() {

        const total = unidades(valorTotal.value, 2, 8);
        const modo = tipo.value;

        const ativos = linhas.filter(linha =>
            linha.querySelector('.member-check').checked
        );

        const partes = [];

        // CONFIGURAR CAMPOS
        linhas.forEach(linha => {

            const selecionada = linha.querySelector(
                '.member-check'
            ).checked;

            const campo = linha.querySelector('.member-value');

            campo.style.display =
                modo === 'equal' ? 'none' : '';

            campo.disabled =
                bloqueada || modo === 'equal' || !selecionada;

            campo.required =
                !bloqueada && modo !== 'equal' && selecionada;

            campo.placeholder =
                modo === 'percentage'
                    ? 'Porcentagem (%)'
                    : 'Valor (R$)';

            campo.max =
                modo === 'percentage'
                    ? '100'
                    : '99999999.99';

            linha.querySelector('.member-result')
                .textContent = '—';
        });

        function aviso(texto, valido = false) {

            mensagem.textContent = texto;

            mensagem.classList.toggle('error', !valido);

            salvar.disabled = bloqueada || !valido;
        }

        // VALIDAR INFORMAÇÕES
        if (bloqueada) {
            aviso('A edição está bloqueada porque existem pagamentos.');
            return;
        }

        if (total === null || total <= 0) {
            aviso('Informe um valor total válido.');
            return;
        }

        if (ativos.length === 0) {
            aviso('Selecione pelo menos um morador.');
            return;
        }

        if (total < ativos.length) {
            aviso('O valor é insuficiente para os moradores.');
            return;
        }

        // 1 - IGUALITÁRIO
        if (modo === 'equal') {

            const base = Math.floor(total / ativos.length);
            const resto = total % ativos.length;

            ativos.forEach((linha, indice) => {

                partes.push(
                    base + (indice < resto ? 1 : 0)
                );
            });

        // 2 - PERCENTUAL
        } else if (modo === 'percentage') {

            const porcentagens = ativos.map(linha => {

                return unidades(
                    linha.querySelector('.member-value').value,
                    2,
                    3
                );
            });

            if (porcentagens.some(
                p => p === null || p > 10000
            )) {
                aviso('Informe porcentagens válidas (até 100%).');
                return;
            }

            const soma = porcentagens.reduce(
                (a, b) => a + b,
                0
            );

            if (soma !== 10000) {
                aviso('As porcentagens devem somar 100%.');
                return;
            }

            const restos = [];
            let distribuido = 0;

            porcentagens.forEach((percentual, indice) => {

                const produto = total * percentual;

                const parte = Math.floor(
                    produto / 10000
                );

                partes.push(parte);

                restos.push({
                    indice: indice,
                    resto: produto % 10000
                });

                distribuido += parte;
            });

            restos.sort((a, b) =>
                b.resto - a.resto ||
                a.indice - b.indice
            );

            let faltam = total - distribuido;

            for (const item of restos) {

                if (faltam <= 0) {
                    break;
                }

                partes[item.indice]++;
                faltam--;
            }

        // 3 - PERSONALIZADO
        } else if (modo === 'custom') {

            for (const linha of ativos) {

                const valor = unidades(
                    linha.querySelector('.member-value').value,
                    2,
                    8
                );

                if (valor === null) {
                    aviso('Informe o valor de cada morador.');
                    return;
                }

                partes.push(valor);
            }

            const soma = partes.reduce(
                (a, b) => a + b,
                0
            );

            if (soma !== total) {
                aviso(
                    'Os valores devem somar ' +
                    moeda.format(total / 100) + '.'
                );
                return;
            }

        } else {
            aviso('Tipo de rateio inválido.');
            return;
        }

        // VERIFICAR VALORES POSITIVOS
        if (partes.some(parte => parte < 1)) {
            aviso('Cada morador deve pagar pelo menos R$ 0,01.');
            return;
        }

        // MOSTRAR RESULTADOS
        ativos.forEach((linha, indice) => {

            linha.querySelector('.member-result')
                .textContent = moeda.format(
                    partes[indice] / 100
                );
        });

        // HABILITAR BOTÃO
        aviso(
            'Rateio válido: ' +
            moeda.format(total / 100) +
            ' entre ' +
            ativos.length +
            ' morador(es).',
            true
        );
    }

    // EVENTOS
    tipo.addEventListener('change', function () {

        // Evita usar valores monetários como porcentagens
        linhas.forEach(linha => {
            linha.querySelector('.member-value').value = '';
        });

        calcular();
    });

    valorTotal.addEventListener('input', calcular);

    linhas.forEach(linha => {

        linha.querySelector('.member-check')
            .addEventListener('change', calcular);

        linha.querySelector('.member-value')
            .addEventListener('input', calcular);
    });

    // Verifica novamente antes de enviar
    formulario.addEventListener('submit', function (evento) {

        calcular();

        if (salvar.disabled) {
            evento.preventDefault();
        }
    });

    // Carrega o rateio atual
    calcular();

})();

</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
