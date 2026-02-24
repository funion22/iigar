<?php
require_once 'auth.php';
require_once 'db.php';

$msg = '';
$msgType = '';
$editing = null;

// Mapa de slug a código ISO para banderas SVG
$flagCodes = [
    'czech' => 'cz', 'denmark' => 'dk', 'netherlands' => 'nl',
    'uk' => 'gb', 'finland' => 'fi', 'france' => 'fr',
    'germany' => 'de', 'greece' => 'gr', 'italy' => 'it',
    'norway' => 'no', 'poland' => 'pl', 'portuguese' => 'pt',
    'spain' => 'es', 'sweden' => 'se'
];

// ── ELIMINAR ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $count = $pdo->prepare("SELECT COUNT(*) FROM domains WHERE country_id = ?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $msg = 'No se puede eliminar: este país tiene dominios asignados. Elimina los dominios primero.';
        $msgType = 'error';
    } else {
        $stmt = $pdo->prepare("DELETE FROM countries WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'País eliminado correctamente.';
        $msgType = 'success';
    }
}

// ── EDITAR (cargar datos) ──
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM countries WHERE id = ?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

// ── GUARDAR (crear o actualizar) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $slug = trim($_POST['slug']);
    $button_name = trim($_POST['button_name']);
    $title = trim($_POST['title']);
    $sub_countries = trim($_POST['sub_countries']);
    $sort_order = (int)$_POST['sort_order'];
    // Mantener el flag_image existente al editar, vacío al crear
    $flag_image = '';
    if ($id > 0) {
        $old = $pdo->prepare("SELECT flag_image FROM countries WHERE id = ?");
        $old->execute([$id]);
        $flag_image = $old->fetchColumn() ?: '';
    }

    if (empty($slug) || empty($button_name) || empty($title)) {
        $msg = 'Los campos Slug, Nombre del botón y Título son obligatorios.';
        $msgType = 'error';
    } else {
        if ($id > 0) {
            $conflict = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE sort_order = ? AND id != ?");
            $conflict->execute([$sort_order, $id]);
            if ($conflict->fetchColumn() > 0) {
                $pdo->prepare("UPDATE countries SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")
                    ->execute([$sort_order, $id]);
            }
            $stmt = $pdo->prepare("UPDATE countries SET slug=?, button_name=?, title=?, flag_image=?, sub_countries=?, sort_order=? WHERE id=?");
            $stmt->execute([$slug, $button_name, $title, $flag_image, $sub_countries, $sort_order, $id]);
            $msg = 'País actualizado correctamente.';
        } else {
            $conflict = $pdo->prepare("SELECT COUNT(*) FROM countries WHERE sort_order = ?");
            $conflict->execute([$sort_order]);
            if ($conflict->fetchColumn() > 0) {
                $pdo->prepare("UPDATE countries SET sort_order = sort_order + 1 WHERE sort_order >= ?")
                    ->execute([$sort_order]);
            }
            $stmt = $pdo->prepare("INSERT INTO countries (slug, button_name, title, flag_image, sub_countries, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$slug, $button_name, $title, $flag_image, $sub_countries, $sort_order]);
            $msg = 'País creado correctamente.';
        }
        $msgType = 'success';
        $editing = null;
    }
}

// ── LISTAR ──
$countries = $pdo->query("
    SELECT c.*, COUNT(d.id) as domain_count
    FROM countries c
    LEFT JOIN domains d ON d.country_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order
")->fetchAll();

$nextOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM countries")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Países - Panel Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🛠️ Pagifier - Panel de Administración</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="countries.php" class="active">Países</a>
                <a href="domains.php">Dominios</a>
                <a href="landings.php">Landings</a>
                <a href="brandless_landings.php">Brandless</a>
                <a href="campaign_types.php">Campañas</a>
                <a href="../index.php" target="_blank">Ver página →</a>
            </nav>
        </header>

        <main>
            <?php if ($msg): ?>
                <div class="msg msg-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="form-card">
                <h2><?= $editing ? 'Editar país' : 'Añadir nuevo país' ?></h2>
                <form method="POST">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Slug (identificador único, sin espacios)</label>
                            <input type="text" name="slug" required placeholder="ej: denmark"
                                   value="<?= htmlspecialchars($editing['slug'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Nombre del botón (sidebar)</label>
                            <input type="text" name="button_name" required placeholder="ej: Denmark"
                                   value="<?= htmlspecialchars($editing['button_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Título (se muestra en la cabecera del país)</label>
                        <input type="text" name="title" required placeholder="ej: DENMARK / DINAMARCA"
                               value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sub-países (separados por coma, dejar vacío si es país único)</label>
                            <input type="text" name="sub_countries" placeholder="ej: Nederland, België"
                                   value="<?= htmlspecialchars($editing['sub_countries'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? $nextOrder ?>">
                        </div>
                    </div>

                    <div class="form-group" style="display:flex; gap:10px;">
                        <button type="submit" class="btn btn-primary">
                            <?= $editing ? 'Guardar cambios' : 'Añadir país' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a href="countries.php" class="btn btn-danger">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Lista -->
            <div class="section-header">
                <h2>Países (<?= count($countries) ?>)</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Bandera</th>
                        <th>Slug</th>
                        <th>Nombre botón</th>
                        <th>Título</th>
                        <th>Sub-países</th>
                        <th>Dominios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($countries as $c):
                        $flagCode = $flagCodes[$c['slug']] ?? '';
                        $flagSrc = $flagCode
                            ? 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/flags/4x3/' . $flagCode . '.svg'
                            : ($c['flag_image'] ? '../images/' . htmlspecialchars($c['flag_image']) : '');
                    ?>
                    <tr>
                        <td><?= $c['sort_order'] ?></td>
                        <td>
                            <?php if ($flagSrc): ?>
                                <img src="<?= $flagSrc ?>" alt="" style="height:20px; vertical-align:middle;">
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
                        <td><?= htmlspecialchars($c['button_name']) ?></td>
                        <td><?= htmlspecialchars($c['title']) ?></td>
                        <td><?= htmlspecialchars($c['sub_countries']) ?: '<em style="color:#999">—</em>' ?></td>
                        <td><?= $c['domain_count'] ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="domains.php?country=<?= $c['slug'] ?>" class="btn btn-success btn-sm">Dominios</a>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar <?= htmlspecialchars($c['button_name']) ?>?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
