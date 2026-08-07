<?php
/**
 * Title: Commercial Solutions
 * Slug: chairforce/commercial-solutions
 * Description: Four Icon Box items for commercial solutions content
 * Categories: section, elements
 * Keywords: commercial, solutions, bulk, orders, icon, features
 */

$icon_box_block = static function () {
	return [
		'blockName'    => 'chairforce/icon-box',
		'attrs'        => [
			'iconPosition' => 'left',
			'alignment'    => 'center',
			'layout'       => [
				'type'        => 'flex',
				'orientation' => 'horizontal',
			],
			'style'        => [
				'spacing' => [
					'blockGap' => 'var:preset|spacing|small',
				],
			],
		],
		'innerBlocks'  => [
			[
				'blockName'    => 'outermost/icon-block',
				'attrs'        => [
					'iconColor'           => 'white',
					'iconBackgroundColor' => 'primary',
					'hasNoIconFill'       => true,
					'width'               => '40px',
					'height'              => '40px',
					'lock'                => [
						'remove' => true,
						'move'   => true,
					],
					'style'               => [
						'spacing' => [
							'padding' => [
								'top'    => 'var:preset|spacing|xx-small',
								'right'  => 'var:preset|spacing|xx-small',
								'bottom' => 'var:preset|spacing|xx-small',
								'left'   => 'var:preset|spacing|xx-small',
							],
						],
					],
				],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			],
			[
				'blockName'    => 'core/group',
				'attrs'        => [
					'lock'   => [
						'remove' => true,
					],
					'style'  => [
						'spacing' => [
							'blockGap' => 'var:preset|spacing|xxx-small',
						],
					],
					'layout' => [
						'type' => 'constrained',
					],
				],
				'innerBlocks'  => [
					[
						'blockName'    => 'core/heading',
						'attrs'        => [
							'level' => 4,
						],
						'innerBlocks'  => [],
						'innerHTML'    => '<h4 class="wp-block-heading">Feature heading</h4>',
						'innerContent' => [
							'<h4 class="wp-block-heading">Feature heading</h4>',
						],
					],
					[
						'blockName'    => 'core/paragraph',
						'attrs'        => [],
						'innerBlocks'  => [],
						'innerHTML'    => '<p>Add the approved feature description.</p>',
						'innerContent' => [
							'<p>Add the approved feature description.</p>',
						],
					],
				],
				'innerHTML'    => '<div class="wp-block-group"></div>',
				'innerContent' => [
					'<div class="wp-block-group">',
					null,
					null,
					'</div>',
				],
			],
		],
		'innerHTML'    => "\n<div class=\"wp-block-chairforce-icon-box is-icon-left is-align-center\"></div>\n",
		'innerContent' => [
			"\n<div class=\"wp-block-chairforce-icon-box is-icon-left is-align-center\">",
			null,
			null,
			"</div>\n",
		],
	];
};

$icon_boxes_group = [
	'blockName'    => 'core/group',
	'attrs'        => [
		'style'  => [
			'spacing' => [
				'blockGap' => 'var:preset|spacing|small',
			],
		],
		'layout' => [
			'type'        => 'grid',
			'columnCount' => 2,
		],
	],
	'innerBlocks'  => [
		$icon_box_block(),
		$icon_box_block(),
		$icon_box_block(),
		$icon_box_block(),
	],
	'innerHTML'    => '<div class="wp-block-group"></div>',
	'innerContent' => [
		'<div class="wp-block-group">',
		null,
		null,
		null,
		null,
		'</div>',
	],
];

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block serialization is trusted editor markup.
echo serialize_block( $icon_boxes_group );
