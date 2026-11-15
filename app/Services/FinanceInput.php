<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class FinanceInput
{
    public static function text(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Formato de campo inválido.');
        }
        return trim($value);
    }

    public static function month(mixed $value): array
    {
        $month = self::text($value);
        if (!preg_match('/\A(?:20\d{2}|2100)-(?:0[1-9]|1[0-2])\z/', $month)) {
            throw new InvalidArgumentException('Selecione um mês entre 2000 e 2100.');
        }
        $date = new DateTimeImmutable($month . '-01');
        return [$month, $date->format('Y-m-d'), $date->modify('+1 month')->format('Y-m-d')];
    }

    public static function transaction(array $input): array
    {
        $type = self::text($input['type'] ?? '');
        if (!in_array($type, ['income', 'expense'], true)) {
            throw new InvalidArgumentException('Selecione entrada ou saída.');
        }
        $amount = ExpenseRateio::centavos(self::text($input['amount'] ?? ''));
        if ($amount <= 0) {
            throw new InvalidArgumentException('Informe um valor maior que zero.');
        }
        $description = self::text($input['description'] ?? '');
        if (mb_strlen($description, 'UTF-8') < 3 || mb_strlen($description, 'UTF-8') > 255) {
            throw new InvalidArgumentException('A descrição deve ter entre 3 e 255 caracteres.');
        }
        $day = self::text($input['transaction_date'] ?? '');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day);
        if (!$date || $date->format('Y-m-d') !== $day || $day < '2000-01-01' || $day > date('Y-m-d')) {
            throw new InvalidArgumentException('Informe uma data válida, a partir de 2000 e até hoje. Registre apenas movimentos realizados.');
        }
        return compact('type', 'amount', 'description', 'day');
    }

    // Valores DECIMAL do banco são strings; todos os cálculos monetários usam inteiros.
    public static function cents(string $decimal): int
    {
        if (!preg_match('/\A(-?)(\d{1,14})(?:\.(\d{1,2}))?\z/', $decimal, $parts)) {
            throw new InvalidArgumentException('Valor monetário inválido no banco.');
        }
        $cents = (int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0');
        return ($parts[1] === '-' ? -1 : 1) * $cents;
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function money(int $cents): string
    {
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) intdiv(abs($cents), 100));
        return ($cents < 0 ? '- ' : '') . 'R$ ' . $integer . ',' . str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function csvText(string $text): string
    {
        // Evita fórmulas ao abrir texto controlado pelo usuário em planilhas.
        return preg_match('/\A[\s\x{FEFF}]*[=+@-]/u', $text) ? "'" . $text : $text;
    }
}
