<?php
$cardClass = 'pergunta-card';
if ($pergunta['tipo_criador'] === 'admin') {
    $cardClass .= ' pergunta-admin';
} elseif ($pergunta['tipo_pergunta'] === 'recebida') {
    $cardClass .= ' pergunta-recebida';
}
?>
<div class="card <?php echo $cardClass; ?> mb-4" 
     data-tipo="<?php echo $pergunta['tipo_criador'] === 'admin' ? 'admin' : $pergunta['tipo_pergunta']; ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h5 class="card-title mb-0"><?php echo htmlspecialchars($pergunta['titulo']); ?></h5>
            <div>
                <?php if ($pergunta['tipo_criador'] === 'admin'): ?>
                    <span class="badge bg-danger">Pergunta do Administrador</span>
                <?php elseif ($pergunta['tipo_pergunta'] === 'recebida'): ?>
                    <span class="badge bg-purple">Pergunta Recebida</span>
                <?php endif; ?>
                
                <?php if ($pergunta['criado_por'] === $_SESSION['user_id'] && empty($pergunta['resposta'])): ?>
                    <button class="btn btn-sm btn-outline-primary ms-2" 
                            onclick="editarPergunta(<?php echo $pergunta['id']; ?>, 
                                                  '<?php echo addslashes($pergunta['titulo']); ?>', 
                                                  '<?php echo addslashes($pergunta['descricao']); ?>', 
                                                  <?php echo $pergunta['id_medico'] ?? 'null'; ?>)">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['descricao'])); ?></p>
        
        <div class="pergunta-timestamp">
            <i class="bi bi-clock"></i> 
            <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?>
            <?php if ($pergunta['id_medico']): ?>
                <span class="ms-2">
                    <i class="bi bi-person-badge"></i> 
                    Para: <?php echo htmlspecialchars($pergunta['nome_medico']); ?>
                </span>
            <?php endif; ?>
            <span class="ms-2">
                <i class="bi bi-person"></i> 
                <?php if ($pergunta['tipo_criador'] === 'admin'): ?>
                    De: Administrador
                <?php else: ?>
                    De: <?php echo htmlspecialchars($pergunta['criado_por_nome']); ?>
                <?php endif; ?>
            </span>
        </div>

        <?php if ($pergunta['tipo_criador'] !== $_SESSION['user_id'] && empty($pergunta['resposta_paciente'])): ?>
            <div class="mt-3">
                <button class="btn btn-sm btn-outline-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#responderModal" 
                        onclick="prepararResposta(<?php echo $pergunta['id']; ?>)">
                    <i class="bi bi-reply"></i> Responder Pergunta
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pergunta['resposta'])): ?>
            <div class="card resposta-card mt-3">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-success">
                        <i class="bi bi-chat-right-text"></i> Resposta do Médico
                    </h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['resposta'])); ?></p>
                    <small class="text-muted d-block mb-3">
                        <i class="bi bi-clock"></i> 
                        Respondido em: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_resposta'])); ?>
                    </small>

                    <?php if (empty($pergunta['resposta_paciente'])): ?>
                        <button class="btn btn-sm btn-outline-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#responderModal" 
                                onclick="prepararResposta(<?php echo $pergunta['id']; ?>)">
                            <i class="bi bi-reply"></i> Responder ao Médico
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($pergunta['resposta_paciente'])): ?>
            <div class="card resposta-card mt-3">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-primary">
                        <i class="bi bi-chat-right-text"></i> Sua Resposta
                    </h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($pergunta['resposta_paciente'])); ?></p>
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> 
                        Respondido em: <?php echo date('d/m/Y H:i', strtotime($pergunta['data_resposta_paciente'])); ?>
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
