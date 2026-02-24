<?php
// ============================================
// Panel de Administración - Dashboard
// ============================================
require_once 'auth.php';
require_once 'db.php';

// Obtener conteos
$countCountries = $pdo->query("SELECT COUNT(*) FROM countries")->fetchColumn();
$countDomains = $pdo->query("SELECT COUNT(*) FROM domains")->fetchColumn();
$countLandings = $pdo->query("SELECT COUNT(*) FROM landings")->fetchColumn();
$countBrandless = $pdo->query("SELECT COUNT(*) FROM brandless_landings")->fetchColumn();

// Obtener dominios por país
$domainsByCountry = $pdo->query("
    SELECT c.button_name, c.slug, COUNT(d.id) as total 
    FROM countries c 
    LEFT JOIN domains d ON d.country_id = c.id 
    GROUP BY c.id 
    ORDER BY c.sort_order
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Pagifier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>🛠️ Pagifier - Panel de Administración</h1>
            <nav>
                <a href="index.php" class="active">Dashboard</a>
                <a href="countries.php">Países</a>
                <a href="domains.php">Dominios</a>
                <a href="landings.php">Landings</a>
                <a href="brandless_landings.php">Brandless</a>
                <a href="campaign_types.php">Campañas</a>
                <a href="../index.php" target="_blank">Ver página →</a>
            </nav>
        </header>

        <main>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $countCountries ?></span>
                    <span class="stat-label">Países</span>
                    <a href="countries.php">Gestionar →</a>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $countDomains ?></span>
                    <span class="stat-label">Dominios</span>
                    <a href="domains.php">Gestionar →</a>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $countLandings ?></span>
                    <span class="stat-label">Landings</span>
                    <a href="landings.php">Gestionar →</a>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $countBrandless ?></span>
                    <span class="stat-label">Brandless Landings</span>
                </div>
            </div>
            <div class="section-header">
                <h2>Dominios por país</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>País</th>
                        <th>Dominios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domainsByCountry as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['button_name']) ?></td>
                        <td><?= $row['total'] ?></td>
                        <td><a href="domains.php?country=<?= $row['slug'] ?>">Ver dominios →</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
