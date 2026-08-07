/**
 * Banner CPT editor guidance — snackbar notice + document panel.
 * Heading blocks are removed server-side for chairforce_banner (Editor_Curation).
 */

wp.domReady( function () {
	if (
		! wp.plugins?.registerPlugin ||
		! wp.editPost?.PluginDocumentSettingPanel ||
		! wp.data?.useSelect ||
		! wp.element?.useEffect ||
		! wp.element?.createElement
	) {
		return;
	}

	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { Notice } = wp.components;
	const { useEffect, createElement: el } = wp.element;
	const { useSelect } = wp.data;

	const BANNER_POST_TYPE = 'chairforce_banner';
	const BANNER_GUIDANCE_PANEL =
		'chairforce-banner-editor-guidance/chairforce-banner-guidance';
	const NOTICE_ID = 'chairforce-banner-no-headings-notice';

	const GUIDANCE_MESSAGE = __(
		'Please do not use Heading blocks in banners — they break heading hierarchy on category archive pages. Use Paragraph blocks instead. Apply the "Heading Like" paragraph style (or adjust font size and weight) for prominent banner text.',
		'chairforce'
	);

	function ensureBannerGuidancePanelOpen() {
		const editorSelect = wp.data.select( 'core/editor' );

		if (
			! editorSelect?.isEditorPanelOpened ||
			editorSelect.isEditorPanelOpened( BANNER_GUIDANCE_PANEL )
		) {
			return;
		}

		wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened(
			BANNER_GUIDANCE_PANEL
		);
	}

	function BannerEditorGuidance() {
		const postType = useSelect(
			( select ) => select( 'core/editor' )?.getCurrentPostType?.(),
			[]
		);

		useEffect( () => {
			if ( BANNER_POST_TYPE !== postType ) {
				return;
			}

			wp.data.dispatch( 'core/notices' ).createNotice(
				'warning',
				GUIDANCE_MESSAGE,
				{
					id: NOTICE_ID,
					isDismissible: true,
					type: 'snackbar',
				}
			);
		}, [ postType ] );

		useEffect( () => {
			if ( BANNER_POST_TYPE !== postType ) {
				return undefined;
			}

			ensureBannerGuidancePanelOpen();

			return wp.data.subscribe( ensureBannerGuidancePanelOpen );
		}, [ postType ] );

		if ( BANNER_POST_TYPE !== postType ) {
			return null;
		}

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'chairforce-banner-guidance',
				title: __( 'Banner guidelines', 'chairforce' ),
				className: 'chairforce-banner-guidance-panel',
			},
			el(
				Notice,
				{
					status: 'warning',
					isDismissible: false,
				},
				el( 'p', null, GUIDANCE_MESSAGE )
			)
		);
	}

	registerPlugin( 'chairforce-banner-editor-guidance', {
		render: BannerEditorGuidance,
	} );
} );
