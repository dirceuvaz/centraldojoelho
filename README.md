# Central do Joelho

Sistema web desenvolvido pelo GRUPO 25 para gerenciamento de pacientes e reabilitações na área de fisioterapia, com foco especial em tratamentos relacionados ao joelho.

## 📋 Descrição

O Central do Joelho é uma plataforma completa para profissionais de fisioterapia gerenciarem seus pacientes, programas de reabilitação e acompanhamento de tratamentos. O sistema permite o cadastro detalhado de pacientes, registro de avaliações, planejamento de exercícios e acompanhamento da evolução do tratamento.

## 🚀 Funcionalidades

- Cadastro e gestão de pacientes
- Registro de avaliações físicas
- Programas de reabilitação personalizados
- Acompanhamento da evolução do paciente
- Área administrativa para gestão do sistema

## 💻 Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache/Nginx)
- WAMP, XAMPP ou similar para ambiente local

## 🔧 Instalação

1. Clone o repositório para sua máquina local
2. Configure seu servidor web (WAMP/XAMPP) apontando para o diretório do projeto
3. Importe o banco de dados `bando_centraldojoelho.sql` onde está na pasta sql para seu MySQL
4. Configure as credenciais do banco de dados no arquivo de configuração
5. Acesse o sistema através do navegador

## ⚙️ Configuração do Banco de Dados

O sistema utiliza o banco de dados MySQL com codificação UTF-8. Para configurar:

1. Crie um banco de dados chamado `banco_centraldojoelho`
2. Configure a codificação para `utf8mb4_unicode_ci`
3. Importe a estrutura do banco de dados
4. Configure as credenciais de acesso

## 👥 Níveis de Acesso

O sistema possui diferentes níveis de acesso:

- **Administrador**: Acesso completo ao sistema
- **Médicos**: Gestão de pacientes
- **Paciente**: Visualização de seu programa de exercícios e evolução

## 🔐 Segurança

- Autenticação de usuários
- Controle de sessão
- Criptografia de dados sensíveis
- Validação de formulários
- Proteção contra SQL Injection

## 📱 Compatibilidade

O sistema é responsivo e funciona nos principais navegadores:

- Google Chrome
- Mozilla Firefox
- Microsoft Edge
- Safari

## Para fins de ESTUDOS

Questões de licenças é para fins de estudos acadêmicos neste versão públicada.
