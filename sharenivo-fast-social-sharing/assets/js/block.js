/**
 * ShareNivo dynamic blocks.
 */
(function (blocks, element, i18n, ServerSideRender) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var icon = el('svg', {viewBox: '0 0 24 24', 'aria-hidden': true},
		el('path', {d: 'M18 16.1c-.8 0-1.5.3-2 .8L8.9 12.7c.1-.2.1-.5.1-.7s0-.5-.1-.7L16 7.1a3 3 0 1 0-1-1.7L7.9 9.6a3 3 0 1 0 0 4.8l7.1 4.2a3 3 0 1 0 3-2.5Z'})
	);

	function preview(name) {
		return el('div', {className: 'sharenivo-block-preview'},
			el(ServerSideRender, {block: name, attributes: {}})
		);
	}

	blocks.registerBlockType('sharenivo/share-buttons', {
		apiVersion: 2,
		title: __('ShareNivo Share Buttons', 'sharenivo-fast-social-sharing'),
		description: __('Display the configured social sharing buttons.', 'sharenivo-fast-social-sharing'),
		icon: icon,
		category: 'widgets',
		keywords: [__('share', 'sharenivo-fast-social-sharing'), __('social', 'sharenivo-fast-social-sharing')],
		supports: {html: false, multiple: true},
		edit: function () { return preview('sharenivo/share-buttons'); },
		save: function () { return null; }
	});

	blocks.registerBlockType('sharenivo/follow-links', {
		apiVersion: 2,
		title: __('ShareNivo Follow Links', 'sharenivo-fast-social-sharing'),
		description: __('Display the social profiles configured in ShareNivo.', 'sharenivo-fast-social-sharing'),
		icon: icon,
		category: 'widgets',
		keywords: [__('follow', 'sharenivo-fast-social-sharing'), __('social', 'sharenivo-fast-social-sharing')],
		supports: {html: false, multiple: true},
		edit: function () { return preview('sharenivo/follow-links'); },
		save: function () { return null; }
	});

	blocks.registerBlockType('wssp/share-buttons', {
		apiVersion: 2,
		title: __('ShareNivo Share Buttons (Legacy)', 'sharenivo-fast-social-sharing'),
		description: __('Compatibility block for existing ShareNova content.', 'sharenivo-fast-social-sharing'),
		icon: icon,
		category: 'widgets',
		supports: {html: false, inserter: false},
		edit: function () { return preview('wssp/share-buttons'); },
		save: function () { return null; }
	});
}(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.serverSideRender));
