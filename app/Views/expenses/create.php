
<?php

$pageTitle = 'Nova despesa - RepubliCAIXA';
$currentPage = 'expenses';

// Protege os textos exibidos no HTML
$esc = static function ($valor): string {
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

// Dados recebidos do controller
$dados = $dados ?? [];
$erros = $erros ?? [];
$categorias = $categorias ?? [];
$moradores = $moradores ?? [];
$selecionados = $selecionados ?? [];

$tipoAtual = $dados['split_type'] ?? 'equal';

if (!in_array(
    $tipoAtual,
    ['equal', 'percentage', 'custom'],
    true
)) {
    $tipoAtual = 'equal';
}

$valoresAtuais = is_array($dados['values'] ?? null)
    ? $dados['values']
    : [];

require __DIR__ . '/../partials/header.php';

?>

<style>
    .expense-create {
        max-width: 1050px;
    }

    .expense-create-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .expense-create-card {
        margin-top: 22px;
    }

    .expense-create-card h2 {
        margin-bottom: 20px;
    }

    .expense-create-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .expense-create-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .expense-create-field.full {
        grid-column: 1 / -1;
    }

    .expense-create-field label {
        font-weight: 600;
    }

    .expense-create-field input,
    .expense-create-field select,
    .expense-create-field textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        color: #1e293b;
        font: inherit;
    }

    /* Área do rateio */
    .rateio-type {
        margin: 20px 0;
    }

    .rateio-type label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .rateio-type select {
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        font: inherit;
    }

    .expense-create-member {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .expense-participant {
        width: 18px;
        height: 18px;
    }

    .member-name {
        flex: 1;
        min-width: 140px;
    }

    .member-value {
        width: 160px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font: inherit;
    }

    .member-result {
        min-width: 110px;
        text-align: right;
        color: #0f766e;
        font-weight: 600;
    }

    .rateio-message {
        margin-top: 16px;
        font-weight: 600;
        color: #0f766e;
    }

    .rateio-message.error {
        color: #dc2626;
    }

    .rateio-note {
        margin-top: 12px;
        color: #64748b;
        font-size: 14px;
    }

    /* Botões */
    .expense-create-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 24px;
    }

    .expense-create-primary {
        background: #0f766e;
        border: 0;
        color: white;
        border-radius: 8px;
        padding: 12px 20px;
        font-weight: 600;
        cursor: pointer;
    }

    .expense-create-primary:hover {
        background: #115e59;
    }

    .expense-create-primary:disabled {
        background: #94a3b8;
        opacity: 0.7;
        cursor: not-allowed;
    }

    .expense-create-secondary {
        color: #0f766e;
        font-weight: 600;
        text-decoration: none;
    }

    .expense-create-notice {
        background: #fff1f2;
        color: #9f1239;
        padding: 12px 16px;
        border-radius: 8px;
        margin-top: 18px;
    }

    @media (max-width: 650px) {
        .expense-create-grid {
            grid-template-columns: 1fr;
        }

        .member-result {
            text-align: left;
        }
    }
</style>

<div class="app-layout">

    <!-- MENU LATERAL -->
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="main-content expense-create">

        <!-- CABEÇALHO -->
        <div class="page-header expense-create-header">

            <div>
                <h1>Nova despesa</h1>

                <p class="text-muted">
                    Cadastre uma conta e divida entre os moradores.
                </p>
            </div>

            <a
                href="/despesas"
                class="expense-create-secondary"
            >
                Voltar para despesas
            </a>

        </div>

        <!-- MENSAGENS DE ERRO DO PHP -->
        <?php foreach ($erros as $erro): ?>

            <div class="expense-create-notice" role="alert">
                <?= $esc($erro) ?>
            </div>

        <?php endforeach; ?>

        <!-- FORMULÁRIO DE CADASTRO -->
        <form
            action="/despesas/nova"
            method="post"
            id="expenseForm"
        >

            <input
                type="hidden"
                name="_csrf"
                value="<?= $esc($csrf) ?>"
            >

            <!-- INFORMAÇÕES DA DESPESA -->
            <section class="card expense-create-card">

                <h2>Informações da despesa</h2>

                <div class="expense-create-grid">

                    <!-- Nome -->
                    <div class="expense-create-field full">

                        <label for="expense-title">
                            Nome da despesa *
                        </label>

                        <input
                            id="expense-title"
                            name="title"
                            maxlength="150"
                            required
                            placeholder="Ex.: Conta de energia"
                            value="<?= $esc($dados['title'] ?? '') ?>"
                        >

                    </div>

                    <!-- Categoria -->
                    <div class="expense-create-field">

                        <label for="expense-category">
                            Categoria *
                        </label>

                        <select
                            id="expense-category"
                            name="category_id"
                            required
                        >

                            <option value="">
                                Selecione...
                            </option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= (int) $categoria['id'] ?>"
                                    <?= (string) ($dados['category_id'] ?? '') === (string) $categoria['id'] ? 'selected' : '' ?>
                                >
                                    <?= $esc($categoria['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Valor total -->
                    <div class="expense-create-field">

                        <label for="expense-total">
                            Valor total (R$) *
                        </label>

                        <input
                            id="expense-total"
                            type="number"
                            name="total_amount"
                            step="0.01"
                            min="0.01"
                            max="99999999.99"
                            required
                            placeholder="300.00"
                            value="<?= $esc($dados['total_amount'] ?? '') ?>"
                        >

                    </div>

                    <!-- Data -->
                    <div class="expense-create-field">

                        <label for="expense-date">
                            Data da despesa *
                        </label>

                        <input
                            id="expense-date"
                            type="date"
                            name="expense_date"
                            required
                            value="<?= $esc($dados['expense_date'] ?? date('Y-m-d')) ?>"
                        >

                    </div>

                    <!-- Vencimento -->
                    <div class="expense-create-field">

                        <label for="expense-due">
                            Vencimento
                        </label>

                        <input
                            id="expense-due"
                            type="date"
                            name="due_date"
                            value="<?= $esc($dados['due_date'] ?? '') ?>"
                        >

                    </div>

                    <!-- Descrição -->
                    <div class="expense-create-field full">

                        <label for="expense-description">
                            Descrição (opcional)
                        </label>

                        <textarea
                            id="expense-description"
                            name="description"
                            rows="3"
                            placeholder="Informações adicionais..."
                        ><?= $esc($dados['description'] ?? '') ?></textarea>

                    </div>

                </div>

            </section>

            <!-- RATEIO ENTRE MORADORES -->
            <section class="card expense-create-card">

                <h2>Rateio entre moradores</h2>

                <p class="text-muted">
                    Escolha como deseja dividir esta despesa.
                </p>

                <!-- Tipo de rateio -->
                <div class="rateio-type">

                    <label for="splitType">
                        Tipo de rateio
                    </label>

                    <select
                        id="splitType"
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

                <!-- Lista de moradores -->
                <div id="memberList">

                    <?php foreach ($moradores as $morador): ?>

                        <?php $id = (int) $morador['id']; ?>

                        <div class="expense-create-member">

                            <!-- Selecionar morador -->
                            <input
                                type="checkbox"
                                class="expense-participant"
                                id="member-<?= $id ?>"
                                name="members[]"
                                value="<?= $id ?>"
                                <?= in_array($id, $selecionados, true) ? 'checked' : '' ?>
                            >

                            <label
                                class="member-name"
                                for="member-<?= $id ?>"
                            >
                                <?= $esc($morador['name']) ?>
                            </label>

                            <!-- Percentual ou valor personalizado -->
                            <input
                                type="number"
                                class="member-value"
                                name="values[<?= $id ?>]"
                                step="0.01"
                                min="0"
                                placeholder="Valor"
                                aria-label="Rateio de <?= $esc($morador['name']) ?>"
                                value="<?= $esc($valoresAtuais[$id] ?? '') ?>"
                            >

                            <!-- Resultado -->
                            <span class="member-result">
                                —
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- Mensagem dos cálculos -->
                <p
                    id="rateioMessage"
                    class="rateio-message"
                    aria-live="polite"
                ></p>

                <p class="rateio-note">
                    O sistema valida os valores e distribui
                    os centavos entre os participantes.
                </p>

            </section>

            <!-- BOTÕES -->
            <div class="expense-create-actions">

                <button
                    type="submit"
                    id="saveExpense"
                    class="expense-create-primary"
                    disabled
                >
                    Salvar despesa
                </button>

                <a
                    href="/despesas"
                    class="expense-create-secondary"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </main>

</div>

<!-- JAVASCRIPT DOS TRÊS TIPOS DE RATEIO -->
<script>

    // Elementos da página
    const tipoRateio = document.getElementById('splitType');

    const valorTotal = document.getElementById('expense-total');

    const linhasRateio = [
        ...document.querySelectorAll('.expense-create-member')
    ];

    const mensagem = document.getElementById('rateioMessage');

    const salvar = document.getElementById('saveExpense');

    // Formatação em reais
    const moeda = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    // Converte um valor monetário para centavos
    function converterCentavos(texto) {

        texto = String(texto).trim().replace(',', '.');

        if (!/^\d{1,8}(\.\d{1,2})?$/.test(texto)) {
            return null;
        }

        const partes = texto.split('.');

        const inteiro = Number(partes[0]);

        const decimal = Number(
            (partes[1] || '0').padEnd(2, '0')
        );

        const resultado = inteiro * 100 + decimal;

        return Number.isSafeInteger(resultado)
            ? resultado
            : null;
    }

    // Converte porcentagens para centésimos de %
    function converterPercentual(texto) {

        texto = String(texto).trim().replace(',', '.');

        if (!/^\d{1,3}(\.\d{1,2})?$/.test(texto)) {
            return null;
        }

        const partes = texto.split('.');

        const inteiro = Number(partes[0]);

        const decimal = Number(
            (partes[1] || '0').padEnd(2, '0')
        );

        const percentual = inteiro * 100 + decimal;

        if (percentual > 10000) {
            return null;
        }

        return percentual;
    }

    // Mostra uma mensagem e bloqueia o salvamento
    function mostrarErro(texto) {

        mensagem.textContent = texto;

        mensagem.classList.add('error');

        salvar.disabled = true;
    }

    // Mostra uma mensagem de sucesso
    function mostrarSucesso(texto) {

        mensagem.textContent = texto;

        mensagem.classList.remove('error');

        salvar.disabled = false;
    }

    // FUNÇÃO PRINCIPAL DO RATEIO
    function calcularRateio() {

        const tipo = tipoRateio.value;

        const total = converterCentavos(valorTotal.value);

        // Seleciona somente os moradores marcados
        const ativos = linhasRateio.filter(linha => {

            return linha.querySelector(
                '.expense-participant'
            ).checked;

        });

        // Ajusta os campos conforme o tipo de rateio
        linhasRateio.forEach(linha => {

            const campo = linha.querySelector('.member-value');

            const selecionado = linha.querySelector(
                '.expense-participant'
            ).checked;

            campo.style.display =
                tipo === 'equal' ? 'none' : '';

            campo.disabled =
                tipo === 'equal' || !selecionado;

            campo.required =
                tipo !== 'equal' && selecionado;

            campo.placeholder =
                tipo === 'percentage'
                    ? 'Porcentagem (%)'
                    : 'Valor (R$)';

            campo.max = tipo === 'percentage'
                ? '100'
                : '99999999.99';

            // Limpa a prévia anterior
            linha.querySelector('.member-result')
                .textContent = '—';

        });

        // Valida o valor total
        if (total === null || total <= 0) {

            mostrarErro(
                'Informe o valor total da despesa.'
            );

            return;
        }

        // Valida a quantidade de moradores
        if (ativos.length === 0) {

            mostrarErro(
                'Selecione pelo menos um morador.'
            );

            return;
        }

        if (total < ativos.length) {

            mostrarErro(
                'O valor é insuficiente para os moradores.'
            );

            return;
        }

        const resultados = [];

        // ==================================
        // 1 - RATEIO IGUALITÁRIO
        // ==================================
        if (tipo === 'equal') {

            const base = Math.floor(
                total / ativos.length
            );

            const resto = total % ativos.length;

            ativos.forEach((linha, indice) => {

                const valor = base + (
                    indice < resto ? 1 : 0
                );

                resultados.push(valor);

            });

        // ==================================
        // 2 - RATEIO PERCENTUAL
        // ==================================
        } else if (tipo === 'percentage') {

            const percentuais = [];

            // Recebe os percentuais informados
            for (const linha of ativos) {

                const campo = linha.querySelector(
                    '.member-value'
                );

                const percentual = converterPercentual(
                    campo.value
                );

                if (percentual === null) {

                    mostrarErro(
                        'Informe porcentagens válidas.'
                    );

                    return;
                }

                percentuais.push(percentual);
            }

            // Soma os percentuais
            const soma = percentuais.reduce(
                (a, b) => a + b,
                0
            );

            // A soma precisa ser 100%
            if (soma !== 10000) {

                mostrarErro(
                    'As porcentagens devem somar 100%.'
                );

                return;
            }

            // Calcula as partes em centavos
            const restos = [];

            let distribuido = 0;

            percentuais.forEach((percentual, indice) => {

                const produto = total * percentual;

                const parte = Math.floor(
                    produto / 10000
                );

                resultados.push(parte);

                restos.push({
                    indice: indice,
                    resto: produto % 10000
                });

                distribuido += parte;
            });

            // Distribui os centavos restantes
            restos.sort((a, b) => {

                return b.resto - a.resto ||
                    a.indice - b.indice;

            });

            let faltam = total - distribuido;

            for (const item of restos) {

                if (faltam <= 0) {
                    break;
                }

                resultados[item.indice]++;

                faltam--;
            }

        // ==================================
        // 3 - RATEIO PERSONALIZADO
        // ==================================
        } else if (tipo === 'custom') {

            let soma = 0;

            for (const linha of ativos) {

                const campo = linha.querySelector(
                    '.member-value'
                );

                const valor = converterCentavos(
                    campo.value
                );

                if (valor === null) {

                    mostrarErro(
                        'Informe o valor de cada morador.'
                    );

                    return;
                }

                resultados.push(valor);

                soma += valor;
            }

            // O total precisa ser igual ao da despesa
            if (soma !== total) {

                mostrarErro(
                    'Os valores devem somar ' +
                    moeda.format(total / 100) + '.'
                );

                return;
            }

        } else {

            mostrarErro('Tipo de rateio inválido.');

            return;
        }

        // Nenhum morador pode pagar R$ 0,00
        if (resultados.some(valor => valor < 1)) {

            mostrarErro(
                'Cada morador deve pagar pelo menos R$ 0,01.'
            );

            return;
        }

        // Mostra o valor calculado para cada morador
        ativos.forEach((linha, indice) => {

            const resultado = linha.querySelector(
                '.member-result'
            );

            resultado.textContent = moeda.format(
                resultados[indice] / 100
            );

        });

        // HABILITA O BOTÃO SALVAR
        mostrarSucesso(
            'Rateio válido: ' +
            moeda.format(total / 100) +
            ' distribuídos entre ' +
            ativos.length +
            ' morador(es).'
        );
    }

    // Atualiza quando o usuário muda o tipo de rateio
    tipoRateio.addEventListener('change', function () {

        // Evita reaproveitar porcentagens como valores em reais
        linhasRateio.forEach(linha => {

            linha.querySelector('.member-value').value = '';

        });

        calcularRateio();
    });

    // Atualiza quando o valor total muda
    valorTotal.addEventListener(
        'input',
        calcularRateio
    );

    // Atualiza quando os moradores ou valores mudam
    linhasRateio.forEach(linha => {

        const checkbox = linha.querySelector(
            '.expense-participant'
        );

        const campo = linha.querySelector('.member-value');

        checkbox.addEventListener(
            'change',
            calcularRateio
        );

        campo.addEventListener(
            'input',
            calcularRateio
        );

    });

    // Calcula ao abrir o formulário
    calcularRateio();

</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
