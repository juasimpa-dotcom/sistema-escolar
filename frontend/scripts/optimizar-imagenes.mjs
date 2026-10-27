import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(scriptDir, '..');
const originalsDir = path.join(rootDir, 'imagenes-originales');
const publicImagesDir = path.join(rootDir, 'public', 'images');

const photos = [
  { source: 'login.jpg', output: 'login.webp', width: 1920, maxBytes: 500_000 },
  { source: 'superadmin.jpg', output: 'superadmin.webp', width: 1600, height: 400, maxBytes: 250_000 },
  { source: 'director.jpg', output: 'director.webp', width: 1600, height: 400, maxBytes: 250_000 },
  { source: 'administrativo.png', output: 'administrativo.webp', width: 1600, height: 400, maxBytes: 250_000 },
  { source: 'profesor.jpg', output: 'profesor.webp', width: 1600, height: 400, maxBytes: 250_000 },
  { source: 'padre.jpg', output: 'padre.webp', width: 1600, height: 400, maxBytes: 250_000 },
  { source: 'estudiante.jpg', output: 'estudiante.webp', width: 1600, height: 400, maxBytes: 250_000 },
];

await Promise.all(photos.map(async ({ source, output, width, height, maxBytes }) => {
  const pipeline = sharp(path.join(originalsDir, source)).rotate();
  if (height) {
    pipeline.resize(width, height, { fit: 'cover', position: 'centre' });
  } else {
    pipeline.resize({ width, withoutEnlargement: true });
  }
  const result = await pipeline.webp({ quality: 75, effort: 6, smartSubsample: true })
    .toFile(path.join(publicImagesDir, output));
  if (result.size >= maxBytes) {
    throw new Error(`${output} pesa ${Math.ceil(result.size / 1024)} KB y debe pesar menos de ${Math.floor(maxBytes / 1024)} KB.`);
  }
  console.log(`${output}: ${result.width}x${result.height}, ${(result.size / 1024).toFixed(1)} KB`);
}));

await sharp(path.join(publicImagesDir, 'logo.png'))
  .resize(64, 64, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
  .png()
  .toFile(path.join(publicImagesDir, 'favicon.png'));

await sharp(path.join(publicImagesDir, 'logo.png'))
  .resize(180, 180, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 0 } })
  .png()
  .toFile(path.join(publicImagesDir, 'apple-touch-icon.png'));

console.log('Iconos generados desde logo.png: favicon.png (64x64) y apple-touch-icon.png (180x180).');
