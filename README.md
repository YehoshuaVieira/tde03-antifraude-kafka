\# Sistema Antifraude com Apache Kafka



\## Descrição



Projeto acadêmico de detecção de possíveis fraudes em transações financeiras usando Apache Kafka, PHP e SQLite.



O sistema possui um produtor que gera transações aleatórias a cada 3 segundos e um consumidor que recebe as mensagens, aplica regras de detecção de fraude, armazena as transações no banco de dados e exibe alertas no terminal.



\## Tecnologias utilizadas



\- PHP

\- Apache Kafka 4.1.1

\- Docker e Docker Compose

\- SQLite



\## Regras de detecção



1\. \*\*ALTO\_VALOR:\*\* transações com valor igual ou superior a R$ 10.000,00.

2\. \*\*TEMPO\_60s:\*\* quatro ou mais transações do mesmo cliente em menos de 60 segundos, considerando a transação atual.

3\. \*\*GEO\_10m:\*\* transações do mesmo cliente em cidades diferentes dentro de um intervalo de 10 minutos.



\## Estrutura do projeto



\- `src/producer.php`: gera e publica transações no Kafka.

\- `src/consumer.php`: consome as mensagens, aplica as regras e salva os resultados.

\- `src/testes.php`: verifica as condições básicas das três regras.

\- `docker-compose.yml`: configura os serviços do projeto.

\- `Dockerfile`: configura o ambiente PHP.

\- `data/`: armazena o banco SQLite.



\## Como executar



Com o Docker Desktop aberto, inicie os serviços:



```bash

docker compose up -d --build

```



Crie o tópico Kafka, caso ainda não exista:



```bash

docker exec tde03-kafka /opt/kafka/bin/kafka-topics.sh --create --topic transacoes --bootstrap-server localhost:9092

```



Se o tópico já existir, o Kafka informará isso; nesse caso, continue.



Em um terminal, execute o consumidor:



```bash

docker compose run --rm app php src/consumer.php

```



Em outro terminal, execute o produtor:



```bash

docker compose run --rm app php src/producer.php

```



Para executar os testes básicos das regras:



```bash

docker compose run --rm app php src/testes.php

```



Para encerrar o produtor e o consumidor, use `Ctrl+C` nos respectivos terminais.



\## Banco de dados



Todas as transações processadas são registradas no SQLite, incluindo aquelas sem alertas de fraude. O campo `fraude` armazena `NAO` quando nenhuma regra é acionada ou o nome das regras detectadas.



\## Finalidade acadêmica



Projeto desenvolvido para demonstrar o uso de mensageria com Apache Kafka e processamento de transações para identificação de possíveis fraudes.



