import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import metadata from './block.json';
import edit from './edit';
import save from './save';

registerBlockType(metadata.name, {
	...metadata,
	edit,
	save,
});
