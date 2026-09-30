import test from 'node:test';
import assert from 'node:assert/strict';
import {createGeometry,updateGeometry,defaults,noise} from './contour-math.mjs';

test('both motions start at the reference and zero amount stays there', () => {
  const g = createGeometry();
  for (const mode of ['waves','perlin']) {
    updateGeometry(g,mode,0,defaults);
    assert.deepEqual(g.radii,g.base);
    updateGeometry(g,mode,1234,{...defaults,amount:0});
    assert.deepEqual(g.radii,g.base);
  }
});

test('every supported detail remains finite, nested and inside the disc at extreme settings', () => {
  for (const count of [128,256,512]) {
    const g = createGeometry(count);
    for (const mode of ['waves','perlin']) for (const detail of [1,2,3,4]) for (const cohesion of [0,100]) {
      for (const time of [0,0.01,0.7,3,10,37,91,1000,10000]) {
        updateGeometry(g,mode,time,{...defaults,amount:100,detail,cohesion});
        for (let i=0;i<count;i++) for (let ring=0;ring<6;ring++) {
          const r = g.radii[ring][i], x = g.points[ring][i*2], y = g.points[ring][i*2+1];
          assert.ok(Number.isFinite(r) && r > 0);
          assert.ok(Math.hypot(x-112.5,y-112.5)+g.widths[ring]/2 < 112.5,'stroke must fit inside the disc');
          if (ring) assert.ok(r-g.radii[ring-1][i] > 1.1,'adjacent rings must keep their spacing');
        }
        // Straight segments lie in the convex disc. Ordered positive radii on
        // shared rays imply no polygon intersections, including the closing edge.
      }
    }
  }
});

test('fixed outer contour is unchanged while inner contours move', () => {
  const g = createGeometry();
  for (const mode of ['waves','perlin']) {
    updateGeometry(g,mode,7,{...defaults,amount:100,fixedOuter:true});
    assert.deepEqual(g.radii[5],g.base[5]);
    assert.notDeepEqual(g.radii[0],g.base[0]);
  }
});

test('tiny time steps do not produce jumps, including noise lattice boundaries', () => {
  const g = createGeometry();
  for (const mode of ['waves','perlin']) for (const t of [0,1,10/3,10,100,1000]) {
    updateGeometry(g,mode,t-1e-6,{...defaults,amount:100});
    const before = g.radii.map(r=>r.slice());
    updateGeometry(g,mode,t+1e-6,{...defaults,amount:100});
    for (let ring=0;ring<6;ring++) for (let i=0;i<g.count;i++) {
      assert.ok(Math.abs(before[ring][i]-g.radii[ring][i]) < 0.0001);
    }
  }
  for (const x of [-10,0,1,17,256]) assert.ok(Math.abs(noise(x-1e-7,2.1,3.4)-noise(x+1e-7,2.1,3.4)) < 1e-5);
});
