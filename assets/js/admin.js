/**
 * ShareNivo - Admin dashboard interactions
 */
(function ($) {
	'use strict';

	$(document).ready(function () {

		/* ── Tab Switching ── */
		$('.sharenivo-tab-link').on('click', function (e) {
			e.preventDefault();

			var target = $(this).attr('href');

			$('.sharenivo-tab-link').removeClass('active');
			$('.sharenivo-tab-content').removeClass('active');

			$(this).addClass('active');
			$(target).addClass('active');

			$('#sharenivo_active_tab').val(target);
		});

		// Restore active tab on page load
		var activeTab = $('#sharenivo_active_tab').val();
		var allowedTabs = ['#general', '#display', '#style', '#advanced'];
		if (allowedTabs.indexOf(activeTab) !== -1 && $(activeTab).length) {
			$('.sharenivo-tab-link[href="' + activeTab + '"]').trigger('click');
		}

		/* ── WordPress Color Picker ── */
		if ($.fn.wpColorPicker) {
			$('.sharenivo-color-picker').wpColorPicker();
		}

		/* ── Color Scheme Toggle ── */
		$('#sharenivo-color-scheme').on('change', function () {
			if ($(this).val() === 'custom') {
				$('.sharenivo-custom-color-row').slideDown(200);
			} else {
				$('.sharenivo-custom-color-row').slideUp(200);
			}
		}).trigger('change');

		/* ── Position Toggle ── */
		$('select[name="sharenivo_settings[position]"]').on('change', function () {
			var val = $(this).val();
			if (val === 'inline_top' || val === 'inline_bottom') {
				$('.sharenivo-inline-alignment-row').slideDown(200);
			} else {
				$('.sharenivo-inline-alignment-row').slideUp(200);
			}
		});

		/* ── Share Count Sub-rows ── */
		var $shareCountCheckbox = $('input[name="sharenivo_settings[show_share_counts]"]');
		var $shareCountRows = $('select[name="sharenivo_settings[share_count_mode]"], input[name="sharenivo_settings[share_count_cache_ttl]"], input[name="sharenivo_settings[sharedcount_api_key]"]').closest('.sharenivo-field-row');

		function toggleShareCountRows() {
			if ($shareCountCheckbox.is(':checked')) {
				$shareCountRows.slideDown(200);
			} else {
				$shareCountRows.slideUp(200);
			}
		}

		$shareCountCheckbox.on('change', toggleShareCountRows);
		toggleShareCountRows();

		/* ── Network Chip Toggles ── */
		// Sync checked state to chip visual state
		function syncChips() {
			$('.sharenivo-network-chip').each(function () {
				var $chip = $(this);
				var $checkbox = $chip.find('input[type="checkbox"]');
				if ($checkbox.is(':checked')) {
					$chip.addClass('sharenivo-chip-active');
				} else {
					$chip.removeClass('sharenivo-chip-active');
				}
			});
		}

		$(document).on('click', '.sharenivo-network-chip', function (event) {
			var $chip = $(this);
			var $checkbox = $chip.find('input[type="checkbox"]');

			// The browser toggles a label's input automatically. Only toggle
			// manually when the user clicked the non-input portion of the chip.
			if ($(event.target).is('input')) {
				syncChips();
				rebuildOrderList();
				return;
			}

			event.preventDefault();
			$checkbox.prop('checked', !$checkbox.is(':checked'));
			syncChips();
			rebuildOrderList();
		});

		syncChips();

		/* ── Network Order Controls ── */
		var $orderList = $('#sharenivo-network-order-list');
		var $orderInput = $('#sharenivo-network-order');
		var $networkCheckboxes = $('.sharenivo-network-checkbox');
		var $moreButtonRow = $('.sharenivo-more-button-row');

		function createOrderItem(network, label) {
			return $('<li/>', { 'data-network': network })
				.append($('<span/>', { 'class': 'sharenivo-order-drag', html: '&#9783;' }))
				.append($('<span/>', { 'class': 'sharenivo-order-label', text: label }))
				.append(
					$('<button/>', {
						type: 'button',
						'class': 'button button-small sharenivo-order-up',
						'aria-label': 'Move up',
						html: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>'
					})
				)
				.append(
					$('<button/>', {
						type: 'button',
						'class': 'button button-small sharenivo-order-down',
						'aria-label': 'Move down',
						html: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
					})
				);
		}

		function getSelectedNetworksMap() {
			var map = {};
			$networkCheckboxes.each(function () {
				var $cb = $(this);
				if ($cb.is(':checked')) {
					map[$cb.val()] = $cb.data('networkLabel') || $.trim($cb.closest('.sharenivo-network-chip').find('.sharenivo-chip-text').text()) || $.trim($cb.parent().text());
				}
			});
			return map;
		}

		function syncOrderInput() {
			var order = [];
			$orderList.find('li').each(function () {
				order.push($(this).data('network'));
			});
			$orderInput.val(order.join(','));
		}

		function rebuildOrderList() {
			if (!$orderList.length) {
				return;
			}

			var selectedMap = getSelectedNetworksMap();
			var selectedKeys = Object.keys(selectedMap);
			var existingOrder = [];

			$orderList.find('li').each(function () {
				var key = $(this).data('network');
				if (selectedMap[key]) {
					existingOrder.push(key);
				}
			});

			selectedKeys.forEach(function (key) {
				if (existingOrder.indexOf(key) === -1) {
					existingOrder.push(key);
				}
			});

			$orderList.empty();
			existingOrder.forEach(function (key) {
				$orderList.append(createOrderItem(key, selectedMap[key]));
			});

			syncOrderInput();
			toggleMoreButtonRow();
		}

		function toggleMoreButtonRow() {
			if (!$moreButtonRow.length) {
				return;
			}

			var count = 0;
			$networkCheckboxes.each(function () {
				if ($(this).is(':checked')) {
					count++;
				}
			});

			if (count > 4) {
				$moreButtonRow.show();
			} else {
				$moreButtonRow.hide();
			}
		}

		$orderList.on('click', '.sharenivo-order-up', function () {
			var $item = $(this).closest('li');
			var $prev = $item.prev();
			if ($prev.length) {
				$item.insertBefore($prev);
				syncOrderInput();
				// Quick flash animation
				$item.css('background', 'rgba(99,91,255,0.08)');
				setTimeout(function () {
					$item.css('background', '');
				}, 300);
			}
		});

		$orderList.on('click', '.sharenivo-order-down', function () {
			var $item = $(this).closest('li');
			var $next = $item.next();
			if ($next.length) {
				$item.insertAfter($next);
				syncOrderInput();
				$item.css('background', 'rgba(99,91,255,0.08)');
				setTimeout(function () {
					$item.css('background', '');
				}, 300);
			}
		});

		$networkCheckboxes.on('change', rebuildOrderList);
		rebuildOrderList();

		/* ── Shortcode Copy Button ── */
		$(document).on('click', '.sharenivo-copy-btn', function () {
			var text = '[sharenivo_share]';
			var $btn = $(this);

			// Show copied state visually
			var originalHtml = $btn.html();
			$btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!');

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					setTimeout(function () {
						$btn.html(originalHtml);
					}, 2000);
				});
			} else {
				// Fallback
				var $temp = $('<input>').val(text).appendTo('body').select();
				document.execCommand('copy');
				$temp.remove();
				setTimeout(function () {
					$btn.html(originalHtml);
				}, 2000);
			}
		});

		/* ── Save Button Loading State & AJAX ── */
		$('.sharenivo-form-wrap').on('submit', function (e) {
			e.preventDefault();
			var $form = $(this);
			var $btn = $('.sharenivo-save-btn');
			var $spinner = $btn.find('.sharenivo-spinner');

			$btn.css({
				'opacity': '0.7',
				'pointer-events': 'none'
			});
			if ($spinner.length) {
				$spinner.css('display', 'inline-block');
			}

			// Remove any existing notices
			$('.sharenivo-notice').remove();

			var formData = $form.serialize() + '&action=sharenivo_save_settings_action';

			$.post(sharenivo_admin_obj.ajax_url, formData, function (response) {
				$btn.css({
					'opacity': '1',
					'pointer-events': 'all'
				});
				if ($spinner.length) {
					$spinner.css('display', 'none');
				}

				var messageHtml = '';
				var isSuccess = response.success;
				var messageClass = isSuccess ? 'sharenivo-success' : 'sharenivo-error';
				var messageText = response.data && response.data.message ? response.data.message : (isSuccess ? 'Settings saved.' : 'An error occurred.');

				var $notice = $('<div/>', {
					'class': 'sharenivo-notice ' + messageClass,
					'role': 'status',
					'aria-live': 'polite',
					'style': 'display:none;'
				}).append($('<p/>', { text: messageText }));
				$('.sharenivo-page-header').after($notice);
				$notice.slideDown(200);

				setTimeout(function () {
					$notice.slideUp(200, function () {
						$(this).remove();
					});
				}, 5000);
			}).fail(function () {
				$btn.css({
					'opacity': '1',
					'pointer-events': 'all'
				});
				if ($spinner.length) {
					$spinner.css('display', 'none');
				}

				var $error = $('<div/>', {
					'class': 'sharenivo-notice sharenivo-error',
					'role': 'alert',
					'style': 'display:none;'
				}).append($('<p/>', { text: 'Network error while saving. Please try again.' }));
				$('.sharenivo-page-header').after($error);
				$error.slideDown(200);

				setTimeout(function () {
					$error.slideUp(200, function () {
						$(this).remove();
					});
				}, 5000);
			});
		});

	});

})(jQuery);
