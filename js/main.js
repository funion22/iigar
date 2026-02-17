// ============================================
// MAIN.JS - Versión dinámica compatible
// ============================================

function initEllipsisToggle(root = document){
  root.querySelectorAll('ul.listdomains').forEach(list => {
    if (list.dataset.ellipsisBound) return;

    list.addEventListener('wheel', (e) => {
      const H_FACTOR = 1.2;
      const isHorizontalGesture = Math.abs(e.deltaX) > Math.abs(e.deltaY) * H_FACTOR;
      const forceHorizontal = e.shiftKey;

      if (!(isHorizontalGesture || forceHorizontal)) {
        return;
      }

      if (!list.classList.contains('scrolling')) list.classList.add('scrolling');
      const delta = isHorizontalGesture ? e.deltaX : e.deltaY;
      list.scrollLeft += delta;
      e.preventDefault();
    }, { passive: false });

    let dragging = false, startX = 0, startLeft = 0;
    list.addEventListener('mousedown', (e) => {
      dragging = true;
      list.classList.add('scrolling');
      startX = e.clientX;
      startLeft = list.scrollLeft;
      e.preventDefault();
    });
    window.addEventListener('mousemove', (e) => {
      if (!dragging) return;
      list.scrollLeft = startLeft - (e.clientX - startX);
    });
    window.addEventListener('mouseup', () => {
      if (!dragging) return;
      dragging = false;
      if (list.scrollLeft <= 0) list.classList.remove('scrolling');
    });

    let raf;
    const onScroll = () => {
      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        if (list.scrollLeft <= 0) list.classList.remove('scrolling');
        else list.classList.add('scrolling');
      });
    };
    list.addEventListener('scroll', onScroll, { passive:true });

    if (list.scrollLeft > 0) list.classList.add('scrolling');
    list.dataset.ellipsisBound = '1';
  });
}

(function boot(){
  const start = () => initEllipsisToggle();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once:true });
  } else {
    start();
  }
})();

(function cleanXbarStuff(){
  document.querySelectorAll('.no-xbar, .hscroll').forEach(w=>{
    const ul = w.querySelector('ul.listdomains');
    if (ul) w.replaceWith(ul);
  });
  document.querySelectorAll('.xbar').forEach(b=>b.remove());
  document.querySelectorAll('ul.listdomains li.__scrollGhost, ul.listdomains .overflow-extender, ul.listdomains .ghost-width')
    .forEach(n=>n.remove());
})();

function insertWBR(str) {
  return str.replace(/([\/=&\-_?])/g, '$1<wbr>');
}

const replacers = document.querySelectorAll('.replacer');
const replaceUs = document.querySelectorAll('.replaceMe');

let currentCountry = 'all';
let currentColor = 'pink';

const DOMAIN_COLOR_MAP = {
  'pinkshows': 'pink',
  'pinkshowst3': 'pink-t3',
  'redshows': 'red',
  'orangeshows': 'orange',
  'maturepinkshows': 'mature-pink',
  'matureorangeshows': 'mature-orange',
};

const COLOR_COMPATIBILITY = {
  'pink': ['pink'],
  'pink-t3': ['pink', 'pink-t3'],
  'red': ['red'],
  'orange': ['orange'],
  'mature-pink': ['mature-pink'],
  'mature-orange': ['mature-orange'],
};

function getColorFromDomainClass(className) {
  const classes = className.split(' ');
  for (const cls of classes) {
    if (DOMAIN_COLOR_MAP[cls]) {
      return DOMAIN_COLOR_MAP[cls];
    }
  }
  return 'pink';
}

const replaceLinks = (evt) => {
  const button = evt.currentTarget;
  const { replacer, country } = button.dataset;

  const domainLink = button.querySelector('[data-attribute="showcolourcontent"]');
  const domainClass = domainLink ? domainLink.className : '';
  const color = getColorFromDomainClass(domainClass);

  currentCountry = country || 'all';
  currentColor = color;

  replaceUs.forEach((elem) => {
    const wholeString = elem.getAttribute('href');
    const toBeReplaced = elem.dataset.toBeReplaced;
    const stringWithReplacement = wholeString.replace(toBeReplaced, replacer);

    elem.dataset.toBeReplaced = replacer;
    elem.setAttribute('href', stringWithReplacement);
    elem.textContent = stringWithReplacement;
  });
}

function filterLandingsByCountryAndColor(selectedCountry, selectedColor) {
  const allLandings = document.querySelectorAll('.contentdomains li.ci[data-country]');
  const allowedColors = COLOR_COMPATIBILITY[selectedColor] || [selectedColor];

  allLandings.forEach(landing => {
    const landingCountry = landing.dataset.country || 'all';
    const landingColors = (landing.dataset.color || 'all').split(',').map(c => c.trim());

    const countryMatch = landingCountry === 'all' || landingCountry === selectedCountry;
    const colorMatch = landingColors.includes('all') ||
                      landingColors.some(color => allowedColors.includes(color));

    if (countryMatch && colorMatch) {
      landing.style.display = 'list-item';
    } else {
      landing.style.display = 'none';
    }
  });

  hideEmptySections();
}

function hideEmptySections() {
  const allHeaders = document.querySelectorAll('#landingscontent h3.mains, #landingscontent h4.mainssection');

  allHeaders.forEach(header => {
    let nextElement = header.nextElementSibling;

    while (nextElement && nextElement.tagName !== 'UL') {
      nextElement = nextElement.nextElementSibling;
    }

    if (nextElement && nextElement.tagName === 'UL') {
      const visibleLis = nextElement.querySelectorAll('li.ci[style*="list-item"]');

      if (visibleLis.length === 0) {
        header.style.display = 'none';
        nextElement.style.display = 'none';
      } else {
        header.style.display = 'block';
        nextElement.style.display = 'block';
      }
    }
  });

  markLastVisibleUl();
}

function markLastVisibleUl() {
  document.querySelectorAll('.summarylinks.is-last-visible, .summary.is-last-visible').forEach(ul => {
    ul.classList.remove('is-last-visible');
  });

  const sections = document.querySelectorAll('#landingscontent h3.mains');

  sections.forEach(section => {
    let currentElement = section.nextElementSibling;
    const ulsInSection = [];

    while (currentElement && !currentElement.classList.contains('mains')) {
      if (currentElement.tagName === 'UL' &&
          (currentElement.classList.contains('summarylinks') || currentElement.classList.contains('summary'))) {
        ulsInSection.push(currentElement);
      }
      currentElement = currentElement.nextElementSibling;
    }

    const visibleUls = ulsInSection.filter(ul => {
      return ul.style.display !== 'none' && ul.offsetParent !== null;
    });

    if (visibleUls.length > 0) {
      const lastUl = visibleUls[visibleUls.length - 1];
      lastUl.classList.add('is-last-visible');
    }
  });
}

replacers.forEach((replacer) => replacer.addEventListener('click', replaceLinks))

document.querySelectorAll("button").forEach(function(button) {
  button.addEventListener("click", function() {
    const element = document.getElementById(event.target.id + "1");
    const showinputs = document.querySelector('.subsubmenu');
    const countriesList = Array.from(document.querySelectorAll('.countries'));

    function showCountries() {
      if (element.style.display == "block") {
        element.style.display = "none";
        button.classList.remove('buttonactive');
      } else {
        element.style.display = "block";
        document.getElementById("content-domains").style.display = "block";
        button.classList.add('buttonactive');
      }
      hiddenLandings();
      hiddenInputs();
    }

    showCountries();

    function hiddenLandings() {
      let check = countriesList.every((count) => count.style.display == "none");
      if (check === true) {
        document.getElementById("content-domains").style.display = "none";
        [].forEach.call(document.querySelectorAll('.contentdomains'), function (el) {
          el.style.display = 'none';
        });
      }
    };

    function hiddenInputs() {
      let check = countriesList.some((count) => count.style.display == "block");
      if ((check === true) && (showinputs.style.display == 'block')) {
        showinputs.style.display = 'block';
      } else {
        showinputs.style.display = 'none';
      }
    }

  })
})

document.querySelectorAll('[data-attribute*="showcolourcontent"]').forEach(function(href) {
  href.addEventListener("click", function() {
      document.getElementById("content-domains").style.display = "block";
  })
})

// ══════════════════════════════════════════════════════════════
// SISTEMA DE CAMPAÑAS DINÁMICO
// Lee la configuración de window.CAMPAIGN_CONFIG (inyectado por PHP)
// ══════════════════════════════════════════════════════════════

const selectone = document.querySelector(".selecttraffic");
const optionSelect = document.querySelectorAll('[data-attribute*="options"]');
const inputOption = document.querySelectorAll('[data-attribute*="inputOption"]');
const selectedOption = document.querySelectorAll('[data-attribute*="selectedOption"]');
const selectedIframe = document.querySelectorAll('ul.summary > li');
const showinputs = document.querySelector('.subsubmenu');
const slink1 = Array.from(document.querySelectorAll('.sTxturl1'));
const iframeCapture = document.querySelectorAll('.iframecapture');

// ── Construir mapa de campañas desde la config inyectada por PHP ──
// window.CAMPAIGN_CONFIG = [{ code, urlParams, index }, ...]
const campaignConfig = window.CAMPAIGN_CONFIG || [];

// Mapa: inputName ("s1","s2",...) → { code, urlParams, links, resetSelector }
const campaignsByInput = {};
// Mapa: code → config entry (para lookup rápido)
const campaignsByCode = {};

campaignConfig.forEach(ct => {
  const inputName = 's' + ct.index;               // s1, s2, s3, ...
  const linkClass = 'sTxt' + ct.code;              // sTxtcpm, sTxtcpl, ...
  const altClass  = 'sTxt' + ct.code + 'Alt';      // sTxtcpmAlt (para ? vs &)
  const resetSelector = '.s' + ct.index + 'reset' + ct.index;  // .s1reset1, .s2reset2, ...

  const entry = {
    code: ct.code,
    urlParams: ct.urlParams || 's1={INPUT}',
    linkClass: linkClass,
    altClass: altClass,
    resetSelector: resetSelector,
    links: document.querySelectorAll('.' + linkClass),
  };

  campaignsByInput[inputName] = entry;
  campaignsByCode[ct.code] = entry;
});

// Selector combinado de TODOS los enlaces de tráfico (para show/hide)
const trafficSelector = campaignConfig.map(ct => '.sTxt' + ct.code).join(', ');
const allTrafficLinks = trafficSelector
  ? document.querySelectorAll(trafficSelector)
  : [];

// ── Select de tipo de tráfico ──
selectone.addEventListener("input", function (event) {
  // Mostrar URLs base, ocultar tráfico
  slink1.forEach(el => el.style.display = 'block');
  allTrafficLinks.forEach(el => el.style.display = 'none');

  // Limpiar solo los inputs de campaña (no TODOS los inputs de la página)
  document.querySelectorAll('input[data-attribute="inputOption"]').forEach(input => {
    input.value = "";
  });

  // Mostrar el option correspondiente
  let optionSelectTarget = document.getElementById(event.target.value + "option");
  optionSelect.forEach(element => element.style.display = "none");
  if (optionSelectTarget) {
    optionSelectTarget.style.display = "block";
  }
});

// ── ShowContents (click en dominio) ──
const showContents = document.querySelectorAll(".showcontent");
for(var i = 0; i < showContents.length; i++) {
  showContents[i].addEventListener("click", function() {

    [].forEach.call(document.querySelectorAll('.contentdomains'), function (el) {
      el.style.display = 'none';
      showinputs.style.display = 'none';
    });

    var containerIdToFind = event.target.className + "content";
    var showcontentelement = document.getElementById(containerIdToFind);

    if (!showcontentelement) {
      showcontentelement = document.getElementById('landingscontent');
    }

    if (showcontentelement) {
      showcontentelement.style.display = "block";
    } else {
      return;
    }

    filterLandingsByCountryAndColor(currentCountry, currentColor);

    if (showcontentelement.classList.contains('showcontentbrandless')) {
      showinputs.style.display = 'none';
    } else {
      showinputs.style.display = 'block';
    }

    // Limpiar solo inputs de campaña
    document.querySelectorAll('input[data-attribute="inputOption"]').forEach(input => {
      input.value = "";
    });

    slink1.forEach(el => el.style.display = 'block');
    allTrafficLinks.forEach(el => el.style.display = 'none');

    selectone.value = "";
    optionSelect.forEach(function(el) {
      el.style.display = 'none';
    });

    // Actualizar iframes
    const hrefsIframes = [];
    for (const anchor of slink1) {
      hrefsIframes.push(anchor.getAttribute("href"));
    }
    for(var i = 0; i < iframeCapture.length; i++) {
      let hrefLinkIframe = hrefsIframes[i];
      let iframeCapturei = iframeCapture[i];

      const proxyUrl = 'iframe-proxy.php?url=' + encodeURIComponent(hrefLinkIframe);
      iframeCapturei.setAttribute("src", proxyUrl);
    }
    const landingsAnchor = document.getElementById('landings');
    if (landingsAnchor) {
      landingsAnchor.scrollIntoView({ behavior: 'smooth' });
    }
  })
}

// ── Toggle de iframes ──
const checkbox = document.querySelector(".toggle-state");
if (checkbox) {
  checkbox.addEventListener("change", () => {
    if (checkbox.checked) {
      document.querySelector(".label-text-1").style.display = "none";
      document.querySelector(".label-text-2").style.display = "block";
      iframeCapture.forEach(el => el.classList.add("removeIframe"));
      selectedIframe.forEach(el => el.classList.add("checkedbutton"));
    } else {
      document.querySelector(".label-text-1").style.display = "block";
      document.querySelector(".label-text-2").style.display = "none";
      iframeCapture.forEach(el => el.classList.remove("removeIframe"));
      selectedIframe.forEach(el => el.classList.remove("checkedbutton"));
    }
  });
}

// ══════════════════════════════════════════════════════════════
// LISTENER DINÁMICO PARA INPUTS DE CAMPAÑA
// ══════════════════════════════════════════════════════════════

inputOption.forEach(function(input) {
  input.addEventListener('input', function() {
    const inputName = input.getAttribute('name'); // s1, s2, s3, ...
    const campaign = campaignsByInput[inputName];

    if (!campaign) {
      console.warn('No campaign config found for input name:', inputName);
      return;
    }

    const sTxtn = input.value.trim();
    if (!sTxtn) return;

    // Ocultar todos los enlaces de opción
    selectedOption.forEach(el => el.style.display = "none");

    // Obtener URLs base
    const hrefs = slink1.map(anchor => anchor.getAttribute("href"));

    // Ocultar URLs base
    slink1.forEach(el => el.style.display = 'none');

    // Construir los parámetros reemplazando {INPUT} con el valor del usuario
    const urlParams = campaign.urlParams.replace(/\{INPUT\}/g, sTxtn);

    // Obtener elementos reset
    const resetElements = document.querySelectorAll(campaign.resetSelector);

    // Actualizar cada enlace
    campaign.links.forEach((link, i) => {
      if (i >= hrefs.length) return;

      const hrefLink = hrefs[i];

      // Determinar separador: & si ya tiene ? o si es clase Alt, sino ?
      const separator = (hrefLink.includes('?') || link.classList.contains(campaign.altClass))
        ? '&'
        : '?';

      const finalUrl = hrefLink + separator + urlParams;

      // Actualizar enlace
      link.style.display = "block";
      link.target = "_blank";
      link.setAttribute("href", finalUrl);

      // Actualizar texto visible
      const resetEl = resetElements[i];
      if (resetEl) {
        resetEl.innerHTML = insertWBR(finalUrl);
      }
    });
  });
});
