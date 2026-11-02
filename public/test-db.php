<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    $file =
        $baseDir
        . str_replace('\\', '/', $relativeClass)
        . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;

try {

    $pdo = Database::connection();

    $query = $pdo->query(
        'SELECT COUNT(*) AS total
         FROM expense_categories'
    );

    $result = $query->fetch();

    echo '<h1>RepubliCAIXA</h1>';

    echo '<p>Banco conectado com sucesso.</p>';

    echo '<p>Categorias cadastradas: '
        . (int) $result['total']
        . '</p>';

} catch (Throwable $exception) {

    echo '<h1>Erro de conexão</h1>';

    echo '<pre>'
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '</pre>';
}