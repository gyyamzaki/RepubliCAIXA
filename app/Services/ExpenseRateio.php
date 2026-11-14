<?php

namespace App\Services;

use InvalidArgumentException;

final class ExpenseRateio
{
    // Converte reais para centavos
    public static function centavos(string $texto): int
    {
        $texto = str_replace(',', '.', trim($texto));

        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $texto)) {
            throw new InvalidArgumentException(
                'Informe um valor válido. Ex.: 300.00'
            );
        }

        [$inteiro, $decimais] = array_pad(
            explode('.', $texto, 2), 2, '0'
        );

        return (int) $inteiro * 100
            + (int) str_pad($decimais, 2, '0');
    }

    // Calcula o rateio escolhido
    public static function calcular(
        string $tipo,
        int $totalCentavos,
        array $moradores,
        array $valores = []
    ): array {

        $moradores = array_values(
            array_unique(array_map('intval', $moradores))
        );

        $quantidade = count($moradores);

        if (
            $totalCentavos <= 0 ||
            $quantidade === 0 ||
            count(array_filter($moradores, fn($id) => $id <= 0)) > 0
        ) {
            throw new InvalidArgumentException(
                'Informe valor e moradores válidos.'
            );
        }

        $partes = [];

        // RATEIO IGUALITÁRIO
        if ($tipo === 'equal') {

            $base = intdiv($totalCentavos, $quantidade);
            $resto = $totalCentavos % $quantidade;

            foreach ($moradores as $indice => $id) {
                $partes[$id] = [
                    'centavos' => $base + (
                        $indice < $resto ? 1 : 0
                    ),
                    'percentual' => null
                ];
            }

        // RATEIO PERSONALIZADO
        } elseif ($tipo === 'custom') {

            $soma = 0;

            foreach ($moradores as $id) {

                $centavos = self::centavos(
                    (string) ($valores[$id] ?? '')
                );

                $soma += $centavos;

                $partes[$id] = [
                    'centavos' => $centavos,
                    'percentual' => null
                ];
            }

            if ($soma !== $totalCentavos) {
                throw new InvalidArgumentException(
                    'Os valores devem somar o total da despesa.'
                );
            }

        // RATEIO PERCENTUAL
        } elseif ($tipo === 'percentage') {

            $somaPercentual = 0;
            $somaCentavos = 0;
            $restos = [];

            foreach ($moradores as $id) {

                $texto = str_replace(
                    ',',
                    '.',
                    trim((string) ($valores[$id] ?? ''))
                );

                if (!preg_match(
                    '/^\d{1,3}(?:\.\d{1,2})?$/D',
                    $texto
                )) {
                    throw new InvalidArgumentException(
                        'Informe percentuais com até 2 casas decimais.'
                    );
                }

                [$inteiro, $decimal] = array_pad(
                    explode('.', $texto, 2),
                    2,
                    '0'
                );

                $percentual = (int) $inteiro * 100
                    + (int) str_pad($decimal, 2, '0');

                if ($percentual > 10000) {
                    throw new InvalidArgumentException(
                        'Percentual maior que 100%.'
                    );
                }

                $produto = $totalCentavos * $percentual;
                $centavos = intdiv($produto, 10000);

                $partes[$id] = [
                    'centavos' => $centavos,
                    'percentual' => number_format(
                        $percentual / 100, 2, '.', ''
                    )
                ];

                $restos[$id] = $produto % 10000;

                $somaCentavos += $centavos;
                $somaPercentual += $percentual;
            }

            if ($somaPercentual !== 10000) {
                throw new InvalidArgumentException(
                    'A soma dos percentuais deve ser 100%.'
                );
            }

            // Distribui centavos restantes
            arsort($restos, SORT_NUMERIC);

            $faltam = $totalCentavos - $somaCentavos;

            foreach (array_keys($restos) as $id) {
                if ($faltam <= 0) {
                    break;
                }

                $partes[$id]['centavos']++;
                $faltam--;
            }

        } else {

            throw new InvalidArgumentException(
                'Tipo de rateio inválido.'
            );
        }

        // Verifica se todos recebem um valor positivo
        foreach ($partes as $parte) {
            if ($parte['centavos'] < 1) {
                throw new InvalidArgumentException(
                    'Cada morador deve pagar pelo menos R$ 0,01.'
                );
            }
        }

        return $partes;
    }
}
