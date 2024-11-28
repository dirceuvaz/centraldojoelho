<?php

function checkPageAccess($page) {
    // Páginas públicas que não precisam de autenticação
    $publicPages = ['login', 'logout', 'register', 'forgot-password'];
    
    // Se for uma página pública, permite o acesso
    if (in_array($page, $publicPages)) {
        return true;
    }

    if (!isset($_SESSION['tipo_usuario'])) {
        header('Location: index.php?page=login');
        exit;
    }

    $userType = $_SESSION['tipo_usuario'];
    
    // Define as permissões de acesso por tipo de usuário
    $permissions = [
        'admin' => [
            // Páginas do Admin
            'admin/painel',
            'admin/usuarios',
            'admin/notificacoes',
            'admin/config-gerais',
            'admin/relatorios',
            'admin/videos',
            'admin/arquivos',
            // Páginas do Médico
            'medico/painel',
            'medico/reabilitacao',
            'medico/reabilitacao_process',
            'medico/perfil_process',
            'medico/pacientes',
            'medico/consultas',
            'medico/evolucao',
            // Páginas do Paciente
            'paciente/painel',
            'paciente/consultas',
            'paciente/exercicios',
            'paciente/evolucao',
            'paciente/perfil'
        ],
        'medico' => [
            'medico/painel',
            'medico/reabilitacao',
            'medico/reabilitacao_process',
            'medico/perfil_process',
            'medico/pacientes',
            'medico/consultas',
            'medico/evolucao',
            'paciente/exercicios', // Acesso para ver exercícios dos pacientes
            'paciente/evolucao'    // Acesso para ver evolução dos pacientes
        ],
        'paciente' => [
            'paciente/painel',
            'paciente/consultas',
            'paciente/exercicios',
            'paciente/evolucao',
            'paciente/perfil'
        ]
    ];

    // Verifica se o usuário tem permissão para acessar a página
    if (isset($permissions[$userType]) && in_array($page, $permissions[$userType])) {
        return true;
    }

    // Se não tiver permissão, redireciona para o painel apropriado
    $defaultPages = [
        'admin' => 'admin/painel',
        'medico' => 'medico/painel',
        'paciente' => 'paciente/painel'
    ];

    header('Location: index.php?page=' . $defaultPages[$userType] . '&erro=Acesso não autorizado');
    exit;
}

// Função para verificar se o usuário tem permissão para uma ação específica
function checkActionPermission($action) {
    if (!isset($_SESSION['tipo_usuario'])) {
        return false;
    }

    $userType = $_SESSION['tipo_usuario'];
    
    $actionPermissions = [
        'admin' => ['*'], // Admin pode tudo
        'medico' => [
            'create_reabilitacao',
            'edit_reabilitacao',
            'delete_reabilitacao',
            'view_patient',
            'edit_patient',
            'create_evolucao',
            'edit_evolucao'
        ],
        'paciente' => [
            'view_exercicios',
            'view_evolucao',
            'edit_perfil'
        ]
    ];

    // Admin tem acesso total
    if ($userType === 'admin') {
        return true;
    }

    // Verifica se o usuário tem permissão para a ação
    return isset($actionPermissions[$userType]) && 
           (in_array($action, $actionPermissions[$userType]) || in_array('*', $actionPermissions[$userType]));
}
