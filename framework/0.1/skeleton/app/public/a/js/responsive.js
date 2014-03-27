
	(function() {

		//--------------------------------------------------
		// Start

			if (!window.addEventListener) {
				return false;
			}

			var bodyRef,
				headingLink,
				headingUrl,
				navigationRef;

		//--------------------------------------------------
		// Navigation

			function navigationToggle() {

				if (window.matchMedia('only screen and (min-width: 480px)').matches) {
					window.location = headingUrl;
					return false;
				}

				var currentDisplay = window.getComputedStyle(navigationRef).getPropertyValue('display');
				if (currentDisplay == 'none') {
					bodyRef.classList.add('nav_show');
				} else {
					bodyRef.classList.remove('nav_show');
				}

			}

			function navigationToggleEvent(e) {
				navigationToggle();
				e.preventDefault();
			}

		//--------------------------------------------------
		// Init

			window.addEventListener('DOMContentLoaded', function() {

				bodyRef = document.querySelector('body');
				headingLink = document.querySelector('#page_header a');
				headingUrl = headingLink.getAttribute('href');
				navigationRef = document.getElementById('page_navigation');

				if (navigationRef && headingLink) {

					headingLink.removeAttribute('href'); // Disable default from iPhone.js

					headingLink.addEventListener('touchstart', navigationToggleEvent, false);
					headingLink.addEventListener('mousedown', navigationToggleEvent, false);

					headingLink.style.cursor = 'pointer';

				}

				if (('standalone' in window.navigator) && window.navigator.standalone) {

					var a = document.getElementsByTagName('a');
					for (var i = 0; i < a.length; i++) {
						a[i].addEventListener('click', function(e) {
								var url = this.getAttribute('href');
								if (url !== null) {
									window.location = url;
								}
								e.preventDefault();
							}, false);
					}

				}

			}, false);

	})();
