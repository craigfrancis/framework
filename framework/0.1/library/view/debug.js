
if (document.addEventListener) {

	document.addEventListener('DOMContentLoaded', function() {

			var body = document.getElementsByTagName('body');
			if (body[0]) {
				var wrapper = document.createElement('div');
				wrapper.innerHTML = debug_html;
				body[0].appendChild(wrapper);
			}

		});

}
