/**
 * ShareNivo - Gutenberg Block
 */
( function( blocks, element, i18n, serverSideRender ) {
	'use strict';

	var el = element.createElement;

	function registerShareBlock( name, isInserterVisible ) {
		blocks.registerBlockType( name, {
			apiVersion: 2,
			title: i18n.__( 'Social Share Buttons', 'sharenivo-fast-social-sharing' ),
			description: i18n.__( 'Display social share buttons with live preview.', 'sharenivo-fast-social-sharing' ),
			icon: 'share',
			category: 'widgets',
			supports: {
				html: false,
				inserter: isInserterVisible
			},
			edit: function( props ) {
				return el(
					'div',
					{ className: props.className },
					el( serverSideRender, {
						block: name
					} )
				);
			},
			save: function() {
				return null;
			}
		} );
	}

	registerShareBlock( 'sharenivo/share-buttons', true );
	// Keep legacy blocks editable after upgrading from ShareNova.
	registerShareBlock( 'wssp/share-buttons', false );
} )( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
