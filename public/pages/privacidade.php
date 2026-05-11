<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade e Cookies – Cruz Azul</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.7;
            color: #333;
            background-color: #f4f6f8;
            margin: 0;
            padding: 40px 20px;
        }
 
        .content-wrapper {
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            padding: 48px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        }
 
        h1 {
            color: #007BFF;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 12px;
            margin-bottom: 6px;
            font-size: 1.8rem;
        }
 
        .subtitulo {
            color: #888;
            font-size: .9rem;
            margin-bottom: 32px;
        }
 
        h2 {
            font-size: 1.1rem;
            color: #1a3a5c;
            margin-top: 36px;
            margin-bottom: 10px;
            padding-left: 10px;
            border-left: 3px solid #007BFF;
        }
 
        p { margin-bottom: 14px; text-align: justify; }
 
        ul { margin-bottom: 15px; padding-left: 20px; }
        li { margin-bottom: 8px; }
 
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 24px;
            font-size: .93rem;
        }
 
        thead {
            background: #007BFF;
            color: #fff;
        }
 
        thead th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
        }
 
        tbody tr:nth-child(even) { background: #f4f8ff; }
        tbody tr:nth-child(odd)  { background: #fff; }
 
        tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #e4eaf2;
            vertical-align: top;
        }
 
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: .78rem;
            font-weight: 600;
        }
 
        .badge-obrigatorio  { background: #fde8e8; color: #c0392b; }
        .badge-opcional     { background: #e8f5e9; color: #276221; }
        .badge-tecnico      { background: #e8f0fe; color: #1a56b0; }
 
        .destaque {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 16px 0;
            font-size: .93rem;
        }
 
        .footer-note {
            margin-top: 48px;
            font-size: .88rem;
            color: #888;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 24px;
        }
 
        .btn-fechar {
            display: inline-block;
            background: #007BFF;
            color: #fff;
            padding: 10px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 16px;
            font-size: .95rem;
        }
 
        .btn-fechar:hover { background: #0056b3; }
 
        @media (max-width: 768px) {
            body { padding: 20px 15px; }
            .content-wrapper { padding: 28px 20px; }
            h1 { font-size: 1.4rem; }
            table { font-size: .85rem; }
        }
 
        @media (max-width: 480px) {
            body { padding: 12px 8px; }
            .content-wrapper { padding: 20px 14px; }
            h1 { font-size: 1.2rem; }
            h2 { font-size: 1rem; }
            p, li { font-size: .9rem; }
            thead th, tbody td { padding: 8px; }
        }
    </style>
</head>
<body>
 
<div class="content-wrapper">
 
    <h1>Política de Privacidade e Cookies</h1>
    <p class="subtitulo">Cruz Azul &nbsp;·&nbsp; Em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018)</p>
 
    <p>Esta política descreve quais dados pessoais coletamos, como os utilizamos, por quanto tempo os mantemos e quais são os seus direitos como titular. Leia com atenção antes de se cadastrar.</p>
 
 
    <!-- 1. DADOS COLETADOS -->
    <h2>1. Dados Coletados</h2>
    <p>Os dados coletados variam conforme o tipo de cadastro realizado na plataforma.</p>
 
    <strong>1.1 Doadores</strong>
    <table>
        <thead>
            <tr>
                <th>Dado</th>
                <th>Finalidade</th>
                <th>Obrigatoriedade</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nome completo</td>
                <td>Identificação do usuário no sistema</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>E-mail</td>
                <td>Login, confirmação de conta e recuperação de senha</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>Senha</td>
                <td>Autenticação segura (armazenada com hash)</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>CPF</td>
                <td>Identificação única do doador</td>
                <td><span class="badge badge-opcional">Opcional</span></td>
            </tr>
            <tr>
                <td>Telefone / WhatsApp</td>
                <td>Contato para comunicações relacionadas às doações</td>
                <td><span class="badge badge-opcional">Opcional</span></td>
            </tr>
            <tr>
                <td>Data de nascimento</td>
                <td>Verificação de maioridade (mínimo 18 anos)</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>Chave de autenticação 2FA</td>
                <td>Segurança adicional de acesso (TOTP)</td>
                <td><span class="badge badge-tecnico">Técnico</span></td>
            </tr>
        </tbody>
    </table>
 
    <strong>1.2 ONGs / Instituições</strong>
    <table>
        <thead>
            <tr>
                <th>Dado</th>
                <th>Finalidade</th>
                <th>Obrigatoriedade</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nome da instituição</td>
                <td>Identificação pública da ONG na plataforma</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>E-mail</td>
                <td>Login, confirmação de conta e comunicações</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>CNPJ</td>
                <td>Identificação legal da instituição</td>
                <td><span class="badge badge-obrigatorio">Obrigatório</span></td>
            </tr>
            <tr>
                <td>Área de atuação</td>
                <td>Categorização e busca na plataforma</td>
                <td><span class="badge badge-opcional">Opcional</span></td>
            </tr>
            <tr>
                <td>Cidade, Estado e Endereço</td>
                <td>Localização da instituição para os doadores</td>
                <td><span class="badge badge-opcional">Opcional</span></td>
            </tr>
            <tr>
                <td>Descrição</td>
                <td>Apresentação da ONG para os doadores</td>
                <td><span class="badge badge-opcional">Opcional</span></td>
            </tr>
        </tbody>
    </table>
 
 
    <!-- 2. COOKIES -->
    <h2>2. Cookies e Dados de Sessão</h2>
    <p>Utilizamos apenas cookies estritamente necessários para o funcionamento da plataforma. Não utilizamos cookies de rastreamento, publicidade ou analytics de terceiros.</p>
 
    <table>
        <thead>
            <tr>
                <th>Cookie</th>
                <th>Tipo</th>
                <th>Finalidade</th>
                <th>Duração</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>PHPSESSID</code></td>
                <td><span class="badge badge-tecnico">Sessão</span></td>
                <td>Mantém o usuário autenticado durante a navegação</td>
                <td>Até fechar o navegador ou fazer logout</td>
            </tr>
            <tr>
                <td>Token CSRF</td>
                <td><span class="badge badge-tecnico">Segurança</span></td>
                <td>Protege formulários contra ataques de falsificação de requisição</td>
                <td>Duração da sessão</td>
            </tr>
            <tr>
                <td>Cookie de consentimento</td>
                <td><span class="badge badge-tecnico">Preferência</span></td>
                <td>Registra que o aviso de cookies foi aceito</td>
                <td>365 dias</td>
            </tr>
        </tbody>
    </table>
 
    <div class="destaque">
        <strong>Sem rastreamento:</strong> não utilizamos Google Analytics, Facebook Pixel, nem qualquer outra ferramenta de rastreamento de comportamento. Seus dados de navegação não são compartilhados com terceiros.
    </div>
 
 
    <!-- 3. FINALIDADE -->
    <h2>3. Finalidade do Tratamento</h2>
    <p>Seus dados são utilizados exclusivamente para:</p>
    <ul>
        <li>Gerenciar seu acesso e autenticação na plataforma (incluindo 2FA).</li>
        <li>Permitir o registro e rastreamento de doações realizadas.</li>
        <li>Conectar doadores a ONGs cadastradas.</li>
        <li>Enviar e-mails transacionais: confirmação de cadastro, recuperação de senha e notificações de doações.</li>
        <li>Cumprir obrigações legais de registro de acesso (Marco Civil da Internet).</li>
        <li>Verificar a maioridade do doador (mínimo 18 anos) conforme exigência legal.</li>
    </ul>
 
 
    <!-- 4. COMPARTILHAMENTO -->
    <h2>4. Compartilhamento de Dados</h2>
    <p>Nós <strong>não</strong> vendemos, alugamos ou compartilhamos seus dados pessoais com terceiros para fins comerciais ou de marketing.</p>
    <p>Os dados podem ser compartilhados apenas nas seguintes situações:</p>
    <ul>
        <li><strong>Entre doador e ONG:</strong> quando uma doação é realizada, a ONG recebe as informações necessárias para processar e confirmar o recebimento.</li>
        <li><strong>Serviço de e-mail:</strong> utilizamos um serviço de envio de e-mails transacionais exclusivamente para comunicações da plataforma.</li>
        <li><strong>Obrigação legal:</strong> quando exigido por autoridade competente, mediante ordem judicial.</li>
    </ul>
 
 
    <!-- 5. ARMAZENAMENTO -->
    <h2>5. Armazenamento e Segurança</h2>
    <p>Adotamos as seguintes medidas técnicas de segurança:</p>
    <ul>
        <li>Senhas armazenadas com <strong>hash bcrypt</strong> — nunca em texto puro.</li>
        <li>Autenticação em dois fatores (<strong>2FA via TOTP</strong>) disponível para todos os usuários.</li>
        <li>Proteção contra <strong>CSRF</strong> em todos os formulários.</li>
        <li>Headers de segurança HTTP (<code>X-Frame-Options</code>, <code>X-Content-Type-Options</code>, <code>Strict-Transport-Security</code>).</li>
        <li>Tokens de confirmação e recuperação de senha com <strong>expiração</strong>.</li>
        <li>Conexão com banco de dados via PDO com <strong>prepared statements</strong> (proteção contra SQL Injection).</li>
    </ul>
 
 
    <!-- 6. EXCLUSÃO E ANONIMIZAÇÃO -->
    <h2>6. Exclusão e Anonimização de Dados</h2>
    <p>Você pode remover seus dados de duas formas pela área do perfil:</p>
    <ul>
        <li><strong>Exclusão por campo:</strong> é possível apagar individualmente nome, CPF, telefone, data de nascimento ou e-mail, sem encerrar a conta.</li>
        <li><strong>Encerramento de conta:</strong> todos os dados pessoais são anonimizados (substituídos por valores neutros) e o acesso é bloqueado permanentemente.</li>
    </ul>
    <div class="destaque">
        O histórico de doações e distribuições é mantido de forma <strong>anônima</strong> após o encerramento, sem qualquer vínculo com seus dados pessoais, para fins de controle interno e obrigações legais. O CNPJ de ONGs é preservado por obrigação legal.
    </div>
 
 
    <!-- 7. SEUS DIREITOS -->
    <h2>7. Seus Direitos (LGPD – Art. 18)</h2>
    <p>Como titular dos dados, você tem direito a:</p>
    <ul>
        <li><strong>Acesso:</strong> visualizar todos os seus dados cadastrados na página de perfil.</li>
        <li><strong>Correção:</strong> atualizar dados incompletos, inexatos ou desatualizados pela página de edição de perfil.</li>
        <li><strong>Exclusão parcial:</strong> apagar campos individuais diretamente no perfil.</li>
        <li><strong>Encerramento:</strong> solicitar o bloqueio e anonimização completa da conta pelo botão "Encerrar minha conta".</li>
        <li><strong>Revogação do consentimento:</strong> a qualquer momento, sem prejuízo das operações já realizadas.</li>
        <li><strong>Portabilidade:</strong> mediante solicitação ao suporte, seus dados podem ser fornecidos em formato estruturado.</li>
    </ul>
 
 
    <!-- 8. CONSENTIMENTO -->
    <h2>8. Consentimento</h2>
    <p>Ao marcar o checkbox no formulário de cadastro, você manifesta seu <strong>consentimento livre, informado e inequívoco</strong> para o tratamento de seus dados conforme descrito nesta política, nos termos do Art. 8º da LGPD.</p>
    <p>O consentimento pode ser revogado a qualquer momento pelo encerramento da conta ou por solicitação ao suporte.</p>
 
 
    <!-- 9. CONTATO -->
    <h2>9. Contato e Encarregado de Dados (DPO)</h2>
    <p>Em caso de dúvidas, solicitações ou reclamações relacionadas ao tratamento de seus dados pessoais, entre em contato pelo e-mail de suporte da plataforma. Você também pode registrar reclamações perante a <strong>Autoridade Nacional de Proteção de Dados (ANPD)</strong> em <a href="https://www.gov.br/anpd" target="_blank">www.gov.br/anpd</a>.</p>
 
 
    <div class="footer-note">
        <p>Última atualização: <?php echo date('d/m/Y'); ?></p>
        <a href="javascript:window.close();" class="btn-fechar">Fechar esta página</a>
    </div>
 
</div>
 
</body>
</html>
 