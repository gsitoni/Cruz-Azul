const regionData = {
    norte: {
        name: 'Norte',
        level: 'Alto',
        population: '24,2 milhões',
        need: 'Desafios elevados em infraestrutura, saneamento e educação.',
        description: 'Região mais vulnerável do país segundo indicadores do IBGE. Alta concentração de pobreza, baixas taxas de acesso a serviços básicos e grande presença de comunidades rurais e indígenas.',
        ongs: [
            'SOS Amazônia',
            'Instituto Socioambiental (ISA)',
            'Instituto Mamirauá'
        ]
    },
    nordeste: {
        name: 'Nordeste',
        level: 'Muito alto',
        population: '57,1 milhões',
        need: 'Alta desigualdade e dependência de programas sociais. Regiões internas com baixa renda e acesso limitado a saúde e saneamento.',
        description: 'A região Nordeste apresenta indicadores de carência social entre os mais altos do Brasil, com necessidade de investimento em educação, água potável e inclusão produtiva.',
        ongs: [
            'Ação da Cidadania',
            'CUFA Nordeste',
            'Projeto Uerê'
        ]
    },
    'centro-oeste': {
        name: 'Centro-Oeste',
        level: 'Alto',
        population: '16,7 milhões',
        need: 'Pressão sobre terras indígenas, comunidades rurais e infraestrutura de transporte. Necessidade de apoio social e ambiental.',
        description: 'Mesmo com importantes polos urbanos, o Centro-Oeste ainda possui bolsões de vulnerabilidade rural e comunidades tradicionais expostas a desafios socioambientais.',
        ongs: [
            'SOS Pantanal',
            'Instituto Socioambiental (ISA)',
            'Agroecologia na Prática'
        ]
    },
    sudeste: {
        name: 'Sudeste',
        level: 'Moderado',
        population: '88,9 milhões',
        need: 'Desigualdade urbana e periferias com alta demanda por moradia, educação e geração de renda.',
        description: 'Região mais populosa com pontos críticos em grandes centros urbanos. A vulnerabilidade aparece principalmente em periferias e áreas de transporte precário.',
        ongs: [
            'Fundação Abrinq',
            'Ação da Cidadania',
            'Instituto Alliança'
        ]
    },
    sul: {
        name: 'Sul',
        level: 'Moderado',
        population: '30,0 milhões',
        need: 'Menor índice de carência, mas com desafios em comunidades rurais e em bairros periféricos.',
        description: 'O Sul apresenta menores índices de vulnerabilidade proporcional, mas ainda abriga regiões que demandam apoio para inclusão social e serviços públicos.',
        ongs: [
            'SOS Mata Atlântica Sul',
            'Cantinho do Céu',
            'Instituto Lixo Zero'
        ]
    }
};

const regionButtons = document.querySelectorAll('.region-selector button');
const toolTip = document.getElementById('mapTooltip');
const svgContainer = document.getElementById('svgContainer');
const regionName = document.getElementById('regionName');
const regionLevel = document.getElementById('regionLevel');
const regionDescription = document.getElementById('regionDescription');
const regionPopulation = document.getElementById('regionPopulation');
const regionNeed = document.getElementById('regionNeed');
const regionOngs = document.getElementById('regionOngs');

const stateToRegion = {
    brac: 'norte',
    bram: 'norte',
    brap: 'norte',
    brpa: 'norte',
    brro: 'norte',
    brrr: 'norte',
    brto: 'norte',
    bral: 'nordeste',
    brba: 'nordeste',
    brce: 'nordeste',
    brma: 'nordeste',
    brpb: 'nordeste',
    brpe: 'nordeste',
    brpi: 'nordeste',
    brrn: 'nordeste',
    brse: 'nordeste',
    brdf: 'centro-oeste',
    brgo: 'centro-oeste',
    brmt: 'centro-oeste',
    brms: 'centro-oeste',
    bres: 'sudeste',
    brmg: 'sudeste',
    brrj: 'sudeste',
    brsp: 'sudeste',
    brpr: 'sul',
    brrs: 'sul',
    brsc: 'sul'
};

let mapPaths = [];

function updateTooltipPosition(event) {
    const x = event.clientX + 18;
    const y = event.clientY + 18;
    toolTip.style.left = `${x}px`;
    toolTip.style.top = `${y}px`;
}

function selectRegion(regionKey) {
    const region = regionData[regionKey];
    if (!region) return;

    mapPaths.forEach((path) => {
        const isSelected = path.dataset.region === regionKey;
        path.classList.toggle('active', isSelected);
        path.classList.toggle('dimmed', !isSelected && Boolean(path.dataset.region));
    });

    regionButtons.forEach((button) => {
        button.classList.toggle('active', button.dataset.region === regionKey);
    });

    regionName.textContent = region.name;
    regionLevel.textContent = `IBGE: ${region.level}`;
    regionDescription.textContent = region.description;
    regionPopulation.textContent = region.population;
    regionNeed.textContent = region.need;

    regionOngs.innerHTML = '';
    region.ongs.forEach((ong) => {
        const item = document.createElement('li');
        item.textContent = ong;
        regionOngs.appendChild(item);
    });
}

function attachMapEvents() {
    mapPaths.forEach((path) => {
        path.addEventListener('click', () => {
            const regionKey = path.dataset.region;
            if (regionKey) selectRegion(regionKey);
        });

        path.addEventListener('pointerenter', (event) => {
            const stateName = path.getAttribute('name') || path.id;
            const regionKey = path.dataset.region;
            const regionName = regionKey ? regionData[regionKey].name : 'Região desconhecida';
            toolTip.textContent = `${stateName} — ${regionName}`;
            toolTip.style.opacity = '0.95';
            updateTooltipPosition(event);
        });

        path.addEventListener('pointermove', updateTooltipPosition);

        path.addEventListener('pointerleave', () => {
            toolTip.textContent = 'Clique em uma região';
            toolTip.style.opacity = '0.9';
        });
    });
}

function insertSvgMap(svgText) {
    const cleaned = svgText
        .replace(/<\?xml[^>]*>/g, '')
        .replace(/<!DOCTYPE[^>]*>/g, '')
        .trim();

    svgContainer.innerHTML = cleaned;
    const svg = svgContainer.querySelector('svg');
    if (!svg) {
        svgContainer.innerHTML = '<p class="svg-error">Não foi possível carregar o mapa.</p>';
        return;
    }

    svg.classList.add('brazil-map');
    mapPaths = Array.from(svg.querySelectorAll('path[id^="BR"]'));
    mapPaths.forEach((path) => {
        const stateId = path.id.toLowerCase();
        const regionKey = stateToRegion[stateId];
        if (regionKey) {
            path.dataset.region = regionKey;
            path.classList.add('region-state');
        }
        path.style.cursor = 'pointer';
        path.style.transition = 'fill 0.2s ease, opacity 0.2s ease, transform 0.2s ease';
    });

    attachMapEvents();
    selectRegion('nordeste');
}

function loadSvgMap() {
    fetch('../assets/br.svg')
        .then((response) => {
            if (!response.ok) {
                throw new Error('Falha ao carregar SVG');
            }
            return response.text();
        })
        .then(insertSvgMap)
        .catch(() => {
            svgContainer.innerHTML = '<p class="svg-error">Não foi possível carregar o mapa. Atualize a página.</p>';
        });
}

function initializePage() {
    regionButtons.forEach((button) => {
        button.addEventListener('click', () => selectRegion(button.dataset.region));
    });
    loadSvgMap();
}

document.addEventListener('DOMContentLoaded', initializePage);
