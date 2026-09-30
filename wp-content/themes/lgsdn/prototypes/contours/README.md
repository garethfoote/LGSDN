# Contour experiments

Open http://localhost:8080/wp-content/themes/lgsdn/prototypes/contours/ with the existing local WordPress server running. These standalone prototypes do not change the homepage or enqueue any production scripts.

- Gentle waves: periodic angular harmonics with slowly changing time factors.
- Perlin flow: deterministic improved gradient noise, sampled around circles with time as the third coordinate. A smooth tanh bound limits displacement. The difference from the initial sample makes both modes start at the reference.
- SVG and Canvas draw the same sampled, closed polygons. Canvas scales its backing store independently of curve sampling; stroke weights are in the same 225-unit coordinate system.
- One requestAnimationFrame loop. Pause, original comparison, hidden documents and offscreen previews suspend it. Each experiment keeps its own phase, and renderer changes preserve the current phase. Reduced motion starts paused; Play is an explicit opt-in. The `?study=gentle-waves-canvas` link opens the preserved Canvas study with a faster, more pronounced preset.
- Movement preferences are saved locally per experiment. Display settings are shared for fair comparisons and reset on reload.

## Geometry and limitations

`reference.mjs` contains 512 radial centre-line samples for each of the homepage SVG's six outlined contours. The generator checks that each ray has exactly two outline intersections and projects outline thickness onto the local normal. The measured median width is approximately 1.0085 units per 225-unit artwork. This is a close reconstruction, not the original Bezier paths; use Show original to compare.

Rings share a polar centre and sample angles. Their displacement is bounded to less than one quarter of the smallest original radial gap, and also bounded by distance from the centre and an inset circular boundary. Every resulting polygon stays positive, ordered and closed. Consecutive vertices occupy common angular sectors, so their edges cannot cross, including the final-to-first edge. The circular domain is convex, so contained vertices and the stroke margin imply contained edges. No clipping or spline overshoot conceals errors. The outer contour can remain fixed in its original organic shape; this option does not turn it into a perfect circle.

At 128 points, corners may be perceptible at large display sizes. 256 is the initial compromise; 512 improves approximation. These are contour-style deformations, not recomputed equal-height lines of a physical landscape. The three optional dots stay at their reference positions.

The diagnostic JS timing excludes the browser's later paint/compositing work. Frame cadence is page-wide, not an isolated renderer benchmark or a battery measurement. Compare modes with identical settings and on real target devices. Speed changes only the time supplied to the function, so it does not increase geometric or drawing work per frame. Very high speeds can appear less smooth if the display has a low frame rate. A frame stalled for longer than 50ms advances motion by at most 50ms to avoid a large visual jump; offscreen time is excluded.

## Checks

```sh
node --test wp-content/themes/lgsdn/prototypes/contours/contour-math.test.mjs
node wp-content/themes/lgsdn/prototypes/contours/build-reference.mjs
```

The reference generator supports the absolute SVG path commands used in the current artwork and fails for unsupported geometry. Test the geometry again after regeneration.
