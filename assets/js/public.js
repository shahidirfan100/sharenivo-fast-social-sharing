/**
 * ShareNivo - Public JavaScript
 */
(function () {
	'use strict';

	function setToggleState(toggleButton, panel, isOpen) {
		if (!toggleButton || !panel) {
			return;
		}

		toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		toggleButton.classList.toggle('is-open', isOpen);
		panel.hidden = !isOpen;
		panel.classList.toggle('is-open', isOpen);
	}

	function closeAllMorePanels(exceptWrapper) {
		var wrappers = document.querySelectorAll('.sharenivo-share-buttons');
		wrappers.forEach(function (wrapper) {
			if (exceptWrapper && wrapper === exceptWrapper) {
				return;
			}
			var panel = wrapper.querySelector('.sharenivo-more-networks');
			var toggle = wrapper.querySelector('.sharenivo-more-toggle');
			setToggleState(toggle, panel, false);
		});
	}

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('.sharenivo-more-toggle');
		if (toggle) {
			event.preventDefault();
			var wrapper = toggle.closest('.sharenivo-share-buttons');
			if (!wrapper) {
				return;
			}
			var panel = wrapper.querySelector('.sharenivo-more-networks');
			if (!panel) {
				return;
			}

			var isOpen = toggle.getAttribute('aria-expanded') === 'true';
			closeAllMorePanels(wrapper);
			setToggleState(toggle, panel, !isOpen);
			return;
		}

		if (!event.target.closest('.sharenivo-more-networks')) {
			closeAllMorePanels(null);
		}
	});
})();
