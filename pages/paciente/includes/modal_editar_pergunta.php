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
<!-- Modal de Edição -->
<div class="modal fade" id="editarPerguntaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarPerguntaForm" action="index.php?page=paciente/editar_pergunta" method="post">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_id_medico" class="form-label">Médico (opcional)</label>
                        <select class="form-control" id="edit_id_medico" name="id_medico">
                            <option value="">Selecione um médico...</option>
                            <?php foreach ($medicos as $medico): ?>
                                <option value="<?php echo $medico['id']; ?>"><?php echo htmlspecialchars($medico['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
