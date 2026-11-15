<?php $financeStyles = true; require dirname(__DIR__) . '/partials/header.php'; ?>
<a class="finance-skip" href="#finance-main">Pular para o conteúdo</a>
<div class="app-layout">
<?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
<main id="finance-main" class="main-content finance" tabindex="-1">
    <header class="page-header finance-heading">
        <div>
            <p class="eyebrow">Finanças da república</p>
            <h1><?= $e($title) ?></h1>
            <p class="text-muted"><?= $e($republic['name']) ?></p>
        </div>
        <form method="get" class="finance-filter">
            <div class="form-group">
                <label for="month">Mês de referência</label>
                <input type="month" id="month" name="month" value="<?= $e($month) ?>" min="2000-01" max="2100-12" required>
            </div>
            <button class="button button-primary" type="submit">Aplicar</button>
        </form>
    </header>
    <?php if ($notice): ?><div class="alert alert-success" role="status"><?= $e($notice) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Confira o lançamento</strong><ul><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
