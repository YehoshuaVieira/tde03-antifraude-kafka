<?php

declare(strict_types=1);

$bootstrapServers = 'kafka:9092';
$topicName = 'transacoes';

$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $bootstrapServers);

$producer = new RdKafka\Producer($conf);
$topic = $producer->newTopic($topicName);

$cidades = [
    'Juazeiro do Norte',
    'Fortaleza',
    'Crato',
    'Barbalha',
    'Sobral',
    'Recife'
];

echo "=== PRODUTOR DE TRANSACOES ===\n";
echo "Enviando uma transacao a cada 3 segundos.\n";
echo "Pressione Ctrl+C para encerrar.\n\n";

while (true) {
    $transacao = [
        'id' => uniqid('txn_', true),
        'cliente_id' => 'cliente_' . random_int(1, 5),
        'valor' => random_int(100, 1500000) / 100,
        'cidade' => $cidades[array_rand($cidades)],
        'timestamp' => date('c')
    ];

    $mensagem = json_encode(
        $transacao,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );

    $topic->produce(
        RD_KAFKA_PARTITION_UA,
        0,
        $mensagem
    );

    $producer->poll(0);
    $producer->flush(10000);

    echo '[' . date('H:i:s') . '] Transacao enviada: '
        . $mensagem . PHP_EOL;

    sleep(3);
}
