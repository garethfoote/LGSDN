import { reference } from './reference.mjs';

const svg = document.getElementById('dots');
const artwork = document.getElementById('artwork');
const play = document.getElementById('play');
const restart = document.getElementById('restart');
const speed = document.getElementById('speed');
const status = document.getElementById('status');
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const namespace = 'http://www.w3.org/2000/svg';
const tau = Math.PI * 2;

// Ring indices run from innermost (0) to outermost (5): fourth from outside is 2.
// Keep one dot on each requested contour, all at the same size.
const seeds = [
	{ ring: 5, radius: 3.5, direction: 1, rate: 1.03 },
	{ ring: 4, radius: 3.5, direction: -1, rate: 0.98 },
	{ ring: 2, radius: 3.5, direction: 1, rate: 1 },
];

function createTrack(seed) {
	const radii = reference.radii[seed.ring];
	const points = Array.from(radii, (radius, index) => {
		const angle = tau * index / radii.length;
		return [reference.centre[0] + Math.cos(angle) * radius, reference.centre[1] + Math.sin(angle) * radius];
	});
	const lengths = new Float64Array(points.length + 1);
	for (let index = 0; index < points.length; index++) {
		const next = points[(index + 1) % points.length];
		lengths[index + 1] = lengths[index] + Math.hypot(next[0] - points[index][0], next[1] - points[index][1]);
	}
	const total = lengths.at(-1);
	return { ...seed, points, lengths, total, phase: 0 };
}

const tracks = seeds.map((seed) => {
	const circle = document.createElementNS(namespace, 'circle');
	circle.setAttribute('r', seed.radius);
	circle.setAttribute('cx', reference.centre[0]);
	circle.setAttribute('cy', reference.centre[1]);
	svg.append(circle);
	return { ...createTrack(seed), circle };
});

let running = !reducedMotion.matches;
let phaseTime = 0;
let previousTime = null;
let animationFrame = 0;
let inView = true;

function pointAt(track, distance) {
	const wrapped = ((distance % track.total) + track.total) % track.total;
	let low = 0;
	let high = track.points.length;
	while (low + 1 < high) {
		const middle = (low + high) >> 1;
		if (track.lengths[middle] <= wrapped) low = middle;
		else high = middle;
	}
	const start = track.points[low];
	const end = track.points[(low + 1) % track.points.length];
	const segmentLength = track.lengths[low + 1] - track.lengths[low];
	const amount = segmentLength ? (wrapped - track.lengths[low]) / segmentLength : 0;
	return [
		start[0] + (end[0] - start[0]) * amount,
		start[1] + (end[1] - start[1]) * amount,
	];
}

function randomizeTracks() {
	phaseTime = 0;
	const sectorOffset = Math.floor(Math.random() * 6);
	tracks.forEach((track, index) => {
		// Use alternating 60° sectors. Keeping each start inside the middle half
		// of its sector leaves at least 90° between dots while varying every run.
		const sector = (sectorOffset + index * 2) % 6;
		const angle = (sector + 0.25 + Math.random() * 0.5) * (tau / 6);
		const sample = angle / tau * track.points.length;
		const start = Math.floor(sample);
		const fraction = sample - start;
		const segment = track.lengths[start + 1] - track.lengths[start];
		track.phase = track.lengths[start] + segment * fraction;
	});
	render();
}

function render() {
	for (const track of tracks) {
		// One lap takes about five minutes at the default, intentionally subtle pace.
		const distance = track.phase + phaseTime * (track.total / 300) * (Number(speed.value) / 0.35) * track.rate * track.direction;
		const [x, y] = pointAt(track, distance);
		track.circle.setAttribute('cx', x.toFixed(3));
		track.circle.setAttribute('cy', y.toFixed(3));
	}
}

function updateStatus() {
	play.textContent = running ? 'Pause' : 'Play';
	play.setAttribute('aria-pressed', String(running));
	status.textContent = running ? (inView && !document.hidden ? 'Playing' : 'Paused offscreen') : 'Paused';
}

function stopLoop() {
	if (animationFrame) cancelAnimationFrame(animationFrame);
	animationFrame = 0;
	previousTime = null;
	updateStatus();
}

function frame(now) {
	animationFrame = 0;
	if (!running || !inView || document.hidden) return;
	const elapsed = previousTime === null ? 0 : Math.min(now - previousTime, 50);
	previousTime = now;
	phaseTime += elapsed / 1000;
	render();
	animationFrame = requestAnimationFrame(frame);
}

function reconcile() {
	stopLoop();
	if (running && inView && !document.hidden) animationFrame = requestAnimationFrame(frame);
}

function updateSpeed() {
	const value = Number(speed.value);
	document.getElementById('speed-value').textContent = value < 0.6 ? 'Very slow' : value < 1.1 ? 'Slow' : 'Gentle';
}

async function loadContourArtwork() {
	try {
		const response = await fetch('../../assets/images/hero-graphic-mark.svg');
		if (!response.ok) throw new Error('Homepage artwork could not be loaded');
		const source = new DOMParser().parseFromString(await response.text(), 'image/svg+xml').documentElement;
		// Keep the homepage artwork's exact contour paths and remove only its three
		// baked-in circles. This lets the SVG strokes continue cleanly under motion.
		source.querySelectorAll('circle').forEach((circle) => circle.remove());
		source.classList.add('moving-dots__source');
		source.setAttribute('aria-hidden', 'true');
		artwork.querySelector('img').replaceWith(source);
		artwork.classList.add('is-ready');
	} catch {
		// Keep the original SVG visible if loading fails, without drawing duplicate dots.
		svg.hidden = true;
		running = false;
		status.textContent = 'Artwork could not be loaded';
		artwork.classList.add('is-ready');
		updateStatus();
	}
}

play.addEventListener('click', () => { running = !running; reconcile(); });
restart.addEventListener('click', () => {
	randomizeTracks();
	reconcile();
});
speed.addEventListener('input', updateSpeed);
document.addEventListener('visibilitychange', reconcile);
new IntersectionObserver(([entry]) => { inView = entry.isIntersecting; reconcile(); }).observe(artwork);
reducedMotion.addEventListener('change', () => {
	if (reducedMotion.matches) running = false;
	document.getElementById('motion-note').hidden = !reducedMotion.matches;
	reconcile();
});

document.getElementById('motion-note').hidden = !reducedMotion.matches;
updateSpeed();
randomizeTracks();
reconcile();
loadContourArtwork();
