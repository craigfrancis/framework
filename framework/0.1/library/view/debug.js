
(function() {

	"use strict";

	var debug_open, debug_links;

	function debug_setup() {

		//--------------------------------------------------
		// Variables

			var body,
				debug_types,
				wrapper,
				output,
				ref,
				link,
				notes,
				note,
				note_wrapper,
				note_content,
				note_time,
				note_extra,
				k,
				j;

		//--------------------------------------------------
		// Body reference

			body = document.getElementsByTagName('body');
			if (body[0]) {
				body = body[0];
			} else {
				return;
			}

		//--------------------------------------------------
		// Group by type

			debug_types = {
				'C': {'name': 'Config', 'notes': []},
				'H': {'name': 'Help',   'notes': []},
				'L': {'name': 'Log',    'notes': []}
			};

			for (k in debug_notes) {
				debug_types[debug_notes[k].type].notes.push(debug_notes[k]);
			}

		//--------------------------------------------------
		// Loading time

			debug_types['L'].notes.push({
					'type': 'L',
					'color': '#F00',
					'time': null,
					'html': debug_htmlencode(debug_time)
				});

		//--------------------------------------------------
		// Add to DOM

			wrapper = document.createElement('div');
			output = document.createElement('div');

			debug_links = document.createElement('p');
			debug_links.id = 'debug_links';

			wrapper.id = 'debug_wrapper';

			for (k in debug_types) {
				if (debug_types[k].notes.length > 0) {

					//--------------------------------------------------
					// Ref

						ref = k.toLowerCase();

					//--------------------------------------------------
					// Notes

						notes = document.createElement('div');
						notes.className = 'debug_output';
						notes.id = 'debug_output_' + ref;
						notes.style.display = 'none';

						for (j in debug_types[k].notes) {

							note = debug_types[k].notes[j];

							note_wrapper = document.createElement('div');
							note_content = document.createElement('div');

							note_wrapper.className = 'note';
							note_wrapper.style.background = note.colour;

							note_content.className = 'note_body';
							note_content.innerHTML = note.html;
							note_wrapper.appendChild(note_content);

							if (note.time !== null) {
								note_time = document.createElement('div');
								note_time.className = 'note_time';
								note_time.appendChild(document.createTextNode('Time Elapsed: ' + note.time));
								note_wrapper.appendChild(note_time);
							}

							if (note.extra_html && note.extra_html !== '') {
								note_extra = document.createElement('div');
								note_extra.className = 'note_extra';
								note_extra.innerHTML = note.extra_html;
								note_wrapper.appendChild(note_extra);
							}

							notes.appendChild(note_wrapper);

						}

						output.appendChild(notes);

					//--------------------------------------------------
					// Link

						link = document.createElement('a');
						link.appendChild(document.createTextNode('[' + k + ']'));
						link.className = 'debug_link';
						link.title = debug_types[k].name;
						link.href = '#debug_output_' + ref;
						link.debugOutput = notes;
						link.onclick = debug_open_link;

						debug_links.appendChild(link);

				}
			}

			wrapper.appendChild(debug_links);
			wrapper.appendChild(output);

			body.appendChild(wrapper);

	}

	function debug_htmlencode(text) {
		var e = document.createElement('div');
		e.innerText = text;
		return e.innerHTML;
	}

	function debug_open_link() {

		if (debug_open && debug_open !== this) {
			debug_open.debugOutput.style.display = 'none';
			debug_open.style.color = '#DDD';
		}

		var open = (this.debugOutput.style.display === 'block');
		this.style.color = (open ? '#DDD' : '#000');
		this.debugOutput.style.display = (open ? 'none' : 'block');
		this.debugOutput.style.minHeight = (window.innerHeight - debug_links.offsetHeight) + 'px';
		this.scrollIntoView();
		debug_open = this;

		return false;

	}

	if (document.readyState !== 'loading') {

		debug_setup();

	} else if (document.addEventListener) {

		document.addEventListener('DOMContentLoaded', debug_setup);

	}

}());
