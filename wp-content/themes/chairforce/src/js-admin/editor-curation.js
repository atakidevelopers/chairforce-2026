/**
 * The purpose of the following code is to customize the WordPress Editor by removing unnecessary features and adding custom functionality by tailoring the interface to meet specific needs. By curating the editing environment, you can streamline content creation, reduce clutter, enforce consistency, and ensure that the tools available are relevant.
 */

/**
 * Globally disable RichText formatting options.
 */
wp.domReady(function () {
	const formatsToUnregister = [
		'core/code',
		'core/image',
		'core/keyboard',
		'core/language',
	];

	formatsToUnregister.forEach(function (format) {
		wp.richText.unregisterFormatType(format);
	});
});


/**
 * Unregister selected Embed block variations.
 */
wp.domReady(() => {
	const embedVariations = [
		'animoto',
		'dailymotion',
		'hulu',
		'reddit',
		'tumblr',
		'vine',
		'amazon-kindle',
		'cloudup',
		'crowdsignal',
		'speaker',
		'scribd'
	];

	embedVariations.forEach((variation) => {
		wp.blocks.unregisterBlockVariation('core/embed', variation);
	});
});

/**
 * Unregister Image block styles.
 */
wp.domReady(function () {
	wp.blocks.unregisterBlockStyle('core/image', [ 'rounded' ]);
});

/**
 * Banner CPT: remove Heading block from the inserter (JS fallback).
 */
wp.domReady(function () {
	if (!wp.data?.subscribe || !wp.blocks?.unregisterBlockType) {
		return;
	}

	let headingRemoved = false;

	const maybeRemoveHeadingBlock = function () {
		if (headingRemoved) {
			return;
		}

		const postType = wp.data.select('core/editor')?.getCurrentPostType?.();

		if (postType !== 'chairforce_banner') {
			return;
		}

		if (wp.blocks.getBlockType('core/heading')) {
			wp.blocks.unregisterBlockType('core/heading');
		}

		headingRemoved = true;
	};

	wp.data.subscribe(maybeRemoveHeadingBlock);
	maybeRemoveHeadingBlock();
});
