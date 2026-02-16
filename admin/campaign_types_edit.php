<?php
require_once 'auth.php';
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Cargar datos si es edición
if ($id > 0) {
    $campaign = $pdo->prepare("SELECT * FROM campaign_types WHERE id = ?");
    $campaign->execute([$id]);
    $campaign = $campaign->fetch();
    
    if (!$campaign) {
        header("Location: campaign_types.php");
        exit;
    }
} else {
    // Valores por defecto para nuevo registro
    $campaign = [
        'code' => '',
        'label' => '',
        'url_params' => '',
        'sort_order' => 0,
        'active' => 1
    ];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $label = trim($_POST['label']);
    $url_params = trim($_POST['url_params']);
    $sort_order = (int)$_POST['sort_order'];
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validaciones
    if (empty($code)) {
        $errors[] = "El código es obligatorio";
    } elseif (!preg_match('/^[a-z0-9_-]+$/i', $code)) {
        $errors[] = "El código solo puede contener letras, números, guiones y guiones bajos";
    }
    
    if (empty($label)) {
        $errors[] = "La etiqueta es obligatoria";
    }

    if (empty($url_params)) {
        $errors[] = "El patrón de URL es obligatorio";
    } elseif (strpos($url_params, '{INPUT}') === false) {
        $errors[] = "El patrón de URL debe contener {INPUT} como placeholder para el valor del input";
    }
    
    // Verificar código único
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM campaign_types WHERE code = ? AND id != ?");
        $check->execute([$code, $id]);
        if ($check->fetch()) {
            $errors[] = "Ya existe un tipo de campaña con ese código";
        }
    }
    
    // Guardar
    if (empty($errors)) {
        try {
            if ($id > 0) {
                $conflict = $pdo->prepare("SELECT COUNT(*) FROM campaign_types WHERE sort_order = ? AND id != ?");
                $conflict->execute([$sort_order, $id]);
                if ($conflict->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE campaign_types SET sort_order = sort_order + 1 WHERE sort_order >= ? AND id != ?")
                        ->execute([$sort_order, $id]);
                }
                // Actualizar
                $stmt = $pdo->prepare("
                    UPDATE campaign_types
                    SET code = ?, label = ?, url_params = ?, sort_order = ?, active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$code, $label, $url_params, $sort_order, $active, $id]);
            } else {
                $conflict = $pdo->prepare("SELECT COUNT(*) FROM campaign_types WHERE sort_order = ?");
                $conflict->execute([$sort_order]);
                if ($conflict->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE campaign_types SET sort_order = sort_order + 1 WHERE sort_order >= ?")
                        ->execute([$sort_order]);
                }
                // Crear
                $stmt = $pdo->prepare("
                    INSERT INTO campaign_types (code, label, url_params, sort_order, active)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$code, $label, $url_params, $sort_order, $active]);
            }

            header("Location: campaign_types.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'Editar' : 'Nuevo' ?> Tipo de Campaña - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .back-link {
            margin-bottom: 20px;
            display: inline-block;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .help-text {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input {
            width: auto;
            margin-right: 8px;
        }
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        label .required {
            color: #dc3545;
        }
        .url-preview {
            margin-top: 8px;
            padding: 10px 14px;
            background: #1e1e2e;
            color: #a6e3a1;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
            display: none;
        }
        .url-preview .domain-part { color: #89b4fa; }
        .url-preview .separator-part { color: #f9e2af; }
        .url-preview .input-part { color: #f38ba8; font-weight: bold; }
    </style>
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
                <a href="campaign_types.php" class="active">Campañas</a>
                <a href="../index.php" target="_blank">Ver página →</a>
            </nav>
        </header>

        <main>
            <a href="campaign_types.php" class="back-link">← Volver al listado</a>

            <div class="form-card">
                <h1><?= $id > 0 ? 'Editar' : 'Nuevo' ?> Tipo de Campaña</h1>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin-left: 20px;">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>
                    Código <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="code"
                    value="<?= htmlspecialchars($campaign['code']) ?>"
                    required
                    <?= $id > 0 ? 'readonly' : '' ?>
                >
                <div class="help-text">
                    Identificador único en minúsculas (ej: cpm, cpl, custom_traffic).
                    <?= $id > 0 ? 'No se puede modificar después de crear.' : '' ?>
                </div>
            </div>

            <div class="form-group">
                <label>
                    Etiqueta <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="label"
                    value="<?= htmlspecialchars($campaign['label']) ?>"
                    required
                >
                <div class="help-text">
                    Nombre mostrado en el selector (ej: CPM, CPL, ADWORDS)
                </div>
            </div>

            <div class="form-group">
                <label>
                    Patrón de URL <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="url_params"
                    id="url_params"
                    value="<?= htmlspecialchars($campaign['url_params'] ?? '') ?>"
                    required
                    placeholder="add=BckBtn&s1={INPUT}&s2=campaign&s3=source&tracking_id=clickid"
                >
                <div class="help-text">
                    Parámetros que se añaden a la URL. Usa <code>{INPUT}</code> donde va el valor que introduce el usuario.<br>
                    Ejemplos:<br>
                    • CPM: <code>add=BckBtn&s1={INPUT}&s2=campaign&s3=source&s4=s4&tracking_id=clickid</code><br>
                    • CPL: <code>add=BckBtn&s1={INPUT}&s2=affId&tracking_id=clickid</code><br>
                    • SEM: <code>s1={INPUT}&s2={Campaign}&s3={AdGroup}&s4={keyword}-{MatchType}&tracking_id={msclkid}</code>
                </div>
                <div class="url-preview" id="urlPreview"></div>
            </div>

            <div class="form-group">
                <label>Orden de aparición</label>
                <input
                    type="number"
                    name="sort_order"
                    value="<?= htmlspecialchars($campaign['sort_order']) ?>"
                    min="0"
                >
                <div class="help-text">
                    Número que determina el orden en el selector (menor = primero)
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input
                        type="checkbox"
                        name="active"
                        id="active"
                        <?= $campaign['active'] ? 'checked' : '' ?>
                    >
                    <label for="active" style="margin: 0; font-weight: normal;">
                        Activo (mostrar en el selector)
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $id > 0 ? 'Guardar cambios' : 'Crear tipo de campaña' ?>
                </button>
                <a href="campaign_types.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
            </div>
        </main>
    </div>

    <script>
    // Preview en vivo del patrón de URL
    const urlInput = document.getElementById('url_params');
    const preview = document.getElementById('urlPreview');

    function updatePreview() {
        const val = urlInput.value.trim();
        if (!val) {
            preview.style.display = 'none';
            return;
        }
        preview.style.display = 'block';
        const highlighted = val
            .replace(/\{INPUT\}/g, '<span class="input-part">{INPUT}</span>')
            .replace(/([?&])/g, '<span class="separator-part">$1</span>');
        preview.innerHTML = '<span class="domain-part">https://domain.com/landing</span><span class="separator-part">?</span>' + highlighted;
    }

    urlInput.addEventListener('input', updatePreview);
    updatePreview();
    </script>
</body>
</html>
