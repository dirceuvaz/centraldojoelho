// Funções para gerenciamento de usuários
function salvarNovoUsuario() {
    const form = document.getElementById('formNovoUsuario');
    const formData = new FormData(form);
    formData.append('acao', 'novo');

    fetch('index.php?page=admin/usuarios_process', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar usuário');
    });
}

function editarUsuario(id) {
    // Buscar dados do usuário
    fetch(`index.php?page=admin/usuarios_process&acao=buscar&id=${id}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Preencher modal com dados do usuário
            const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
            document.getElementById('editId').value = data.usuario.id;
            document.getElementById('editNome').value = data.usuario.nome;
            document.getElementById('editEmail').value = data.usuario.email;
            document.getElementById('editTipo').value = data.usuario.tipo_usuario;
            modal.show();
        } else {
            alert(data.message || 'Erro ao carregar dados do usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar dados do usuário');
    });
}

function salvarEdicaoUsuario() {
    const form = document.getElementById('formEditarUsuario');
    const formData = new FormData(form);
    formData.append('acao', 'editar');

    fetch('index.php?page=admin/usuarios_process', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao atualizar usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar usuário');
    });
}

function alterarStatus(id, status) {
    if (!confirm(`Deseja realmente ${status === 'ativo' ? 'ativar' : 'inativar'} este usuário?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'status');
    formData.append('id', id);
    formData.append('status', status);

    fetch('index.php?page=admin/usuarios_process', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao alterar status do usuário');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao alterar status do usuário');
    });
}
