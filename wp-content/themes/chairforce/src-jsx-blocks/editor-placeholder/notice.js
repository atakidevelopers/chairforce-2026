import { Fragment } from '@wordpress/element';
import { Notice } from '@wordpress/components';

export default function EditorPlaceholderNotice( {
	title,
	description,
	displayLinks,
} ) {
	return (
		<div className="wp-block-group alignwide">
			<Notice status="info" isDismissible={ false }>
				<p>
					{ title && <strong>{ title }</strong> }
					{ title && description && ' — ' }
					{ description }
					{ displayLinks.length > 0 && (
						<>
							{ ( title || description ) && ' ' }
							{ displayLinks.map( ( link, index ) => (
								<Fragment key={ link.url }>
									{ index > 0 && (
										<span aria-hidden="true"> · </span>
									) }
									<a href={ link.url }>{ link.label }</a>
								</Fragment>
							) ) }
						</>
					) }
				</p>
			</Notice>
		</div>
	);
}
