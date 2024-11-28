<?php
function renderFiltroForm($config) {
    $page = $config['page'] ?? '';
    $filters = $config['filters'] ?? [];
    $action = $config['action'] ?? '';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo $action; ?>" class="row g-3">
                <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
                
                <?php foreach ($filters as $filter): ?>
                    <div class="col-md-<?php echo $filter['col'] ?? '3'; ?>">
                        <?php if ($filter['type'] === 'text'): ?>
                            <label for="<?php echo $filter['name']; ?>" class="form-label"><?php echo $filter['label']; ?></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="<?php echo $filter['name']; ?>" 
                                   name="<?php echo $filter['name']; ?>"
                                   value="<?php echo htmlspecialchars($_GET[$filter['name']] ?? ''); ?>"
                                   placeholder="<?php echo $filter['placeholder'] ?? ''; ?>">
                        <?php elseif ($filter['type'] === 'select'): ?>
                            <label for="<?php echo $filter['name']; ?>" class="form-label"><?php echo $filter['label']; ?></label>
                            <select class="form-select" 
                                    id="<?php echo $filter['name']; ?>" 
                                    name="<?php echo $filter['name']; ?>">
                                <option value="">Todos</option>
                                <?php foreach ($filter['options'] as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>"
                                            <?php echo (isset($_GET[$filter['name']]) && $_GET[$filter['name']] == $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($filter['type'] === 'date'): ?>
                            <label for="<?php echo $filter['name']; ?>" class="form-label"><?php echo $filter['label']; ?></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="<?php echo $filter['name']; ?>" 
                                   name="<?php echo $filter['name']; ?>"
                                   value="<?php echo htmlspecialchars($_GET[$filter['name']] ?? ''); ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="index.php?page=<?php echo htmlspecialchars($page); ?>" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
