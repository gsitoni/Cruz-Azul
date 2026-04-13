-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 14-Abr-2026 às 00:22
-- Versão do servidor: 10.4.24-MariaDB
-- versão do PHP: 8.1.6

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
    UPDATE beneficiario
    SET status_elegibilidade = 'suspenso'
    WHERE status_elegibilidade = 'ativo'
      AND data_atualizacao < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `beneficiario`
--

CREATE TABLE `beneficiario` (
  `id_beneficiario` int(10) UNSIGNED NOT NULL,
  `nome_receptor` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnpj` char(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localizacao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classificacao_risco` enum('emergencia','continuo','pontual','baixa_prioridade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_elegibilidade` enum('ativo','suspenso','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `data_atualizacao` datetime NOT NULL DEFAULT current_timestamp(),
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `distribuicao`
--

CREATE TABLE `distribuicao` (
  `id_operacao` int(10) UNSIGNED NOT NULL,
  `id_lote` int(10) UNSIGNED NOT NULL,
  `id_beneficiario` int(10) UNSIGNED NOT NULL,
  `id_voluntario` int(10) UNSIGNED NOT NULL,
  `quantidade_retirada` decimal(12,3) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `comprovante_url` text NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    DECLARE v_status_ben VARCHAR(20);
    DECLARE v_status_lot VARCHAR(20);
    DECLARE v_qtd_atual  DECIMAL(12,3);

    SELECT status_operacional INTO v_status_vol
        FROM voluntario WHERE id_voluntario = NEW.id_voluntario;
    IF v_status_vol <> 'ativo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Voluntário não está ativo.';
    END IF;

    SELECT status_elegibilidade INTO v_status_ben
        FROM beneficiario WHERE id_beneficiario = NEW.id_beneficiario;
    IF v_status_ben <> 'ativo' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Beneficiário não está ativo/elegível.';
    END IF;

    SELECT status_operacional, quantidade_atual
        INTO v_status_lot, v_qtd_atual
        FROM estoque WHERE id_lote = NEW.id_lote;
    IF v_status_lot <> 'disponivel' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lote não está disponível.';
    END IF;
    IF NEW.quantidade_retirada > v_qtd_atual THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantidade retirada excede saldo do lote.';
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
-- Estrutura da tabela `doacao`
--

CREATE TABLE `doacao` (
  `id_doacao` int(10) UNSIGNED NOT NULL,
  `id_doador` int(10) UNSIGNED NOT NULL,
  `categoria` enum('alimento','roupa','brinquedo','higiene','movel','eletronico','outro') NOT NULL,
  `item` varchar(200) NOT NULL,
  `quantidade` decimal(12,3) NOT NULL,
  `unidade_medida` varchar(30) NOT NULL,
  `data_validade` date DEFAULT NULL,
  `estado_conservacao` enum('novo','usado','desgastado') DEFAULT NULL,
  `data_doacao` date NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `STATUS` varchar(20) DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
-- Estrutura da tabela `doador`
--

CREATE TABLE `doador` (
  `id_doador` int(10) UNSIGNED NOT NULL,
  `cpf_cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `estoque`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_hash` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_cadastro` enum('pendente','confirmado','bloqueado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `token_confirmacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_recuperacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `chave_2fa` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `voluntario`
--

CREATE TABLE `voluntario` (
  `id_voluntario` int(10) UNSIGNED NOT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` char(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `funcao` enum('recepcionista','motorista','almoxarife','assistente_social','coordenador') COLLATE utf8mb4_unicode_ci NOT NULL,
  `disponibilidade` enum('dias_uteis','fins_de_semana','ambos') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_entrada` date NOT NULL,
  `status_operacional` enum('ativo','inativo','suspenso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Índices para tabela `beneficiario`
--
ALTER TABLE `beneficiario`
  ADD PRIMARY KEY (`id_beneficiario`),
  ADD UNIQUE KEY `uq_beneficiario_cnpj` (`cnpj`),
  ADD KEY `idx_beneficiario_status` (`status_elegibilidade`);

--
-- Índices para tabela `distribuicao`
--
ALTER TABLE `distribuicao`
  ADD PRIMARY KEY (`id_operacao`),
  ADD KEY `idx_distribuicao_lote` (`id_lote`),
  ADD KEY `idx_distribuicao_ben` (`id_beneficiario`),
  ADD KEY `idx_distribuicao_vol` (`id_voluntario`);

--
-- Índices para tabela `doacao`
--
ALTER TABLE `doacao`
  ADD PRIMARY KEY (`id_doacao`),
  ADD KEY `idx_doacao_doador` (`id_doador`);

--
-- Índices para tabela `doador`
--
ALTER TABLE `doador`
  ADD PRIMARY KEY (`id_doador`),
  ADD UNIQUE KEY `uq_doador_cpf_cnpj` (`cpf_cnpj`),
  ADD UNIQUE KEY `uq_doador_telefone` (`telefone`),
  ADD UNIQUE KEY `uq_doador_email` (`email`);

--
-- Índices para tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`id_lote`),
  ADD UNIQUE KEY `uq_estoque_codigo_lote` (`codigo_lote`),
  ADD UNIQUE KEY `uq_estoque_doacao` (`id_doacao`),
  ADD KEY `idx_estoque_status` (`status_operacional`),
  ADD KEY `idx_estoque_validade` (`data_validade`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`);

--
-- Índices para tabela `voluntario`
--
ALTER TABLE `voluntario`
  ADD PRIMARY KEY (`id_voluntario`),
  ADD UNIQUE KEY `uq_voluntario_cpf` (`cpf`),
  ADD UNIQUE KEY `uq_voluntario_telefone` (`telefone`),
  ADD UNIQUE KEY `uq_voluntario_email` (`email`),
  ADD KEY `idx_voluntario_status` (`status_operacional`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `beneficiario`
--
ALTER TABLE `beneficiario`
  MODIFY `id_beneficiario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id_doador` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `id_lote` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `voluntario`
--
ALTER TABLE `voluntario`
  MODIFY `id_voluntario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `distribuicao`
--
ALTER TABLE `distribuicao`
  ADD CONSTRAINT `fk_dist_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`),
  ADD CONSTRAINT `fk_dist_lote` FOREIGN KEY (`id_lote`) REFERENCES `estoque` (`id_lote`),
  ADD CONSTRAINT `fk_dist_voluntario` FOREIGN KEY (`id_voluntario`) REFERENCES `voluntario` (`id_voluntario`);

--
-- Limitadores para a tabela `doacao`
--
ALTER TABLE `doacao`
  ADD CONSTRAINT `fk_doacao_doador` FOREIGN KEY (`id_doador`) REFERENCES `doador` (`id_doador`);

--
-- Limitadores para a tabela `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `fk_estoque_doacao` FOREIGN KEY (`id_doacao`) REFERENCES `doacao` (`id_doacao`);

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
