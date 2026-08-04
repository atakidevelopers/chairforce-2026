import { DEFAULT_FOCAL_POINT } from './constants';

/**
 * Normalizes a focal point object for editor components.
 *
 * @param {{ x?: number, y?: number }|null|undefined} focalPoint Focal point.
 * @return {{ x: number, y: number }} Normalized focal point.
 */
export function normalizeFocalPoint(focalPoint) {
	const x = Number(focalPoint?.x);
	const y = Number(focalPoint?.y);

	return {
		x: Number.isFinite(x) ? x : DEFAULT_FOCAL_POINT.x,
		y: Number.isFinite(y) ? y : DEFAULT_FOCAL_POINT.y,
	};
}

/**
 * Returns inline background styles for the hero section wrapper.
 *
 * @param {string} backgroundImageUrl Image URL.
 * @param {{ x?: number, y?: number }|null|undefined} focalPoint Focal point coordinates (0-1).
 * @return {Object} Style object for useBlockProps.
 */
export function getBackgroundStyle(backgroundImageUrl, focalPoint) {
	if (!backgroundImageUrl) {
		return {};
	}

	const point = normalizeFocalPoint(focalPoint);
	const x = point.x * 100;
	const y = point.y * 100;
	const safeUrl = String(backgroundImageUrl).replace(/"/g, '\\"');

	return {
		backgroundImage: `url("${safeUrl}")`,
		backgroundSize: 'cover',
		backgroundRepeat: 'no-repeat',
		backgroundPosition: `${x}% ${y}%`,
	};
}
