<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'sort_order.php';

// Eliminar tipo de campaña
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM campaign_types WHERE id = ?")->execute([$id]);
        sortRenumber($pdo, 'campaign_types');
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    header("Location: campaign_types.php");
    exit;
}

// Obtener todos los tipos de campaña
$campaigns = $pdo->query("SELECT * FROM campaign_types ORDER BY sort_order, id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Types - Admin</title>
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
        .code {
            font-family: 'Courier New', monospace;
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        .url-params {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #555;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
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
                <a href="brandless_landings.php">Brandless</a>
                <a href="campaign_types.php" class="active">Campañas</a>
                <a href="../index.php" target="_blank">Ver página →</a>
            </nav>
        </header>

        <main>
        
        <h1>Tipos de Campaña</h1>
        
        <div class="actions actionscampaigns">
            <a href="campaign_types_edit.php" class="btn btn-success">+ Añadir nuevo tipo</a>
        </div>

        <?php if (empty($campaigns)): ?>
        <div class="empty-state">
            <p>No hay tipos de campaña configurados.</p>
            <p>Haz clic en "Añadir nuevo tipo" para crear uno.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Código</th>
                    <th>Etiqueta</th>
                    <th>Patrón URL</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['sort_order']) ?></td>
                    <td><span class="code"><?= htmlspecialchars($c['code']) ?></span></td>
                    <td><?= htmlspecialchars($c['label']) ?></td>
                    <td><span class="url-params" title="<?= htmlspecialchars($c['url_params'] ?? '') ?>"><?= htmlspecialchars($c['url_params'] ?? '—') ?></span></td>
                    <td>
                        <?php if ($c['active']): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="campaign_types_edit.php?id=<?= $c['id'] ?>" class="btn">Editar</a>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este tipo de campaña?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </main>
    </div>
</body>
</html>
