/**
 * Shared formatting utility — used in both edit.js and save.js.
 * Must be kept as a plain JS module (no JSX / WP deps) so it can be
 * imported from either context without side effects.
 *
 * @param {number} n   The numeric value to format.
 * @param {string} sep Thousands separator, typically ',' or '' (none).
 * @return {string}
 */
export function formatNumber( n, sep ) {
	if ( ! sep ) return String( Math.round( n ) );
	return Math.round( n ).toLocaleString( 'en-US' );
}
