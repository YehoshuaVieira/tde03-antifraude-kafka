<?php
declare(strict_types=1);

$bootstrapServers = 'kafka:9092';
$topicName = 'transacoes';
$dbPath = __DIR__ . '/../data/transacoes.sqlite';

// Conecta ao banco de dados SQLite
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cria a tabela se ela ainda nao existir
$pdo->exec("
    CREATE TABLE IF NOT EXISTS transacoes (
        id TEXT PRIMARY KEY,
        cliente_id TEXT NOT NULL,
        valor REAL NOT NULL,
        cidade TEXT NOT NULL,
        timestamp TEXT NOT NULL,
        fraude TEXT NOT NULL
    )
");

// Configura o consumidor Kafka
$conf = new RdKafka\Conf();
$conf->set('bootstrap.servers', $bootstrapServers);
$conf->set('group.id', 'grupo-antifraude');
$conf->set('auto.offset.reset', 'earliest');

$consumer = new RdKafka\KafkaConsumer($conf);
$consumer->subscribe([$topicName]);

// Guarda o historico recente de cada cliente
$historico = [];

echo "=== CONSUMIDOR ANTIFRAUDE ===\n";
echo "Aguardando transacoes... Pressione Ctrl+C para encerrar.\n\n";

while (true) {
    $mensagem = $consumer->consume(1000);

    if ($mensagem->err === RD_KAFKA_RESP_ERR__TIMED_OUT) {
        continue;
    }

    if ($mensagem->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
        echo "Erro Kafka: {$mensagem->errstr()}\n";
        continue;
    }

    try {
        $transacao = json_decode(
            $mensagem->payload,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        echo "JSON invalido: {$e->getMessage()}\n";
        continue;
    }

    $agora = strtotime($transacao['timestamp']);
    $cliente = $transacao['cliente_id'];
    $fraudes = [];

    $historico[$cliente] ??= [];

    // Remove transacoes antigas, com mais de 10 minutos
    $historico[$cliente] = array_values(array_filter(
        $historico[$cliente],
        fn($item) => $agora - $item['tempo'] <= 600
    ));

    // Regra 1: valor igual ou superior a R$ 10.000
    if ($transacao['valor'] >= 10000) {
        $fraudes[] = 'ALTO_VALOR';
    }

    // Regra 2: quatro transacoes do cliente em menos de 60 segundos
    $ultimos60 = array_filter(
        $historico[$cliente],
        fn($item) => $agora - $item['tempo'] < 60
    );

    if (count($ultimos60) + 1 >= 4) {
        $fraudes[] = 'TEMPO_60s';
    }

    // Regra 3: transacoes em cidades diferentes em ate 10 minutos
    foreach ($historico[$cliente] as $item) {
        if (
            $agora - $item['tempo'] <= 600
            && $item['cidade'] !== $transacao['cidade']
        ) {
            $fraudes[] = 'GEO_10m';
            break;
        }
    }

    $fraude = $fraudes
        ? implode(',', array_unique($fraudes))
        : 'NAO';

    // Salva todas as transacoes, inclusive as normais
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO transacoes
        (id, cliente_id, valor, cidade, timestamp, fraude)
        VALUES (:id, :cliente_id, :valor, :cidade, :timestamp, :fraude)
    ");

    $stmt->execute([
        ':id' => $transacao['id'],
        ':cliente_id' => $cliente,
        ':valor' => $transacao['valor'],
        ':cidade' => $transacao['cidade'],
        ':timestamp' => $transacao['timestamp'],
        ':fraude' => $fraude
    ]);

    // Adiciona a transacao atual ao historico
    $historico[$cliente][] = [
        'tempo' => $agora,
        'cidade' => $transacao['cidade']
    ];

    echo sprintf(
        "[%s] Cliente: %s | Valor: R$ %.2f | Cidade: %s | Fraude: %s\n",
        date('H:i:s'),
        $cliente,
        $transacao['valor'],
        $transacao['cidade'],
        $fraude
    );

    if ($fraude !== 'NAO') {
        echo "  >>> ALERTA DE FRAUDE: {$fraude} <<<\n";
    }
}
