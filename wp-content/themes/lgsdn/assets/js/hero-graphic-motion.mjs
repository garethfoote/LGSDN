import { reference } from './hero-contour-reference.mjs';

const namespace = 'http://www.w3.org/2000/svg';
const tau = Math.PI * 2;
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const dots = [
	{ ring: 5, direction: 1, rate: 1.03 },
	{ ring: 4, direction: -1, rate: 0.98 },
	{ ring: 2, direction: 1, rate: 1 },
];

function createTrack(seed, circle) {
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
	return { ...seed, circle, points, lengths, total: lengths.at(-1), phase: 0 };
}

function phaseForAngle(track, angle) {
	const sample = angle / tau * track.points.length;
	const start = Math.floor(sample);
	return track.lengths[start] + (track.lengths[start + 1] - track.lengths[start]) * (sample - start);
}

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
	return [start[0] + (end[0] - start[0]) * amount, start[1] + (end[1] - start[1]) * amount];
}

function startMotion(disc, tracks) {
	let running = !reducedMotion.matches;
	let onScreen = true;
	let phaseTime = 0;
	let previousTime = null;
	let animationFrame = 0;

	// Distribute the three dots through alternating 60° sectors. Each start sits
	// in the middle half of its sector, leaving at least 90° between dots.
	const sectorOffset = Math.floor(Math.random() * 6);
	tracks.forEach((track, index) => {
		const sector = (sectorOffset + index * 2) % 6;
		const angle = (sector + 0.25 + Math.random() * 0.5) * (tau / 6);
		track.phase = phaseForAngle(track, angle);
	});

	function render() {
		for (const track of tracks) {
			const distance = track.phase + phaseTime * (track.total / 300) * track.rate * track.direction;
			const [x, y] = pointAt(track, distance);
			track.circle.setAttribute('cx', x.toFixed(3));
			track.circle.setAttribute('cy', y.toFixed(3));
		}
	}

	function frame(now) {
		animationFrame = 0;
		if (!running || !onScreen || document.hidden) return;
		const elapsed = previousTime === null ? 0 : Math.min(now - previousTime, 50);
		previousTime = now;
		phaseTime += elapsed / 1000;
		render();
		animationFrame = requestAnimationFrame(frame);
	}

	function reconcile() {
		if (animationFrame) cancelAnimationFrame(animationFrame);
		animationFrame = 0;
		previousTime = null;
		if (running && onScreen && !document.hidden) animationFrame = requestAnimationFrame(frame);
	}

	if (!reducedMotion.matches) reconcile();
	document.addEventListener('visibilitychange', reconcile);
	reducedMotion.addEventListener('change', () => {
		running = !reducedMotion.matches;
		reconcile();
	});
	new IntersectionObserver(([entry]) => {
		onScreen = entry.isIntersecting;
		reconcile();
	}).observe(disc);
}

async function enhanceGraphic(disc) {
	if (reducedMotion.matches) {
		disc.removeAttribute('data-motion-pending');
		return;
	}

	try {
		const image = disc.querySelector('img');
		const response = await fetch(image.currentSrc || image.src);
		if (!response.ok) throw new Error('Homepage contour artwork could not be loaded');
		const parsed = new DOMParser().parseFromString(await response.text(), 'image/svg+xml');
		const source = document.importNode(parsed.documentElement, true);
		if (source.localName !== 'svg' || parsed.querySelector('parsererror')) throw new Error('Homepage contour artwork is invalid');
		source.querySelectorAll('circle').forEach((circle) => circle.remove());
		source.classList.add('home-hero__graphic-source');
		source.setAttribute('aria-hidden', 'true');
		source.setAttribute('focusable', 'false');

		const overlay = document.createElementNS(namespace, 'svg');
		overlay.classList.add('home-hero__graphic-dots');
		overlay.setAttribute('viewBox', '0 0 225 225');
		overlay.setAttribute('aria-hidden', 'true');
		overlay.setAttribute('focusable', 'false');
		const tracks = dots.map((seed) => {
			const circle = document.createElementNS(namespace, 'circle');
			circle.setAttribute('r', '3.5');
			circle.setAttribute('fill', '#fff');
			overlay.append(circle);
			return createTrack(seed, circle);
		});

		disc.replaceChildren(source, overlay);
		startMotion(disc, tracks);
		disc.removeAttribute('data-motion-pending');
	} catch {
		// Keep the original static mark visible if enhancement cannot be prepared.
		disc.removeAttribute('data-motion-pending');
	}
}

document.querySelectorAll('[data-hero-graphic]').forEach(enhanceGraphic);
