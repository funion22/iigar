<?php
/**
 * Proxy para iframes
 * Oculta popups de cookies de landing pages
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    die('Error: URL no proporcionada');
}

// Validar dominios permitidos
$allowed_domains = [
    'afspraak.nl',
    'aikuistendeitit.com',
    'aikuistentreffit.fi',
    'amicadiletto.com',
    'amigosconventaja.com',
    'amigovios.com',
    'amizadecolorida.com',
    'aussieflirting.com',
    'baiser.fr',
    'bezzavazku.net',
    'bezzobowiazan.com',
    'bumsen.com',
    'coger.mx',
    'coupdunsoir.fr',
    'coupsdunsoir.com',
    'datingforvoksne.com',
    'derechoaroce.com',
    'derechoaroce.es',
    'dojrzalerandkowanie.com',
    'encontroadulto.com',
    'engangsknald.com',
    'engangsligg.com',
    'engatar.com',
    'eplancul.com',
    'erotofilarakia.com',
    'fick.co',
    'fickfreunde.de',
    'filoimeofeli.com',
    'flirtare.com',
    'flirtcontacten.eu',
    'flirting.co.nz',
    'flirttaillaan.com',
    'flirttaillaan.fi',
    'follahora.com',
    'follando.es',
    'freundschaft.com',
    'freundschaftplus.net',
    'friends-with-benefits.com',
    'friendswithbenefits.club',
    'friendswithbenefits.net',
    'fuckbuddies.club',
    'fuckbuds.com',
    'geheimedate.nl',
    'hottedates.com',
    'ilmanehtoja.fi',
    'incontromaturi.com',
    'ingenforpliktelser.com',
    'instadates.net',
    'instaflirt.co.uk',
    'instaflirt.it',
    'instaflirt.nl',
    'itrombamici.com',
    'letteflirt.com',
    'ligar.mx',
    'ligando.com',
    'ligando.mx',
    'ligga.com',
    'maturedate.de',
    'maturedates.com',
    'maturedates.nl',
    'namoromaduro.net',
    'neuken.com',
    'no-strings-attached.com',
    'one-nightstand.com',
    'one-nightstands.com',
    'panokavereita.com',
    'panokavereita.fi',
    'pasionoculta.com',
    'pieprzyc.com',
    'plancul.club',
    'planscul.com',
    'povyrazeni.com',
    'pratelstvisvyhodami.com',
    'randeprozrale.com',
    'randkowanie.net',
    'rdvadultes.com',
    'rencontresmatures.com',
    'rendezvousamoureux.com',
    'rijpedate.com',
    'rijpedating.com',
    'rimorchiando.com',
    'sansengagement.com',
    'scopamici.com',
    'scopare.com',
    'seniorideitit.com',
    'sexbook.nl',
    'sexe.net',
    'sexomigos.com',
    'sexvenner.com',
    'shag.co.uk',
    'singles.club',
    'stoutedate.nl',
    'trombare.com',
    'vakipanot.com',
    'vannermedformaner.com',
    'vennermedfordeler.com',
    'voksendater.com',
    'voksnedater.com',
    'voksnedates.com',
    'vuxendates.com',
    'vuxendejter.com',
    'xn--flrte-wua.com',
    'xn--hotflrt-u1a.com',
    'xn--mten-5qa.com',
    'xn--reifeaffren-s8a.de',
    'xn--sexmter-t1a.com',
    'yhdenyonjuttu.com',
];

$parsed_url = parse_url($url);
$domain = $parsed_url['host'] ?? '';

$is_allowed = false;
foreach ($allowed_domains as $allowed) {
    if (strpos($domain, $allowed) !== false) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403);
    die('Error: Dominio no permitido');
}

// ── CACHÉ ──
// Las landings remotas apenas cambian, y sin caché cada iframe de la página
// bloquea un worker de PHP durante toda la descarga remota: con 30-40 landings
// un solo usuario agota el pool. Se cachea el HTML ya procesado.
$cache_ttl  = 900; // 15 minutos
$cache_dir  = __DIR__ . '/cache/proxy';
$cache_file = $cache_dir . '/' . hash('sha256', $url) . '.html';

if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
    header('X-Proxy-Cache: HIT');
    readfile($cache_file);
    exit;
}

// Obtener contenido
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$html = @file_get_contents($url, false, $context);

if ($html === false) {
    // Si el sitio remoto falla pero queda una copia caducada, servirla:
    // mejor una landing algo antigua que un iframe roto.
    if (is_file($cache_file)) {
        header('X-Proxy-Cache: STALE');
        readfile($cache_file);
        exit;
    }
    http_response_code(500);
    die('Error: No se pudo cargar la URL');
}

// Inyectar CSS y JavaScript para ocultar popups de cookies
$custom_code = '
<style id="iframe-proxy-fix">
.cookie-box {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: absolute !important;
    left: -9999px !important;
}

body {
    overflow: auto !important;
}
</style>

<script>
(function() {
    // Bloquear popups de cookies
    function eliminarCookieBox() {
        document.querySelectorAll(".cookie-box").forEach(el => el.remove());
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", eliminarCookieBox);
    } else {
        eliminarCookieBox();
    }

    setTimeout(eliminarCookieBox, 500);
    setTimeout(eliminarCookieBox, 1000);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.classList && node.classList.contains("cookie-box")) {
                    node.remove();
                }
            });
        });
    });

    observer.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true
    });

    // BLOQUEAR POPUP "¿Salir del sitio?"
    window.addEventListener("beforeunload", function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return undefined;
    }, true);

    // Eliminar listeners beforeunload existentes
    window.onbeforeunload = null;

    // Sobrescribir cualquier intento de añadir beforeunload
    const originalAddEventListener = window.addEventListener;
    window.addEventListener = function(type, listener, options) {
        if (type === "beforeunload") {
            return; // Ignorar completamente
        }
        return originalAddEventListener.call(this, type, listener, options);
    };
})();
</script>
';
// Resolver rutas relativas mediante <base> en lugar de reescribir el HTML con
// regex: el reemplazo anterior insertaba siempre comilla doble y rompía los
// atributos escritos con comilla simple (src='/x.js' -> src="https://host//x.js').
$base_tag = '';
if (!preg_match('/<base\b/i', $html)) {
    $base_tag = '<base href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
}
$inject = $base_tag . $custom_code;

// Insertar código antes de </head> o al inicio del body (solo la primera vez)
$head_pos = stripos($html, '</head>');
if ($head_pos !== false) {
    $html = substr_replace($html, $inject, $head_pos, 0);
} elseif (preg_match('/<body[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
    $html = substr_replace($html, $inject, $m[0][1] + strlen($m[0][0]), 0);
} else {
    $html = $inject . $html;
}

// Guardar en caché (escritura atómica para no servir respuestas a medias)
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0775, true);
}
if (is_dir($cache_dir)) {
    $tmp_file = $cache_file . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp_file, $html) !== false) {
        @rename($tmp_file, $cache_file);
    } else {
        @unlink($tmp_file);
    }
}

header('X-Proxy-Cache: MISS');
echo $html;
