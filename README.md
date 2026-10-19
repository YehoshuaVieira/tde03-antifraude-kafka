# Sistema Antifraude com Apache Kafka

Projeto acadêmico de detecção de possíveis fraudes em transações financeiras usando **Apache Kafka, PHP, Docker e SQLite**.

## Funcionalidades

- Produção de transações financeiras em tempo real.
- Processamento de transações com Kafka.
- Detecção de possíveis fraudes por regras de negócio.
- Armazenamento das transações em banco SQLite.
- Verificação dos resultados armazenados.
- Testes automatizados das regras antifraude.

## Tecnologias utilizadas

- PHP 8.4
- Apache Kafka 4.1.1
- Docker e Docker Compose
- SQLite
- Extensão PHP `rdkafka`

## Pré-requisitos

Antes de começar, instale:

- [Git](https://git-scm.com/downloads)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) no Windows ou Docker Engine e Docker Compose no Linux.

No Windows, utilize o terminal do Docker Desktop com WSL2 habilitado ou um terminal Linux no WSL.

Confira se o Docker está funcionando:

```bash
docker --version
docker compose version
```

## 1. Clonar o repositório

```bash
git clone https://github.com/YehoshuaVieira/tde03-antifraude-kafka.git
cd tde03-antifraude-kafka
```

Se você já clonou o projeto anteriormente, entre na pasta e atualize:

```bash
git pull
```

## 2. Iniciar o Kafka

Na pasta raiz do projeto, execute:

```bash
docker compose up -d kafka
```

Confira o estado dos serviços:

```bash
docker compose ps
```

Aguarde o Kafka terminar de iniciar antes de executar o consumidor.

## 3. Executar os testes

Os testes de regras são independentes do Kafka e do banco de dados.

### Testes controlados

```bash
docker compose run --rm app php src/teste_regras_controlado.php
```

O arquivo verifica seis cenários:

1. Transação de valor alto.
2. Quatro transações em menos de 60 segundos.
3. Transações em cidades diferentes dentro de 10 minutos.
4. Transação normal sem alertas.
5. Limite de exatamente 60 segundos.
6. Limite de 10 minutos para detecção geográfica.

Resultado esperado: seis mensagens `[OK]`.

### Testes básicos

```bash
docker compose run --rm app php src/testes.php
```

Resultado esperado: três mensagens `[OK]`.

Se algum teste apresentar `[FALHOU]`, confira a mensagem exibida no terminal.

## 4. Executar o sistema antifraude

São necessários dois terminais, ambos abertos na pasta do projeto.

### Terminal 1 — Consumidor

```bash
docker compose run --rm app php src/consumer.php
```

O consumidor deve exibir uma mensagem indicando que está aguardando transações. Deixe esse terminal aberto.

### Terminal 2 — Produtor

```bash
docker compose run --rm app php src/producer.php
```

O produtor começa a enviar transações para o tópico Kafka `transacoes`. O consumidor recebe as mensagens, aplica as regras antifraude e salva os registros no SQLite.

Mantenha os dois processos ativos por alguns minutos para observar o processamento e eventuais alertas.

Para encerrar os processos, pressione `Ctrl+C` em cada terminal.

## 5. Regras de detecção de fraude

O sistema utiliza as seguintes regras:

| Regra | Condição |
|---|---|
| `ALTO_VALOR` | Transação de valor igual ou superior a R$ 10.000 |
| `TEMPO_60s` | Quatro ou mais transações do mesmo cliente em menos de 60 segundos |
| `GEO_10m` | Transações do mesmo cliente em cidades diferentes dentro de 10 minutos |

Uma transação pode receber mais de uma classificação caso satisfaça múltiplas regras.

Essas regras indicam possíveis fraudes e não constituem uma confirmação definitiva de fraude.

## 6. Verificar os dados armazenados

Depois que o consumidor processar algumas transações, execute:

```bash
docker compose run --rm app php src/verificar_banco.php
```

O programa deve mostrar:

- O total de transações salvas.
- A quantidade de registros por classificação de fraude.

O banco utilizado pelo projeto é:

```text
data/transacoes.sqlite
```

O diretório `data` deve ser criado automaticamente pelo consumidor quando não existir.

**Importante:** o verificador consulta o banco existente. Execute o consumidor antes de utilizá-lo, para garantir que a tabela tenha sido criada.

## 7. Solução de problemas

### Erro: `unable to open database file`

Esse erro indica que o SQLite não conseguiu abrir ou criar o arquivo do banco.

Verifique se o `consumer.php` cria a pasta `data` antes de conectar ao SQLite e se o diretório do projeto permite escrita.

A conexão deve usar o caminho:

```php
$dbDir = __DIR__ . '/../data';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$dbPath = $dbDir . '/transacoes.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
```

O `verificar_banco.php` deve utilizar o mesmo arquivo de banco.

### Erro de conexão com Kafka

- Confira se o serviço Kafka está em execução com `docker compose ps`.
- Aguarde alguns segundos após iniciar o serviço.
- Confira se o consumidor utiliza `kafka:9092` como endereço do broker dentro da rede Docker.

### Comando `docker compose` não encontrado

Atualize ou instale o Docker com suporte ao Docker Compose v2.

### Alterações do GitHub não aparecem

Na pasta do projeto, execute:

```bash
git pull
```

Se houver alterações locais conflitantes, resolva os conflitos antes de continuar.

## 8. Encerrar o ambiente

Para parar os serviços iniciados pelo Compose:

```bash
docker compose down
```

Esse comando para e remove os containers do projeto, mas não remove automaticamente os arquivos do projeto. Não utilize `docker compose down -v` sem entender o efeito sobre os volumes.

## Estrutura principal

```text
tde03-antifraude-kafka/
├── src/
│   ├── producer.php
│   ├── consumer.php
│   ├── testes.php
│   ├── teste_regras_controlado.php
│   └── verificar_banco.php
├── data/
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Objetivo acadêmico

Demonstrar o uso de processamento de eventos com Apache Kafka, aplicação de regras antifraude em PHP, persistência dos dados em SQLite e validação do comportamento por testes.

Projeto desenvolvido para fins educacionais.
