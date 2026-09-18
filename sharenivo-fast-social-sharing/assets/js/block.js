/**
 * ShareNivo dynamic blocks.
 */
(function (blocks, element, i18n, ServerSideRender) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var TextareaControl = window.wp.components.TextareaControl;
	var SelectControl = window.wp.components.SelectControl;
	var icon = el('svg', {viewBox: '0 0 24 24', 'aria-hidden': true},
		el('path', {d: 'M18 16.1c-.8 0-1.5.3-2 .8L8.9 12.7c.1-.2.1-.5.1-.7s0-.5-.1-.7L16 7.1a3 3 0 1 0-1-1.7L7.9 9.6a3 3 0 1 0 0 4.8l7.1 4.2a3 3 0 1 0 3-2.5Z'})
	);

	function preview(name, attributes) {
		return el('div', {className: 'sharenivo-block-preview'},
			el(ServerSideRender, {block: name, attributes: attributes || {}})
		);
	}

	function quoteEdit(props) {
		return el('div', {className: 'sharenivo-quote-editor'},
			el(TextareaControl, {
				label: __('Quote text', 'sharenivo-fast-social-sharing'),
				value: props.attributes.text || '',
				onChange: function (value) { props.setAttributes({text: value}); },
				help: __('The quote is kept local and only sent when a visitor chooses to share it.', 'sharenivo-fast-social-sharing')
			}),
			el(SelectControl, {
				label: __('Quote style', 'sharenivo-fast-social-sharing'),
				value: props.attributes.style || 'card',
				options: [
					{label: __('Card', 'sharenivo-fast-social-sharing'), value: 'card'},
					{label: __('Minimal', 'sharenivo-fast-social-sharing'), value: 'minimal'},
					{label: __('Accent', 'sharenivo-fast-social-sharing'), value: 'accent'},
					{label: __('Bordered', 'sharenivo-fast-social-sharing'), value: 'bordered'}
				],
				onChange: function (value) { props.setAttributes({style: value}); }
			}),
			preview('sharenivo/share-quote', props.attributes)
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

	blocks.registerBlockType('sharenivo/share-quote', {
		apiVersion: 2,
		title: __('ShareNivo Click-to-Share Quote', 'sharenivo-fast-social-sharing'),
		description: __('Create an accessible quote that visitors can share on X.', 'sharenivo-fast-social-sharing'),
		icon: icon,
		category: 'widgets',
		keywords: [__('quote', 'sharenivo-fast-social-sharing'), __('tweet', 'sharenivo-fast-social-sharing'), __('share', 'sharenivo-fast-social-sharing')],
		attributes: {
			text: {type: 'string', default: ''},
			style: {type: 'string', default: 'card'}
		},
		supports: {html: false, multiple: true},
		edit: quoteEdit,
		save: function () { return null; }
	});
}(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.serverSideRender));
