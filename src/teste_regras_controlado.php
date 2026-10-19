<?php
declare(strict_types=1);

function classificar(
    float $valor,
    array $anteriores,
    int $tempoAtual,
    string $cidadeAtual
): array {
    $fraudes = [];
    $ultimas60 = 0;
    $cidadeDiferente = false;

    foreach ($anteriores as $anterior) {
        $diferenca = $tempoAtual - $anterior['tempo'];

        if ($diferenca < 0) {
            continue;
        }

        if ($diferenca < 60) {
            $ultimas60++;
        }

        if (
            $diferenca <= 600
            && $anterior['cidade'] !== $cidadeAtual
        ) {
            $cidadeDiferente = true;
        }
    }

    if ($valor >= 10000) {
        $fraudes[] = 'ALTO_VALOR';
    }

    if ($ultimas60 + 1 >= 4) {
        $fraudes[] = 'TEMPO_60s';
    }

    if ($cidadeDiferente) {
        $fraudes[] = 'GEO_10m';
    }

    return $fraudes;
}

function testar(
    string $nome,
    array $resultado,
    array $esperado
): void {
    sort($resultado);
    sort($esperado);

    echo ($resultado === $esperado ? '[OK] ' : '[FALHOU] ')
        . $nome . PHP_EOL;

    if ($resultado !== $esperado) {
        echo '  Esperado: ' . implode(', ', $esperado) . PHP_EOL;
        echo '  Obtido: ' . implode(', ', $resultado) . PHP_EOL;
    }
}

// 1. Valor alto, sem histórico
testar(
    'Valor de R$ 12.000',
    classificar(12000, [], 1000, 'Crato'),
    ['ALTO_VALOR']
);

// 2. Quatro transações em menos de 60 segundos
testar(
    'Quatro transações em 30 segundos',
    classificar(100, [
        ['tempo' => 970, 'cidade' => 'Crato'],
        ['tempo' => 980, 'cidade' => 'Crato'],
        ['tempo' => 990, 'cidade' => 'Crato'],
    ], 1000, 'Crato'),
    ['TEMPO_60s']
);

// 3. Cidades diferentes em menos de 10 minutos
testar(
    'Cidades diferentes em 5 minutos',
    classificar(100, [
        ['tempo' => 700, 'cidade' => 'Fortaleza'],
    ], 1000, 'Recife'),
    ['GEO_10m']
);

// 4. Transação normal
testar(
    'Transação normal sem alertas',
    classificar(100, [], 1000, 'Crato'),
    []
);

// 5. Uma transação de exatamente 60 segundos atrás não conta
testar(
    'Limite de 60 segundos',
    classificar(100, [
        ['tempo' => 940, 'cidade' => 'Crato'],
    ], 1000, 'Crato'),
    []
);

// 6. Uma cidade diferente há mais de 10 minutos não conta
testar(
    'Limite de 10 minutos',
    classificar(100, [
        ['tempo' => 399, 'cidade' => 'Fortaleza'],
    ], 1000, 'Recife'),
    []
);
