
;(function() {

	"use strict";

	var debug_open, debug_wrapper, debug_links;

	function debug_setup() {

		//--------------------------------------------------
		// Variables

			var body,
				debug_types,
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
		// Trusted types

			var debugTrustedTypes = {
					'createHTML': function (s) {
							return s; // Unsafe
						},
					'createURL': function (s) {
							if (['c','h','l'].indexOf(s) >= 0) { // Not -1
								return '#debug_notes_' + s;
							} else {
								return '#';
							}
						}
				};

			if (window.TrustedTypes) {
				debugTrustedTypes = TrustedTypes.createPolicy('debug', debugTrustedTypes);
			}

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
		// Add to DOM

			debug_wrapper = document.createElement('div');
			debug_wrapper.id = 'debug_output';

			output = document.createElement('div');

			debug_links = document.createElement('p');
			debug_links.id = 'debug_links';

			for (k in debug_types) {
				if (debug_types[k].notes.length > 0) {

					//--------------------------------------------------
					// Ref

						ref = k.toLowerCase();

					//--------------------------------------------------
					// Notes

						notes = document.createElement('div');
						notes.className = 'debug_notes';
						notes.id = 'debug_notes_' + ref;

						for (j in debug_types[k].notes) {

							note = debug_types[k].notes[j];

							note_wrapper = document.createElement('div');
							note_content = document.createElement('div');

							note_wrapper.className = 'note';
							note_wrapper.style.background = note.colour;

							note_content.className = 'note_body';
							note_content.innerHTML = debugTrustedTypes.createHTML(note.html);
							note_wrapper.appendChild(note_content);

							if (note.time !== null) {
								note_time = document.createElement('div');
								note_time.className = 'note_time';
								note_time.appendChild(document.createTextNode('Time: ' + note.time));
								note_wrapper.appendChild(note_time);
							}

							if (note.extra_html && note.extra_html !== '') {
								note_extra = document.createElement('div');
								note_extra.className = 'note_extra';
								note_extra.innerHTML = debugTrustedTypes.createHTML(note.extra_html);
								note_wrapper.appendChild(note_extra);
							}

							notes.appendChild(note_wrapper);

						}

						output.appendChild(notes);

					//--------------------------------------------------
					// Link

						link = document.createElement('a');
						link.appendChild(document.createTextNode(k));
						link.debugOutput = notes;
						link.setAttribute('class', 'debug_link');
						link.setAttribute('title', debug_types[k].name);
						link.setAttribute('href', debugTrustedTypes.createURL(ref));
						link.addEventListener('click', debug_open_link);

						debug_links.appendChild(link);

				}
			}

			var time_text = document.createElement('span');
			time_text.setAttribute('class', 'debug_time' + (debug_time > 0.1 ? ' debug_slow' : ''));
			time_text.addEventListener('dblclick', debug_close);
			time_text.appendChild(document.createTextNode(' - ' + debug_time));
			debug_links.appendChild(time_text);

			debug_wrapper.appendChild(debug_links);
			debug_wrapper.appendChild(output);

			body.appendChild(debug_wrapper);

	}

	function debug_close() {
		debug_wrapper.parentNode.removeChild(debug_wrapper);
	}

	function debug_open_link(e) {

		if (debug_open && debug_open !== this) {
			debug_open.debugOutput.className = 'debug_notes';
			debug_open.className = 'debug_link';
		}

		var o = (this.debugOutput.className.indexOf('debug_notes_open') > -1);
		this.className = (o ? 'debug_link' : 'debug_link debug_link_open');
		this.debugOutput.className = (o ? 'debug_notes' : 'debug_notes debug_notes_open');
		this.debugOutput.style.minHeight = (window.innerHeight - debug_links.offsetHeight) + 'px';
		this.scrollIntoView();
		debug_open = this;

		e.preventDefault();

	}

	if (document.readyState !== 'loading') {

		debug_setup();

	} else if (document.addEventListener) {

		document.addEventListener('DOMContentLoaded', debug_setup);

	}

}());
