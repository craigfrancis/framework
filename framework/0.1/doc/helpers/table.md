
# Table helper

To see some how the table helper can be used, look at the [examples](/examples/table/).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/main/framework/0.1/library/class/table.php).

---

## Example

	$table = new table();
	$table->class_set('basic_table');
	$table->caption_set('Table caption');
	$table->no_records_set('No records found');

	$table->heading_add('Heading 1');
	$table->heading_add('Heading 2', NULL, 'class_name');

	while (false) {
		$table_row = new table_row($table);
		$table_row->cell_add_html('<html>');
		$table_row->cell_add('Plain text');
	}

	// $table->charset_output_set('ISO-8859-1');
	// $table->csv_download('File.csv');
	// exit();

	<?= $table->html(); ?>

---

## Add row

Start by creating the table_row object:

	$table_row = new table_row($table);

Then to add the cells, call:

	$table_row->cell_add($content);
	$table_row->cell_add_html($content_html);
	$table_row->cell_add_link($url, $text); // $url can be NULL.

Or if you want to set a class and/or colspan:

	$table_row->cell_add($content, 'class_name', array('colspan' => 1));
	$table_row->cell_add_html($content_html, 'class_name', array('colspan' => 1));
	$table_row->cell_add_link($url, $text, 'class_name', array('colspan' => 1));

For example:

	$table_row->cell_add('Hello');

While the colspan will default to 1, it can be changed (e.g. 3), or set to -1 (to match the tables column count):

	$table_row = new table_row($table);
	$table_row->cell_add('Col 1');
	$table_row->cell_add('Col 2');
	$table_row->cell_add('Col 3');

	$table_row = new table_row($table);
	$table_row->cell_add('Spans 3 columns', NULL, array('colspan' => -1));

---

## Site config

	$config['table.active_asc_suffix_html']  = ' <span class="sort asc" title="Ascending">&#9650;</span>';
	$config['table.active_desc_suffix_html'] = ' <span class="sort desc" title="Descending">&#9660;</span>';
	$config['table.inactive_suffix_html']    = ' <span class="sort inactive" title="Sort">&#9650;</span>';
