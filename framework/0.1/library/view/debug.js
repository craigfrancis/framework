
;(function(document, window, undefined) {

	'use strict';

	if (!document.addEventListener || !document.querySelector || !window.XMLHttpRequest || !window.JSON) {
		return;
	}

	var debug_script = document.currentScript,
		debug_setup_count = 0,
		debug_urgent_list,
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

	function debug_urgent_error(type, message) {
		if (!debug_urgent_list) {
			debug_urgent_list = document.createElement('ol');
			debug_urgent_list.setAttribute('id', 'debug_urgent_list');
			document.body.insertBefore(debug_urgent_list, document.body.firstChild);
		}
		var li = document.createElement('li');
		li.setAttribute('data-type', type);
		li.textContent = message;
		debug_urgent_list.appendChild(li);
	}

	function debug_reporting_callback(reports, observer) {
		var message;
		for (var k = 0, l = reports.length; k < l; k++) {
			message = reports[k].body.message;
			// if (reports[k].body.lineNumber) {
			// 	message = 'Line ' + reports[k].body.lineNumber + ': ' + message;
			// }
			debug_urgent_error(reports[k].type, message);
		}
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
			for (var content_count = 0, content_length = contents.length; content_count < content_length; content_count++) {
				element = document.createElement(contents[content_count][0]);
				element.textContent = contents[content_count][1];
				if (contents[content_count][2]) {
					element.setAttribute('class', contents[content_count][2]);
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

		for (var note_count = 0, note_length = notes.length; note_count < note_length; note_count++) {

			note = notes[note_count];

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
				if (note.elapsed) {
					note_content.appendChild(document.createTextNode(note.elapsed + ' - '));
				}
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
				for (var line_count = 0, line_length = note.lines.length; line_count < line_length; line_count++) {
					note_item = document.createElement('li');
					debug_build_item(note_item, note.lines[line_count])
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
				for (var table_count = 0, table_length = note.table.length; table_count < table_length; table_count++) {
					row = note.table[table_count];
					if (table_count == 0) {
						tr = document.createElement('tr');
						for (var field in row) {
							if (!row.hasOwnProperty(field)) continue;
							td = document.createElement('th');
							td.textContent = field;
							tr.appendChild(td);
						}
						note_content.appendChild(tr);
					}
					tr = document.createElement('tr');
					for (var field in row) {
						if (!row.hasOwnProperty(field)) continue;
						td = document.createElement('td');
						if (field == 'type') {
							link = document.createElement('a');
							link.setAttribute('href', (debug_mysql_url + row[field]));
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
				for (var list_count = 0, list_length = note.list.length; list_count < list_length; list_count++) {
					note_item = document.createElement('li');
					debug_build_item(note_item, note.list[list_count])
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

			var body = document.getElementsByTagName('body'),
				output,
				ref,
				wrapper,
				link,
				links = {};

		//--------------------------------------------------
		// Body, where IE can sometimes fail to find.

			if (body.length > 0) {
				body = body[0];
			} else {
				if (debug_setup_count++ < 2) {
					setTimeout(debug_setup, 100);
				}
				return;
			}

		//--------------------------------------------------
		// Group by type

			for (var note_count = 0, note_length = debug_data.notes.length; note_count < note_length; note_count++) {
				debug_types[debug_data.notes[note_count]['type']]['notes'].push(debug_data.notes[note_count]);
			}

		//--------------------------------------------------
		// Output by type

			output = document.createElement('div');

			debug_links = document.createElement('p');
			debug_links.setAttribute('id', 'debug_links');

			for (var k in debug_types) {
				if (!debug_types.hasOwnProperty(k)) continue;
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
						link.setAttribute('href', '#debug_notes_' + ref);
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

		//--------------------------------------------------
		// Reporting Observer

			if (window.ReportingObserver) {

				new ReportingObserver(debug_reporting_callback, {
						'types': [
								'deprecation',
								'intervention',
								'permissions-policy-violation'
							],
						'buffered': true
					}).observe();

			}

		//--------------------------------------------------
		// API Data

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
		document.addEventListener('DOMContentLoaded', init, {'once': 1});
	}

})(document, window);
