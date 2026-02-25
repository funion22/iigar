<?php
require_once 'auth_index.php';
require_once 'admin/db.php';

// ── CARGAR DATOS ──

// Países ordenados
$countries = $pdo->query("SELECT * FROM countries ORDER BY sort_order")->fetchAll();

// Dominios agrupados por país y categoría
$allDomains = $pdo->query("
    SELECT d.*, c.slug as country_slug, c.title as country_title, 
           c.flag_image, c.sub_countries as country_sub_countries, c.button_name
    FROM domains d
    JOIN countries c ON c.id = d.country_id
    ORDER BY c.sort_order, d.sort_order
")->fetchAll();

// Agrupar dominios: [country_slug][category] => array de dominios
$domainsByCountry = [];
foreach ($allDomains as $d) {
    $domainsByCountry[$d['country_slug']][$d['category']][] = $d;
}

// Landings normales
$landings = $pdo->query("SELECT * FROM landings ORDER BY sort_order")->fetchAll();

// Agrupar landings: [parent_section][section_title] => array
$landingsBySection = [];
foreach ($landings as $l) {
    $landingsBySection[$l['parent_section']][$l['section_title']][] = $l;
}

// Brandless landings
$brandlessLandings = $pdo->query("SELECT * FROM brandless_landings ORDER BY sort_order")->fetchAll();

// Agrupar: [language_code][section] => array
$brandlessByLang = [];
foreach ($brandlessLandings as $bl) {
    $brandlessByLang[$bl['language_code']][$bl['section']][] = $bl;
}

// TIPOS DE CAMPAÑA (NUEVO - Desde BD)
$campaignTypes = $pdo->query("SELECT * FROM campaign_types WHERE active = 1 ORDER BY sort_order")->fetchAll();

// Mapa de categorías
$categoryMap = [
    'adult' => ['column' => 'columnone', 'h2class' => 'unimobile', 'label' => 'ADULT & CASUAL'],
    'mature' => ['column' => 'columntwo', 'h2class' => 'bordernone', 'label' => 'MATURE'],
    'mainstream' => ['column' => 'columntwopartial', 'h2class' => 'bordernone', 'label' => 'MAINSTREAM'],
    'brandless' => ['column' => 'columnthree', 'h2class' => 'bordernone', 'label' => 'BRANDLESS'],
];

// Mapa de brandless color classes por código de idioma
$brandlessColorMap = [
    'da' => 'fiirtingdashows',
    'nl' => 'fiirtingnlshows',
    'en' => 'fiirtingenshows',
    'fi' => 'fiirtingfishows',
    'fr' => 'fiirtingfrshows',
    'de' => 'fiirtingdeshows',
    'it' => 'fiirtingitshows',
    'no' => 'fiirtingnoshows',
    'po' => 'fiirtingposhows',
    'es' => 'fiirtingesshows',
    'se' => 'fiirtingseshows',
];

// Mapa de slug a código ISO para banderas SVG
$flagCodes = [
    'czech' => 'cz', 'denmark' => 'dk', 'netherlands' => 'nl',
    'uk' => 'gb', 'finland' => 'fi', 'france' => 'fr',
    'germany' => 'de', 'greece' => 'gr', 'italy' => 'it',
    'norway' => 'no', 'poland' => 'pl', 'portuguese' => 'pt',
    'spain' => 'es', 'sweden' => 'se'
];

// Para el índice alfabético: todos los dominios NO brandless, ordenados alfabéticamente
$allDomainsAlpha = $pdo->query("
    SELECT d.*, c.button_name, c.sub_countries as country_sub_countries
    FROM domains d
    JOIN countries c ON c.id = d.country_id
    WHERE d.category != 'brandless'
    ORDER BY LOWER(d.display_name)
")->fetchAll();

// Agrupar por letra inicial
$domainsByLetter = [];
foreach ($allDomainsAlpha as $d) {
    $letter = strtolower(substr($d['display_name'], 0, 1));
    $domainsByLetter[$letter][] = $d;
}

// Letras activas
$activeLetters = array_keys($domainsByLetter);
$allLetters = range('a', 'z');

// Mapa de categoría a label para el índice
$categoryLabels = [
    'adult' => 'Adult & Casual',
    'mature' => 'Mature',
    'mainstream' => 'Mainstream',
    'brandless' => 'Brandless',
];

// CACHE BUSTER - versiones de archivos estáticos
$vCSS = @filemtime('css/styles.css') ?: time();
$vClickyCSS = @filemtime('css/clicky-menus.css') ?: time();
$vClickyJS = @filemtime('js/clicky-menus.js') ?: time();
$vMainJS = @filemtime('js/main.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landings Pagifier</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="js/clicky-menus.js?v=<?= $vClickyJS ?>"></script>
    <link rel="stylesheet" href="css/styles.css?v=<?= $vCSS ?>">
    <link rel="stylesheet" href="css/clicky-menus.css?v=<?= $vClickyCSS ?>" />
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <style>
        ul > li.namepopup {
            margin-bottom: 10px !important;
        }
    </style>
</head>
<body class="preSelection">
    <div class="iframeSelection">
        <p style="padding: 0 30px 10px">¿Quieres que se muestren los pop-up al lado de cada landing? <br> <span> Do you want the pop-ups to be shown next to each landing page?</span> </p>
        <p style="padding: 0 30px">Si eliges sí el tiempo de carga de esta página será mayor <br>(Se ha mejorado el tiempo de carga de la página) <br> <span> If you choose yes the loading time of this page will be longer <br>(Improved page load time)</span></p>
        <div class="buttons">
            <a class="showIframes">Sí / Yes</a>
            <a class="hideIframes">No</a>
        </div>
    </div>
    <div id="loading-overlay" style="display: none">
        <div class="loader"></div>
    </div>
<!-- #region countries -->
<div class="overlay" id="overlay"></div>
    <div class="body">
        <div class="blockleft">
            <div class="flags">
                <?php foreach ($countries as $i => $c): ?>
                <button type="button" class="countrylink" id="<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['button_name']) ?></button>
                <div class="linedivisor"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- #endregion -->
        <main>
            <!-- Explanation -->
            <div class="iframe">
                <div class="iframetext">
                    <p>Si quieres ver/ocultar todas las landings disponibles pincha <span class="openiframe">aquí</span></p>
                    <p class="iframeenglish">&nbsp; If you want to show/hide all available landings press <span class="openiframe">here</span></p>
                </div>
                <iframe src="_old/index.html" name="all_landings" id="iframe" style="display: none; resize: vertical;overflow: auto"></iframe>
                <script>
                    (function() {
                        const iframe = document.getElementById('iframe');
                        if (!iframe) return;
                        const STORAGE_KEY = 'pagifier_iframe_height';
                        const MIN_HEIGHT = 400;
                        let isResizing = false;
                        let lockedHeight = null;

                        const saved = sessionStorage.getItem(STORAGE_KEY);
                        if (saved) {
                            const h = parseInt(saved);
                            if (h >= MIN_HEIGHT) {
                                iframe.style.height = saved;
                                lockedHeight = saved;
                            } else {
                                sessionStorage.removeItem(STORAGE_KEY);
                            }
                        }

                        iframe.addEventListener('pointerdown', (e) => {
                            const rect = iframe.getBoundingClientRect();
                            if ((rect.bottom - e.clientY) < 20 && (rect.right - e.clientX) < 20) {
                                isResizing = true;
                            }
                        });

                        document.addEventListener('pointerup', () => {
                            if (isResizing) {
                                const h = iframe.offsetHeight;
                                if (h >= MIN_HEIGHT) {
                                    const val = h + 'px';
                                    sessionStorage.setItem(STORAGE_KEY, val);
                                    lockedHeight = val;
                                }
                                isResizing = false;
                            }
                        });

                        const observer = new ResizeObserver(() => {
                            if (!isResizing && lockedHeight && iframe.style.display !== 'none') {
                                iframe.style.height = lockedHeight;
                            }
                        });
                        observer.observe(iframe);

                        document.querySelectorAll('.openiframe').forEach(btn => {
                            btn.addEventListener('click', () => {
                                if (iframe.style.display === 'none') {
                                    iframe.style.display = 'block';
                                    if (lockedHeight) {
                                        iframe.style.height = lockedHeight;
                                    }
                                } else {
                                    sessionStorage.removeItem(STORAGE_KEY);
                                    lockedHeight = null;
                                    iframe.style.height = '';
                                    iframe.style.display = 'none';
                                }
                            });
                        });
                    })();
                </script>
            </div>
            <div class="contentcountries">
                <!-- PAISES -->
                <?php foreach ($countries as $c):
                    $slug = $c['slug'];
                    $hasSubCountries = !empty($c['sub_countries']);
                    $flagCode = $flagCodes[$slug] ?? '';
                    $flagSrc = $flagCode
                        ? 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/flags/4x3/' . $flagCode . '.svg'
                        : 'images/' . htmlspecialchars($c['flag_image']);
                ?>
                <div class="countries" id="<?= $slug ?>1" style="display:none;">
                    <div class="countrylist<?= $hasSubCountries ? ' multiplecountry' : '' ?>">
                        <img <?= $hasSubCountries ? 'class="multiplenames" ' : '' ?>src="<?= $flagSrc ?>" alt="">
                        <h1><?= htmlspecialchars($c['title']) ?></h1>
                        <?php if ($hasSubCountries): ?>
                        <span>(<?= htmlspecialchars($c['sub_countries']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="colourdomains">
                        <?php foreach ($categoryMap as $catKey => $catInfo): ?>
                        <div class="colourdomainstotalnew <?= $catInfo['column'] ?>">
                            <h2 class="<?= $catInfo['h2class'] ?>"><?= $catInfo['label'] ?></h2>
                            <div class="listtotalnew">
                                <?php if (!empty($domainsByCountry[$slug][$catKey])): ?>
                                <ul class="listdomains">
                                    <?php foreach ($domainsByCountry[$slug][$catKey] as $d): ?>
                                    <li class='replacer showcontent' data-replacer='<?= htmlspecialchars($d['domain']) ?>'<?php if ($d['data_country']): ?> data-country="<?= htmlspecialchars($d['data_country']) ?>"<?php endif; ?>><?php if ($catKey === 'brandless'): ?><h3><a class="<?= htmlspecialchars($d['color_class']) ?>" data-attribute="showcolourcontent" href="javascript:void(0)"><?= htmlspecialchars($d['display_name']) ?></a></h3><?php else: ?>
                                        <h3><a class="<?= htmlspecialchars($d['color_class']) ?>" data-attribute="showcolourcontent" href="javascript:void(0)"><?= htmlspecialchars($d['display_name']) ?></a></h3>
                                        <?php if ($d['template']): ?>
                                        <img src="images/<?= $d['template'] ?>.png" alt="">
                                        <?php endif; ?>
                                    <?php endif; ?></li>
                                    <?php
                                    // Mostrar sub-países si el dominio los tiene O si el país los tiene
                                    $subInfo = $d['sub_countries'] ?: ($hasSubCountries ? $c['sub_countries'] : '');
                                    if ($subInfo && $catKey !== 'brandless'):
                                    ?>
                                    <li class="infodomains">
                                        <div class="infodomainscountry">
                                            <em>(<?= htmlspecialchars($subInfo) ?>)</em>
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div id="landings"></div>
                <!-- Dirección dominios -->
                <div class="contentnamesdomains" id="content-domains" style="display: none">
                    <!-- Dominios brandless -->
                    <?php foreach ($brandlessByLang as $langCode => $sections):
                        $colorClass = $brandlessColorMap[$langCode] ?? 'fiirtingenshows';
                    ?>
                    <div>
                        <div class="contentdomains showcontentbrandless" id="<?= $colorClass ?>content" style="display: none">
                            <?php
                            $currentH3 = '';
                            foreach ($sections as $sectionTitle => $items):
                            ?>
                            <?php if ($currentH3 === ''): $currentH3 = 'Naked'; ?>
                            <h3 class="mains">Naked</h3>
                            <?php endif; ?>
                            <h4 class="mainssection"><?= htmlspecialchars($sectionTitle) ?></h4>
                            <ul class="summary summarylinks">
                                <?php foreach ($items as $bl): ?>
                                <li class="brandless">
                                    <a class='replaceMe' href="https://domainName<?= htmlspecialchars($bl['url_path']) ?>" target="_blank" data-to-be-replaced='domainName'>https://domainName<?= htmlspecialchars($bl['url_path']) ?></a><?php if ($bl['label']): ?><span> (<?= htmlspecialchars($bl['label']) ?>)</span><?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- SELECTOR DE TIPO DE CAMPAÑA (DINÁMICO PERO COMPATIBLE) -->
                    <ul class="subsubmenu">
                        <h2>Tipo de tráfico <span>/ Traffic type</span></h2>
                        <li class="showhiddeniframe">
                            <select name="selecttraffic" class="selecttraffic">
                                <option value="" disabled selected>Elige una opción / Choose an option</option>
                                <?php foreach ($campaignTypes as $ct): ?>
                                <option value="<?= htmlspecialchars($ct['code']) ?>"><?= htmlspecialchars($ct['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="label">
                                <div class="label-text-1">Hide Pop-ups</div>
                                <div class="label-text-2" style="display: none">Show Pop-ups</div>
                                <div class="toggle">
                                    <input class="toggle-state" type="checkbox" name="check" value="check" />
                                    <div class="indicator"></div>
                                </div>
                            </label>
                        </li>
                        <?php foreach ($campaignTypes as $index => $ct): ?>
                        <li class="option<?= $index + 1 ?>" data-attribute="options" id="<?= htmlspecialchars($ct['code']) ?>option" style="display: none">
                            <h4><?= htmlspecialchars($ct['label']) ?></h4>
                            <input id="sTxt<?= htmlspecialchars($ct['code']) ?>" data-attribute="inputOption" class="sTxt" name="s<?= $index + 1 ?>" placeholder="Introduce el s1 aquí" onclick="this.value=''">
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Landings normales -->
                    <div>
                        <div class="contentdomains main-navigation clicky-menu no-js" id="landingscontent" style="display: none">
                            <?php foreach ($landingsBySection as $parentSection => $subsections): ?>
                            <h3 class="mains"><?= ucfirst(htmlspecialchars($parentSection)) ?></h3>
                            <?php foreach ($subsections as $sectionTitle => $items): ?>
                            <h4 class="mainssection"><?= htmlspecialchars($sectionTitle) ?></h4>
                            <ul class="summary summarylinks submenu">
                                <?php foreach ($items as $l): ?>
                                <li class="ci" data-country="<?= htmlspecialchars($l['data_country']) ?>" data-color="<?= htmlspecialchars($l['data_color']) ?>">
                                    <a class='replaceMe sTxturl1' href="https://domainName<?= htmlspecialchars($l['url_path']) ?>" target="_blank" data-to-be-replaced='domainName'>https://domainName<?= htmlspecialchars($l['url_path']) ?></a><?php if ($l['is_new']): ?><span class="badge-new">New</span><?php endif; ?>
                                    <?php
                                    // Generar enlaces con clases compatibles (sTxtcpm, sTxtcpl, etc) y spans con formato sNresetN
                                    foreach ($campaignTypes as $i => $ct):
                                        $num = $i + 1;
                                    ?>
                                    <a class="sTxt<?= htmlspecialchars($ct['code']) ?>" data-attribute="selectedOption" style="display: none;"><span class="s<?= $num ?>reset<?= $num ?>"></span></a>
                                    <?php endforeach; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <div class="blockright">
            <div class="contentalfor">
                <ul>
                    <?php foreach (array_slice($allLetters, 0, 14) as $letter): ?>
                    <li><a href="<?= in_array($letter, $activeLetters) ? '#'.$letter : '' ?>"<?= !in_array($letter, $activeLetters) ? ' class="disabled"' : '' ?>><?= strtoupper($letter) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <ul>
                    <?php foreach (array_slice($allLetters, 14) as $letter): ?>
                    <li><a href="<?= in_array($letter, $activeLetters) ? '#'.$letter : '' ?>"<?= !in_array($letter, $activeLetters) ? ' class="disabled"' : '' ?>><?= strtoupper($letter) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="contentalfordom">
                <?php foreach ($allLetters as $letter):
                    if (!isset($domainsByLetter[$letter])) continue;
                ?>
                <ul id="<?= $letter ?>">
                    <?php foreach ($domainsByLetter[$letter] as $d):
                        $countryLabel = $d['sub_countries'] ?: ($d['country_sub_countries'] ?: $d['button_name']);
                    ?>
                    <li class='replacer showcontent' data-replacer='<?= htmlspecialchars($d['domain']) ?>'>
                        <div>
                            <a class="<?= htmlspecialchars($d['color_class']) ?>" data-attribute="showcolourcontent" href="javascript:void(0)"><?= htmlspecialchars(ucfirst(strtolower($d['display_name']))) ?></a>
                            <?php if ($d['template']): ?>
                            <img src="images/<?= $d['template'] ?>.png" alt="">
                            <?php endif; ?>
                        </div>
                        <span><?= htmlspecialchars($countryLabel) ?></span>
                        <span class="classad-main"><?= $categoryLabels[$d['category']] ?? ucfirst($d['category']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
            const JS_MAIN_VERSION = '<?= $vMainJS ?>';

            document.addEventListener("DOMContentLoaded", () => {
              document.querySelectorAll('[data-attribute="showcolourcontent"]').forEach(el => {
                const text = el.textContent.trim();
                if (text.length > 0) {
                  el.textContent = text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
                }
              });
            });

            function ajustarAlturas() {
                const blockLeft = document.querySelector('.blockleft');
                const blockRight = document.querySelector('.blockright');
                const main = document.querySelector('main');

                if (blockLeft && blockRight && main) {
                    const rightHeight = blockRight.scrollHeight;
                    blockLeft.style.height = rightHeight + 'px';
                    main.style.height = rightHeight + 'px';
                }
            }
            ajustarAlturas();

            window.addEventListener('resize', ajustarAlturas)

            function getIframes() {
                setTimeout(() => {
                    var interval = setInterval(function() {
                        for (let i = 0; i < document.querySelectorAll('.ci').length; i++) {
                            if ((document.querySelectorAll('.ci')[i].children.length = 7) && (document.querySelector('.script').children.length > 0)) {
                                clearInterval(interval);
                                document.getElementById("loading-overlay").style.display = "none";
                                document.querySelector(".body").classList.add("bodywb");
                                document.getElementById('overlay').style.display = 'none';
                            }
                        }
                    }, 100);
                }, 100);
            }

            function getNoIframes() {
                setTimeout(() => {
                    var interval = setInterval(function() {
                        if (document.querySelector('.script').children.length > 0) {
                            clearInterval(interval);
                            document.getElementById("loading-overlay").style.display = "none";
                            document.querySelector(".body").classList.add("bodywb");
                            document.getElementById('overlay').style.display = 'none';
                        }
                    }, 100);
                }, 100);
            }

            window.iframeProxyEnabled = true;

            document.querySelector('.showIframes').addEventListener("click", function (e) {
                e.stopImmediatePropagation();

                document.querySelector('.iframeSelection').style.display = "none";
                document.getElementById("loading-overlay").style.display = "flex";
                limpiarIframesAntiguos();
                setTimeout(() => {
                    cargarNuevosIframes();
                    cargarScriptSiNoExiste("js/main.js");
                }, 100);
                getIframes();
            }, true);

            function limpiarIframesAntiguos() {
                document.querySelectorAll('.iframecapture').forEach(iframe => {
                    try {
                        iframe.src = 'about:blank';
                    } catch (e) {
                        console.warn("No se pudo limpiar src del iframe", e);
                    }
                    iframe.remove();
                });
            }

            function cargarNuevosIframes() {
                const contenedores = document.querySelectorAll('.ci');

                contenedores.forEach((container, index) => {
                    const iframe = document.createElement('iframe');
                    iframe.className = "iframecapture";
                    iframe.sandbox = "allow-same-origin allow-scripts allow-forms allow-popups";
                    iframe.loading = "lazy";
                    iframe.width = "600";
                    iframe.height = "450";

                    const linkElement = container.querySelector('a.replaceMe, a.sTxturl1');
                    if (linkElement) {
                        const originalUrl = linkElement.href;
                        const proxyUrl = 'iframe-proxy.php?url=' + encodeURIComponent(originalUrl);
                        iframe.src = proxyUrl;
                    }
                    container.appendChild(iframe);
                });
            }

            function cargarScriptSiNoExiste(src) {
                const versionedSrc = src + '?v=' + JS_MAIN_VERSION;
                if (!document.querySelector(`script[src="${versionedSrc}"]`)) {
                    const script = document.createElement("script");
                    script.src = versionedSrc;
                    document.querySelector('.script').appendChild(script);
                }
            }

            document.querySelector('.hideIframes').addEventListener( "click", function() {
                document.querySelector('.iframeSelection').style.display = "none";
                document.querySelector('.label').style.display = "none";
                document.querySelectorAll('ul.summary > li').forEach(el => el.classList.add("checkedbutton"));
                document.getElementById("loading-overlay").style.display = "flex";
                var newScript = document.createElement("script");
                newScript.src = "js/main.js?v=" + JS_MAIN_VERSION;
                document.querySelector('.script').appendChild(newScript);
                getNoIframes();
            });

            document.querySelectorAll('.contentalfor a[href^="#"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href').substring(1);
                    const target = document.getElementById(targetId);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth' });
                        history.replaceState(null, '', window.location.pathname);
                    }
                });
            });
    </script>

    <script>
    window.CAMPAIGN_CONFIG = <?= json_encode(array_values(array_map(function($ct, $i) {
        return [
            'code'      => $ct['code'],
            'urlParams' => $ct['url_params'],
            'index'     => $i + 1,
        ];
    }, $campaignTypes, array_keys($campaignTypes)))) ?>;
    </script>

    <div class="script"></div>
</body>
</html>
