<?php
require_once 'auth.php';
require_once 'db.php';

$msg = '';
$msgType = '';
$editing = null;

// ── ELIMINAR ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brandless_landings WHERE id = ?");
    $stmt->execute([$id]);

    // Reordenar para cerrar huecos
    $pdo->exec("SET @pos := 0; UPDATE brandless_landings SET sort_order = (@pos := @pos + 1) ORDER BY sort_order");

    $msg = 'Brandless landing eliminada correctamente.';
    $msgType = 'success';
}

// ── EDITAR (cargar datos) ──
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM brandless_landings WHERE id = ?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

// ── GUARDAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $language_code = trim($_POST['language_code']);
    $section = trim($_POST['section']);
    $url_path = trim($_POST['url_path']);
    $label = trim($_POST['label']);
    $sort_order = (int)$_POST['sort_order'];

    if (empty($url_path)) {
        $msg = 'La URL es obligatoria.';
        $msgType = 'error';
    } elseif (empty($language_code)) {
        $msg = 'El idioma es obligatorio.';
        $msgType = 'error';
    } else {
        // Asegurar que empieza con /
        if ($url_path[0] !== '/') {
            $url_path = '/' . $url_path;
        }

        // Comprobar duplicados
        $checkStmt = $pdo->prepare("SELECT id FROM brandless_landings WHERE url_path = ? AND language_code = ? AND id != ?");
        $checkStmt->execute([$url_path, $language_code, $id]);
        if ($checkStmt->fetch()) {
            $msg = 'Ya existe una brandless landing con esa URL para ese idioma.';
            $msgType = 'error';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE brandless_landings SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?");
                $stmt->execute([$sort_order, $id]);
                $stmt = $pdo->prepare("UPDATE brandless_landings SET language_code=?, section=?, url_path=?, label=?, sort_order=? WHERE id=?");
                $stmt->execute([$language_code, $section, $url_path, $label, $sort_order, $id]);
                $msg = 'Brandless landing actualizada correctamente.';
            } else {
                $stmt = $pdo->prepare("UPDATE brandless_landings SET sort_order = sort_order + 1 WHERE sort_order >= ?");
                $stmt->execute([$sort_order]);
                $stmt = $pdo->prepare("INSERT INTO brandless_landings (language_code, section, url_path, label, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$language_code, $section, $url_path, $label, $sort_order]);
                $msg = 'Brandless landing creada correctamente.';
            }
            $msgType = 'success';
            $editing = null;
        }
    }
}

// ── FILTROS ──
$filterLang = $_GET['lang'] ?? '';
$filterSection = $_GET['section'] ?? '';
$filterSearch = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($filterLang) {
    $where[] = "language_code = ?";
    $params[] = $filterLang;
}
if ($filterSection) {
    $where[] = "section = ?";
    $params[] = $filterSection;
}
if ($filterSearch) {
    $where[] = "(url_path LIKE ? OR label LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM brandless_landings $whereSQL ORDER BY language_code, sort_order");
$stmt->execute($params);
$landings = $stmt->fetchAll();

$nextOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM brandless_landings")->fetchColumn();

// Idiomas y secciones disponibles
$languages = $pdo->query("SELECT DISTINCT language_code FROM brandless_landings ORDER BY language_code")->fetchAll(PDO::FETCH_COLUMN);
$sections = $pdo->query("SELECT DISTINCT section FROM brandless_landings ORDER BY section")->fetchAll(PDO::FETCH_COLUMN);

// Mapa de códigos de idioma a nombres
$langNames = [
    'da' => 'Danish', 'nl' => 'Dutch', 'en' => 'English',
    'fi' => 'Finnish', 'fr' => 'French', 'de' => 'German',
    'gr' => 'Greek', 'it' => 'Italian', 'no' => 'Norwegian',
    'pl' => 'Polish', 'po' => 'Portuguese', 'es' => 'Spanish', 'se' => 'Swedish'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brandless Landings - Panel Admin</title>
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
                <a href="landings.php">Landings</a>
                <a href="brandless_landings.php" class="active">Brandless</a>
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
                <h2><?= $editing ? 'Editar brandless landing' : 'Añadir nueva brandless landing' ?></h2>
                <form method="POST">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Idioma *</label>
                            <select name="language_code" required>
                                <option value="" disabled <?= !$editing ? 'selected' : '' ?>>Seleccionar idioma</option>
                                <?php foreach ($langNames as $code => $name): ?>
                                <option value="<?= $code ?>" <?= ($editing['language_code'] ?? '') === $code ? 'selected' : '' ?>><?= $code ?> (<?= $name ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sección *</label>
                            <input type="text" name="section" required placeholder="ej: Prelandings, Direct..."
                                   value="<?= htmlspecialchars($editing['section'] ?? '') ?>">
                            <?php if (!empty($sections)): ?>
                            <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:4px;">
                                <?php foreach ($sections as $sec): ?>
                                <button type="button" class="code-tag" onclick="document.querySelector('[name=section]').value='<?= htmlspecialchars($sec) ?>'"><?= htmlspecialchars($sec) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>URL path * (ej: /dk/1/)</label>
                        <input type="text" name="url_path" required placeholder="/dk/1/"
                               value="<?= htmlspecialchars($editing['url_path'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Label (texto entre paréntesis, ej: "Prelanding to sexvenner.com/lps/int-nak-btn/")</label>
                            <input type="text" name="label" placeholder="Opcional"
                                   value="<?= htmlspecialchars($editing['label'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? $nextOrder ?>">
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end; gap:10px;">
                            <button type="submit" class="btn btn-primary">
                                <?= $editing ? 'Guardar cambios' : 'Añadir brandless landing' ?>
                            </button>
                            <?php if ($editing): ?>
                                <a href="brandless_landings.php" class="btn btn-danger">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filtros -->
            <div class="section-header">
                <h2>Brandless Landings (<?= count($landings) ?>)</h2>
            </div>

            <form method="GET" class="filters">
                <select name="lang">
                    <option value="">Todos los idiomas</option>
                    <?php foreach ($langNames as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $filterLang === $code ? 'selected' : '' ?>><?= $code ?> (<?= $name ?>)</option>
                    <?php endforeach; ?>
                </select>
                <select name="section">
                    <option value="">Todas las secciones</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= htmlspecialchars($sec) ?>" <?= $filterSection === $sec ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sec) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Buscar URL o label..." value="<?= htmlspecialchars($filterSearch) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <?php if ($filterLang || $filterSection || $filterSearch): ?>
                    <a href="brandless_landings.php" class="btn btn-danger btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>

            <!-- Lista -->
            <table>
                <thead>
                    <tr>
                        <th>Idioma</th>
                        <th>Sección</th>
                        <th>URL Path</th>
                        <th>Label</th>
                        <th>Orden</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($landings)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999">No se encontraron brandless landings</td></tr>
                    <?php endif; ?>
                    <?php
                    $prevLang = '';
                    foreach ($landings as $l):
                        $langChanged = $l['language_code'] !== $prevLang;
                        $prevLang = $l['language_code'];
                    ?>
                    <?php if ($langChanged && !$filterLang): ?>
                        <tr style="background:#1a1a2e; color:white;">
                            <td colspan="6" style="font-weight:bold; text-transform:uppercase; padding:10px 16px;">
                                <?= htmlspecialchars($l['language_code']) ?> — <?= $langNames[$l['language_code']] ?? $l['language_code'] ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= htmlspecialchars($l['language_code']) ?></td>
                        <td style="font-size:0.85rem"><?= htmlspecialchars($l['section']) ?></td>
                        <td><code style="font-size:0.85rem"><?= htmlspecialchars($l['url_path']) ?></code></td>
                        <td style="font-size:0.85rem"><?= $l['label'] ? htmlspecialchars($l['label']) : '<span style="color:#ccc">—</span>' ?></td>
                        <td><?= $l['sort_order'] ?></td>
                        <td class="actions">
                            <a href="?edit=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar esta brandless landing?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
