
	var cms_blocks = new function () {

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

					// console.log('cms-text.js: Initialisation');

				//--------------------------------------------------
				// Setup the edit links

					var matches = document.querySelectorAll('XXX');

					for (var k = (matches.length - 1); k >= 0; k--) {
						cms_text.setupEditLink(matches[k]);
					}

			}

		//--------------------------------------------------
		// Setup the edit links

			this.setupEditLink = function (area) {
			}

		//--------------------------------------------------
		// Class to show the JS is active

			document.getElementsByTagName('html')[0].className += ' cms_blocks_js';

		//--------------------------------------------------
		// When the page has loaded, run the init function

			document.addEventListener('DOMContentLoaded', this.init, {'once': 1});

	}
