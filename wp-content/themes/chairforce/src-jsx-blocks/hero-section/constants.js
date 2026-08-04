import { __ } from '@wordpress/i18n';

export const ALLOWED_MEDIA_TYPES = ['image'];

export const DEFAULT_FOCAL_POINT = {
	x: 0.5,
	y: 0.5,
};

export const ALLOWED_BLOCKS = ['core/group'];

export const CONTENT_GROUP_CLASS = 'cf-hero-section__content';

export const INNER_BLOCKS_TEMPLATE = [
	[
		'core/group',
		{
			className: CONTENT_GROUP_CLASS,
			backgroundColor: 'surface',
			textColor: 'body',
			style: {
				spacing: {
					padding: {
						top: 'var:preset|spacing|large',
						right: 'var:preset|spacing|large',
						bottom: 'var:preset|spacing|large',
						left: 'var:preset|spacing|large',
					},
					blockGap: 'var:preset|spacing|small',
				},
			},
		},
		[
			[
				'core/paragraph',
				{
					content: __('Add eyebrow', 'chairforce'),
					className: 'is-style-text-eyebrow',
				},
			],
			[
				'core/heading',
				{
					level: 1,
					content: __('Add hero heading', 'chairforce'),
					textColor: 'heading',
				},
			],
			[
				'core/paragraph',
				{
					content: __('Add supporting hero text.', 'chairforce'),
				},
			],
			[
				'core/buttons',
				{
					className: 'is-style-full-on-small',
					style: {
						spacing: {
							blockGap: 'var:preset|spacing|x-small',
						},
					},
				},
				[
					[
						'core/button',
						{
							className: 'is-style-primary',
							text: __('Primary button', 'chairforce'),
						},
					],
					[
						'core/button',
						{
							className: 'is-style-secondary',
							text: __('Secondary button', 'chairforce'),
						},
					],
				],
			],
		],
	],
];
