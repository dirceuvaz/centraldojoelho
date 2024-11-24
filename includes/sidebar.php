<div class="col-md-3 col-lg-2 px-0 sidebar">
    <div class="text-center p-3">
        <h5>Central do Joelho</h5>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'admin/painel' ? 'active' : ''; ?>" href="index.php?page=admin/painel">
                <i class="bi bi-speedometer2"></i> Painel
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'admin/usuarios' ? 'active' : ''; ?>" href="index.php?page=admin/usuarios">
                <i class="bi bi-people"></i> Usuários
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'admin/cirurgias' ? 'active' : ''; ?>" href="index.php?page=admin/cirurgias">
                <i class="bi bi-heart-pulse"></i> Cirurgias
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'admin/reabilitacao' ? 'active' : ''; ?>" href="index.php?page=admin/reabilitacao">
                <i class="bi bi-activity"></i> Reabilitação
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'admin/midias' ? 'active' : ''; ?>" href="index.php?page=admin/midias">
                <i class="bi bi-film"></i> Mídias
            </a>
        </li>
    </ul>
</div>
