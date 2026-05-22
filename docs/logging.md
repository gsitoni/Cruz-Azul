# Sistema de Logs e Auditoria

## Visao geral

O logging foi centralizado em `src/api/logs_sistema.php`, mantendo a funcao historica `registrarLogSistema()` para compatibilidade. A implementacao real fica em `src/api/logging/`:

- `SecurityLogger.php`: monta o evento, aplica severidade, registra em arquivo e banco.
- `ThreatDetector.php`: detecta sinais de SQL Injection, Path Traversal, brute force, acesso admin suspeito, token invalido e acoes destrutivas.
- `LogSanitizer.php`: remove caracteres perigosos e mascara senhas, tokens, cookies, secrets e codigos.
- `LogFormatter.php`: gera JSON estruturado e formato humano para uso futuro.
- `FileLogWriter.php`: grava JSONL por categoria, faz rotacao por tamanho, compressao `.gz` e retencao.

## Campos registrados

Cada evento estruturado inclui:

- timestamp UTC ISO 8601
- event_id unico
- correlation_id da requisicao
- severidade
- categoria
- usuario e ID
- IP de origem
- metodo HTTP
- rota e endpoint
- acao
- status
- motivo
- user-agent
- sessao mascarada
- contexto sanitizado
- destino afetado
- tempo de execucao em milissegundos
- sinais de ameaca e risco

## Armazenamento

Os arquivos sao criados em `storage/logs/`:

- `auth.log`
- `access.log`
- `audit.log`
- `security.log`
- `error.log`
- `critical.log`
- `system.log`

Os logs sao JSONL, um evento por linha, prontos para envio futuro para Loki, ELK, Grafana, Docker logging driver ou SIEM. O banco `logs_sistema` continua recebendo um resumo compativel com o schema atual.

## Seguranca

Boas praticas implementadas:

- nunca registrar senhas, tokens completos, cookies ou secrets;
- mascaramento de sessao;
- sanitizacao contra log injection;
- severidade elevada automaticamente para eventos suspeitos;
- contexto expandido em eventos `CRITICAL`;
- `alert_ready=true` para integracao futura com alertas;
- rotacao e compressao de arquivos;
- trilha de auditoria com antes/depois em acoes administrativas.

## Expansao futura

Para integrar com SIEM ou monitoramento em tempo real, leia os arquivos JSONL em `storage/logs/` ou adicione um exporter depois de `FileLogWriter`. A estrutura ja possui `correlation_id`, `event_id` e `siem.schema` para facilitar rastreamento distribuido.
