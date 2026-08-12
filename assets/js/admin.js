/**
 * ShareNivo settings dashboard.
 */
(function ($) {
	'use strict';

	var strings = window.sharenivoAdmin || {};
	var $form = $('.sharenivo-settings-form');

	function activateTab(tab) {
		var $button = $('.sharenivo-tabs__button[data-tab="' + tab + '"]');
		var $panel = $('.sharenivo-panel[data-panel="' + tab + '"]');
		if (!$button.length || !$panel.length) {
			return;
		}
		$('.sharenivo-tabs__button').removeClass('is-active').attr('aria-selected', 'false');
		$('.sharenivo-panel').removeClass('is-active').prop('hidden', true);
		$button.addClass('is-active').attr('aria-selected', 'true');
		$panel.addClass('is-active').prop('hidden', false);
		window.sessionStorage.setItem('sharenivoAdminTab', tab);
	}

	function selectedNetworks() {
		var selected = {};
		$('.sharenivo-network-card').each(function () {
			var $card = $(this);
			var $input = $card.find('input[type="checkbox"]');
			$card.toggleClass('is-selected', $input.prop('checked'));
			if ($input.prop('checked')) {
				selected[$input.val()] = $.trim($card.children('span').last().text());
			}
		});
		return selected;
	}

	function orderItem(network, label) {
		return $('<li>', {'data-network': network})
			.append($('<span>', {text: label}))
			.append($('<span>')
				.append($('<button>', {type: 'button', class: 'sharenivo-order-up', 'aria-label': 'Move up', text: '↑'}))
				.append($('<button>', {type: 'button', class: 'sharenivo-order-down', 'aria-label': 'Move down', text: '↓'})));
	}

	function syncOrder() {
		var selected = selectedNetworks();
		var order = [];
		$('#sharenivo-order-list li').each(function () {
			var network = String($(this).data('network'));
			if (selected[network]) {
				order.push(network);
			}
		});
		Object.keys(selected).forEach(function (network) {
			if (order.indexOf(network) === -1) {
				order.push(network);
			}
		});
		var $list = $('#sharenivo-order-list').empty();
		order.forEach(function (network) {
			$list.append(orderItem(network, selected[network]));
		});
		$('#sharenivo-network-order').val(order.join(','));
	}

	function syncLocationCards() {
		$('.sharenivo-location-card').each(function () {
			var $card = $(this);
			$card.toggleClass('is-enabled', $card.find('header input[type="checkbox"]').prop('checked'));
		});
	}

	function syncPreview() {
		var shape = $('[name="sharenivo_settings[style][shape]"]').val();
		var size = $('[name="sharenivo_settings[style][size]"]').val();
		var scheme = $('[name="sharenivo_settings[style][color_scheme]"]').val();
		var background = $('[name="sharenivo_settings[style][brand_bg]"]').val();
		var icon = $('[name="sharenivo_settings[style][icon_color]"]').val();
		var gap = $('[name="sharenivo_settings[style][gap]"]').val();
		var radius = shape === 'circle' ? '50%' : (shape === 'square' ? '0' : (shape === 'pill' ? '999px' : '10px'));
		var dimension = size === 'small' ? '36px' : (size === 'large' ? '52px' : '44px');

		$('.sharenivo-preview__buttons').css('gap', gap + 'px').find('i').css({
			'border-radius': radius,
			'width': shape === 'pill' ? '68px' : dimension,
			'height': dimension,
			'color': icon,
			'background': scheme === 'brand' ? background : ''
		});
		$('.sharenivo-color-fields').toggle(scheme === 'brand');
	}

	function copyText(text, $status) {
		function done() {
			$status.text(strings.copied || 'Copied.');
			window.setTimeout(function () { $status.text(''); }, 1800);
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done);
			return;
		}
		var $temp = $('<textarea>').val(text).css({position: 'fixed', opacity: 0}).appendTo('body').select();
		document.execCommand('copy');
		$temp.remove();
		done();
	}

	$('.sharenivo-tabs__button').on('click', function () {
		activateTab($(this).data('tab'));
	});
	var storedTab = window.sessionStorage.getItem('sharenivoAdminTab');
	if (storedTab) {
		activateTab(storedTab);
	}

	$('.sharenivo-color').wpColorPicker({change: syncPreview, clear: syncPreview});
	$form.on('change input', '.sharenivo-network-checkbox', syncOrder);
	$form.on('change', '.sharenivo-location-card header input[type="checkbox"]', syncLocationCards);
	$form.on('change input', '[name^="sharenivo_settings[style]"]', syncPreview);
	$('#sharenivo-order-list').on('click', '.sharenivo-order-up, .sharenivo-order-down', function () {
		var $item = $(this).closest('li');
		if ($(this).hasClass('sharenivo-order-up')) {
			if ($item.prev().length) {
				$item.insertBefore($item.prev());
			}
		} else {
			if ($item.next().length) {
				$item.insertAfter($item.next());
			}
		}
		$('#sharenivo-network-order').val($('#sharenivo-order-list li').map(function () { return $(this).data('network'); }).get().join(','));
	});
	$('.sharenivo-copy-export').on('click', function () {
		copyText($('.sharenivo-export').val(), $('.sharenivo-savebar__message'));
	});

	$form.on('submit', function (event) {
		event.preventDefault();
		var $button = $('.sharenivo-save-button');
		var $message = $('.sharenivo-savebar__message');
		$button.prop('disabled', true).addClass('is-saving');
		$message.removeClass('is-error').text(strings.saving || 'Saving…');

		$.ajax({
			url: strings.ajaxUrl,
			type: 'POST',
			data: $form.serialize(),
			dataType: 'json'
		}).done(function (response) {
			if (!response || !response.success) {
				$message.addClass('is-error').text(response && response.data && response.data.message ? response.data.message : (strings.error || 'Could not save.'));
				return;
			}
			$message.text(response.data.message || strings.saved || 'Settings saved.');
			if (response.data.export) {
				$('.sharenivo-export').val(response.data.export);
			}
		}).fail(function (xhr) {
			var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
			$message.addClass('is-error').text(message || strings.error || 'Could not save.');
		}).always(function () {
			$button.prop('disabled', false).removeClass('is-saving');
			window.setTimeout(function () { $message.text('').removeClass('is-error'); }, 4500);
		});
	});

	syncOrder();
	syncLocationCards();
	syncPreview();
}(jQuery));
