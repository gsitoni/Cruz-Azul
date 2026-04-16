# Cruz-Azul

Sistema web para gestão de ONGs e usuários, com painel administrativo completo.

## 📁 Estrutura do Projeto

```
Cruz-Azul/
├── .git/                          # Controle de versão
├── .htaccess                      # Redirecionamentos e regras Apache
├── composer.json                  # Dependências PHP
├── composer.lock                  # Lock das versões das dependências
├── README.md                      # Este arquivo
│
├── config/                        # Configurações da aplicação (reservado)
├── database/                      # Scripts e migrations do banco (reservado)
│
├── public/                        # Arquivos públicos acessíveis via web
│   ├── assets/
│   │   ├── css/                   # Estilos CSS públicos
│   │   └── js/                    # Scripts JavaScript públicos
│   └── pages/                     # Páginas PHP públicas
│       ├── index.php              # Página inicial
│       ├── login.php              # Login de usuários
│       ├── cadastro.php           # Cadastro de usuários
│       └── ...                    # Outras páginas públicas
│
├── src/                           # Código fonte da aplicação
│   ├── admin/                     # Painel administrativo
│   │   ├── assets/
│   │   │   ├── css/               # Estilos do admin
│   │   │   └── js/                # Scripts do admin
│   │   └── pages/                 # Páginas PHP do admin
│   │       ├── dashboard.php      # Dashboard principal
│   │       ├── usuarios.php       # Gerenciamento de usuários
│   │       ├── ongs.php           # Aprovação de ONGs
│   │       ├── logs.php           # Visualização de logs
│   │       └── configuracoes.php  # Configurações do sistema
│   │
│   └── api/                       # APIs e funcionalidades backend
│       ├── 2fatores/              # Autenticação 2FA
│       ├── confirmar.php          # Confirmação de ações
│       ├── database.php           # Conexão com banco de dados
│       ├── mailer.php             # Envio de emails
│       └── ...                    # Outras APIs
│
└── vendor/                        # Dependências externas (Composer)
    ├── autoload.php               # Autoloader do Composer
    ├── composer/                  # Arquivos do Composer
    └── phpmailer/                 # Biblioteca PHPMailer
```

## 🚀 Instalação e Configuração

### Pré-requisitos
- PHP 8.0+
- MySQL 5.7+
- Composer
- Apache/Nginx com mod_rewrite

### Instalação
1. Clone o repositório
2. Instale as dependências: `composer install`
3. Configure o banco de dados em `src/api/database.php`
4. Execute as migrations SQL
5. Configure o servidor web para apontar para `public/`

### Estrutura de URLs
- **Público:** `http://localhost/Cruz-Azul/public/`
- **Admin:** `http://localhost/Cruz-Azul/src/admin/pages/dashboard.php`
- **API:** `http://localhost/Cruz-Azul/src/api/`

## 🔧 Desenvolvimento

### Adicionando novas funcionalidades
1. **Frontend público:** Adicione em `public/pages/` e `public/assets/`
2. **Admin:** Adicione em `src/admin/pages/` e `src/admin/assets/`
3. **API:** Adicione em `src/api/`
4. **Configurações:** Use `config/` para arquivos de configuração
5. **Banco:** Use `database/` para migrations e seeds

### Padrões de código
- Use PSR-4 para autoloading
- Mantenha a estrutura MVC simples
- Documente funções críticas
- Use prepared statements para SQL

## 📋 Funcionalidades

### 👥 Área Pública
- Cadastro e login de usuários
- Recuperação de senha
- Cadastro de ONGs
- Autenticação 2FA

### 🔐 Painel Administrativo
- Dashboard com visão geral
- Gerenciamento de usuários (bloquear/ativar/promover)
- Aprovação/rejeição de ONGs
- Visualização de logs de segurança
- Configurações do sistema

### 📧 Sistema de Email
- PHPMailer integrado
- Templates de email
- Confirmações automáticas

## 🔒 Segurança

- Sessões seguras com SameSite
- CSRF protection em formulários
- Password hashing
- Autenticação 2FA
- Logs de auditoria
- Validação de entrada

## 📊 Banco de Dados

### Tabelas Principais
- `usuarios` - Usuários do sistema
- `ongs` - Organizações não-governamentais
- `logs` - Logs de auditoria
- `configuracoes` - Configurações do sistema

### Estrutura Detalhada
Consulte `src/admin/INTEGRACAO_BD.md` para detalhes completos.

## 🧪 Testes

Para testes futuros, use a pasta `tests/` (a ser criada).

## 📝 Licença

Este projeto é propriedade da Cruz Azul.

---

**Última atualização:** Abril 2026
**Versão:** 1.0.0


