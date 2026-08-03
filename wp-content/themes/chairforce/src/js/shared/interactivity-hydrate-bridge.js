/**
 * Bridge from the webpack public bundle to the Interactivity hydrate module.
 */

import { CONTENT_UPDATED_EVENT } from './delegated-events';

/** @type {Promise<Record<string, Function>>|null} */
let modulePromise = null;

/**
 * @returns {Record<string, string|undefined>}
 */
function getPublicConfig() {
	return window.Chairforce_Public || {};
}

/**
 * @return {Promise<Record<string, Function>|null>}
 */
async function loadHydrateModule() {
	const moduleUrl = getPublicConfig().interactivityHydrateUrl;

	if ( ! moduleUrl ) {
		return null;
	}

	if ( ! modulePromise ) {
		modulePromise = import(
			/* webpackIgnore: true */
			moduleUrl
		);
	}

	return modulePromise;
}

/**
 * @param {ParentNode|ParentNode[]} root
 * @param {{ selector?: string }}   [options]
 */
export async function hydrateInteractiveRoots( root, options = {} ) {
	const mod = await loadHydrateModule();

	if ( ! mod?.hydrateInteractiveRoots ) {
		return;
	}

	await mod.hydrateInteractiveRoots( root, options );
}

/**
 * @param {Document} doc
 */
export async function mergeInteractivityDataFromDocument( doc ) {
	const mod = await loadHydrateModule();

	if ( mod?.mergeInteractivityDataFromDocument ) {
		mod.mergeInteractivityDataFromDocument( doc );
	}
}

/**
 * @param {number[]} productIds
 */
export async function ensureProductsLoaded( productIds ) {
	const mod = await loadHydrateModule();

	if ( mod?.ensureProductsLoaded ) {
		await mod.ensureProductsLoaded( productIds );
	}
}

/**
 * Re-hydrate archive/QV markup after full-page fetch or REST injection.
 */
export function initInteractivityHydrate() {
	document.addEventListener( CONTENT_UPDATED_EVENT, async ( event ) => {
		const root = event.detail?.root;
		const selector = event.detail?.selector;

		if ( root instanceof Element || root instanceof DocumentFragment ) {
			await hydrateInteractiveRoots( root, { selector } );
		}
	} );
}
