import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

// Exercise the shipped picker conversion functions without replacing them in production.
for (const path of ['resources/ebsMenuPage.js', 'src/deprecated/resources/ebsMenuPage.js']) {
  const source = readFileSync(path, 'utf8');
  const conversions = source.slice(source.indexOf('function convertHexToHSL'), source.indexOf('function checkLightness'));
  const { toHex, toHSL } = runInNewContext(conversions + '; ({ toHex: convertHSLToHex, toHSL: convertHexToHSL })');
  describe(`picker color conversion: ${path}`, () => {
    for (const color of ['#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#33258a', '#160038']) {
      it(`round-trips ${color} without changing saved colors`, () => {
        const actual = toHex(toHSL(color)).toLowerCase();
        // HSL conversion rounds hue and saturation/lightness; permit the documented quantization.
        for (let offset = 1; offset < 7; offset += 2) {
          expect(Math.abs(parseInt(actual.slice(offset, offset + 2), 16) - parseInt(color.slice(offset, offset + 2), 16))).toBeLessThanOrEqual(3);
        }
      });
    }
    for (const color of ['#000000', '#010101', '#160038', '#ffffff']) {
      it(`generates valid hover colors for ${color}`, () => {
        const [h, s, l] = toHSL(color);
        for (const offset of [-20, -10, 10]) {
          expect(toHex([h, s, l + offset])).toMatch(/^#[0-9a-f]{6}$/i);
        }
      });
    }
    it('clamps a hover color below black to black', () => {
      const [h, s, l] = toHSL('#160038');
      expect(toHex([h, s, l - 20])).toBe('#000000');
    });
  });
}
