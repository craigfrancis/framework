
	var cms_admin = new function () {

		//--------------------------------------------------
		// Do not allow older browsers to run this script

			if (!document.addEventListener || !document.querySelectorAll) {
				return;
			}

		//--------------------------------------------------
		// Initialisation function used for setup

			this.init = function () {

				//--------------------------------------------------
				// Debug

					//console.log('cms-admin.js: Initialisation');

				//--------------------------------------------------
				// Setup the edit links

					var matches = document.querySelectorAll('span.cms_admin_editable, div.cms_admin_editable');

					for (var k = (matches.length - 1); k >= 0; k--) {
						cms_admin.setupEditLink(matches[k]);
					}

			}

		//--------------------------------------------------
		// Setup the edit links

			this.setupEditLink = function (area) {

				//--------------------------------------------------
				// Get the edit link

					var link = area.querySelector('a.cms_admin_link');

					if (link === null) {
						//console.log('cms-admin.js: Link not found');
						return;
					}

				//--------------------------------------------------
				// Link configuration

					area.cms_admin_link = link;

					try {
						area.style.cursor = 'pointer';
					} catch (e) {
						try {
							area.style.cursor = 'hand';
						} catch (e) {
						}
					}

					area.onclick = function(e) {

							//--------------------------------------------------
							// Stop event propagation

								if (!e) var e = window.event;
								e.cancelBubble = true;
								if (e.stopPropagation) e.stopPropagation();

							//--------------------------------------------------
							// Load URL

								window.location.href = this.cms_admin_link.href;

						};

					area.onkeypress = function (e) {
							var keyCode = e ? e.which : window.event.keyCode;
							if (keyCode != 13 && keyCode != 32) return true;
							this.onclick();
							return false;
						};

				//--------------------------------------------------
				// Disable tab functionality... as this is an admin
				// feature, with known users (who do not know about
				// tabbing between links), this will be disabled to
				// help with tabbing between form elements.

					area.tabIndex = -1;
					link.tabIndex = -1;

			}

		//--------------------------------------------------
		// Helper functions

			this.has_class = function (el, css_class) {
				return el.className && new RegExp('\\b' + css_class + '\\b').test(el.className);
			}

		//--------------------------------------------------
		// Class to show the JS is active

			document.getElementsByTagName('html')[0].className += ' cms_admin_js';

		//--------------------------------------------------
		// When the page has loaded, run the init function

			document.addEventListener('DOMContentLoaded', this.init, false);

	}
