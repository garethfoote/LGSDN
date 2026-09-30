import { reference } from './reference.mjs';

export const TAU = Math.PI * 2;
// Speed changes time only: it does not add points, calculations or draw calls.
export const defaults = Object.freeze({ speed: 3, amount: 45, detail: 2, cohesion: 85, fixedOuter: false });

// Deterministic improved gradient noise. The permutation is shuffled once, never per frame.
const permutation = new Uint8Array(512);
const shuffled = Array.from({ length: 256 }, (_, i) => i);
let seed = 7319;
for (let i = 255; i > 0; i--) {
  seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0;
  const j = seed % (i + 1);
  [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
}
for (let i = 0; i < 512; i++) permutation[i] = shuffled[i & 255];
const fade = t => t*t*t*(t*(t*6-15)+10);
const lerp = (a,b,t) => a+(b-a)*t;
function gradient(hash,x,y,z) {
  const h = hash & 15, u = h < 8 ? x : y, v = h < 4 ? y : (h === 12 || h === 14 ? x : z);
  return ((h & 1) ? -u : u) + ((h & 2) ? -v : v);
}
export function noise(x,y,z) {
  const X = Math.floor(x)&255, Y = Math.floor(y)&255, Z = Math.floor(z)&255;
  x -= Math.floor(x); y -= Math.floor(y); z -= Math.floor(z);
  const u = fade(x), v = fade(y), w = fade(z), p = permutation;
  const A=p[X]+Y, AA=p[A]+Z, AB=p[A+1]+Z, B=p[X+1]+Y, BA=p[B]+Z, BB=p[B+1]+Z;
  return lerp(
    lerp(lerp(gradient(p[AA],x,y,z),gradient(p[BA],x-1,y,z),u),lerp(gradient(p[AB],x,y-1,z),gradient(p[BB],x-1,y-1,z),u),v),
    lerp(lerp(gradient(p[AA+1],x,y,z-1),gradient(p[BA+1],x-1,y,z-1),u),lerp(gradient(p[AB+1],x,y-1,z-1),gradient(p[BB+1],x-1,y-1,z-1),u),v),w);
}
const boundedNoise = (x,y,z) => Math.tanh(noise(x,y,z)*2);

export function createGeometry(count = 256) {
  if (![128,256,512].includes(count)) throw new Error('Unsupported curve detail');
  const cos = new Float64Array(count), sin = new Float64Array(count);
  const base = reference.radii.map(() => new Float64Array(count));
  const radii = base.map(() => new Float64Array(count));
  const points = base.map(() => new Float64Array(count*2));
  const origins = Array.from({length:7}, () => new Float64Array(count));
  let allowance = Infinity;
  for (let j=0; j<count; j++) {
    const angle = TAU*j/count;
    cos[j] = Math.cos(angle); sin[j] = Math.sin(angle);
    for (let ring=0; ring<6; ring++) base[ring][j] = reference.radii[ring][j*512/count];
    const ox = reference.centre[0]-112.5, oy = reference.centre[1]-112.5;
    const dot = ox*cos[j]+oy*sin[j];
    // Include the stroke plus an antialiasing margin inside the 225-unit disc.
    const safeRadius = -dot+Math.sqrt(dot*dot+110*110-ox*ox-oy*oy);
    allowance = Math.min(allowance, safeRadius-base[5][j], base[0][j]-3);
    for (let ring=1; ring<6; ring++) allowance = Math.min(allowance,(base[ring][j]-base[ring-1][j])/4);
  }
  if (!(allowance > 0)) throw new Error('Reference contours exceed the safe boundary');
  return { count, cos, sin, base, radii, points, origins, allowance:allowance*0.95, cachedDetail:null, widths:reference.widths };
}

// Time is an accumulated phase, independent of frame rate; evaluate from the original
// artwork every time. No integration drift, terrain rebuild, or random regeneration.
export function updateGeometry(geometry, mode, time, settings) {
  const {count,cos,sin,base,radii,points,origins,allowance} = geometry;
  const detail = Math.max(1,Math.min(4,settings.detail));
  const amount = Math.max(0,Math.min(100,settings.amount))/100*allowance;
  const cohesion = Math.max(0,Math.min(100,settings.cohesion))/100;
  if (mode === 'perlin' && geometry.cachedDetail !== detail) {
    for (let ring=0; ring<7; ring++) for (let j=0; j<count; j++) {
      origins[ring][j] = boundedNoise(17+cos[j]*detail+ring*3.7,31+sin[j]*detail+ring*2.1,7);
    }
    geometry.cachedDetail = detail;
  }
  const a = Math.sin(time), b = Math.sin(time*0.71), c = Math.sin(time*0.43);
  for (let j=0; j<count; j++) {
    const angle = TAU*j/count;
    const common = mode === 'perlin'
      ? (boundedNoise(17+cos[j]*detail,31+sin[j]*detail,7+time*0.3)-origins[0][j])/2
      : (Math.sin(angle*detail+0.4)*a*0.55 + Math.cos(angle*(detail+1)-0.7)*b*0.3 + Math.sin(angle+1.3)*c*0.15);
    for (let ring=0; ring<6; ring++) {
      const k = ring+1;
      const individual = mode === 'perlin'
        ? (boundedNoise(17+cos[j]*detail+k*3.7,31+sin[j]*detail+k*2.1,7+time*0.3)-origins[k][j])/2
        : Math.sin(angle*(detail+1)+ring*0.63)*Math.sin(time*(0.7+ring*0.06));
      const movement = (settings.fixedOuter && ring === 5) ? 0 : amount*(cohesion*common+(1-cohesion)*individual);
      const radius = base[ring][j]+movement;
      radii[ring][j] = radius;
      points[ring][j*2] = reference.centre[0]+cos[j]*radius;
      points[ring][j*2+1] = reference.centre[1]+sin[j]*radius;
    }
  }
  // All rings use the same rays and straight segments between them. Positive ordered
  // radii imply closed, simple, nested polygons even BETWEEN sample points. Dense
  // sampling avoids spline overshoot while appearing smooth at the intended sizes.
  return geometry;
}
