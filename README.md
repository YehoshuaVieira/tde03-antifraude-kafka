# Sistema Antifraude com Apache Kafka

## Descrição

Projeto acadêmico de detecção de possíveis fraudes em transações financeiras usando Apache Kafka, PHP e SQLite.

O sistema possui um produtor que gera transações aleatórias a cada 3 segundos e um consumidor que recebe as mensagens, aplica regras de detecção de fraude, armazena as transações no banco de dados e exibe alertas no terminal.

## Tecnologias utilizadas

- PHP
- Apache Kafka 4.1.1
- Docker e Docker Compose
- SQLite

## Regras de detecção

1. **ALTO_VALOR:** transações com valor igual ou superior a R$ 10.000,00.
2. **TEMPO_60s:** quatro ou mais transações do mesmo cliente em menos de 60 segundos, considerando a transação atual.
3. **GEO_10m:** transações do mesmo cliente em cidades diferentes dentro de um intervalo de 10 minutos.

## Estrutura do projeto

- `src/producer.php`: gera e publica transações no Kafka.
- `src/consumer.php`: consome as mensagens, aplica as regras e salva os resultados.
- `src/testes.php`: contém testes básicos das regras.
- `src/teste_regras_controlado.php`: verifica cenários específicos das regras antifraude.
- `src/verificar_banco.php`: exibe o total de transações salvas e o resumo por classificação.
- `docker-compose.yml`: configura os serviços do projeto.
- `Dockerfile`: configura o ambiente PHP.
- `data/`: diretório utilizado para armazenar o banco SQLite.

## Como executar

Pré-requisitos: Docker Desktop instalado e em execução.

### 1. Iniciar os serviços

Na pasta do projeto, execute:

```bash
docker compose up -d --build
```

### 2. Criar o tópico Kafka

Execute o comando abaixo caso o tópico ainda não exista:

```bash
docker exec tde03-kafka /opt/kafka/bin/kafka-topics.sh --create --topic transacoes --bootstrap-server localhost:9092
```

Se o tópico já existir, prossiga para a próxima etapa.

### 3. Iniciar o consumidor

Em um terminal, execute:

```bash
docker compose run --rm app php src/consumer.php
```

### 4. Iniciar o produtor

Em outro terminal, execute:

```bash
docker compose run --rm app php src/producer.php
```

O produtor enviará uma nova transação a cada 3 segundos. O consumidor exibirá as transações recebidas e os alertas identificados.

## Testes

Para executar os testes básicos:

```bash
docker compose run --rm app php src/testes.php
```

Para executar os testes controlados das regras antifraude:

```bash
docker compose run --rm app php src/teste_regras_controlado.php
```

Para verificar os registros e as classificações armazenadas no banco:

```bash
docker compose run --rm app php src/verificar_banco.php
```

## Banco de dados

Todas as transações processadas são registradas no SQLite, incluindo aquelas sem alertas de fraude.

O campo `fraude` armazena `NAO` quando nenhuma regra é acionada ou os nomes das regras detectadas quando uma ou mais condições são atendidas.

## Encerramento

Tente pressionar `Ctrl+C` nos terminais do produtor e do consumidor.

Se precisar encerrar os contêineres temporários da aplicação pelo PowerShell, execute:

```powershell
docker ps --filter "name=tde03-antifraude-kafka-app-run-" -q | ForEach-Object { docker stop $_ }
```

Para encerrar também os serviços iniciados pelo Docker Compose:

```bash
docker compose down
```

## Finalidade acadêmica

Projeto desenvolvido para demonstrar o uso de mensageria com Apache Kafka e processamento de transações para identificação de possíveis fraudes.
