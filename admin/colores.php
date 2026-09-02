<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'colors.php';
require_once 'laravel_domains.php';

$orden = ($_GET['orden'] ?? 'color') === 'laravel' ? 'laravel' : 'color';

$filas = $pdo->query("
    SELECT d.domain, d.color_class, c.button_name AS country_name
    FROM domains d
    JOIN countries c ON c.id = d.country_id
    WHERE d.category != 'brandless'
    ORDER BY c.sort_order, d.domain
")->fetchAll();

// Agrupados por color, en el orden de $colorOptions
$grupos = array_fill_keys(array_keys($colorOptions), []);
foreach ($filas as $f) {
    $grupos[$f['color_class']][] = $f;
}
// Colores que no están en el mapa (por si algún dominio tiene una clase suelta)
$sueltos = array_diff_key($grupos, $colorOptions);
$grupos = array_intersect_key($grupos, $colorOptions) + $sueltos;

// Indexados por dominio, para el orden de Laravel
$porDominio = [];
foreach ($filas as $f) {
    $porDominio[strtolower($f['domain'])] = $f;
}

// Lista de Laravel emparejada, y al final los que solo están en Pagifier
$enOrdenLaravel = [];
$sinColor = 0;
foreach ($laravelDomains as $id => $dominio) {
    $d = $porDominio[strtolower($dominio)] ?? null;
    $enOrdenLaravel[] = ['id' => $id, 'domain' => $dominio, 'datos' => $d];
    if (!$d) {
        $sinColor++;
    }
}
$enLaravel = array_flip(array_map('strtolower', $laravelDomains));
$soloPagifier = [];
foreach ($porDominio as $clave => $f) {
    if (!isset($enLaravel[$clave])) {
        $soloPagifier[] = $f;
    }
}

// Bolita + etiqueta de un color
$etiqueta = fn(string $clase) => '<span class="color-dot ' . htmlspecialchars($clase) . '"></span>'
    . htmlspecialchars($allColors[$clase] ?? $clase);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colores por dominio - Panel Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .leyenda {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }
        .leyenda span {
            background: white;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .leyenda b { color: #999; font-weight: 500; }
        .orden a {
            background: white;
            color: #666;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .orden a.btn-primary { background: #4a6cf7; color: white; }
        .nota {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 18px;
        }
        .aviso {
            background: #fff8e6;
            border-left: 3px solid #e0b44a;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            font-size: 0.85rem;
            color: #6b5520;
            margin-bottom: 18px;
        }
        .grupo td {
            background: #f7f8fa;
            font-weight: 600;
            border-top: 1px solid #e4e6eb;
        }
        .muted { color: #999; }
        .num { color: #bbb; font-size: 0.8rem; width: 50px; }
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
                <a href="colores.php" class="active">Colores</a>
                <a href="landings.php">Landings</a>
                <a href="brandless_landings.php">Brandless</a>
                <a href="campaign_types.php">Campañas</a>
                <a href="../index.php" target="_blank">Ver página →</a>
            </nav>
        </header>

        <main>
            <div class="section-header">
                <h2>Colores por dominio</h2>
                <div class="orden">
                    <a href="?orden=color"
                       class="btn btn-sm <?= $orden === 'color' ? 'btn-primary' : '' ?>">Por color</a>
                    <a href="?orden=laravel"
                       class="btn btn-sm <?= $orden === 'laravel' ? 'btn-primary' : '' ?>">Orden de Laravel</a>
                </div>
            </div>

            <p class="nota">
                Los dominios <strong>Pink T3</strong> ven las landings <strong>pink</strong>
                y <strong>pink-t3</strong>. El resto solo ve las de su color.
            </p>

            <?php if ($orden === 'color'): ?>

            <div class="leyenda">
                <?php foreach ($grupos as $clase => $lista): ?>
                <span><?= $etiqueta($clase) ?> <b><?= count($lista) ?></b></span>
                <?php endforeach; ?>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Dominio</th>
                        <th>País</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $clase => $lista): ?>
                    <tr class="grupo">
                        <td colspan="2"><?= $etiqueta($clase) ?></td>
                    </tr>
                        <?php if (!$lista): ?>
                        <tr><td colspan="2" class="muted">Ningún dominio con este color</td></tr>
                        <?php endif; ?>
                        <?php foreach ($lista as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['domain']) ?></strong></td>
                            <td class="muted"><?= htmlspecialchars($d['country_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php else: ?>

            <?php if ($sinColor): ?>
            <p class="aviso">
                <?= $sinColor ?> <?= $sinColor === 1 ? 'dominio de Laravel no está' : 'dominios de Laravel no están' ?>
                dado de alta en Pagifier, así que no tiene color asignado.
            </p>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dominio</th>
                        <th>Color</th>
                        <th>País</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enOrdenLaravel as $t): $d = $t['datos']; ?>
                    <tr>
                        <td class="num"><?= $t['id'] ?></td>
                        <td><strong><?= htmlspecialchars($t['domain']) ?></strong></td>
                        <td>
                            <?php if ($d): ?>
                                <?= $etiqueta($d['color_class']) ?>
                            <?php else: ?>
                                <span class="muted">no está en Pagifier</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= $d ? htmlspecialchars($d['country_name']) : '' ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if ($soloPagifier): ?>
                    <tr class="grupo">
                        <td colspan="4">En Pagifier pero no en la lista de Laravel</td>
                    </tr>
                        <?php foreach ($soloPagifier as $d): ?>
                        <tr>
                            <td class="num"></td>
                            <td><strong><?= htmlspecialchars($d['domain']) ?></strong></td>
                            <td><?= $etiqueta($d['color_class']) ?></td>
                            <td class="muted"><?= htmlspecialchars($d['country_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>
