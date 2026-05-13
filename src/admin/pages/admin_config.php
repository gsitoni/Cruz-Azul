<?php

function adminConfigPadrao(): array
{
    return [
        'nome_sistema' => 'Cruz Azul',
        'email_admin' => 'admin@cruzazul.com',
        'email_noreply' => 'noreply@cruzazul.com',
        'tentativas_login' => 5,
        'timeout_sessao' => 3600,
        'notificacoes_email' => true,
        'autenticacao_2fa' => true,
    ];
}

function adminConfigCaminho(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'admin_config.json';
}

function adminConfigNormalizar(array $config): array
{
    $padrao = adminConfigPadrao();
    $config = array_merge($padrao, array_intersect_key($config, $padrao));

    $config['nome_sistema'] = trim((string) $config['nome_sistema']) ?: $padrao['nome_sistema'];
    $config['email_admin'] = filter_var((string) $config['email_admin'], FILTER_VALIDATE_EMAIL) ?: $padrao['email_admin'];
    $config['email_noreply'] = filter_var((string) $config['email_noreply'], FILTER_VALIDATE_EMAIL) ?: $padrao['email_noreply'];
    $config['tentativas_login'] = min(10, max(1, (int) $config['tentativas_login']));
    $config['timeout_sessao'] = min(86400, max(300, (int) $config['timeout_sessao']));
    $config['notificacoes_email'] = (bool) $config['notificacoes_email'];
    $config['autenticacao_2fa'] = (bool) $config['autenticacao_2fa'];

    return $config;
}

function adminConfigCarregar(): array
{
    $caminho = adminConfigCaminho();

    if (!is_file($caminho)) {
        return adminConfigPadrao();
    }

    $json = file_get_contents($caminho);
    $config = json_decode($json ?: '', true);

    return adminConfigNormalizar(is_array($config) ? $config : []);
}

function adminConfigSalvar(array $config): bool
{
    $json = json_encode(adminConfigNormalizar($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return $json !== false && file_put_contents(adminConfigCaminho(), $json . PHP_EOL, LOCK_EX) !== false;
}
