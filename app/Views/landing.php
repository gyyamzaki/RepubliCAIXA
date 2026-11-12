<?php

use App\Core\Session;

$pageTitle = 'RepubliCAIXA - Gestão financeira para repúblicas';
$user = Session::get('user');

require __DIR__ . '/partials/header.php';
?>

<div class="landing-page">
    <header class="landing-header">
        <div class="landing-shell landing-nav">
            <a class="landing-brand" href="/" aria-label="RepubliCAIXA - Página inicial">
                <span class="brand-mark">R$</span>
                <span>RepubliCAIXA</span>
            </a>

            <nav class="landing-links" aria-label="Navegação da página inicial">
                <a href="#recursos">Recursos</a>
                <a href="#como-funciona">Como funciona</a>
            </nav>

            <div class="landing-actions">
                <?php if (is_array($user)): ?>
                    <a class="button button-primary" href="/dashboard">
                        Ir para o painel
                    </a>
                <?php else: ?>
                    <a class="button button-ghost" href="/login">
                        Entrar
                    </a>

                    <a class="button button-primary" href="/cadastro">
                        Criar conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <section class="landing-hero">
            <div class="landing-shell hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Finanças compartilhadas, sem confusão</p>

                    <h1>
                        Organize as contas da república em um só lugar.
                    </h1>

                    <p class="hero-text">
                        O RepubliCAIXA ajuda moradores a dividir despesas,
                        acompanhar pagamentos e manter o caixa da casa transparente
                        sem depender de planilhas ou mensagens perdidas.
                    </p>

                    <div class="hero-actions">
                        <?php if (is_array($user)): ?>
                            <a class="button button-primary button-large" href="/dashboard">
                                Acessar meu painel
                            </a>
                        <?php else: ?>
                            <a class="button button-primary button-large" href="/cadastro">
                                Começar agora
                            </a>

                            <a class="button button-secondary button-large" href="/login">
                                Já tenho conta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="hero-preview" aria-label="Exemplo de visão financeira da república">
                    <div class="preview-topline">
                        <span>República</span>
                        <span class="status-pill">Organizada</span>
                    </div>

                    <strong class="preview-title">Resumo do mês</strong>

                    <div class="preview-balance">
                        <span>Saldo da casa</span>
                        <strong>R$ 1.480,00</strong>
                    </div>

                    <div class="preview-list">
                        <div>
                            <span>Energia</span>
                            <strong>R$ 327,40</strong>
                        </div>
                        <div>
                            <span>Internet</span>
                            <strong>R$ 129,90</strong>
                        </div>
                        <div>
                            <span>Mercado</span>
                            <strong>R$ 468,20</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section" id="recursos">
            <div class="landing-shell">
                <div class="section-intro">
                    <p class="eyebrow">O que o sistema resolve</p>
                    <h2>Menos cobrança, mais transparência.</h2>
                    <p class="text-muted">
                        A proposta é reunir as tarefas financeiras mais comuns de uma moradia estudantil em uma interface simples e responsiva.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card">
                        <span class="feature-number">01</span>
                        <h3>Despesas e rateio</h3>
                        <p>Cadastre contas e divida os valores entre os moradores de forma igualitária ou personalizada.</p>
                    </article>

                    <article class="feature-card">
                        <span class="feature-number">02</span>
                        <h3>Caixa transparente</h3>
                        <p>Acompanhe entradas, saídas e o fundo de reserva da casa sem perder o histórico.</p>
                    </article>

                    <article class="feature-card">
                        <span class="feature-number">03</span>
                        <h3>Pagamentos e vencimentos</h3>
                        <p>Veja quem já pagou, o que ainda está pendente e quais contas estão próximas do vencimento.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-soft" id="como-funciona">
            <div class="landing-shell">
                <div class="section-intro">
                    <p class="eyebrow">Como funciona</p>
                    <h2>Três passos para começar.</h2>
                </div>

                <div class="steps-grid">
                    <article class="step-item">
                        <span>1</span>
                        <div>
                            <h3>Crie sua conta</h3>
                            <p>Faça o cadastro e entre com seu usuário.</p>
                        </div>
                    </article>

                    <article class="step-item">
                        <span>2</span>
                        <div>
                            <h3>Crie ou entre em uma república</h3>
                            <p>Quem cria recebe um código para convidar os outros moradores.</p>
                        </div>
                    </article>

                    <article class="step-item">
                        <span>3</span>
                        <div>
                            <h3>Organizem a casa juntos</h3>
                            <p>Despesas, pagamentos e caixa ficam disponíveis para acompanhamento no painel.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-cta">
            <div class="landing-shell landing-cta-box">
                <div>
                    <p class="eyebrow eyebrow-light">RepubliCAIXA</p>
                    <h2>Transforme o caos das contas em organização.</h2>
                </div>

                <?php if (is_array($user)): ?>
                    <a class="button button-light" href="/dashboard">Abrir painel</a>
                <?php else: ?>
                    <a class="button button-light" href="/cadastro">Criar minha conta</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-shell">
            <strong>RepubliCAIXA</strong>
            <span>Projeto acadêmico · UNESP · 2026</span>
        </div>
    </footer>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
