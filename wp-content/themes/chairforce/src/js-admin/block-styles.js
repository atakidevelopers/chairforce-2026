wp.domReady(() => {

	wp.blocks.registerBlockStyle('core/buttons', [
		{
			name: 'full-on-small',
			label: 'Full on Small',
		},
	]);

	wp.blocks.registerBlockStyle('core/button', [
		{
			name: 'primary',
			label: 'Primary',
		},
		{
			name: 'secondary',
			label: 'Secondary',
		},
		{
			name: 'ghost',
			label: 'Ghost',
		},
		{
			name: 'light',
			label: 'Light',
		},
	]);

	wp.blocks.registerBlockStyle('woocommerce/product-price', [
		{
			name: 'text-price',
			label: 'Price',
		}
	]);

	wp.blocks.registerBlockStyle('core/paragraph', [
		{
			name: 'text-eyebrow',
			label: 'Eyebrow',
		},
		{
			name: 'text-eyebrow-filled',
			label: 'Filled Eyebrow',
		},
		{
			name: 'text-lead',
			label: 'Lead',
		},
		{
			name: 'text-label-nav',
			label: 'Label / Nav',
		},
		{
			name: 'text-meta',
			label: 'Meta / Small',
		},
		{
			name: 'text-price',
			label: 'Price',
		},
		{
			name: 'text-heading-like',
			label: 'Heading Like',
		}
	]);

	wp.blocks.registerBlockStyle('core/group', [
		{
			name: 'card',
			label: 'Card',
		},
		{
			name: 'narrow',
			label: 'Narrow',
		},
	]);

	wp.blocks.registerBlockStyle('outermost/icon-block', [
		{
			name: 'style-1',
			label: 'Style 1',
		},
		{
			name: 'style-2',
			label: 'Style 2',
		},
		{
			name: 'style-3',
			label: 'Style 3',
		},
		{
			name: 'style-4',
			label: 'Style 4',
		},
		{
			name: 'style-5',
			label: 'Style 5',
		},
		{
			name: 'style-6',
			label: 'Style 6',
		},
		{
			name: 'style-7',
			label: 'Style 7',
		},
	]);
});
