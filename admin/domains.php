<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'sort_order.php';

$msg = '';
$msgType = '';
$editing = null;

// ── ELIMINAR ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->beginTransaction();
    try {
        // El país de la fila, para renumerar solo ese grupo
        $q = $pdo->prepare("SELECT country_id FROM domains WHERE id = ?");
        $q->execute([$id]);
        $countryOfRow = $q->fetchColumn();

        $pdo->prepare("DELETE FROM domains WHERE id = ?")->execute([$id]);
        if ($countryOfRow !== false) {
            sortRenumber($pdo, 'domains', 'country_id', $countryOfRow);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    $msg = 'Dominio eliminado correctamente.';
    $msgType = 'success';
}
// ── EDITAR (cargar datos) ──
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

// ── GUARDAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $country_id = (int)$_POST['country_id'];
    $domain = trim($_POST['domain']);
    $display_name = trim($_POST['display_name']);
    $category = $_POST['category'];
    $color_class = $_POST['color_class'];
    $template = $_POST['template'];
    $sub_countries = trim($_POST['sub_countries']);
    $sort_order = (int)$_POST['sort_order'];

    // Asignar data_country automáticamente según el país
    $countryCodes = [
        'czech' => 'cz', 'denmark' => 'dk', 'netherlands' => 'nl',
        'uk' => 'en', 'finland' => 'fi', 'france' => 'fr',
        'germany' => 'de', 'greece' => 'gr', 'italy' => 'it',
        'norway' => 'no', 'poland' => 'pl', 'portuguese' => 'pt',
        'spain' => 'es', 'sweden' => 'se'
    ];
    $countrySlug = $pdo->prepare("SELECT slug FROM countries WHERE id = ?");
    $countrySlug->execute([$country_id]);
    $slug = $countrySlug->fetchColumn();
    $data_country = $countryCodes[$slug] ?? '';

    // Auto-generar display_name si está vacío
    if (empty($display_name)) {
        $display_name = ucfirst($domain);
    }

    if (empty($domain) || $country_id <= 0) {
        $msg = 'El dominio y el país son obligatorios.';
        $msgType = 'error';
    } else {
        $pdo->beginTransaction();
        try {
            $r = sortPrepare($pdo, 'domains', $id, $sort_order, 'country_id', $country_id);
            $sort_order = $r['pos'];

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE domains SET country_id=?, domain=?, display_name=?, category=?, color_class=?, template=?, data_country=?, sub_countries=?, sort_order=? WHERE id=?");
                $stmt->execute([$country_id, $domain, $display_name, $category, $color_class, $template, $data_country, $sub_countries, $sort_order, $id]);
                $msg = 'Dominio actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO domains (country_id, domain, display_name, category, color_class, template, data_country, sub_countries, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$country_id, $domain, $display_name, $category, $color_class, $template, $data_country, $sub_countries, $sort_order]);
                $msg = 'Dominio creado correctamente.';
            }

            sortRenumber($pdo, 'domains', 'country_id', $country_id);
            // Si el dominio venía de otro país, cerrar también el hueco de origen
            if ($r['oldScope'] !== null) {
                sortRenumber($pdo, 'domains', 'country_id', $r['oldScope']);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $msgType = 'success';
        $editing = null;
    }
}

// ── FILTROS ──
$filterCountry = $_GET['country'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterSearch = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($filterCountry) {
    $where[] = "c.slug = ?";
    $params[] = $filterCountry;
}
if ($filterCategory) {
    $where[] = "d.category = ?";
    $params[] = $filterCategory;
}
if ($filterSearch) {
    $where[] = "d.domain LIKE ?";
    $params[] = "%$filterSearch%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── LISTAR ──
$stmt = $pdo->prepare("
    SELECT d.*, c.button_name as country_name, c.slug as country_slug
    FROM domains d
    JOIN countries c ON c.id = d.country_id
    $whereSQL
    ORDER BY c.sort_order, d.category, d.sort_order
");
$stmt->execute($params);
$domains = $stmt->fetchAll();

// Datos para el formulario
$countries = $pdo->query("SELECT * FROM countries ORDER BY sort_order")->fetchAll();
$nextOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM domains")->fetchColumn();

// Colores disponibles: $colorOptions, $brandlessColors y $allColors
require_once 'colors.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dominios - Panel Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🛠️ Pagifier - Panel de Administración</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="countries.php">Países</a>
                <a href="domains.php" class="active">Dominios</a>
                <a href="colores.php">Colores</a>
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
                <h2><?= $editing ? 'Editar dominio' : 'Añadir nuevo dominio' ?></h2>
                <form method="POST">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>País *</label>
                            <select name="country_id" required>
                                <option value="">Selecciona un país</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($editing['country_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['button_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Categoría *</label>
                            <select name="category" required>
                                <option value="adult" <?= ($editing['category'] ?? '') === 'adult' ? 'selected' : '' ?>>Adult & Casual</option>
                                <option value="mature" <?= ($editing['category'] ?? '') === 'mature' ? 'selected' : '' ?>>Mature</option>
                                <option value="mainstream" <?= ($editing['category'] ?? '') === 'mainstream' ? 'selected' : '' ?>>Mainstream</option>
                                <option value="brandless" <?= ($editing['category'] ?? '') === 'brandless' ? 'selected' : '' ?>>Brandless</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Dominio * (ej: sexvenner.com)</label>
                            <input type="text" name="domain" required placeholder="ej: sexvenner.com"
                                   value="<?= htmlspecialchars($editing['domain'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Nombre a mostrar (si se deja vacío = dominio con mayúscula inicial)</label>
                            <input type="text" name="display_name" placeholder="ej: Sexvenner.com"
                                   value="<?= htmlspecialchars($editing['display_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Color</label>
                            <select name="color_class">
                                <?php foreach ($allColors as $cls => $label): ?>
                                    <option value="<?= $cls ?>" <?= ($editing['color_class'] ?? 'pinkshows') === $cls ? 'selected' : '' ?>>
                                        <?= $label ?> (<?= $cls ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Template</label>
                            <select name="template">
                                <option value="t1" <?= ($editing['template'] ?? 't1') === 't1' ? 'selected' : '' ?>>T1</option>
                                <option value="t2" <?= ($editing['template'] ?? '') === 't2' ? 'selected' : '' ?>>T2</option>
                                <option value="t3" <?= ($editing['template'] ?? '') === 't3' ? 'selected' : '' ?>>T3</option>
                                <option value="" <?= ($editing['template'] ?? '') === '' ? 'selected' : '' ?>>Ninguno</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sub-países específicos del dominio (separados por coma)</label>
                            <input type="text" name="sub_countries" placeholder="ej: Nederland, België"
                                   value="<?= htmlspecialchars($editing['sub_countries'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? $nextOrder ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="display:flex; align-items:flex-end; gap:10px;">
                            <button type="submit" class="btn btn-primary">
                                <?= $editing ? 'Guardar cambios' : 'Añadir dominio' ?>
                            </button>
                            <?php if ($editing): ?>
                                <a href="domains.php" class="btn btn-danger">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filtros -->
            <div class="section-header">
                <h2>Dominios (<?= count($domains) ?>)</h2>
            </div>

            <form method="GET" class="filters">
                <select name="country">
                    <option value="">Todos los países</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['slug'] ?>" <?= $filterCountry === $c['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['button_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="category">
                    <option value="">Todas las categorías</option>
                    <option value="adult" <?= $filterCategory === 'adult' ? 'selected' : '' ?>>Adult & Casual</option>
                    <option value="mature" <?= $filterCategory === 'mature' ? 'selected' : '' ?>>Mature</option>
                    <option value="mainstream" <?= $filterCategory === 'mainstream' ? 'selected' : '' ?>>Mainstream</option>
                    <option value="brandless" <?= $filterCategory === 'brandless' ? 'selected' : '' ?>>Brandless</option>
                </select>
                <input type="text" name="search" placeholder="Buscar dominio..." value="<?= htmlspecialchars($filterSearch) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <?php if ($filterCountry || $filterCategory || $filterSearch): ?>
                    <a href="domains.php" class="btn btn-danger btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>

            <!-- Lista -->
            <table>
                <thead>
                    <tr>
                        <th>País</th>
                        <th>Dominio</th>
                        <th>Categoría</th>
                        <th>Color</th>
                        <th>Template</th>
                        <th>Sub-países</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($domains)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#999">No se encontraron dominios</td></tr>
                    <?php endif; ?>
                    <?php foreach ($domains as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['country_name']) ?></td>
                        <td><strong><?= htmlspecialchars($d['domain']) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $d['category'] ?>">
                                <?= $d['category'] === 'adult' ? 'Adult & Casual' : ucfirst($d['category']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="color-dot <?= htmlspecialchars($d['color_class']) ?>"></span>
                            <?= htmlspecialchars($d['color_class']) ?>
                        </td>
                        <td><?= $d['template'] ?: '—' ?></td>
                        <td><?= htmlspecialchars($d['sub_countries']) ?: '<em style="color:#999">—</em>' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="?delete=<?= $d['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar <?= htmlspecialchars($d['domain']) ?>?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
