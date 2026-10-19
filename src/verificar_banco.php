<?php
declare(strict_types=1);

$caminho = __DIR__ . '/../data/transacoes.sqlite';

$pdo = new PDO('sqlite:' . $caminho);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$total = $pdo->query(
    'SELECT COUNT(*) FROM transacoes'
)->fetchColumn();

echo "Total de transacoes salvas: " . $total . PHP_EOL;

$consulta = $pdo->query("
    SELECT fraude, COUNT(*) AS quantidade
    FROM transacoes
    GROUP BY fraude
    ORDER BY quantidade DESC
");

echo "\nResumo por classificacao:\n";

foreach ($consulta as $linha) {
    echo $linha['fraude'] . ': '
        . $linha['quantidade'] . PHP_EOL;
}
