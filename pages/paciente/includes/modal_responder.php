<!-- Modal de Resposta -->
<div class="modal fade" id="responderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Responder Pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="responderForm" action="index.php?page=paciente/responder_pergunta" method="post">
                    <input type="hidden" id="resposta_id" name="id">
                    <div class="mb-3">
                        <label for="resposta_paciente" class="form-label">Sua Resposta</label>
                        <textarea class="form-control" id="resposta_paciente" name="resposta_paciente" rows="4" required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
