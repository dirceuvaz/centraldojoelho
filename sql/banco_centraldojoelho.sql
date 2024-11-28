-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 28/11/2024 às 07:30
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `centraldojoelho`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `atividades_paciente`
--

DROP TABLE IF EXISTS `atividades_paciente`;
CREATE TABLE IF NOT EXISTS `atividades_paciente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `tipo_atividade` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_atividade` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_paciente` (`id_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atividades_paciente`
--

INSERT INTO `atividades_paciente` (`id`, `id_paciente`, `tipo_atividade`, `descricao`, `data_atividade`) VALUES
(1, 2, 'resposta_perguntas', 'Respondeu às perguntas da reabilitação', '2024-11-27 10:01:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cirurgias`
--

DROP TABLE IF EXISTS `cirurgias`;
CREATE TABLE IF NOT EXISTS `cirurgias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `tipo_cirurgia` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_cirurgia` date NOT NULL,
  `status` enum('agendada','realizada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'agendada',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_paciente` (`id_paciente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `exercicios`
--

DROP TABLE IF EXISTS `exercicios`;
CREATE TABLE IF NOT EXISTS `exercicios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int DEFAULT NULL,
  `tipo_exercicio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_realizacao` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `id_paciente` (`id_paciente`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicos`
--

DROP TABLE IF EXISTS `medicos`;
CREATE TABLE IF NOT EXISTS `medicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `crm` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuario` (`id_usuario`),
  KEY `idx_crm` (`crm`),
  KEY `idx_status` (`status`),
  KEY `idx_especialidade` (`especialidade`),
  KEY `idx_status_data` (`status`,`data_criacao`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medicos`
--

INSERT INTO `medicos` (`id`, `id_usuario`, `crm`, `especialidade`, `status`, `data_criacao`, `data_atualizacao`) VALUES
(3, 4, '12345', 'Ortopedista', 'ativo', '2024-11-21 21:22:21', '2024-11-22 13:06:48'),
(4, 5, '12346', 'Psiquiatra', 'ativo', '2024-11-21 21:22:48', NULL),
(5, 32, '12322', 'Traumatologia', '', '2024-11-23 04:40:20', '2024-11-23 04:40:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `midias`
--

DROP TABLE IF EXISTS `midias`;
CREATE TABLE IF NOT EXISTS `midias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_upload` datetime NOT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `momentos_reabilitacao`
--

DROP TABLE IF EXISTS `momentos_reabilitacao`;
CREATE TABLE IF NOT EXISTS `momentos_reabilitacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ordem` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `momentos_reabilitacao`
--

INSERT INTO `momentos_reabilitacao` (`id`, `nome`, `descricao`, `ordem`, `created_at`, `updated_at`) VALUES
(1, 'Pré-operatório', 'Fase anterior à cirurgia', 1, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(2, '1ª Semana', 'Primeira semana após a cirurgia', 2, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(3, '2ª Semana', 'Segunda semana após a cirurgia', 3, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(4, '3ª Semana', 'Terceira semana após a cirurgia', 4, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(5, '4ª Semana', 'Quarta semana após a cirurgia', 5, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(6, '5ª Semana', 'Quinta semana após a cirurgia', 6, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(7, '6ª Semana', 'Sexta semana após a cirurgia', 7, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(8, '2º Mês', 'Segundo mês de reabilitação', 8, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(9, '3º Mês', 'Terceiro mês de reabilitação', 9, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(10, '4º Mês', 'Quarto mês de reabilitação', 10, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(11, '5º Mês', 'Quinto mês de reabilitação', 11, '2024-11-27 13:53:11', '2024-11-27 13:53:11'),
(12, '6º Mês', 'Sexto mês de reabilitação', 12, '2024-11-27 13:53:11', '2024-11-27 13:53:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orientacoes_reabilitacao`
--

DROP TABLE IF EXISTS `orientacoes_reabilitacao`;
CREATE TABLE IF NOT EXISTS `orientacoes_reabilitacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `momento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_criacao` datetime NOT NULL,
  `data_atualizacao` datetime NOT NULL,
  `id_medico` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_medico` (`id_medico`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `data_cirurgia` date NOT NULL,
  `fisioterapeuta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `problema` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  `medico` int NOT NULL,
  `tipo_cirurgia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_cirurgia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes_cirurgia` text COLLATE utf8mb4_unicode_ci,
  `id_reabilitacao` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `fk_paciente_medico` (`medico`),
  KEY `id_reabilitacao` (`id_reabilitacao`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`id`, `id_usuario`, `data_cirurgia`, `fisioterapeuta`, `problema`, `status`, `data_cadastro`, `medico`, `tipo_cirurgia`, `local_cirurgia`, `observacoes_cirurgia`, `id_reabilitacao`) VALUES
(1, 3, '2024-10-31', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-19 16:20:28', 4, '1', NULL, NULL, NULL),
(3, 7, '2024-11-23', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-20 23:39:40', 4, '1', NULL, NULL, NULL),
(4, 8, '2024-11-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 01:14:28', 4, '1', NULL, NULL, NULL),
(8, 12, '2024-11-11', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 01:58:21', 4, '1', NULL, NULL, NULL),
(9, 13, '2024-11-16', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 12:11:07', 4, '1', NULL, NULL, NULL),
(10, 14, '2024-12-04', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 14:40:22', 4, '1', NULL, NULL, NULL),
(11, 18, '2024-11-01', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 16:15:48', 4, '1', NULL, NULL, NULL),
(12, 20, '2024-11-12', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-21 16:23:54', 4, '1', NULL, NULL, NULL),
(13, 2, '2024-11-13', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', '', '2024-11-22 17:28:52', 4, '1', NULL, NULL, 20),
(14, 22, '2024-11-20', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-22 17:56:42', 4, '1', NULL, NULL, NULL),
(15, 24, '2024-11-11', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-22 20:08:18', 4, '1', NULL, NULL, NULL),
(16, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-22 22:55:19', 4, '1', NULL, NULL, NULL),
(17, 28, '2024-11-22', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-22 23:31:56', 4, '1', NULL, NULL, NULL),
(18, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:54:04', 4, '1', NULL, NULL, NULL),
(19, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:54:12', 4, '1', NULL, NULL, NULL),
(20, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:54:22', 4, '1', NULL, NULL, NULL),
(21, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:54:35', 4, '1', NULL, NULL, NULL),
(22, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:54:52', 4, '1', NULL, NULL, NULL),
(23, 27, '2024-10-08', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Cancelada', '2024-11-23 03:55:11', 4, '1', NULL, NULL, NULL),
(25, 29, '2024-11-30', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 04:12:06', 4, '1', NULL, NULL, NULL),
(27, 31, '2024-11-15', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 04:14:56', 4, '1', NULL, NULL, NULL),
(29, 15, '2024-11-22', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 07:40:39', 4, '1', NULL, NULL, NULL),
(31, 16, '2024-11-30', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 07:41:15', 4, '1', NULL, NULL, NULL),
(34, 21, '0000-00-00', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 07:43:38', 4, '1', NULL, NULL, NULL),
(35, 23, '2024-11-09', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'Realizada', '2024-11-23 07:43:58', 32, '1', NULL, NULL, NULL),
(36, 33, '0000-00-00', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-23 09:53:10', 4, '1', NULL, NULL, NULL),
(37, 13, '2024-11-13', 'Dra. Juliana', 'Joelho', 'Realizada', '2024-11-27 08:59:17', 4, NULL, NULL, NULL, NULL),
(42, 39, '2024-10-29', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-28 03:07:14', 4, NULL, NULL, NULL, NULL),
(43, 40, '2024-11-21', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-28 03:12:45', 4, NULL, NULL, NULL, NULL),
(44, 41, '2024-11-16', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-28 03:17:18', 4, NULL, NULL, NULL, NULL),
(45, 42, '2024-11-19', 'Dra. Juliana', 'Ligamento Cruzado Anterior (LCA)', 'ativo', '2024-11-28 03:18:45', 4, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `paciente_respostas`
--

DROP TABLE IF EXISTS `paciente_respostas`;
CREATE TABLE IF NOT EXISTS `paciente_respostas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pergunta` int NOT NULL,
  `id_paciente` int NOT NULL,
  `resposta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_resposta` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_pergunta` (`id_pergunta`),
  KEY `id_paciente` (`id_paciente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perguntas`
--

DROP TABLE IF EXISTS `perguntas`;
CREATE TABLE IF NOT EXISTS `perguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sequencia` varchar(75) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_reabilitacao` int DEFAULT NULL,
  `momento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `id_paciente` int DEFAULT NULL,
  `criado_por` int NOT NULL,
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `id_medico` int DEFAULT NULL,
  `data_comentario` datetime DEFAULT NULL,
  `comentario_afirmativo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario_negativo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_momento` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_medico` (`id_medico`),
  KEY `id_reabilitacao` (`id_reabilitacao`),
  KEY `fk_pergunta_momento` (`id_momento`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perguntas`
--

INSERT INTO `perguntas` (`id`, `sequencia`, `id_reabilitacao`, `momento`, `titulo`, `descricao`, `id_paciente`, `criado_por`, `data_criacao`, `data_atualizacao`, `id_medico`, `data_comentario`, `comentario_afirmativo`, `comentario_negativo`, `id_momento`) VALUES
(31, '1', 27, NULL, 'Está com Inchaço ou dores', '<p>Seu joelho ainda está inchado e dói??<br>&nbsp;</p>', NULL, 1, '2024-11-24 20:10:00', '2024-11-24 20:29:17', 4, NULL, 'Verificar o exercício (acesse o link exercícios) para controle de inchaço.', 'Parabéns, você esta indo muito bem.', NULL),
(32, '2', 1, NULL, 'Você consegue esticar a perna completamente (extensão de 0 grau)?', '<p>Você consegue esticar a perna completamente (extensão de 0 grau)?&nbsp;</p>', NULL, 1, '2024-11-24 20:11:03', '2024-11-24 20:20:11', 4, NULL, 'Muito bom, PARABÉNS!', 'Intensifique para 3 vezes no dia por 15 minutos os exercícios: \r\nEXTENSÃO PASSIVA DO JOELHO\r\nELEVAÇÃO DO TORNOZELO\r\nPENDÊNCIA DA PERNA EM DECUBITO PRONO\r\nEXERCITANDO O QUADRÍCEPS', NULL),
(33, '3', 1, NULL, 'Você consegue dobrar o joelho até 90 graus?', '<p>Você consegue dobrar o joelho até 90 graus?</p>', NULL, 1, '2024-11-24 20:12:46', '2024-11-24 20:20:54', 4, NULL, 'Parabéns, você está indo muito bem. Mantenha-se focado e retorne com médico na próxima semana. \r\n\r\nExistirão outros desafios. Ao final de cada semana você deve sempre preencher esse rápido questionário.', 'Intensifique para 3 vezes por dia por 15 minutos os exercícios:\r\nFLEXÃO PASSIVA\r\nEXERCITANDO O QUADRÍCEPS\r\n\r\nEntrar em contato com seu médico.', NULL),
(34, '2', 20, NULL, 'Seu joelho ainda esta inchado e doí?', '<p>Seu joelho ainda esta inchado e doí?&nbsp;</p>', NULL, 1, '2024-11-24 20:18:59', '2024-11-24 20:19:34', 4, NULL, 'Olá vá no link exercícios, temos que trabalhar o Controle da Dor e do Inchaço.', 'Parabéns, estamos no caminho certo para a recuperação do seu Joelho.', NULL),
(35, '2', 20, NULL, 'Você consegue esticar a perna completamente (extensão de 0 grau)?', '<p>Você consegue esticar a perna completamente (extensão de 0 grau)?&nbsp;</p>', NULL, 1, '2024-11-24 20:39:08', '2024-11-24 20:39:08', 4, NULL, 'Parabéns, continue assim :)', 'Intensifique para 3 vezes por dia por 15 minutos os exercicios: EXTENSÃO PASSIVA DO JOELHO, ELEVAÇÃO DO TORNOZELO, PENDÊNCIA DA PERNA EM DECUBITO PRONO, EXERCITANDO O QUADRÍCEPS', NULL),
(36, '3', 21, NULL, 'Você consegue dobrar o joelho entre 100-120 graus?', '<p>Você consegue dobrar o joelho entre 100-120 graus?</p>', NULL, 1, '2024-11-24 20:40:21', '2024-11-24 20:40:21', 4, NULL, 'Ótimo, seus resultados estão cada vez melhores! Próxima semana teremos metas ainda mais desafiadoras.', 'B. Exercícios para flexão completa (B1, B2 E B3)  . ( Exercicios do Pre-OP) + notificar medico e FST', NULL),
(37, '1', 20, NULL, 'Ainda sente dores ?', '<p>Ainda sente dores ?</p>', NULL, 1, '2024-11-24 20:43:53', '2024-11-24 20:43:53', 4, NULL, 'Obrigado !', 'Sinto muito, vamos ajuda-lo.', NULL),
(38, '4', 22, NULL, 'Você consegue dobrar o joelho em 120 graus de flexão?', '<p>Você consegue dobrar o joelho em 120 graus de flexão?</p>', NULL, 1, '2024-11-24 20:47:38', '2024-11-24 20:47:38', 4, NULL, 'Parabéns, continue assim!', 'B. Exercícios para flexão completa (B1, B2 E B3)  . ( Exercicios do Pre-OP) + notificar medico e FST', NULL),
(39, '', 26, 'Decima_Primeira_a_Vigesima_Semana', 'Você consegue caminhar rápido e andar em zigue zague?', '<p>Você consegue caminhar rápido e realizar movimentos em zigue-zague?</p>', NULL, 1, '2024-11-24 20:51:55', '2024-11-27 09:36:11', 4, NULL, 'Ótimo! Estamos quase concluindo a reabilitação. Retorne no início do sexto mês após a cirurgia para liberação dos exercícios e retomada à vida normal.', 'Iniciar programa de corrida em linha reta (ida e volta).\r\n\r\nApós a fase de jogging, introduzir corrida funcional.\r\n\r\nPróximo à 20ª semana, começar exercícios de zig-zag e treinos de agilidade.\r\n\r\nInformar o médico e o fisioterapeuta funcional (FST).', NULL),
(40, '1', 28, NULL, 'Você acha que atingiu suas expectativas? (em sua opinião)', '<p>Você acha que atingiu suas expectativas? (em sua opinião)</p>', NULL, 1, '2024-11-24 20:52:49', '2024-11-24 20:52:49', 4, NULL, 'Converse com seu médico sobre retorno ao esporte.', 'Talvez ainda não seja a hora de retornar ao esporte. Converse com seu médico.', NULL),
(41, '1', 29, NULL, 'Você acha que atingiu suas expectativas? (em sua opinião)', '<p>Você acha que atingiu suas expectativas? (em sua opinião)</p>', NULL, 1, '2024-11-27 00:27:24', '2024-11-27 00:27:24', 4, NULL, 'Converse com seu médico sobre retorno ao esporte.', 'Talvez ainda não seja a hora de retornar ao esporte. Converse com seu médico.', NULL),
(42, '1', 27, NULL, 'Tudo certo para ir a cirurgia', '<p>Tudo pronto para a cirurgia ?</p>\r\n', NULL, 1, '2024-11-27 08:35:31', '2024-11-27 12:23:14', 4, NULL, '<p>Tudo certoo!</p>\r\n', '<p>Houve altera&ccedil;&otilde;es no exames.</p>\r\n', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `reabilitacao`
--

DROP TABLE IF EXISTS `reabilitacao`;
CREATE TABLE IF NOT EXISTS `reabilitacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `momento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sequencia` int DEFAULT '0',
  `id_pergunta` int DEFAULT NULL,
  `tipo_problema` int NOT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_medico` int DEFAULT NULL,
  `id_paciente` int DEFAULT NULL,
  `duracao_dias` int NOT NULL DEFAULT '7',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  KEY `id_medico` (`id_medico`),
  KEY `fk_momento` (`momento`),
  KEY `fk_tipo` (`tipo_problema`) USING BTREE,
  KEY `id_paciente` (`id_paciente`),
  KEY `id_pergunta` (`id_pergunta`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `reabilitacao`
--

INSERT INTO `reabilitacao` (`id`, `titulo`, `texto`, `momento`, `sequencia`, `id_pergunta`, `tipo_problema`, `data_criacao`, `data_atualizacao`, `id_medico`, `id_paciente`, `duracao_dias`, `status`) VALUES
(1, 'Primeira Semana', '<p><strong>Pós-Operatório</strong></p><p><strong>PRIMEIRA SEMANA</strong></p><p>Vejo que você se encontra na primeira semana de pós-operatório.</p><p>Essa é uma fase crucial pra sua recuperação. Veja o <strong>ponto chave</strong> e os <strong>objetivos</strong> dessa semana.</p><p><strong>Ponto chave:&nbsp;</strong>trabalhar para obter extensão completa do joelho.</p><p>Ao final da semana (na sexta feira, no sábado ou no domingo) você deverá entrar novamente no site e responder 3 perguntas. São 3 cliques e nao tomarão mais que 5 minutos do seu tempo. Sua recuperação está em jogo!</p><p><strong>OBJETIVOS:</strong></p><p>- Controle da dor e do Inchaço</p><p>- Cuidados de curativo e manutenção da ferida operatória limpa e seca</p><p>- Exercícios de mobilidade precoce do joelho</p><p>- Atingir e manter a extensão completa</p><p>- Prevenir atrofia do quadríceps</p><p><strong>Controle da dor e do inchaço:</strong></p><p><strong>Controle do inchaço:</strong>&nbsp;manter o membro inferior elevado e fazer compressas de gelo por 15 minutos, 3 vezes/dia. Você pode levantar-se para ir ao banheiro, fazendo uso de muletas. Nas ocasiões em que se sentar, manter o tornozelo elevado, apoiado em uma cadeira ou superfície que mantenha o tornozelo na altura do quadril.</p><p><strong>Controle da dor:</strong>&nbsp;Você será medicado com medicação analgésica potente. Use-a sempre que dor intensa. Faca uma compressa de gelo por 20 min 3 vezes por dia.&nbsp;</p><p>Assim que a dor e o inchaço diminuírem você poderá usar as muletas para locomover-se por pequenas distâncias. Evite exageros.</p><p><strong>Cuidados com o joelho:</strong></p><p>- No primeiro dia de pós-operatório, a faixa imobilizadora pode ficar umedecida de sangue. Isso é normal! A saída de sangue pelo curativo - - É normal durante os primeiros dias e é estimulada pelo uso da medicação antitrombótica.</p><p>- É importante manter a incisão seca nos primeiros 10 dias.</p>', 'Primeira Semana', 0, NULL, 1, '2024-11-23 16:38:08', '2024-11-23 19:52:53', 4, NULL, 7, 'ativo'),
(20, 'Segunda Semana', '<p>Olá, você passou pela fase mais difícil da reabilitação. Nessa semana, todo o seu treinamento será feito na clinica de reabilitação.&nbsp;</p><p>Ao final da semana (sexta-feira, sábado ou domingo) não esqueça de retornar ao site e preencher 3 perguntas. Não levara mais que 5 minutos!</p><p><strong>Objetivos:</strong> Fisioterapia, manter extensão completa, retorno ao trabalho</p><ul><li>− A aparência da cicatriz pode ser melhorada evitando-se a luz solar</li><li>− Iniciar FST dirigida</li></ul>', 'Segunda Semana', 0, NULL, 1, '2024-11-23 17:17:50', '2024-11-23 18:10:54', 4, NULL, 14, 'ativo'),
(21, 'Terceira Semana', '<p>Olá, você se encontra na terceira semana de operado. Esses são seus objetivos:</p><p>- &nbsp; &nbsp; Desenvolver controle muscular para desmame de muletas e marcha</p><p>- &nbsp; &nbsp; Controle do inchaço</p><p>- &nbsp; &nbsp; Manter extensão completa e desenvolver a musculatura&nbsp; é muito importante</p><p>- &nbsp; &nbsp; Continue fazendo os exercícios de elevação da perna estendida, flexão por gravidade assistida, extensão ativa assistida, isométrico de quadríceps e levantamento da perna com o joelho em extensão</p><p>- &nbsp; &nbsp;Trabalhar entre 90-100 graus de flexão</p><p>- Desenvolvendo controle muscular</p><p>- Agachamento parcial</p><p>- Posicione os pés ao nível dos ombros, com os pés em ligeira rotação externa</p><p>- Use apoio de uma mesa para estabilidade</p><p>- Gentilmente empurra a região dos glúteos para baixo e para trás</p><p>- Segure por 6 segundos</p><p>- Faça 10 repetições, 3 vezes ao dia</p><p>&nbsp;</p><p><strong>Flexão Plantar</strong></p><p><strong><img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/Captura%20de%20tela%202013-11-22%20%C3%A0s%2014.25.10.png\" alt=\"\"></strong></p><p>- Gentilmente apoiando em uma mesa, eleve os calcanhares do solo</p><p>- Segure por 6 segundos</p><p>- Retorne calmamente a posição inicial</p><p>- 3 séries de 10 repetições por dia</p><p>−Faça o desmame das muletas quando houver perfeito controle do peso da perna e não hover flacidez da musculatura</p><p>−&nbsp;A bicicleta estacionária deve ser estimulada, usando-se o pedal da perna não operada para impulsionar o movimento. Não se usa resistência nessa fase. Iniciar por 5 min por dia e ir aumentando conforme tolerância ao longo das semanas.</p><p><img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/Captura%20de%20tela%202013-11-22%20%C3%A0s%2014.26.07.png\" alt=\"\"></p><p><br>&nbsp;</p>', 'Terceira Semana', 0, NULL, 1, '2024-11-23 17:30:36', '2024-11-23 18:10:29', 4, NULL, 21, 'ativo'),
(22, 'Quarta Semana', '<p><strong>Quarta Semana</strong></p><p>- Olá, você está na quarta semana de operado. Seus objetivos para essa semana são:&nbsp;</p><p>- Mobilidade completa e força muscular</p><p>- A mobilidade esperada ótima é de completa extensão do joelho a 120 - 140 graus de flexão</p><p>- Adicionar deslizamento na parede, flexão do joelho com ajuda das 2 mãos</p><p>- Continue isométrico de quadríceps e levantamento da perna em extensão</p><p>- Continue Agachamentos parciais e flexão plantar</p><p>- Permitidos em academia: bicicleta estacioná-ria, elíptico (15-20 min por dia), exercícios de tronco superior</p><p>- Natação: andar na piscina, batimento de pernas (mobilizando o quadril), bicicleta aquática, trote na água. Proibido mergulhos e chutes na piscina</p><p><br>&nbsp;</p>', 'Quarta Semana', 0, NULL, 1, '2024-11-23 17:31:42', '2024-11-23 18:10:14', 4, NULL, 28, 'ativo'),
(23, 'Quinta Semana', '<p><strong>Quinta e Sexta Semana</strong></p><p>Você passou do primeiro mês de operado. Nessa etapa temos como objetivo:</p><p><strong>Objetivos:</strong> 125 graus de flexão tentando flexão máxima e fortalecimento muscular</p><p>Você devera retornar ao site quando completar 6 semanas de operado (1 mês e meio após sua cirurgia) para responder alguns quesitos. Você já sabe: e muito rápido e fácil!</p><p>- Adicionar o deslizamento de parede se a flexão não estiver dentro do padrão ótimo</p><p>- Continuar o fortalecimento do quadríceps, levantamento da perna com joelho em extensão, agachamento parcial, flexão plantar, bicicleta estacionária, elíptico</p><p><img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/Captura%20de%20tela%202013-11-22%20%C3%A0s%2015.01.52.png\" alt=\"\"></p><p>- Iniciar leg press (de 70 a 0 graus, com abertura de pernas nivelada em ombros, em leve rotação externa de forma assistida)</p><p><strong>- Exercícios de propriocepção</strong></p><p><strong><img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/Captura%20de%20tela%202013-11-22%20%C3%A0s%2015.02.01.png\" alt=\"\"></strong></p><p><br>&nbsp;</p>', 'Quinta Semana', 0, NULL, 1, '2024-11-23 17:34:41', '2024-11-23 18:09:58', 4, NULL, 35, 'ativo'),
(24, 'Sexta a Décima Semana', '<p>- Olá, já temos mais de 6 meses de operado. E hora de seguir a vida normal e preparar pro retorno ao esporte.</p><p>Veja as condições de retorno ao esporte:</p><p><strong>− Condições de retorno ao esporte:</strong></p><ul><li>1. Força de Quadríceps de pelo menos 80% da perna normal</li><li>2. Força de flexores de pelo menos 80% da perna normal</li><li>3. Completa mobilidade</li><li>4. Ausência de Inchaço</li><li>5. Boa estabilidade</li><li>6. Habildade de completar o programa de corrida</li></ul><p><br>&nbsp;</p>', 'Sexta a Décima Semana', 0, NULL, 1, '2024-11-23 17:35:28', '2024-11-23 20:13:07', 4, NULL, 70, 'ativo'),
(26, 'Décima Primeira a Vigésima Semana', '<p>Olá, você já está passado de 3 meses de operado. Iniciaremos agora uma reabilitação em academia (Converse com seu médico sobre o plano de academia), alem dos reforços de casa.</p><p>Quando completar 5 meses de operado retorne ao site para responder 1 pergunta. Levará menos de 2 min!</p><p><strong>Objetivos:</strong>&nbsp;Continuar fortalecimento, introduzir jogging e corrida leve, introduzir zig-zag, determinar uso do brace funcional de LCA</p><p>− Mantenha todos os exercícios da fase anterior</p><p>− Iniciar programa de corrida em linha reta para frente e para trás</p><p>− Após a fase de jogging, iniciar programa de corrida funcional</p><p>− Próximo da vigésima semana inicar exercícios de <strong>zig-zag</strong> e de agilidade</p>', 'Decima_Primeira_a_Vigesima_Semana', 0, 39, 1, '2024-11-23 18:04:04', '2024-11-27 12:36:11', 4, NULL, 140, 'ativo'),
(27, 'Pré Operatório', '<p>Sua cirurgia ainda não aconteceu. Até lá, preparamos um programa de exercícios pra você. Eles vão ajudá-lo (a)<br>a ter uma recuperação mais rápida e um melhor retorno às suas atividades.<br>Leia sobre sua cirurgia na aba \"tratamento cirúrgico\" e converse com seu médico<br>Vamos a eles...</p><p>A. Exercícios para obter&nbsp; <strong>extensão completa do joelho</strong></p><p>&nbsp;</p><p><strong>1. Extensão passiva do joelho</strong></p><p>- Sente-se em uma cadeira e posicione o calcanhar em outra cadeira a sua frente</p><p>- Relaxe a musculatura da coxa</p><p>- Deixe o joelho estender-se pela ação da gravidade até a completa extensão</p><p>&nbsp;</p><p><strong>2.&nbsp; Elevação do tornozelo</strong></p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/elevazao.png\" alt=\"\"></p><p>- Deite-se de barriga para cima e coloque uma toalha enrolada embaixo de seu tornozelo. Tenha certeza de que a toalha fez volume suficiente para erguer a perna e também a coxa da cama.</p><p>- Deixe a perna relaxar até que a extensão máxima seja obtida</p><p>- Fazer esse exercícios 3 vezes/dia por 10-15 min em cada sessão</p><p>&nbsp;</p><p><strong>3. Pendencia da perna em decúbito prono</strong></p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/pendencia.png\" alt=\"\"></p><p>- Deite-se com a barriga encostada em sua cama ou mesa até que a região do joelho e perna e tornozelo fiquem para fora da cama</p><p>- Deixe a gravidade baixar sua perna, estendendo o joelho até o máximo</p><p>&nbsp;</p><p><strong>B</strong>. Exercícios para&nbsp;<strong>flexão completa do joelho</strong></p><p>&nbsp;</p><p>1. Flexão passiva do joelho</p><p>- Sente-se na ponta da cama/mesa e deixe o joelho dobrar sobre a ação da gravidade.</p><p>&nbsp;</p><p>2. Deslizamento na parede</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/desli.png\" alt=\"\"></p><p>- Deite-se de barriga para cima em frente a uma parede e apóie o joelho acometido na parede. Com a outra perna force progressivamente a perna acometida para baixo</p><p>&nbsp;</p><p>3. Deslizamento do calcanhar</p><p>- Deite-se na cama de barriga para cima. Deslize o calcanhar em direção a coxa até o máximo que você consiga. Segure por 5 segundos. Depois relaxe esticando a perna, deslizando o calcanhar até o fim, de forma a deixar a perna reta. Mantenha nessa posição por mais 5 segundos.</p><p>- Repita isso por 4 vezes&nbsp; - manha, tarde e noite.</p><p>&nbsp;&nbsp;&nbsp;</p><p><img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/d.png\" alt=\"\"> &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;<img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/d2.png\" alt=\"\"></p><p>&nbsp;</p><p>&nbsp;</p><p>- Nos estágios finais da reabilitação, faça o deslizamento de calcâneo com a ajuda das 2 mãos, forçando a flexão máxima</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img src=\"http://www.centrodojoelho.com.br/uploads/reabilitacao/d3.png\" alt=\"\"></p><p>&nbsp;</p><p>&nbsp;</p><p><strong>Preparo Psicológico:</strong></p><p>- Entenda qual a expectativa real do resultado cirúrgico</p><p>- Prepare-se para a reabilitação fisioterápica pós-operatória</p><p>- Organize-se em seu local de trabalho sobre o período de recuperação e informe sobre o período em que ficará ausente e sobre o período em que retornará com adaptações.</p><p>&nbsp;</p><p><strong>A Cirurgia</strong></p><p>&nbsp;</p><p><strong>Dia anterior</strong></p><p>- Sua cirurgia exige jejum de 8h (por exemplo: se a cirurgia está marcada para as 8h da manhã, sua última refeição deverá ocorrer até meia noite anterior)</p><p>- Leve ao Hospital itens de necessidade pessoal.</p><p>- Providencie Meias elasticas anti-trombo 7/8 entre 18 - 23 mmHg</p><p>- Seu medico ira prescrever apos a cirurgia uma medicacao anti-trombo (enoxaparina sodica 40mg). Essa medicacao sera usada ate o decimo dia apos a cirurgia</p><p>&nbsp;</p><p><strong>Dia da Cirurgia</strong></p><p>- Chegue ao Hospital com pelo menos 3h de antecedência à cirurgia. O processo de internação e acomodação em seu quarto pode levar tempo, atrasando ou inviabilizando sua cirurgia.</p><p>- A cirurgia tem duração aproximada de 60 min. Você permanecerá no Centro Cirúrgico por mais tempo. A anestesia e seu preparo, bem como a recuperação pós-anestésica aumentarão o tempo de permanência no Centro Cirúrgico.</p><p>- O joelho operado será marcado com um \"X\"&nbsp; que poderá permanecer por até quatro dias.</p><p>- A região em torno do joelho operado será submetida a tricotomia, ou seja, será depilada para a cirurgia em formato de um retângulo, na frente do joelho.</p><p>- Você sairá da cirurgia com uma faixa imobilizadora de joelho e um dreno de sucção. Após a cirurgia, você será visitado(a) pelo médico, que irá orientá-lo (a)&nbsp; quanto aos cuidados imediatos e verificar o dreno de sucção.</p><p>- Um travesseiro será colocado em baixo do calcanhar, do mesmo lado operado. Não posicione o travesseiro em baixo do joelho. Essa posição, apesar de mais confortável, poderá retardar sua reabilitação.</p><p>&nbsp;</p>', 'Pré Operatório', 0, NULL, 1, '2024-11-23 18:08:40', '2024-11-23 18:08:40', 4, NULL, 1, 'ativo'),
(28, 'Sexto Mês (Joelho)', '<p>- Olá, já temos mais de 6 meses de operado. E hora de seguir a vida normal e preparar pro retorno ao esporte.</p><p>Veja as condições de retorno ao esporte:</p><p><strong>− Condições de retorno ao esporte:</strong></p><p>1. Força de Quadríceps de pelo menos 80% da perna normal</p><p>2. Força de flexores de pelo menos 80% da perna normal</p><p>3. Completa mobilidade</p><p>4. Ausência de Inchaço</p><p>5. Boa estabilidade</p><p>6. Habildade de completar o programa de corrida</p><p><br>&nbsp;</p>', '12', 0, NULL, 1, '2024-11-23 18:36:38', '2024-11-28 05:19:01', 4, NULL, 180, 'ativo'),
(29, 'Sexto Mês (Pé)', '<p>- Olá, já temos mais de 6 meses de operado. E hora de seguir a vida normal e preparar pro retorno ao esporte.</p><p>Veja as condições de retorno ao esporte:</p><p><strong>− Condições de retorno ao esporte:</strong></p><p>1. Força de Quadríceps de pelo menos 80% da perna normal</p><p>2. Força de flexores de pelo menos 80% da perna normal</p><p>3. Completa mobilidade</p><p>4. Ausência de Inchaço</p><p>5. Boa estabilidade</p><p>6. Habildade de completar o programa de corrida</p><p><br>&nbsp;</p>', 'Sexto Mês', 0, 37, 2, '2024-11-27 03:27:24', '2024-11-27 12:21:17', 32, NULL, 180, 'ativo'),
(30, 'Sétimo Mês', '<p>Sétimo Mês</p>', 'Sétimo Mês', 0, 37, 1, '2024-11-27 11:51:21', '2024-11-27 12:20:25', 4, NULL, 210, 'inativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `respostas`
--

DROP TABLE IF EXISTS `respostas`;
CREATE TABLE IF NOT EXISTS `respostas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pergunta` int NOT NULL,
  `id_paciente` int NOT NULL,
  `resposta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_resposta` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_resposta` (`id_pergunta`,`id_paciente`),
  KEY `id_paciente` (`id_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `respostas`
--

INSERT INTO `respostas` (`id`, `id_pergunta`, `id_paciente`, `resposta`, `data_resposta`) VALUES
(1, 42, 2, 'sim', '2024-11-27 13:01:24'),
(2, 31, 2, 'nao', '2024-11-27 13:01:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_reabilitacao`
--

DROP TABLE IF EXISTS `tipos_reabilitacao`;
CREATE TABLE IF NOT EXISTS `tipos_reabilitacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipos_reabilitacao`
--

INSERT INTO `tipos_reabilitacao` (`id`, `descricao`) VALUES
(1, 'Joelho'),
(2, 'Pé'),
(3, 'Dedo'),
(4, 'Mão'),
(5, 'Ombro'),
(6, 'Cotovelo'),
(7, 'Exercícios'),
(8, 'Cuidados'),
(9, 'Restrições'),
(10, 'Medicamentos'),
(11, 'Retorno às atividades');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `cpf`, `senha`, `tipo_usuario`, `status`, `data_cadastro`) VALUES
(1, 'Administrador', 'admin@centraldojoelho.com', '456.456.422-55', '$2y$10$48uKCipt449wBOEGdyqeSe42aKAZSaMyU2MoMVgraKAV6MY9ru32i', 'admin', 'ativo', '2024-11-19 16:04:27'),
(2, 'Walciney dos Santos', 'walciney@gmail.com', '456.456.456-55', '$2y$10$Lqd08yoOt63WOhhMgCk8Y.Od1DioaL0EqzevJxUn4ybvuIGHRCnOe', 'paciente', 'ativo', '2024-11-19 16:05:04'),
(3, 'OverGameer', 'overgamer@gmail.com', '456.111.456-55', '$2y$10$jQiWpO983yvz/f.yMU8bMuv69PnxjeiuqJ2V2/7/11fzP/pXi3tHe', 'paciente', 'bloqueado', '2024-11-19 16:20:28'),
(4, 'João Bosco', 'joao.bosco@medico.com', '111.111.111-11', '$2y$10$lQukSwLvB3AXofSFnbIHxOeAPXgXapYCMXxfK9eQLqOu9hTCbNoUW', 'medico', 'ativo', '2024-11-19 16:42:06'),
(5, 'Cláudio Karan', 'claudio.karan@centraldojoelho.com', '023.456.456-51', '$2y$10$s3vt3Ldrqc.1GJtE77MP2ued.txM/h2yzn/LpVzS0Em58qDLwMaVy', 'medico', 'ativo', '2024-11-20 00:33:15'),
(8, 'Matheus Dias', 'matheus@gmail.com', '12333344460', '$2y$10$HjtvQeTzmhPPmW11cQEyy.ImMnCxjYcvs.0GZm/GQsedH8XPNT7Ui', 'paciente', 'bloqueado', '2024-11-21 01:14:28'),
(9, 'Dilma Soares Santos', 'dilma.santos24@gmail.com', '123.333.444-99', '$2y$10$XBkIMaupr6R88Zgf0Yg1v.tgWbcbzvOL2LCEGnjAusicrKX4ji/ni', 'paciente', 'ativo', '2024-11-21 01:34:16'),
(10, 'Jesus Cristo', 'jesus@gmail.com', '9999999910', '$2y$10$85e/4XkM108KIcjULEvLrejOELQQJATbY9f4yNoqe0CQuQSF7gabO', 'paciente', 'ativo', '2024-11-21 01:42:12'),
(12, 'Maria Eduarda', 'maria.eduarda@gmail.com', '33344422299', '$2y$10$rru4A7Hgdge/SyvPyhEzQOXQUVSyxRJZLxpQemyFPdkV2xbxfCrIS', 'paciente', 'bloqueado', '2024-11-21 01:58:21'),
(13, 'Matias Filho', 'matias.filho@gmail.com', '64312234554', '$2y$10$1FXR3GUh2I61BxxNvAI6t.DSdfjmEkjAHNfcRjoe0KMVT3T43F/OK', 'paciente', 'bloqueado', '2024-11-21 12:11:07'),
(14, 'Vilela Castro', 'vilela.castro@gmail.com', '12345678909', '$2y$10$B/FbyX4gdvbYBCmzkpaJuOwtpwCguQfsNNH6rbd90uZFC4NKaryyS', 'paciente', 'ativo', '2024-11-21 14:40:22'),
(15, 'Gabriel Moreira', 'gabriel@gmail.com', '456.456.456-55', '$2y$10$AxLTRG8tso3WsMEzR.f6le3HOyS2rxEwPCUJsbElR6wC/j3CN/7d2', 'paciente', 'ativo', '2024-11-21 15:30:45'),
(16, 'Cláudio Neto', 'neto.claudio@gmail.com', '456.456.456-55', '$2y$10$mIc6DmcwJ7NqmO3zHDAeZ.aREp3BR6tkAhCytmW/QCoXg6m/9.Nzu', 'paciente', 'inativo', '2024-11-21 15:54:57'),
(17, 'Carlos Neto Filho', 'carlos.neto@gmail.com', '456.456.456-99', '$2y$10$GrdBqULRgLtCjB8ioPOKTuSyKSAaTdz89/zjA1mlpeMghpL3egE6G', 'paciente', 'ativo', '2024-11-21 16:07:18'),
(18, 'Douglas Lima', 'douglas@gmail.com', '456.456.456-55', '$2y$10$ehN033V2vG/OZMhGJPdoLuSOLYagptBrzfv17mzAZumyABHbbdQ7a', 'paciente', 'ativo', '2024-11-21 16:15:48'),
(19, 'Rogeria Gomes', 'rogeria.gomes@gmail.com', '456.456.456-55', '$2y$10$Ij2qUowF/.wnG27wOJmEoe3C251AmrEI7aZXTsnhBpC/Ds4JM8tUq', 'admin', 'ativo', '2024-11-21 16:19:37'),
(20, 'Diogo Araujo ', 'diogo.araujo@gmail.com', '238.189.873-91', '$2y$10$BgJL9qCIO6d.p1og8xtMNOzDA35eSnYBlpGQX7BvR95XqIw6TiBRK', 'paciente', 'ativo', '2024-11-21 16:23:54'),
(21, 'Raquel Queiroz', 'raquel@outlook.com', '222.333.453-10', '$2y$10$oA1/w9yhJ36AyerP3Sw9DOoxA5Tj.J5bUGLpSngewUjlTq7UFYhfC', 'paciente', 'ativo', '2024-11-22 13:19:36'),
(22, 'Sara Gomes', 'sara@gmail.com', '12341233123', '$2y$10$AcnhIjml3LC77.8GYwepmu9MO5JDEtKTvGxW2KygySW1GGOaI70Ja', 'paciente', 'ativo', '2024-11-22 17:56:42'),
(23, 'Felipe Soares', 'felipe@gmail.com', '000.111.222-34', '$2y$10$EALBumG3AWzC0pRSma2ADeHHV/HBMFCfsCg5l/G6ZAPXAn9MKD2FC', 'paciente', 'ativo', '2024-11-22 19:39:12'),
(24, 'Pabollo Mota', 'paballo@gmail.com', '121.234.789-00', '$2y$10$gFJvbAsURn66k5G/Psv2UOn6Xs92DhQjrfnvzCXZDDJR/JMxTsz/K', 'paciente', 'ativo', '2024-11-22 20:08:18'),
(28, 'Lucas Junior Dias', 'lucas12312@gmail.com', '888.899.921-21', '$2y$10$uSmcplUF8QGhNZM3CAIsKOFb7Ymn8gUPErhyfQnYwyF.50nHNQpXa', 'paciente', 'ativo', '2024-11-22 23:31:56'),
(29, 'Romulo Dias', 'romulo@gmail.com', '123.412.331-20', '$2y$10$3JP96dyBq8EMxSWaXCmSb.QUPbHD65knGGIDv.06wNu8XVcYPZ206', 'paciente', 'ativo', '2024-11-23 02:41:55'),
(31, 'Ziggs', 'ziggs@gmail.com', '666.111.222-44', '$2y$10$XE.kdEOhwA0GjMU491B8IeuDojFmtY4MFOnDlaid0d03frx3t2KiO', 'paciente', 'ativo', '2024-11-23 04:14:56'),
(32, 'Apolo Moreira Cunha ', 'apolo.moreira@gmail.com', '027.973.173-61', '$2y$10$iHJV0369YCCLrWOhoB3eO.juAqpxorT7TDSJq.lOLPHWIN/QLSZti', 'medico', 'ativo', '2024-11-23 04:40:20'),
(33, 'Usuario Interno', 'usuario.interno@centraldojoelho.com', '00.000.000-00', '$2y$10$UIYe0BguycMShEa/FTPriucc49MPsDRLEBDihaoXx47vbbwrWyOV.', 'paciente', 'ativo', '2024-11-23 09:53:10'),
(39, 'Willians Limas', 'willians.limas@gmail.com', '35426342653', '$2y$10$x2HoBiqKgBYNczxT6ZsBJetMwRvqk8h.r6GyA3oA6QA6HiMkpkExW', 'paciente', 'pendente', '2024-11-28 03:07:14'),
(40, 'Samuel Lima', 'samuel123@gmail.com', '027.973.173-60', '$2y$10$D2Lbn.mZTp4oCBgZEep47uyTLpXDKKtOPLWW40i1dedtlfN2zuZ.O', 'paciente', 'ativo', '2024-11-28 03:12:45'),
(41, 'Ligia Silva', 'ligia@gmail.com', '21257348573', '$2y$10$ZPyfxqThaGIYys4tpFLBeOj1O6skCa8u7qwsxLkXiTqbrscFC7qEu', 'paciente', 'pendente', '2024-11-28 03:17:18'),
(42, 'Daniel Nascimento', 'daniel@gmail.com', '123.143.788-76', '$2y$10$2PoqJtBGTI8X5pAsdqvAYulr6eqsXM9IeOoKnjJI5yjF2yTTR3rO6', 'paciente', 'bloqueado', '2024-11-28 03:18:45');

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atividades_paciente`
--
ALTER TABLE `atividades_paciente`
  ADD CONSTRAINT `atividades_paciente_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cirurgias`
--
ALTER TABLE `cirurgias`
  ADD CONSTRAINT `cirurgias_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `fk_medicos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `orientacoes_reabilitacao`
--
ALTER TABLE `orientacoes_reabilitacao`
  ADD CONSTRAINT `orientacoes_reabilitacao_ibfk_1` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_paciente_medico` FOREIGN KEY (`medico`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_reabilitacao`) REFERENCES `reabilitacao` (`id`);

--
-- Restrições para tabelas `paciente_respostas`
--
ALTER TABLE `paciente_respostas`
  ADD CONSTRAINT `paciente_respostas_ibfk_1` FOREIGN KEY (`id_pergunta`) REFERENCES `perguntas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paciente_respostas_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `perguntas`
--
ALTER TABLE `perguntas`
  ADD CONSTRAINT `fk_pergunta_momento` FOREIGN KEY (`id_momento`) REFERENCES `momentos_reabilitacao` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `perguntas_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `perguntas_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `perguntas_ibfk_3` FOREIGN KEY (`id_medico`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `perguntas_ibfk_4` FOREIGN KEY (`id_reabilitacao`) REFERENCES `reabilitacao` (`id`);

--
-- Restrições para tabelas `reabilitacao`
--
ALTER TABLE `reabilitacao`
  ADD CONSTRAINT `reabilitacao_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `reabilitacao_ibfk_2` FOREIGN KEY (`id_pergunta`) REFERENCES `perguntas` (`id`);

--
-- Restrições para tabelas `respostas`
--
ALTER TABLE `respostas`
  ADD CONSTRAINT `respostas_ibfk_1` FOREIGN KEY (`id_pergunta`) REFERENCES `perguntas` (`id`),
  ADD CONSTRAINT `respostas_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
