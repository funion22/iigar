<?php
require_once 'auth.php';
require_once 'db.php';

$msg = '';
$msgType = '';
$editing = null;

// ── ELIMINAR ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM landings WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'Landing eliminada correctamente.';
    $msgType = 'success';
}

// ── EDITAR (cargar datos) ──
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM landings WHERE id = ?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

// ── GUARDAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $parent_section = $_POST['parent_section'];
    $section_title = trim($_POST['section_title']);
    $url_path = trim($_POST['url_path']);
    $data_country = trim($_POST['data_country']);
    $data_color = trim($_POST['data_color']);
    $sort_order = (int)$_POST['sort_order'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;

    if (empty($url_path)) {
        $msg = 'La URL es obligatoria.';
        $msgType = 'error';
    } else {
        // Asegurar que empieza con /
        if ($url_path[0] !== '/') {
            $url_path = '/' . $url_path;
        }

        // Comprobar duplicados
        $checkStmt = $pdo->prepare("SELECT id FROM landings WHERE url_path = ? AND id != ?");
        $checkStmt->execute([$url_path, $id]);
        if ($checkStmt->fetch()) {
            $msg = 'Ya existe una landing con esa URL.';
            $msgType = 'error';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE landings SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?");
                $stmt->execute([$sort_order, $id]);
                $stmt = $pdo->prepare("UPDATE landings SET parent_section=?, section_title=?, url_path=?, data_country=?, data_color=?, sort_order=?, is_new=? WHERE id=?");
                $stmt->execute([$parent_section, $section_title, $url_path, $data_country, $data_color, $sort_order, $is_new, $id]);
                $msg = 'Landing actualizada correctamente.';
            } else {
                $stmt = $pdo->prepare("UPDATE landings SET sort_order = sort_order + 1 WHERE sort_order >= ?");
                $stmt->execute([$sort_order]);
                $stmt = $pdo->prepare("INSERT INTO landings (parent_section, section_title, url_path, data_country, data_color, sort_order, is_new) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$parent_section, $section_title, $url_path, $data_country, $data_color, $sort_order, $is_new]);
                $msg = 'Landing creada correctamente.';
            }
            $msgType = 'success';
            $editing = null;
        }
    }
}

// ── FILTROS ──
$filterSection = $_GET['section'] ?? '';
$filterSubsection = $_GET['subsection'] ?? '';
$filterColor = $_GET['color'] ?? '';
$filterSearch = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($filterSection) {
    $where[] = "parent_section = ?";
    $params[] = $filterSection;
}
if ($filterSubsection) {
    $where[] = "section_title = ?";
    $params[] = $filterSubsection;
}
if ($filterColor) {
    $where[] = "data_color LIKE ?";
    $params[] = "%$filterColor%";
}
if ($filterSearch) {
    $where[] = "url_path LIKE ?";
    $params[] = "%$filterSearch%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM landings $whereSQL ORDER BY sort_order");
$stmt->execute($params);
$landings = $stmt->fetchAll();

$nextOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM landings")->fetchColumn();

// Obtener subsecciones únicas para el filtro
$subsections = $pdo->query("SELECT DISTINCT section_title FROM landings ORDER BY section_title")->fetchAll(PDO::FETCH_COLUMN);

// Colores posibles
$colorOptions = ['pink', 'pink-t3', 'red', 'orange', 'mature-pink', 'mature-orange', 'all'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landings - Panel Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🛠️ Pagifier - Panel de Administración</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="countries.php">Países</a>
                <a href="domains.php">Dominios</a>
                <a href="landings.php" class="active">Landings</a>
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
                <h2><?= $editing ? 'Editar landing' : 'Añadir nueva landing' ?></h2>
                <form method="POST">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sección principal *</label>
                            <select name="parent_section" required>
                                <option value="naked" <?= ($editing['parent_section'] ?? 'naked') === 'naked' ? 'selected' : '' ?>>Naked</option>
                                <option value="clothed" <?= ($editing['parent_section'] ?? '') === 'clothed' ? 'selected' : '' ?>>Clothed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subsección *</label>
                            <select name="section_title" required>
                                <option value="Prelandings - Default" <?= ($editing['section_title'] ?? '') === 'Prelandings - Default' ? 'selected' : '' ?>>Prelandings - Default</option>
                                <option value="Prelandings - Options" <?= ($editing['section_title'] ?? '') === 'Prelandings - Options' ? 'selected' : '' ?>>Prelandings - Options</option>
                                <option value="Prelanding - Redirection to best" <?= ($editing['section_title'] ?? '') === 'Prelanding - Redirection to best' ? 'selected' : '' ?>>Prelanding - Redirection to best</option>
                                <option value="Prelanding and Direct" <?= ($editing['section_title'] ?? '') === 'Prelanding and Direct' ? 'selected' : '' ?>>Prelanding and Direct</option>
                                <option value="Direct" <?= ($editing['section_title'] ?? '') === 'Direct' ? 'selected' : '' ?>>Direct</option>
                                <option value="Prelandings" <?= ($editing['section_title'] ?? '') === 'Prelandings' ? 'selected' : '' ?>>Prelandings</option>
                                <option value="Landings" <?= ($editing['section_title'] ?? '') === 'Landings' ? 'selected' : '' ?>>Landings</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>URL path * (ej: /lps/cha-int-nak/)</label>
                        <input type="text" name="url_path" required placeholder="/lps/cha-int-nak/"
                               value="<?= htmlspecialchars($editing['url_path'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>País(es) (click para añadir, separados por coma)</label>
                            <input type="text" name="data_country" id="data_country" placeholder="all, o varios: dk,no,se"
                                   value="<?= htmlspecialchars($editing['data_country'] ?? 'all') ?>">
                            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:4px;">
                                <button type="button" class="code-tag" onclick="setCode('all')" title="Todos los países">
                                    all <small>(Todos)</small>
                                </button>
                                <?php
                                $countryCodes = [
                                    ['cz', 'Czech Republic'], ['dk', 'Denmark'], ['nl', 'Dutch countries'],
                                    ['en', 'English countries'], ['fi', 'Finland'], ['fr', 'French countries'],
                                    ['de', 'German countries'], ['gr', 'Greece'], ['it', 'Italy'],
                                    ['no', 'Norway'], ['pl', 'Poland'], ['pt', 'Portuguese countries'],
                                    ['es', 'Spanish countries'], ['se', 'Sweden']
                                ];
                                foreach ($countryCodes as [$code, $name]):
                                ?>
                                    <button type="button" class="code-tag" onclick="addCode('<?= $code ?>')" title="<?= $name ?>">
                                        <?= $code ?> <small>(<?= $name ?>)</small>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Data-color (ej: pink, red, orange, all, mature-pink, mature-orange)</label>
                            <input type="text" name="data_color" placeholder="pink"
                                   value="<?= htmlspecialchars($editing['data_color'] ?? 'pink') ?>">
                            <small style="color:#999">Separar múltiples colores por coma: pink,red,orange</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? $nextOrder ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end; gap:10px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none; padding-bottom:8px;">
                                <input type="checkbox" name="is_new" value="1" <?= ($editing['is_new'] ?? 0) ? 'checked' : '' ?>
                                       style="width:18px; height:18px; accent-color:#ff4444;">
                                <span style="color:#ff4444; font-weight:600;">Marcar como NEW</span>
                            </label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end; gap:10px;">
                            <button type="submit" class="btn btn-primary">
                                <?= $editing ? 'Guardar cambios' : 'Añadir landing' ?>
                            </button>
                            <?php if ($editing): ?>
                                <a href="landings.php" class="btn btn-danger">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filtros -->
            <div class="section-header">
                <h2>Landings (<?= count($landings) ?>)</h2>
            </div>

            <form method="GET" class="filters">
                <select name="section">
                    <option value="">Todas las secciones</option>
                    <option value="naked" <?= $filterSection === 'naked' ? 'selected' : '' ?>>Naked</option>
                    <option value="clothed" <?= $filterSection === 'clothed' ? 'selected' : '' ?>>Clothed</option>
                </select>
                <select name="subsection">
                    <option value="">Todas las subsecciones</option>
                    <?php foreach ($subsections as $sub): ?>
                        <option value="<?= htmlspecialchars($sub) ?>" <?= $filterSubsection === $sub ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="color">
                    <option value="">Todos los colores</option>
                    <?php foreach ($colorOptions as $col): ?>
                        <option value="<?= $col ?>" <?= $filterColor === $col ? 'selected' : '' ?>><?= $col ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Buscar URL..." value="<?= htmlspecialchars($filterSearch) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <?php if ($filterSection || $filterSubsection || $filterColor || $filterSearch): ?>
                    <a href="landings.php" class="btn btn-danger btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>

            <!-- Lista -->
            <table>
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th>Subsección</th>
                        <th>URL Path</th>
                        <th>País</th>
                        <th>Color</th>
                        <th>Orden</th>
                        <th>New</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($landings)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:#999">No se encontraron landings</td></tr>
                    <?php endif; ?>
                    <?php
                    $prevSection = '';
                    $prevSubsection = '';
                    foreach ($landings as $l):
                        $sectionChanged = $l['parent_section'] !== $prevSection;
                        $subsectionChanged = $l['section_title'] !== $prevSubsection;
                        $prevSection = $l['parent_section'];
                        $prevSubsection = $l['section_title'];
                    ?>
                    <?php if ($sectionChanged && !$filterSection): ?>
                        <tr style="background:#1a1a2e; color:white;">
                            <td colspan="8" style="font-weight:bold; text-transform:uppercase; padding:10px 16px;">
                                <?= htmlspecialchars($l['parent_section']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($subsectionChanged && !$filterSubsection): ?>
                        <tr style="background:#e8ecf4;">
                            <td colspan="8" style="font-weight:600; padding:8px 16px; color:#555;">
                                <?= htmlspecialchars($l['section_title']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= ucfirst($l['parent_section']) ?></td>
                        <td style="font-size:0.85rem"><?= htmlspecialchars($l['section_title']) ?></td>
                        <td><code style="font-size:0.85rem"><?= htmlspecialchars($l['url_path']) ?></code></td>
                        <td><?= htmlspecialchars($l['data_country']) ?></td>
                        <td style="font-size:0.85rem"><?= htmlspecialchars($l['data_color']) ?></td>
                        <td><?= $l['sort_order'] ?></td>
                        <td><?php if ($l['is_new']): ?><span style="background:#ff4444; color:white; font-size:10px; font-weight:700; padding:2px 6px; border-radius:3px;">NEW</span><?php endif; ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar esta landing?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
<script>
function setCode(code) {
    document.getElementById('data_country').value = code;
}

function addCode(code) {
    const input = document.getElementById('data_country');
    const current = input.value.trim();
    if (!current || current === 'all') {
        input.value = code;
    } else {
        const codes = current.split(',').map(c => c.trim());
        if (!codes.includes(code)) {
            codes.push(code);
            input.value = codes.join(',');
        }
    }
    input.focus();
}
</script>
</body>
</html>
