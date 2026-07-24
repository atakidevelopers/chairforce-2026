wp.domReady(() => {

	wp.blocks.registerBlockStyle('core/buttons', [
		{
			name: 'full-on-small',
			label: 'Full on Small',
		}
	]);

	wp.blocks.registerBlockStyle('core/paragraph', [
		{
			name: 'text-eyebrow',
			label: 'Eyebrow',
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
		}
	]);

});
