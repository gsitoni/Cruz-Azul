-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14/05/2026 às 20:12
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cruzazul`
--

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_gerar_otp_telegram` (IN `p_id_usuario` INT UNSIGNED, IN `p_telegram_chat_id` VARCHAR(64))   BEGIN
  DECLARE v_codigo CHAR(6);
  DECLARE v_ativo TINYINT(1);

  SELECT telegram_2fa_ativo INTO v_ativo FROM usuario WHERE id_usuario = p_id_usuario;
  IF v_ativo = 0 OR v_ativo IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Telegram 2FA não está ativo para este usuário.';
  END IF;

  UPDATE otp_telegram SET usado = 1 WHERE id_usuario = p_id_usuario AND usado = 0;
  SET v_codigo = LPAD(FLOOR(RAND() * 1000000), 6, '0');
  INSERT INTO otp_telegram (id_usuario, telegram_chat_id, codigo, expira_em)
  VALUES (p_id_usuario, p_telegram_chat_id, v_codigo, DATE_ADD(NOW(), INTERVAL 5 MINUTE));
  SELECT v_codigo AS codigo, DATE_ADD(NOW(), INTERVAL 5 MINUTE) AS expira_em;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_marcar_lotes_vencidos` ()   BEGIN
    UPDATE estoque
    SET status_operacional = 'vencido'
    WHERE status_operacional = 'disponivel'
      AND data_validade IS NOT NULL
      AND data_validade < CURDATE();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_suspender_beneficiarios_expirados` ()   BEGIN
  UPDATE ong
  SET status_elegibilidade = 'suspenso'
  WHERE status_elegibilidade IN ('ativo', 'aprovado')
    AND data_atualizacao < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_validar_otp_telegram` (IN `p_id_usuario` INT UNSIGNED, IN `p_codigo` CHAR(6), OUT `p_valido` TINYINT(1))   BEGIN
  DECLARE v_id       int UNSIGNED DEFAULT NULL;
  DECLARE v_expira   datetime     DEFAULT NULL;
  DECLARE v_usado    tinyint(1)   DEFAULT 1;
  DECLARE v_tentativas tinyint(3) DEFAULT 0;

  SELECT id, expira_em, usado, tentativas
    INTO v_id, v_expira, v_usado, v_tentativas
    FROM otp_telegram
   WHERE id_usuario = p_id_usuario
     AND codigo     = p_codigo
     AND usado      = 0
   ORDER BY criado_em DESC
   LIMIT 1;

  IF v_id IS NULL THEN
    SET p_valido = 0;
  ELSEIF v_expira < NOW() THEN
    SET p_valido = 0;
    UPDATE otp_telegram SET tentativas = tentativas + 1 WHERE id = v_id;
  ELSEIF v_tentativas >= 5 THEN
    SET p_valido = 0;
  ELSE
    -- Marca como usado e confirma a conta se ainda pendente
    UPDATE otp_telegram SET usado = 1 WHERE id = v_id;
    UPDATE usuario
       SET status_cadastro = 'confirmado'
     WHERE id_usuario = p_id_usuario
       AND status_cadastro = 'pendente';
    SET p_valido = 1;
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `distribuicao`
--

CREATE TABLE `distribuicao` (
  `id_operacao` int(10) UNSIGNED NOT NULL,
  `id_lote` int(10) UNSIGNED NOT NULL,
  `id_ong` int(10) UNSIGNED NOT NULL,
  `id_voluntario` int(10) UNSIGNED NOT NULL,
  `quantidade_retirada` decimal(12,3) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `comprovante_url` text NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `distribuicao`
--

INSERT INTO `distribuicao` (`id_operacao`, `id_lote`, `id_ong`, `id_voluntario`, `quantidade_retirada`, `data_hora`, `comprovante_url`, `criado_em`) VALUES
(6, 2, 3, 3, 1.847, '2026-04-11 11:25:50', 'comprovantes/dist-001.pdf', '2026-04-11 11:25:50');

--
-- Acionadores `distribuicao`
--
DELIMITER $$
CREATE TRIGGER `tg_distribuicao_timestamp_imutavel` BEFORE UPDATE ON `distribuicao` FOR EACH ROW BEGIN
    IF NEW.data_hora <> OLD.data_hora THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Edição de data_hora em distribuicao é proibida.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_distribuicao_valida` BEFORE INSERT ON `distribuicao` FOR EACH ROW BEGIN
    DECLARE v_status_vol VARCHAR(20);
    DECLARE v_status_ong VARCHAR(20);
    DECLARE v_status_lot VARCHAR(20);
    DECLARE v_qtd_atual  DECIMAL(12,3);
 
    SELECT status_operacional INTO v_status_vol
        FROM voluntario WHERE id_voluntario = NEW.id_voluntario;
    IF v_status_vol <> 'ativo' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Voluntário não está ativo.';
    END IF;
 
    SELECT status_elegibilidade INTO v_status_ong
        FROM ong WHERE id_ong = NEW.id_ong;
    IF v_status_ong <> 'ativo' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ONG não está ativa/elegível.';
    END IF;
 
    SELECT status_operacional, quantidade_atual
        INTO v_status_lot, v_qtd_atual
        FROM estoque WHERE id_lote = NEW.id_lote;
    IF v_status_lot <> 'disponivel' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Lote não está disponível.';
    END IF;
    IF NEW.quantidade_retirada > v_qtd_atual THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quantidade retirada excede saldo do lote.';
    END IF;
 
    UPDATE estoque
    SET quantidade_atual   = quantidade_atual - NEW.quantidade_retirada,
        status_operacional = CASE
            WHEN (quantidade_atual - NEW.quantidade_retirada) = 0 THEN 'esgotado'
            ELSE status_operacional
        END
    WHERE id_lote = NEW.id_lote;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `doacao`
--

CREATE TABLE `doacao` (
  `id_doacao` int(10) UNSIGNED NOT NULL,
  `id_doador` int(10) UNSIGNED NOT NULL,
  `categoria` enum('alimento','roupa','brinquedo','higiene','movel','eletronico','outro') NOT NULL,
  `item` varchar(200) NOT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `unidade_medida` varchar(30) NOT NULL,
  `data_validade` date DEFAULT NULL,
  `estado_conservacao` enum('novo','usado','desgastado') DEFAULT NULL,
  `data_doacao` date NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `doacao`
--

INSERT INTO `doacao` (`id_doacao`, `id_doador`, `categoria`, `item`, `quantidade`, `unidade_medida`, `data_validade`, `estado_conservacao`, `data_doacao`, `criado_em`) VALUES
(3, 3, 'alimento', 'Óleo de soja', 49, 'unidade', '2026-07-13', NULL, '2025-09-30', '2026-04-11 11:22:45');

--
-- Acionadores `doacao`
--
DELIMITER $$
CREATE TRIGGER `tg_doacao_cria_lote` AFTER INSERT ON `doacao` FOR EACH ROW BEGIN
    INSERT INTO estoque (
        codigo_lote, id_doacao, item, quantidade_atual,
        localizacao_fisica, data_validade, status_operacional
    ) VALUES (
        CONCAT('LOT-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(NEW.id_doacao, 4, '0')),
        NEW.id_doacao,
        NEW.item,
        NEW.quantidade,
        'A DEFINIR',
        NEW.data_validade,
        'disponivel'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_doacao_valida_insert` BEFORE INSERT ON `doacao` FOR EACH ROW BEGIN
    IF NEW.data_doacao > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de doação não pode ser futura.';
    END IF;
    IF NEW.categoria = 'alimento' THEN
        IF NEW.data_validade IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alimento exige data de validade.';
        END IF;
        IF NEW.data_validade < CURDATE() THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alimento com validade expirada não pode ser aceito.';
        END IF;
    ELSE
        IF NEW.estado_conservacao IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item não-perecível exige estado de conservação.';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_doacao_valida_update` BEFORE UPDATE ON `doacao` FOR EACH ROW BEGIN
    IF NEW.data_doacao > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de doação não pode ser futura.';
    END IF;
    IF NEW.categoria = 'alimento' THEN
        IF NEW.data_validade IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alimento exige data de validade.';
        END IF;
        IF NEW.data_validade < CURDATE() THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alimento com validade expirada não pode ser aceito.';
        END IF;
    ELSE
        IF NEW.estado_conservacao IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item não-perecível exige estado de conservação.';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_log_doacao` AFTER INSERT ON `doacao` FOR EACH ROW BEGIN

    INSERT INTO logs_sistema (
        tipo,
        categoria,
        acao,
        descricao,
        tabela_afetada,
        id_referencia
    )
    VALUES (
        'INFO',
        'DOACAO',
        'Nova doação cadastrada',
        CONCAT(
            'Doação do item ',
            NEW.item,
            ' cadastrada.'
        ),
        'doacao',
        NEW.id_doacao
    );

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `doador`
--

CREATE TABLE `doador` (
  `id_doador` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `cpf` varchar(15) DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `doador`
--

INSERT INTO `doador` (`id_doador`, `id_usuario`, `cpf`, `nome`, `data_nascimento`, `telefone`, `criado_em`) VALUES
(2, NULL, '123.456.789-00', 'Maria Silva', '1990-05-15', '11987654321', '2026-04-11 11:15:17'),
(3, NULL, '938.761.450-66', 'Cecília Ramos', '1997-04-21', '11929958838', '2026-04-11 11:21:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `id_lote` int(10) UNSIGNED NOT NULL,
  `codigo_lote` varchar(50) NOT NULL,
  `id_doacao` int(10) UNSIGNED NOT NULL,
  `item` varchar(200) NOT NULL,
  `quantidade_atual` decimal(12,3) NOT NULL,
  `localizacao_fisica` varchar(100) NOT NULL DEFAULT 'A DEFINIR',
  `data_validade` date DEFAULT NULL,
  `status_operacional` enum('disponivel','quarentena','avariado','vencido','esgotado') NOT NULL DEFAULT 'disponivel',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `estoque`
--

INSERT INTO `estoque` (`id_lote`, `codigo_lote`, `id_doacao`, `item`, `quantidade_atual`, `localizacao_fisica`, `data_validade`, `status_operacional`, `criado_em`, `atualizado_em`) VALUES
(2, 'LOT-20260411-0003', 3, 'Óleo de soja', 46.836, 'A DEFINIR', '2026-07-13', 'disponivel', '2026-04-11 11:22:45', '2026-04-11 11:25:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id_log` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `tipo` enum('INFO','WARNING','ERROR','CRITICAL') NOT NULL DEFAULT 'INFO',
  `categoria` enum('LOGIN','CADASTRO','DOACAO','ESTOQUE','DISTRIBUICAO','ONG','VOLUNTARIO','USUARIO','SISTEMA','SEGURANCA') NOT NULL,
  `acao` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tabela_afetada` varchar(100) DEFAULT NULL,
  `id_referencia` int(10) UNSIGNED DEFAULT NULL,
  `ip_origem` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `logs_sistema`
--

INSERT INTO `logs_sistema` (`id_log`, `id_usuario`, `tipo`, `categoria`, `acao`, `descricao`, `tabela_afetada`, `id_referencia`, `ip_origem`, `user_agent`, `data_hora`) VALUES
(1, NULL, 'INFO', 'LOGIN', 'Login admin realizado', 'Administrador acessou o painel via 2FA.', 'usuario', 52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 14:50:32'),
(2, 54, 'INFO', 'LOGIN', 'Login admin realizado', 'Administrador acessou o painel via 2FA.', 'usuario', 54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 15:08:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ong`
--

CREATE TABLE `ong` (
  `id_ong` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(300) NOT NULL,
  `cnpj` char(18) NOT NULL,
  `localizacao` varchar(50) NOT NULL,
  `classificacao_risco` enum('emergencia','continuo','pontual','baixa_prioridade') NOT NULL,
  `status_elegibilidade` enum('pendente','aprovado','rejeitado','ativo','suspenso') NOT NULL DEFAULT 'pendente',
  `data_atualizacao` datetime NOT NULL DEFAULT current_timestamp(),
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `sigla_estado` varchar(2) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `descricao` varchar(300) DEFAULT NULL,
  `senha_hash` varchar(200) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `token_confirmacao` varchar(200) DEFAULT NULL,
  `area_atuacao` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ong`
--

INSERT INTO `ong` (`id_ong`, `id_usuario`, `nome`, `cnpj`, `localizacao`, `classificacao_risco`, `status_elegibilidade`, `data_atualizacao`, `criado_em`, `sigla_estado`, `endereco`, `cidade`, `descricao`, `senha_hash`, `email`, `token_confirmacao`, `area_atuacao`) VALUES
(3, NULL, 'Abrigo Esperança', '15.672.083/0001-32', '59797-199', 'emergencia', 'aprovado', '2026-04-11 11:22:07', '2026-04-11 11:22:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, NULL, 'Bismark Otto', '12345678000190', '82560370', 'emergencia', 'ativo', '2026-04-13 10:37:29', '2026-04-13 10:37:29', 'PR', 'Rua Ary Barroso', 'Curitiba', 'aaa', '$2y$10$ZUpDIrUrov0vCZVTjbb.XuNcsI3qvw2RlYnP/o0NYNTzAmdNIh.2u', 'beargamessirwhiter25@gmail.com', '039d7b1291708f162158ce1ec0addc3e', 'Alimentação');

-- --------------------------------------------------------

--
-- Estrutura para tabela `otp_telegram`
--

CREATE TABLE `otp_telegram` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `telegram_chat_id` varchar(64) NOT NULL,
  `codigo` char(6) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `tentativas` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `otp_telegram`
--

INSERT INTO `otp_telegram` (`id`, `id_usuario`, `telegram_chat_id`, `codigo`, `expira_em`, `usado`, `tentativas`, `criado_em`) VALUES
(20, 54, '8002103373', '630806', '2026-05-14 15:13:34', 1, 1, '2026-05-14 15:08:34');

--
-- Acionadores `otp_telegram`
--
DELIMITER $$
CREATE TRIGGER `tg_otp_valida_uso` BEFORE UPDATE ON `otp_telegram` FOR EACH ROW BEGIN
  IF OLD.usado = 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Este código OTP já foi utilizado.';
  END IF;
  IF OLD.expira_em < NOW() THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Código OTP expirado.';
  END IF;
  IF OLD.tentativas >= 5 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Número máximo de tentativas atingido.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `email` varchar(254) NOT NULL,
  `senha_hash` text DEFAULT NULL,
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `telegram_2fa_ativo` tinyint(1) DEFAULT 0,
  `status_cadastro` enum('pendente','confirmado','bloqueado') NOT NULL DEFAULT 'pendente',
  `token_confirmacao` varchar(255) DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `pergunta_seguranca` varchar(255) DEFAULT NULL,
  `resposta_seguranca_hash` varchar(255) DEFAULT NULL,
  `tentativas_recuperacao` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL,
  `chave_2fa` varchar(64) DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('usuario','admin','ong','doador') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `email`, `senha_hash`, `telegram_chat_id`, `telegram_2fa_ativo`, `status_cadastro`, `token_confirmacao`, `token_recuperacao`, `token_expira_em`, `pergunta_seguranca`, `resposta_seguranca_hash`, `tentativas_recuperacao`, `bloqueado_ate`, `chave_2fa`, `data_criacao`, `tipo`) VALUES
(1, '', 'usuario1655@terra.com.br', 'a2ca37fe6fdc490b8f7ce841e1701a169d2b1697c6b5b5c63f94abb8f9b6d6dd', NULL, 0, 'confirmado', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-11 11:21:32', ''),
(2, '', 'teste@teste.com', '$2y$10$35jJlOeRUIRvNodUNE3QNu0Bds70sgshRhCGaoRf3rWYHvwMRsW3S', NULL, 0, 'confirmado', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-11 21:09:44', ''),
(3, 'nome', 'ablu@gmail.com', '$2y$10$lFtaI.y4kU5wlO7sdJs4beD2iLlRSje41ATpdcVdsoEhrDKkG1VkO', NULL, 0, 'pendente', '8062f5936f559de19f6b20e2d916798f6119261e71ad5feab8287d05ceca70b5', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-12 14:54:31', ''),
(5, 'nome', 'email@teste.com', '$2y$10$S4Q4Je8gRKoNrkcNUoHRSOQ9A2gaqt3lyrxyE1LAwU49plENZN/5G', NULL, 0, 'pendente', 'a412968ba7042df1848118e531f1213f52c9be769ded05b75a16871009c69543', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-12 15:01:30', ''),
(54, 'mark', 'mark.oliveira2511@gmail.com', '', '8002103373', 1, 'confirmado', 'bd35d18021469861eceb1ee478d72676e378b5675efaecf8dc29d1e94f8e73cd', NULL, NULL, 'nome_primeiro_pet', '$2y$10$/n6OAfB2bd1hgDFG2R/FCOEF2XSyXDQVYnBQ.GBVtThCkMoEkjGW.', 0, NULL, NULL, '2026-05-14 14:58:56', 'admin');

-- --------------------------------------------------------

--
-- Estrutura para tabela `voluntario`
--

CREATE TABLE `voluntario` (
  `id_voluntario` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `cpf` char(14) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(254) NOT NULL,
  `funcao` enum('recepcionista','motorista','almoxarife','assistente_social','coordenador') NOT NULL,
  `disponibilidade` enum('dias_uteis','fins_de_semana','ambos') NOT NULL,
  `data_entrada` date NOT NULL,
  `status_operacional` enum('ativo','inativo','suspenso') NOT NULL DEFAULT 'ativo',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `voluntario`
--

INSERT INTO `voluntario` (`id_voluntario`, `nome`, `cpf`, `telefone`, `email`, `funcao`, `disponibilidade`, `data_entrada`, `status_operacional`, `criado_em`) VALUES
(3, 'Ryan Mendonça', '052.481.973-41', '11973140807', 'ryan.mendonça430@uol.com.br', 'recepcionista', 'dias_uteis', '2024-01-16', 'ativo', '2026-04-11 11:21:58');

--
-- Acionadores `voluntario`
--
DELIMITER $$
CREATE TRIGGER `tg_voluntario_valida_insert` BEFORE INSERT ON `voluntario` FOR EACH ROW BEGIN
    IF NEW.data_entrada > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de entrada não pode ser futura.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_voluntario_valida_update` BEFORE UPDATE ON `voluntario` FOR EACH ROW BEGIN
    IF NEW.data_entrada > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de entrada não pode ser futura.';
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `distribuicao`
--
ALTER TABLE `distribuicao`
  ADD PRIMARY KEY (`id_operacao`),
  ADD KEY `idx_distribuicao_lote` (`id_lote`),
  ADD KEY `idx_distribuicao_vol` (`id_voluntario`),
  ADD KEY `idx_distribuicao_ong` (`id_ong`);

--
-- Índices de tabela `doacao`
--
ALTER TABLE `doacao`
  ADD PRIMARY KEY (`id_doacao`),
  ADD KEY `idx_doacao_doador` (`id_doador`);

--
-- Índices de tabela `doador`
--
ALTER TABLE `doador`
  ADD PRIMARY KEY (`id_doador`),
  ADD UNIQUE KEY `uq_doador_cpf_cnpj` (`cpf`),
  ADD KEY `idx_doador_usuario` (`id_usuario`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`id_lote`),
  ADD UNIQUE KEY `uq_estoque_codigo_lote` (`codigo_lote`),
  ADD UNIQUE KEY `uq_estoque_doacao` (`id_doacao`),
  ADD KEY `idx_estoque_status` (`status_operacional`),
  ADD KEY `idx_estoque_validade` (`data_validade`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_logs_usuario` (`id_usuario`),
  ADD KEY `idx_logs_tipo` (`tipo`),
  ADD KEY `idx_logs_categoria` (`categoria`),
  ADD KEY `idx_logs_data` (`data_hora`);

--
-- Índices de tabela `ong`
--
ALTER TABLE `ong`
  ADD PRIMARY KEY (`id_ong`),
  ADD UNIQUE KEY `uq_beneficiario_cnpj` (`cnpj`),
  ADD KEY `idx_ong_usuario` (`id_usuario`),
  ADD KEY `idx_ong_status` (`status_elegibilidade`);

--
-- Índices de tabela `otp_telegram`
--
ALTER TABLE `otp_telegram`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_usuario` (`id_usuario`),
  ADD KEY `idx_otp_chat` (`telegram_chat_id`),
  ADD KEY `idx_otp_expira` (`expira_em`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD UNIQUE KEY `uq_usuario_telegram_chat_id` (`telegram_chat_id`);

--
-- Índices de tabela `voluntario`
--
ALTER TABLE `voluntario`
  ADD PRIMARY KEY (`id_voluntario`),
  ADD UNIQUE KEY `uq_voluntario_cpf` (`cpf`),
  ADD UNIQUE KEY `uq_voluntario_telefone` (`telefone`),
  ADD UNIQUE KEY `uq_voluntario_email` (`email`),
  ADD KEY `idx_voluntario_status` (`status_operacional`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `distribuicao`
--
ALTER TABLE `distribuicao`
  MODIFY `id_operacao` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `doacao`
--
ALTER TABLE `doacao`
  MODIFY `id_doacao` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `doador`
--
ALTER TABLE `doador`
  MODIFY `id_doador` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `id_lote` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id_log` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `ong`
--
ALTER TABLE `ong`
  MODIFY `id_ong` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `otp_telegram`
--
ALTER TABLE `otp_telegram`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `voluntario`
--
ALTER TABLE `voluntario`
  MODIFY `id_voluntario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `distribuicao`
--
ALTER TABLE `distribuicao`
  ADD CONSTRAINT `fk_dist_lote` FOREIGN KEY (`id_lote`) REFERENCES `estoque` (`id_lote`),
  ADD CONSTRAINT `fk_dist_ong` FOREIGN KEY (`id_ong`) REFERENCES `ong` (`id_ong`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dist_voluntario` FOREIGN KEY (`id_voluntario`) REFERENCES `voluntario` (`id_voluntario`);

--
-- Restrições para tabelas `doacao`
--
ALTER TABLE `doacao`
  ADD CONSTRAINT `fk_doacao_doador` FOREIGN KEY (`id_doador`) REFERENCES `doador` (`id_doador`);

--
-- Restrições para tabelas `doador`
--
ALTER TABLE `doador`
  ADD CONSTRAINT `fk_doador_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `fk_estoque_doacao` FOREIGN KEY (`id_doacao`) REFERENCES `doacao` (`id_doacao`);

--
-- Restrições para tabelas `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `ong`
--
ALTER TABLE `ong`
  ADD CONSTRAINT `fk_ong_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `otp_telegram`
--
ALTER TABLE `otp_telegram`
  ADD CONSTRAINT `fk_otp_telegram_chat_id` FOREIGN KEY (`telegram_chat_id`) REFERENCES `usuario` (`telegram_chat_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_otp_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_marcar_lotes_vencidos` ON SCHEDULE EVERY 1 DAY STARTS '2026-04-12 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_marcar_lotes_vencidos()$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_suspender_beneficiarios` ON SCHEDULE EVERY 1 DAY STARTS '2026-04-12 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_suspender_beneficiarios_expirados()$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_limpar_otps_expirados` ON SCHEDULE EVERY 1 HOUR STARTS '2026-05-14 12:16:02' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM otp_telegram
     WHERE expira_em < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        OR usado = 1$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
