<?php
require_once __DIR__ . '/../../config/database.php';

// Verifica se o usuário está logado e é paciente
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'paciente') {
    header('Location: index.php?page=login');
    exit;
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Buscar informações do usuário e paciente
$stmt = $conn->prepare("SELECT u.*, p.data_cirurgia, p.problema, p.fisioterapeuta,
                              m.nome as nome_medico, med.especialidade as especialidade_medico,
                              f.nome as nome_fisio, fis.especialidade as especialidade_fisio
                       FROM usuarios u 
                       LEFT JOIN pacientes p ON u.id = p.id_usuario 
                       LEFT JOIN usuarios m ON p.medico = m.id
                       LEFT JOIN medicos med ON m.id = med.id_usuario
                       LEFT JOIN usuarios f ON p.fisioterapeuta = f.id
                       LEFT JOIN medicos fis ON f.id = fis.id_usuario
                       WHERE u.id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug para verificar os dados do usuário
error_log("Dados do usuário: " . print_r($usuario, true));

// Calculando os dias decorridos
if (!empty($usuario['data_cirurgia'])) {
    $data_atual = time(); // Pega a data/hora atual do servidor em timestamp
    $data_cirurgia = strtotime($usuario['data_cirurgia']); // Converte a data da cirurgia para timestamp

    $resultados_dos_dias = ($data_atual - $data_cirurgia) / (60 * 60 * 24); // Calcula os dias decorridos
    $duracao_dias = (int) $resultados_dos_dias; // Converte explicitamente para inteiro
    $semanas = "";
    $fase_atual = 0;

    // Lógica condicional para determinar a semana
    if ($duracao_dias >= 1 && $duracao_dias <= 7) {
        $semanas = "Primeira Semana";
        $fase_atual = 1;
        $momento_atual = "Primeira Semana";
    } elseif ($duracao_dias >= 8 && $duracao_dias <= 14) {
        $semanas = "Segunda Semana";
        $fase_atual = 2;
        $momento_atual = "Segunda Semana";
    } elseif ($duracao_dias >= 15 && $duracao_dias <= 21) {
        $semanas = "Terceira Semana";
        $fase_atual = 3;
        $momento_atual = "Terceira Semana";
    } elseif ($duracao_dias >= 22 && $duracao_dias <= 28) {
        $semanas = "Quarta Semana";
        $fase_atual = 4;
        $momento_atual = "Quarta Semana";
    } elseif ($duracao_dias >= 29 && $duracao_dias <= 35) {
        $semanas = "Quinta Semana";
        $fase_atual = 5;
        $momento_atual = "Quinta Semana";
    } elseif ($duracao_dias >= 36 && $duracao_dias <= 70) {
        $semanas = "Sexta a Décima Semana";
        $fase_atual = 6;
        $momento_atual = "Sexta a Décima Semana";
    } elseif ($duracao_dias >= 71 && $duracao_dias <= 140) {
        $semanas = "Décima Primeira a Vigésima Semana";
        $fase_atual = 7;
        $momento_atual = "Décima Primeira a Vigésima Semana";
    } elseif ($duracao_dias >= 141 && $duracao_dias <= 180) {
        $semanas = "Sexto Mês";
        $fase_atual = 8;
        $momento_atual = "Sexto Mês";
    } else {
        $semanas = "Pré Operatório";
        $fase_atual = 0;
        $momento_atual = "Pré Operatório";
    }
} else {
    $semanas = "Pré Operatório";
    $fase_atual = 0;
    $momento_atual = "Pré Operatório";
    $duracao_dias = 0;
}

// Buscar reabilitações específicas para o momento atual
$stmt = $conn->prepare("
    SELECT r.*, 
           m.nome as nome_medico, 
           med.especialidade as especialidade_medico
    FROM reabilitacao r
    LEFT JOIN usuarios m ON r.id_medico = m.id
    LEFT JOIN medicos med ON m.id = med.id_usuario
    WHERE r.momento = ? 
    AND r.status = 'ativo'
    ORDER BY r.sequencia ASC
");

$stmt->execute([$momento_atual]);
$reabilitacoes_momento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug para verificar os resultados
error_log("Momento Atual: " . $momento_atual);
error_log("Número de resultados: " . count($reabilitacoes_momento));
if (count($reabilitacoes_momento) == 0) {
    error_log("Nenhum resultado encontrado para o momento: " . $momento_atual);
}

// Array com as informações de cada fase
$fases_reabilitacao = [
    0 => [
        'titulo' => 'Pré Operatório',
        'descricao' => 'Preparação para a cirurgia',
        'objetivos' => [
            'Fortalecer a musculatura',
            'Melhorar a amplitude de movimento',
            'Reduzir o edema',
            'Preparar-se mentalmente para a cirurgia'
        ],
        'exercicios' => [
            'Exercícios isométricos de quadríceps',
            'Elevação da perna estendida',
            'Alongamentos suaves',
            'Exercícios de amplitude de movimento'
        ],
        'recomendacoes' => [
            'Manter boa hidratação',
            'Seguir orientações médicas pré-operatórias',
            'Preparar o ambiente doméstico para o pós-operatório',
            'Organizar suporte familiar/cuidador'
        ]
    ],
    1 => [
        'titulo' => 'Primeira Semana',
        'descricao' => 'Fase inicial pós-operatória',
        'objetivos' => [
            'Controle da dor e edema',
            'Proteção da cirurgia',
            'Início da mobilização',
            'Ativação muscular básica'
        ],
        'exercicios' => [
            'Exercícios isométricos suaves',
            'Movimentação ativa do tornozelo',
            'Elevação da perna com apoio',
            'Exercícios respiratórios'
        ],
        'recomendacoes' => [
            'Repouso adequado',
            'Uso de gelo conforme orientação',
            'Manter curativo limpo e seco',
            'Uso correto de muletas/andador'
        ]
    ],
    2 => [
        'titulo' => 'Segunda Semana',
        'descricao' => 'Fase de mobilização inicial',
        'objetivos' => [
            'Aumento gradual da amplitude de movimento',
            'Fortalecimento muscular progressivo',
            'Melhora do controle do edema',
            'Treino de marcha com apoio'
        ],
        'exercicios' => [
            'Flexão passiva assistida do joelho',
            'Exercícios isométricos mais intensos',
            'Elevação da perna reta',
            'Exercícios de propriocepção básicos'
        ],
        'recomendacoes' => [
            'Manter uso de muletas conforme orientado',
            'Cuidados com a ferida operatória',
            'Controle do edema',
            'Atenção aos sinais de alerta'
        ]
    ],
    3 => [
        'titulo' => 'Terceira Semana',
        'descricao' => 'Fase de progressão funcional inicial',
        'objetivos' => [
            'Aumento da força muscular',
            'Melhora da amplitude de movimento',
            'Progresso no treino de marcha',
            'Início de exercícios em cadeia fechada'
        ],
        'exercicios' => [
            'Mini agachamentos com apoio',
            'Exercícios na bicicleta ergométrica',
            'Treino de equilíbrio',
            'Alongamentos progressivos'
        ],
        'recomendacoes' => [
            'Progressão gradual das atividades',
            'Manutenção dos exercícios diários',
            'Atenção à sobrecarga',
            'Hidratação adequada'
        ]
    ],
    4 => [
        'titulo' => 'Quarta Semana',
        'descricao' => 'Fase de ganho funcional',
        'objetivos' => [
            'Aumento progressivo da força',
            'Melhora do equilíbrio',
            'Ganho de resistência',
            'Preparação para atividades cotidianas'
        ],
        'exercicios' => [
            'Agachamentos mais profundos',
            'Exercícios com resistência leve',
            'Treino de marcha mais intenso',
            'Exercícios de propriocepção avançados'
        ],
        'recomendacoes' => [
            'Atenção aos limites do corpo',
            'Manter regularidade nos exercícios',
            'Progressão gradual da carga',
            'Cuidados com impacto'
        ]
    ],
    5 => [
        'titulo' => 'Quinta Semana',
        'descricao' => 'Fase de fortalecimento progressivo',
        'objetivos' => [
            'Fortalecimento muscular intensificado',
            'Melhora da resistência',
            'Ganho de função',
            'Preparação para atividades mais intensas'
        ],
        'exercicios' => [
            'Exercícios com peso',
            'Treino cardiovascular moderado',
            'Exercícios de agilidade básicos',
            'Treino funcional específico'
        ],
        'recomendacoes' => [
            'Monitorar sintomas',
            'Progressão adequada da intensidade',
            'Manter boa alimentação',
            'Descanso adequado'
        ]
    ],
    6 => [
        'titulo' => 'Sexta a Décima Semana',
        'descricao' => 'Fase de retorno às atividades',
        'objetivos' => [
            'Retorno gradual às atividades normais',
            'Fortalecimento avançado',
            'Treino específico para função',
            'Preparação para atividades esportivas leves'
        ],
        'exercicios' => [
            'Exercícios pliométricos básicos',
            'Treino de agilidade avançado',
            'Exercícios específicos do esporte',
            'Fortalecimento com carga progressiva'
        ],
        'recomendacoes' => [
            'Atenção aos sinais do corpo',
            'Progressão gradual e segura',
            'Manter exercícios de manutenção',
            'Preparação para retorno às atividades'
        ]
    ],
    7 => [
        'titulo' => 'Décima Primeira a Vigésima Semana',
        'descricao' => 'Fase de retorno esportivo',
        'objetivos' => [
            'Preparação para retorno esportivo',
            'Ganho de potência muscular',
            'Treino específico do esporte',
            'Melhora da resistência específica'
        ],
        'exercicios' => [
            'Treino pliométrico avançado',
            'Exercícios específicos do esporte',
            'Treino de velocidade e agilidade',
            'Exercícios de potência'
        ],
        'recomendacoes' => [
            'Retorno gradual ao esporte',
            'Manter fortalecimento',
            'Atenção à técnica dos movimentos',
            'Prevenção de lesões'
        ]
    ],
    8 => [
        'titulo' => 'Sexto Mês',
        'descricao' => 'Fase final de reabilitação',
        'objetivos' => [
            'Retorno total às atividades',
            'Manutenção da força e função',
            'Prevenção de lesões',
            'Alta da reabilitação'
        ],
        'exercicios' => [
            'Manutenção do condicionamento',
            'Exercícios preventivos',
            'Atividades esportivas completas',
            'Treino de performance'
        ],
        'recomendacoes' => [
            'Manter rotina de exercícios',
            'Atenção aos sinais do corpo',
            'Prevenção contínua',
            'Acompanhamento médico regular'
        ]
    ]
];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reabilitação - Central do Joelho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .etapa-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .etapa-header {
            background-color: #231F5D;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .timeline-item {
            border-left: 2px solid #231F5D;
            padding: 20px;
            position: relative;
            margin-left: 20px;
        }
        .timeline-item::before {
            content: '';
            width: 15px;
            height: 15px;
            background: #231F5D;
            border-radius: 50%;
            position: absolute;
            left: -8.5px;
            top: 24px;
        }
        .reabilitacao-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .reabilitacao-card:hover {
            transform: translateY(-5px);
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: none;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background-color: #231F5D;
            color: white;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .back-to-top:hover {
            background-color: #1a1747;
            transform: translateY(-3px);
        }

        .back-to-top.show {
            display: block;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Botão Voltar ao Topo -->
    <div class="back-to-top" onclick="scrollToTop()" title="Voltar ao topo">
        <i class="bi bi-arrow-up"></i>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #231F5D;">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=paciente/painel">Central do Joelho</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=paciente/perfil">
                            <i class="bi bi-person-circle"></i> Meu Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Reabilitação</h2>
                <p class="text-muted">Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!</p>
            </div>
            <div class="d-flex align-items-center">
                <a href="index.php?page=paciente/painel" class="btn btn-warning me-2">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>

        <!-- Card de Status Atual -->
        <div class="card mb-4 reabilitacao-card">
            <div class="card-body">
                <h3 class="card-title">
                    <i class="bi bi-calendar-check text-success"></i> 
                    Seu Progresso
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Status Atual:</strong> 
                            <?php echo htmlspecialchars($semanas); ?>
                        </p>
                        <?php if (!empty($usuario['data_cirurgia'])): ?>
                        <p class="mb-2"><strong>Data da Cirurgia:</strong> 
                            <?php echo date('d/m/Y', strtotime($usuario['data_cirurgia'])); ?>
                        </p>
                        <p class="mb-2"><strong>Dias desde a cirurgia:</strong> 
                            <?php echo $duracao_dias; ?> dias
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($usuario['problema'])): ?>
                        <p class="mb-2">
                            <i class="bi bi-activity text-primary"></i>
                            <strong>Tipo de Reabilitação:</strong><br>
                            <?php echo htmlspecialchars($usuario['problema']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($reabilitacoes_momento[0]['nome_medico'])): ?>
                    <div class="col-md-6">
                        <div class="border-start ps-4">
                            <!-- Médico Responsável -->
                            <p class="mb-2">
                                <i class="bi bi-person-badge text-primary"></i>
                                <strong>Médico Responsável está Reabilitação:</strong><br>
                                Dr(a). <?php echo htmlspecialchars($reabilitacoes_momento[0]['nome_medico']); ?>
                                <?php if (!empty($reabilitacoes_momento[0]['especialidade_medico'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($reabilitacoes_momento[0]['especialidade_medico']); ?></small>
                                <?php endif; ?>
                            </p>

                            <!-- Fisioterapeuta -->
                            <?php if (!empty($usuario['nome_fisio'])): ?>
                            <p class="mb-0">
                                <i class="bi bi-person-badge-fill text-primary"></i>
                                <strong>Fisioterapeuta:</strong><br>
                                Dr(a). <?php echo htmlspecialchars($usuario['nome_fisio']); ?>
                                <?php if (!empty($usuario['especialidade_fisio'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($usuario['especialidade_fisio']); ?></small>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            <div class="mt-2">
                                <a href="index.php?page=paciente/perguntas" class="btn btn-warning btn-md">
                                    <i class="bi bi-question-circle"></i> Responder Perguntas
                                </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações da Fase Atual -->
        <?php if (isset($fases_reabilitacao[$fase_atual])): ?>
        <div class="card mb-4 reabilitacao-card">
            <div class="card-header etapa-header">
                <h4 class="mb-0"><?php echo htmlspecialchars($fases_reabilitacao[$fase_atual]['titulo']); ?></h4>
                <p class="mb-0"><?php echo htmlspecialchars($fases_reabilitacao[$fase_atual]['descricao']); ?></p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5><i class="bi bi-bullseye text-primary"></i> Objetivos</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($fases_reabilitacao[$fase_atual]['objetivos'] as $objetivo): ?>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> <?php echo htmlspecialchars($objetivo); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="bi bi-activity text-primary"></i> Exercícios Recomendados</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($fases_reabilitacao[$fase_atual]['exercicios'] as $exercicio): ?>
                                <li class="mb-2"><i class="bi bi-arrow-right-circle text-primary"></i> <?php echo htmlspecialchars($exercicio); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="bi bi-lightbulb text-primary"></i> Recomendações</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($fases_reabilitacao[$fase_atual]['recomendacoes'] as $recomendacao): ?>
                                <li class="mb-2"><i class="bi bi-info-circle text-info"></i> <?php echo htmlspecialchars($recomendacao); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline de Progresso -->
        <div class="card mb-4">
            <div class="card-header etapa-header">
                <h4 class="mb-0">Linha do Tempo da Reabilitação</h4>                
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($fases_reabilitacao as $fase => $info): ?>
                        <div class="timeline-item <?php echo ($fase == $fase_atual) ? 'active bg-light' : ''; ?>">
                            <h5><?php echo htmlspecialchars($info['titulo']); ?></h5>
                            <p><?php echo htmlspecialchars($info['descricao']); ?></p>
                            <?php if ($fase == $fase_atual): ?>
                                <span class="badge bg-success">Você está aqui</span>
                                
                                <!-- Conteúdo específico da reabilitação -->
                                <?php if (!empty($reabilitacoes_momento)): ?>
                                    <div class="mt-4">
                                        <h5 class="text-primary">Orientações Específicas para este Período</h5>
                                        <?php foreach ($reabilitacoes_momento as $reab): ?>
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($reab['titulo']); ?></h6>
                                                    <div class="card-text">
                                                        <?php echo $reab['texto']; ?>
                                                    </div>
                                                    <?php if (!empty($reab['duracao_dias'])): ?>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <i class="bi bi-clock"></i> 
                                                                Duração: <?php echo $reab['duracao_dias']; ?> dias
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                <a href="index.php?page=paciente/perguntas" class="btn btn-warning btn-md">
                                    <i class="bi bi-question-circle"></i> Responder Perguntas
                                </a>
                            </div>
                            <?php endif; ?>                            
                        </div>
                    <?php endforeach; ?>
                    
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para rolar suavemente ao topo
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Mostrar/ocultar botão de voltar ao topo baseado no scroll
        window.onscroll = function() {
            var button = document.querySelector('.back-to-top');
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                button.classList.add('show');
            } else {
                button.classList.remove('show');
            }
        };
    </script>
</body>
</html>
