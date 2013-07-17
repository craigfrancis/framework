
# Paginator helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/paginator.php).

Restricted nav creation, makes a navigation bar like:

	[<]  1  2  3  4  [>]

---

## Example setup

To create a paginator object, you typically just pass in the number of items:

	$paginator = new paginator(1234);

Alternatively, to configure it further you can pass in an array of options:

	$paginator = new paginator(array(
			'item_limit' => 3,
			'item_count' => 1234,
		));

Defaults can be set with the [site config](../../doc/setup/config.md) using:

	paginator.item_limit
	paginator.item_count
	paginator... see below for the rest

---

## Example array usage

To use the paginator to slice an array:

	$paginator = new paginator();

	$array = $paginator->limit_array($array);

And to print

	<?= $paginator->html(); ?>

---

## Example database usage

To use the paginator to return some records in a table:



---

## Example function calls

To get details from the paginator, you can use:

	$limit_sql = $paginator->limit_get_sql();

	$item_count = $paginator->item_count_get();
	$page_size = $paginator->page_size_get();
	$page_number = $paginator->page_number_get();
	$page_count = $paginator->page_count_get();

	$page_5_url = $paginator->page_url_get(5);

---

## Usage with SQL Found Rows

Only do this if it's **actually** more efficient, many times it can be [much slower](http://stackoverflow.com/q/186588) than two separate queries (one to count the rows and one to return the records).

	$paginator = new paginator();

	$db->query('SELECT SQL_CALC_FOUND_ROWS
					id,
					name
				FROM
					' . DB_PREFIX . 'table
				WHERE
					deleted = "0000-00-00 00:00:00"
				LIMIT
					' . $paginator->limit_get_sql());

	while ($row = $db->fetch_row()) {
	}

	$db->query('SELECT FOUND_ROWS();');

	$paginator->item_count_set($db->fetch_result(), true);

Note that the '`true`' used to set the item count will trigger a redirect if the requested page number is too high (and would show no results).

---

## Config options

The pagination helper has the following configuration options available.

	item_limit
		24, as its divisible by 1, 2, 3, 4, 6, 12.

	item_count
		Number of items to represent.

	base_url
		Used for the links, typically not needed.

	mode
		'link', or can be set to 'form' if you need submit buttons.

	variable
		'page', the name of the variable used in the query string.

	elements
		array(
			'<p class="pagination">',
			'hidden',
			'first',
			'back',
			'links',
			'next',
			'last',
			'extra',
			'</p>' . "\n")

	indent_html
		"\n\t\t\t\t"

	first_html
		NULL

	back_html
		[«]

	next_html
		[»]

	last_html
		NULL

	number_pad
		0, allow page numbers to be padded to a certain length (e.g. 3).

	link_count
		9, number of links shown.

	link_wrapper_element
		'span'

	extra_html
		NULL, but could be '<span class="pagination_extra">Page [PAGE] of [COUNT]</span>'

If you need to customise the output further, it is possible to extend the pagination class with your own `html_format()` or `html()` methods:

	/app/library/class/paginator.php

	class paginator extends paginator_base {

		protected function html_format($elements_html) {

			// $item_count = $this->item_count_get();

			// $links_html = implode(' | ', $elements_html['links_array']);

			$html = '
				<div class="paginator">
					' . $elements_html['hidden'] . '
					<div class="first">' . $elements_html['first'] . '</div>
					<div class="back">' . $elements_html['back'] . '</div>
					<div class="links">' . $elements_html['links'] . '</div>
					<div class="next">' . $elements_html['next'] . '</div>
					<div class="last">' . $elements_html['last'] . '</div>
					<div class="extra">' . $elements_html['extra'] . '</div>
				</div>';

			return $html;

		}

	}
