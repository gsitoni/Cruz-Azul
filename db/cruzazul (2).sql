-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/04/2026 às 16:11
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
    WHERE status_elegibilidade = 'ativo'
      AND data_atualizacao < DATE_SUB(NOW(), INTERVAL 6 MONTH);
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
) ;

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
) ;

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
) ;

--
-- Despejando dados para a tabela `estoque`
--

INSERT INTO `estoque` (`id_lote`, `codigo_lote`, `id_doacao`, `item`, `quantidade_atual`, `localizacao_fisica`, `data_validade`, `status_operacional`, `criado_em`, `atualizado_em`) VALUES
(2, 'LOT-20260411-0003', 3, 'Óleo de soja', 46.836, 'A DEFINIR', '2026-07-13', 'disponivel', '2026-04-11 11:22:45', '2026-04-11 11:25:50');

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
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) NOT NULL,
  `email` varchar(254) NOT NULL,
  `senha_hash` text NOT NULL,
  `status_cadastro` enum('pendente','confirmado','bloqueado') NOT NULL DEFAULT 'pendente',
  `token_confirmacao` varchar(255) DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `chave_2fa` varchar(64) DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('usuario','admin','ong','doador') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome`, `email`, `senha_hash`, `status_cadastro`, `token_confirmacao`, `token_recuperacao`, `token_expira_em`, `chave_2fa`, `data_criacao`, `tipo`) VALUES
(1, '', 'usuario1655@terra.com.br', 'a2ca37fe6fdc490b8f7ce841e1701a169d2b1697c6b5b5c63f94abb8f9b6d6dd', 'confirmado', NULL, NULL, NULL, NULL, '2026-04-11 11:21:32', ''),
(2, '', 'teste@teste.com', '$2y$10$35jJlOeRUIRvNodUNE3QNu0Bds70sgshRhCGaoRf3rWYHvwMRsW3S', 'confirmado', NULL, NULL, NULL, NULL, '2026-04-11 21:09:44', ''),
(3, 'nome', 'ablu@gmail.com', '$2y$10$lFtaI.y4kU5wlO7sdJs4beD2iLlRSje41ATpdcVdsoEhrDKkG1VkO', 'pendente', '8062f5936f559de19f6b20e2d916798f6119261e71ad5feab8287d05ceca70b5', NULL, NULL, NULL, '2026-04-12 14:54:31', ''),
(5, 'nome', 'email@teste.com', '$2y$10$S4Q4Je8gRKoNrkcNUoHRSOQ9A2gaqt3lyrxyE1LAwU49plENZN/5G', 'pendente', 'a412968ba7042df1848118e531f1213f52c9be769ded05b75a16871009c69543', NULL, NULL, NULL, '2026-04-12 15:01:30', '');

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
-- Índices de tabela `ong`
--
ALTER TABLE `ong`
  ADD PRIMARY KEY (`id_ong`),
  ADD UNIQUE KEY `uq_beneficiario_cnpj` (`cnpj`),
  ADD KEY `idx_ong_usuario` (`id_usuario`),
  ADD KEY `idx_ong_status` (`status_elegibilidade`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`);

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
  MODIFY `id_operacao` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `doacao`
--
ALTER TABLE `doacao`
  MODIFY `id_doacao` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `doador`
--
ALTER TABLE `doador`
  MODIFY `id_doador` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `id_lote` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ong`
--
ALTER TABLE `ong`
  MODIFY `id_ong` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
-- Restrições para tabelas `ong`
--
ALTER TABLE `ong`
  ADD CONSTRAINT `fk_ong_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_marcar_lotes_vencidos` ON SCHEDULE EVERY 1 DAY STARTS '2026-04-12 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_marcar_lotes_vencidos()$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_suspender_beneficiarios` ON SCHEDULE EVERY 1 DAY STARTS '2026-04-12 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_suspender_beneficiarios_expirados()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
