import { createGeometry, updateGeometry, defaults } from './contour-math.mjs';

const $ = id => document.getElementById(id);
const svg = $('svg'), canvas = $('canvas'), disc = $('disc');
const ctx = canvas.getContext('2d');
const namespace = 'http://www.w3.org/2000/svg';
const modeInfo = {
  waves: ['Gentle waves', 'A few broad waves gently bend the existing contours.'],
  perlin: ['Perlin flow', 'Smooth noise moves around each closed contour, evolving slowly without an obvious motion loop.'],
};
const storageKey = 'lgsdn-contour-experiments-v2';
const experiments = { waves: {...defaults}, perlin: {...defaults, amount:75} };
const phases = { waves:0, perlin:0 };
try {
  const saved = JSON.parse(localStorage.getItem(storageKey));
  for (const mode of Object.keys(experiments)) {
    const values = saved?.[mode];
    if (!values || typeof values !== 'object') continue;
    for (const [key,min,max] of [['speed',0.1,12],['amount',0,100],['detail',1,4],['cohesion',0,100]]) {
      if (typeof values[key] === 'number' && Number.isFinite(values[key])) experiments[mode][key] = Math.max(min,Math.min(max,values[key]));
    }
    experiments[mode].detail = Math.round(experiments[mode].detail);
    if (typeof values.fixedOuter === 'boolean') experiments[mode].fixedOuter = values.fixedOuter;
  }
} catch { /* Private browsing or unavailable storage: keep in-memory settings. */ }
function save() { try { localStorage.setItem(storageKey,JSON.stringify(experiments)); } catch {} }

let mode = 'waves', renderer = 'svg', geometry = createGeometry();
const query = new URLSearchParams(window.location.search);
if (query.get('study') === 'gentle-waves-canvas') {
  renderer = 'canvas';
  Object.assign(experiments.waves, { speed: 6, amount: 68, detail: 2, cohesion: 82, fixedOuter: false });
}
let running = !matchMedia('(prefers-reduced-motion: reduce)').matches;
let onScreen = true, reference = false, raf = 0, previousTime = null;
let canvasScale = 1, density = 1, cssSize = 0;
let reportStart = 0, sampleCount = 0;
const intervals = new Float64Array(240), costs = new Float64Array(240);
const paths = Array.from({length:6}, (_,i) => {
  const path = document.createElementNS(namespace,'path');
  path.setAttribute('fill','none'); path.setAttribute('stroke','#fff');
  path.setAttribute('stroke-width',geometry.widths[i]);
  path.setAttribute('stroke-linejoin','round');
  svg.append(path);
  return path;
});
const dots = [[70.5,122.5,4.5],[122.5,38.5,3.5],[122.5,192.5,3.5]];
const dotGroup = document.createElementNS(namespace,'g');
dotGroup.setAttribute('fill','#fff');
for (const [x,y,r] of dots) {
  const dot = document.createElementNS(namespace,'circle');
  dot.setAttribute('cx',x); dot.setAttribute('cy',y); dot.setAttribute('r',r);
  dotGroup.append(dot);
}
svg.append(dotGroup);
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
$('motion-note').hidden = !reducedMotion.matches;

function setOutputs() {
  const values = experiments[mode];
  $('experiment').value = mode;
  for (const input of document.querySelectorAll('[name="renderer"]')) input.checked = input.value === renderer;
  for (const key of ['speed','amount','detail','cohesion']) {
    $(key).value = values[key];
    $(`${key}-value`).textContent = key === 'speed' ? `${values[key].toFixed(2)}×` : `${values[key]}${key === 'detail' ? '' : '%'}`;
  }
  $('fixedOuter').checked = values.fixedOuter;
  $('scene-title').textContent = modeInfo[mode][0];
  $('description').textContent = modeInfo[mode][1];
}
function clearStats() {
  sampleCount = 0; reportStart = 0;
  for (const id of ['fps','cadence','cost']) $(id).textContent = '—';
}
function updateSurface() {
  const size = Math.round(cssSize);
  $('surface').textContent = reference ? 'Original SVG' : renderer === 'svg' ? `SVG · ${size}px` : `${canvas.width} × ${canvas.height} · ${density.toFixed(2)}×`;
}
function resize() {
  cssSize = disc.getBoundingClientRect().width;
  const screenDensity = window.devicePixelRatio || 1;
  density = $('density').value === 'native' ? screenDensity : Math.min(screenDensity, Number($('density').value));
  const pixels = Math.max(1,Math.round(cssSize*density));
  // Allocate the backing store only when size or density changes, not per frame.
  if (renderer === 'canvas' && (canvas.width !== pixels || canvas.height !== pixels)) {
    canvas.width = pixels; canvas.height = pixels;
  }
  canvasScale = pixels/225;
  updateSurface();
  draw();
}
function draw() {
  svg.hidden = reference || renderer !== 'svg';
  canvas.hidden = reference || renderer !== 'canvas';
  $('original').hidden = !reference;
  if (reference) return;
  updateGeometry(geometry,mode,phases[mode],experiments[mode]);
  if (renderer === 'svg') {
    for (let ring=0; ring<6; ring++) {
      const points = geometry.points[ring];
      let d = `M${points[0].toFixed(4)},${points[1].toFixed(4)}`;
      for (let j=2; j<points.length; j+=2) d += `L${points[j].toFixed(4)},${points[j+1].toFixed(4)}`;
      paths[ring].setAttribute('d',`${d}Z`);
    }
    dotGroup.style.display = $('dots').checked ? '' : 'none';
  } else {
    ctx.setTransform(canvasScale,0,0,canvasScale,0,0);
    ctx.clearRect(0,0,225,225);
    ctx.strokeStyle = '#fff'; ctx.lineJoin = 'round';
    for (let ring=0; ring<6; ring++) {
      const points = geometry.points[ring];
      ctx.beginPath(); ctx.moveTo(points[0],points[1]);
      for (let j=2; j<points.length; j+=2) ctx.lineTo(points[j],points[j+1]);
      ctx.closePath(); ctx.lineWidth = geometry.widths[ring]; ctx.stroke();
    }
    if ($('dots').checked) {
      ctx.fillStyle = '#fff'; ctx.beginPath();
      for (const [x,y,r] of dots) { ctx.moveTo(x+r,y); ctx.arc(x,y,r,0,Math.PI*2); }
      ctx.fill();
    }
  }
}
function shouldAnimate() { return running && onScreen && !document.hidden && !reference; }
function updateStatus() {
  $('play').textContent = running ? 'Pause' : 'Play';
  $('play').disabled = reference;
  $('restart').disabled = reference;
  $('motion-controls').disabled = reference;
  $('play-status').textContent = reference ? 'Original · motion held' : !running ? 'Paused' : !onScreen || document.hidden ? 'Offscreen · motion held' : 'Playing';
  updateSurface();
}
function reconcile() {
  if (raf) cancelAnimationFrame(raf);
  raf = 0; previousTime = null;
  clearStats(); updateStatus();
  if (shouldAnimate()) raf = requestAnimationFrame(frame);
}
function frame(timestamp) {
  raf = 0;
  if (!shouldAnimate()) return;
  const elapsed = previousTime === null ? 0 : timestamp-previousTime;
  previousTime = timestamp;
  // Resume without jumping across time spent asleep or after a long main-thread stall.
  phases[mode] += Math.min(elapsed,50)/1000*experiments[mode].speed*0.18;
  const started = performance.now();
  draw();
  const cost = performance.now()-started;
  if (elapsed > 0) {
    intervals[sampleCount%240] = elapsed; costs[sampleCount%240] = cost; sampleCount++;
  }
  if (!reportStart) reportStart = timestamp;
  if (timestamp-reportStart >= 1200 && sampleCount) {
    const n = Math.min(sampleCount,240);
    const sortedIntervals = Array.from(intervals.subarray(0,n)).sort((a,b)=>a-b);
    const sortedCosts = Array.from(costs.subarray(0,n)).sort((a,b)=>a-b);
    const total = sortedIntervals.reduce((sum,value)=>sum+value,0), p95 = Math.ceil(n*0.95)-1;
    $('fps').textContent = `${Math.round(1000*n/total)} fps`;
    $('cadence').textContent = `${sortedIntervals[p95].toFixed(1)} ms`;
    $('cost').textContent = `${sortedCosts[p95].toFixed(2)} ms`;
    sampleCount = 0; reportStart = timestamp;
  }
  raf = requestAnimationFrame(frame);
}

$('experiment').addEventListener('change', () => { mode = $('experiment').value; setOutputs(); draw(); reconcile(); });
for (const input of document.querySelectorAll('[name="renderer"]')) input.addEventListener('change', () => {
  renderer = input.value;
  $('density').disabled = renderer !== 'canvas';
  resize(); reconcile();
});
for (const key of ['speed','amount','detail','cohesion','fixedOuter']) {
  $(key).addEventListener('input', () => {
    experiments[mode][key] = key === 'fixedOuter' ? $(key).checked : Number($(key).value);
    setOutputs(); draw(); clearStats();
  });
  $(key).addEventListener('change',save);
}
$('play').addEventListener('click', () => { running = !running; reconcile(); });
$('restart').addEventListener('click', () => { phases[mode] = 0; draw(); reconcile(); });
$('reset').addEventListener('click', () => {
  experiments[mode] = {...defaults,...(mode === 'perlin' ? {amount:75} : {})};
  phases[mode] = 0; setOutputs(); save(); draw(); reconcile();
});
$('reference').addEventListener('change', () => { reference = $('reference').checked; draw(); reconcile(); });
$('quality').addEventListener('change', () => { geometry = createGeometry(Number($('quality').value)); draw(); clearStats(); });
$('size').addEventListener('input', () => {
  disc.style.setProperty('--disc-size',`${$('size').value}px`);
  $('size-value').textContent = `${$('size').value}px`;
});
$('density').addEventListener('change', () => { resize(); clearStats(); });
$('colour').addEventListener('change', () => { disc.style.backgroundColor = `var(--lab-${$('colour').value})`; });
$('dots').addEventListener('change',draw);
document.addEventListener('visibilitychange',reconcile);
window.addEventListener('resize',resize);
new ResizeObserver(resize).observe(disc);
new ResizeObserver(entries => {
  document.documentElement.style.setProperty('--lab-preview-height',`${entries[0].target.getBoundingClientRect().height}px`);
}).observe(document.querySelector('.contour-lab__preview'));
new IntersectionObserver(entries => {
  const visible = entries[0].isIntersecting;
  if (visible !== onScreen) { onScreen = visible; reconcile(); }
}, {threshold:0}).observe(disc);
reducedMotion.addEventListener('change', () => {
  $('motion-note').hidden = !reducedMotion.matches;
  if (reducedMotion.matches) { running = false; reconcile(); }
});
if (!ctx) {
  document.querySelector('[name="renderer"][value="canvas"]').disabled = true;
}
setOutputs(); $('density').disabled = renderer !== 'canvas'; resize(); $('restart').disabled = false; reconcile();
