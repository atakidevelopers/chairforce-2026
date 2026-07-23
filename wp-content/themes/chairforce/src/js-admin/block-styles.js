wp.domReady(() => {

	wp.blocks.registerBlockStyle('core/buttons', [
		{
			name: 'full-on-small',
			label: 'Full on Small',
		}
	]);

	wp.blocks.registerBlockStyle('core/paragraph', [
		{
			name: 'subtitle',
			label: 'Subtitle',
		}
	]);

	wp.blocks.registerBlockStyle('core/group', [
		{
			name: 'card',
			label: 'Card',
		}
	]);

});
