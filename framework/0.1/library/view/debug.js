
;(function(document, window, undefined) {

	'use strict';

	if (!document.addEventListener || !document.querySelector || !window.XMLHttpRequest || !window.JSON) {
		return;
	}

	var debug_script = document.currentScript,
		debug_data,
		debug_open,
		debug_wrapper,
		debug_links,
		debug_mysql_url = 'https://dev.mysql.com/doc/refman/8.0/en/explain-output.html#jointype_',
		debug_types = {
				'C': {'name': 'Config', 'notes': []},
				'H': {'name': 'Help',   'notes': []},
				'L': {'name': 'Log',    'notes': []}
			};

	var trusted_types = {
			'createURL': function (s) {
					if (['c','h','l'].indexOf(s) >= 0) { // Not -1
						return '#debug_notes_' + s;
					} else if (s.substring(0, debug_mysql_url.length) == debug_mysql_url) {
						return s;
					} else {
						return '#';
					}
				}
		};

	if (window.TrustedTypes) {
		trusted_types = TrustedTypes.createPolicy('debug', trusted_types);
	}

	function debug_open_click(e) {

		if (debug_open && debug_open !== this) {
			debug_open.debugOutput.setAttribute('class', 'debug_notes');
			debug_open.setAttribute('class', 'debug_link');
		}

		var o = (this.debugOutput.getAttribute('class').indexOf('debug_notes_open') > -1);
		this.setAttribute('class', (o ? 'debug_link' : 'debug_link debug_link_open'));
		this.debugOutput.setAttribute('class', (o ? 'debug_notes' : 'debug_notes debug_notes_open'));
		this.debugOutput.style.minHeight = (window.innerHeight - debug_links.offsetHeight) + 'px';
		this.scrollIntoView();
		debug_open = this;

		e.preventDefault();

	}

	function debug_close_click() {
		debug_wrapper.parentNode.removeChild(debug_wrapper);
	}

	function debug_build_item(item, contents) {
		var element;
		if (typeof contents === 'object') {
			for (var k = 0, l = contents.length; k < l; k++) {
				element = document.createElement(contents[k][0]);
				element.textContent = contents[k][1];
				if (contents[k][2]) {
					element.setAttribute('class', contents[k][2]);
				}
				item.appendChild(element);
			}
		} else {
			item.textContent = contents;
		}
	}

	function debug_build(wrapper, notes) {

		var note,
			note_class,
			note_div,
			note_content,
			note_item,
			note_node;

		for (var k in notes) {

			note = notes[k];

			note_class = 'note';
			if (note.class) {
				note_class += ' ' + note.class;
			}
			if (note.file || note.heading) {
				note_class += ' note_heading';
			}

			note_div = document.createElement('div');
			note_div.setAttribute('class', note_class);
			note_div.style.background = note.colour;

			if (note.heading) {
				note_content = document.createElement('h2');
				note_content.textContent = note.heading;
				if (note.heading_extra) {
					note_node = document.createElement('span');
					note_node.textContent = ': ' + note.heading_extra;
					note_content.appendChild(note_node);
				}
				note_div.appendChild(note_content);
			}

			if (note.file) {
				note_node = document.createElement('strong');
				note_node.textContent = note.file.path;
				note_content = document.createElement('div');
				note_content.setAttribute('class', 'note_file');
				note_content.appendChild(note_node);
				note_content.appendChild(document.createTextNode(' (line ' + note.file.line + ')'));
				note_div.appendChild(note_content);
			}

			if (note.text) {
				note_content = document.createElement('div');
				note_content.setAttribute('class', 'note_text');
				debug_build_item(note_content, note.text)
				note_div.appendChild(note_content);
			}

			if (typeof note.lines === 'object' && note.lines !== null) {
				note_content = document.createElement('ul');
				note_content.setAttribute('class', 'note_lines');
				for (var k = 0, l = note.lines.length; k < l; k++) {
					note_item = document.createElement('li');
					debug_build_item(note_item, note.lines[k])
					note_content.appendChild(note_item);
				}
				if (note.lines.length == 0) {
					note_item = document.createElement('li');
					note_item.textContent = (note.lines_empty ? note.lines_empty : 'none');
					note_content.appendChild(note_item);
				}
				note_div.appendChild(note_content);
			}

			if (note.time) {
				note_content = document.createElement('div');
				note_content.setAttribute('class', 'note_time');
				note_content.appendChild(document.createTextNode('Time: ' + note.time));
				note_div.appendChild(note_content);
			}

			if (note.rows) {
				note_content = document.createElement('div');
				note_content.setAttribute('class', 'note_rows');
				note_content.appendChild(document.createTextNode('Rows: ' + note.rows));
				note_div.appendChild(note_content);
			}

			if (note.table) {
				note_content = document.createElement('table');
				note_content.setAttribute('class', 'note_table');
				var tr, td, row, link;
				for (var k = 0, l = note.table.length; k < l; k++) {
					row = note.table[k];
					if (k == 0) {
						tr = document.createElement('tr');
						for (var field in row) {
							td = document.createElement('th');
							td.textContent = field;
							tr.appendChild(td);
						}
						note_content.appendChild(tr);
					}
					tr = document.createElement('tr');
					for (var field in row) {
						td = document.createElement('td');
						if (field == 'type') {
							link = document.createElement('a');
							link.setAttribute('href', trusted_types.createURL(debug_mysql_url + row[field]));
							link.setAttribute('target', '_blank');
							link.setAttribute('rel', 'noopener');
							link.textContent = row[field];
							td.appendChild(link);
						} else {
							if (field == 'possible_keys' && row[field]) {
								row[field] = row[field].replace(/,/g, ', ');
							}
							td.textContent = row[field];
						}
						tr.appendChild(td);
					}
					note_content.appendChild(tr);
				}
				note_div.appendChild(note_content);
			}

			if (note.list && note.list.length > 0) {
				note_content = document.createElement('ul');
				note_content.setAttribute('class', 'note_list');
				for (var k = 0, l = note.list.length; k < l; k++) {
					note_item = document.createElement('li');
					debug_build_item(note_item, note.list[k])
					note_content.appendChild(note_item);
				}
				note_div.appendChild(note_content);
			}

			wrapper.appendChild(note_div);

		}

	}

	function debug_setup() {

		//--------------------------------------------------
		// Variables

			var body = document.getElementsByTagName('body')[0],
				output,
				ref,
				wrapper,
				link,
				links = {};

		//--------------------------------------------------
		// Group by type

			for (var k in debug_data.notes) {
				debug_types[debug_data.notes[k]['type']]['notes'].push(debug_data.notes[k]);
			}

		//--------------------------------------------------
		// Output by type

			output = document.createElement('div');

			debug_links = document.createElement('p');
			debug_links.setAttribute('id', 'debug_links');

			for (var k in debug_types) {
				if (debug_types[k]['notes'].length > 0) {

					//--------------------------------------------------
					// Ref

						ref = k.toLowerCase();

					//--------------------------------------------------
					// Notes

						wrapper = document.createElement('div');
						wrapper.setAttribute('class', 'debug_notes');
						wrapper.setAttribute('id', 'debug_notes_' + ref);

						debug_build(wrapper, debug_types[k]['notes']);

						output.appendChild(wrapper);

					//--------------------------------------------------
					// Link

						link = document.createElement('a');
						link.appendChild(document.createTextNode(k));
						link.debugOutput = wrapper;
						link.setAttribute('class', 'debug_link');
						link.setAttribute('title', debug_types[k]['name']);
						link.setAttribute('href', trusted_types.createURL(ref));
						link.addEventListener('click', debug_open_click);

						links[k] = link;

						debug_links.appendChild(link);

				}
			}

		//--------------------------------------------------
		// Build

			var time_text = document.createElement('span');
			time_text.setAttribute('class', 'debug_time' + (debug_data.time > 0.1 ? ' debug_slow' : ''));
			time_text.addEventListener('dblclick', debug_close_click);
			time_text.appendChild(document.createTextNode(' - ' + debug_data.time));
			debug_links.appendChild(time_text);

			debug_wrapper = document.createElement('div');
			debug_wrapper.setAttribute('id', 'debug_output');
			debug_wrapper.appendChild(debug_links);
			debug_wrapper.appendChild(output);

			body.appendChild(debug_wrapper);

			// links['H'].click();

	}

	function init() {

		var api_url = (debug_script ? debug_script : document.querySelector('script[src$="debug.js"][data-api]'));
		if (api_url) {
			api_url = api_url.getAttribute('data-api');
		}
		if (api_url) {

			var debug_xhr = new XMLHttpRequest();
			debug_xhr.open('GET', api_url, true);
			debug_xhr.onreadystatechange = function() {
				if (this.readyState == 4) {
					var response;
					if (this.status == 200) {
						try {
							debug_data = JSON.parse(debug_xhr.responseText);
						} catch (e) {
							debug_data = null;
						}
					}
					if (debug_data) {
						debug_setup();
					}
				}
			}
			debug_xhr.send();

		}

	}

	if (document.readyState !== 'loading') {
		window.setTimeout(init); // Handle asynchronously
	} else {
		document.addEventListener('DOMContentLoaded', init);
	}

})(document, window);
