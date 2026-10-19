<?php
declare(strict_types=1);

function verificar(bool $condicao, string $descricao): void
{
    echo ($condicao ? "[OK] " : "[FALHOU] ")
        . $descricao . PHP_EOL;
}

// Regra 1: valor alto
$valor = 12000.00;
verificar(
    $valor >= 10000,
    'ALTO_VALOR: transacao de R$ 12.000,00'
);

// Regra 2: quatro transacoes em menos de 60 segundos
$tempos = [0, 10, 20, 30];
$agora = 30;

$anteriores = array_filter(
    $tempos,
    fn($tempo) => $agora - $tempo < 60
);

verificar(
    count($anteriores) >= 4,
    'TEMPO_60s: quatro transacoes em 30 segundos'
);

// Regra 3: cidades diferentes em ate 10 minutos
$anterior = [
    'tempo' => 1000,
    'cidade' => 'Fortaleza'
];

$atual = [
    'tempo' => 1300,
    'cidade' => 'Recife'
];

verificar(
    $atual['tempo'] - $anterior['tempo'] <= 600
        && $anterior['cidade'] !== $atual['cidade'],
    'GEO_10m: cidades diferentes em 5 minutos'
);
