/**
 * ShareNivo frontend interactions. No network requests are made by this file.
 */
(function () {
	'use strict';

	var config = window.sharenivoPublic || {};
	var lastFocused = null;

	function closest(element, selector) {
		return element && element.closest ? element.closest(selector) : null;
	}

	function closeMoreMenus(except) {
		document.querySelectorAll('.sharenivo-button--more[aria-expanded="true"]').forEach(function (button) {
			if (except === button) {
				return;
			}
			button.setAttribute('aria-expanded', 'false');
			var panel = document.getElementById(button.getAttribute('aria-controls'));
			if (panel) {
				panel.hidden = true;
			}
		});
	}

	function fallbackCopy(value) {
		var input = document.createElement('textarea');
		input.value = value;
		input.setAttribute('readonly', '');
		input.style.position = 'fixed';
		input.style.opacity = '0';
		document.body.appendChild(input);
		input.select();
		var copied = document.execCommand('copy');
		document.body.removeChild(input);
		return copied ? Promise.resolve() : Promise.reject();
	}

	function copyLink(button) {
		var value = button.getAttribute('data-sharenivo-copy');
		var share = closest(button, '.sharenivo-share');
		var status = share ? share.querySelector('.sharenivo-copy-status') : null;
		var promise = navigator.clipboard && navigator.clipboard.writeText ? navigator.clipboard.writeText(value) : fallbackCopy(value);
		promise.then(function () {
			button.classList.add('is-copied');
			if (status) {
				status.textContent = config.copySuccess || 'Link copied.';
			}
			window.setTimeout(function () {
				button.classList.remove('is-copied');
				if (status) {
					status.textContent = '';
				}
			}, 2200);
		}).catch(function () {
			if (status) {
				status.textContent = config.copyError || 'Copy failed.';
			}
		});
	}

	function storageFor(frequency) {
		return frequency === 'session' ? window.sessionStorage : window.localStorage;
	}

	function promptKey(prompt) {
		return 'sharenivo:' + prompt.getAttribute('data-sharenivo-prompt') + ':' + window.location.pathname;
	}

	function promptIsDue(prompt) {
		var frequency = prompt.getAttribute('data-frequency') || 'session';
		if (frequency === 'always') {
			return true;
		}
		try {
			var value = parseInt(storageFor(frequency).getItem(promptKey(prompt)), 10);
			if (!value) {
				return true;
			}
			var age = Date.now() - value;
			return frequency === 'day' ? age >= 86400000 : (frequency === 'week' ? age >= 604800000 : false);
		} catch (error) {
			return true;
		}
	}

	function markPromptSeen(prompt) {
		var frequency = prompt.getAttribute('data-frequency') || 'session';
		if (frequency === 'always') {
			return;
		}
		try {
			storageFor(frequency).setItem(promptKey(prompt), String(Date.now()));
		} catch (error) {
			// Storage may be blocked; the prompt still works for this page view.
		}
	}

	function focusableInside(element) {
		return Array.prototype.slice.call(element.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'));
	}

	function showPrompt(prompt) {
		if (prompt.classList.contains('is-visible') || !promptIsDue(prompt)) {
			return;
		}
		lastFocused = document.activeElement;
		prompt.hidden = false;
		window.requestAnimationFrame(function () {
			prompt.classList.add('is-visible');
			var dialog = prompt.querySelector('[role="dialog"]');
			var close = prompt.querySelector('[data-sharenivo-close]');
			if (dialog) {
				dialog.focus();
			} else if (close) {
				close.focus();
			}
		});
		markPromptSeen(prompt);
	}

	function hidePrompt(prompt) {
		if (!prompt) {
			return;
		}
		prompt.classList.remove('is-visible');
		window.setTimeout(function () { prompt.hidden = true; }, 220);
		if (lastFocused && lastFocused.focus) {
			lastFocused.focus();
		}
	}

	function setupTrigger(prompt) {
		if (!promptIsDue(prompt)) {
			return;
		}
		var trigger = prompt.getAttribute('data-trigger') || 'scroll';
		var value = Math.max(1, parseInt(prompt.getAttribute('data-trigger-value'), 10) || 1);
		var handler;

		if (trigger === 'delay') {
			window.setTimeout(function () { showPrompt(prompt); }, value * 1000);
			return;
		}
		if (trigger === 'comment') {
			if (window.location.hash.indexOf('#comment-') === 0 || window.location.search.indexOf('unapproved=') !== -1) {
				showPrompt(prompt);
			}
			return;
		}
		if (trigger === 'purchase') {
			if (document.body.classList.contains('woocommerce-order-received')) {
				showPrompt(prompt);
			}
			return;
		}
		if (trigger === 'exit') {
			document.addEventListener('mouseout', function (event) {
				if (!event.relatedTarget && event.clientY <= 0) {
					showPrompt(prompt);
				}
			}, {passive: true});
			return;
		}
		if (trigger === 'inactivity') {
			var timer;
			var restart = function () {
				window.clearTimeout(timer);
				timer = window.setTimeout(function () { showPrompt(prompt); }, value * 1000);
			};
			['mousemove', 'keydown', 'touchstart', 'scroll'].forEach(function (eventName) {
				document.addEventListener(eventName, restart, {passive: true});
			});
			restart();
			return;
		}

		handler = function () {
			var documentHeight = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight) - window.innerHeight;
			var progress = documentHeight > 0 ? (window.scrollY / documentHeight) * 100 : 100;
			var article = document.querySelector('article, main');
			var reachedBottom = article ? article.getBoundingClientRect().bottom <= window.innerHeight + 40 : progress >= 95;
			if ((trigger === 'bottom' && reachedBottom) || (trigger === 'scroll' && progress >= value)) {
				window.removeEventListener('scroll', handler);
				showPrompt(prompt);
			}
		};
		window.addEventListener('scroll', handler, {passive: true});
		handler();
	}

	function setupMediaTools() {
		var template = document.getElementById('sharenivo-media-template');
		if (!template || !config.media || !config.media.enabled) {
			return;
		}
		var minimum = parseInt(config.media.minWidth, 10) || 300;
		var pageUrl = window.location.href.split('#')[0];
		var title = document.title;

		document.querySelectorAll('.entry-content img, .wp-block-post-content img, article img').forEach(function (image) {
			var attach = function () {
				if (image.dataset.sharenivoReady || Math.max(image.naturalWidth, image.clientWidth) < minimum || closest(image, '.sharenivo-media-tools')) {
					return;
				}
				var host = closest(image, 'figure') || image.parentElement;
				if (!host) {
					return;
				}
				host.classList.add('sharenivo-media-host');
				var tools = template.content.firstElementChild.cloneNode(true);
				tools.querySelectorAll('[data-sharenivo-copy]').forEach(function (button) {
					button.setAttribute('data-sharenivo-copy', pageUrl);
				});
				var pinterest = tools.querySelector('[data-sharenivo-network="pinterest"]');
				if (pinterest) {
					pinterest.href = 'https://www.pinterest.com/pin/create/button/?url=' + encodeURIComponent(pageUrl) + '&media=' + encodeURIComponent(image.currentSrc || image.src) + '&description=' + encodeURIComponent(title);
				}
				host.appendChild(tools);
				image.dataset.sharenivoReady = '1';
			};
			if (image.complete) {
				attach();
			} else {
				image.addEventListener('load', attach, {once: true});
			}
		});
	}

	document.addEventListener('click', function (event) {
		var more = closest(event.target, '.sharenivo-button--more');
		if (more) {
			event.preventDefault();
			var open = more.getAttribute('aria-expanded') === 'true';
			closeMoreMenus(more);
			more.setAttribute('aria-expanded', open ? 'false' : 'true');
			var panel = document.getElementById(more.getAttribute('aria-controls'));
			if (panel) {
				panel.hidden = open;
			}
			return;
		}
		var copy = closest(event.target, '[data-sharenivo-copy]');
		if (copy) {
			event.preventDefault();
			copyLink(copy);
			return;
		}
		var close = closest(event.target, '[data-sharenivo-close]');
		if (close) {
			hidePrompt(closest(close, '[data-sharenivo-prompt]'));
			return;
		}
		if (!closest(event.target, '.sharenivo-share__more')) {
			closeMoreMenus();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeMoreMenus();
			document.querySelectorAll('[data-sharenivo-prompt].is-visible').forEach(hidePrompt);
		}
		if (event.key === 'Tab') {
			var popup = document.querySelector('.sharenivo-prompt--popup.is-visible');
			if (!popup) {
				return;
			}
			var focusable = focusableInside(popup);
			if (!focusable.length) {
				return;
			}
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});

	document.querySelectorAll('[data-sharenivo-prompt]').forEach(setupTrigger);
	setupMediaTools();
}());
