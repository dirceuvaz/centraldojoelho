<?php
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tipo_usuario = 'medico'");
    $stmt->execute();
    $medicos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar médicos: " . $e->getMessage());
    $medicos = [];
}
?>
<!-- Modal de Nova Pergunta -->
<div class="modal fade" id="novaPerguntaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="novaPerguntaForm" action="index.php?page=paciente/nova_pergunta" method="post">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="id_medico" class="form-label">Médico (opcional)</label>
                        <select class="form-control" id="id_medico" name="id_medico">
                            <option value="">Selecione um médico...</option>
                            <?php foreach ($medicos as $medico): ?>
                                <option value="<?php echo $medico['id']; ?>"><?php echo htmlspecialchars($medico['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Pergunta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
