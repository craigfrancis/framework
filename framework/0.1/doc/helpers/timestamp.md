
# Timestamp helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/main/framework/0.1/library/class/timestamp.php).

---

## Basic usage

The timestamp helper is just an extended version of the base PHP [DateTime](https://php.net/datetime) object:

	$timestamp = new timestamp('2014W04-2');
	$timestamp = new timestamp('2014-09-22 17:43:21', 'db');
	$timestamp = new timestamp('2014-09-22 17:43:21', 'Europe/London');
	$now = new timestamp();

	debug($timestamp->format('l jS F Y, g:i:sa'));

		Monday 22nd September 2014, 5:43:21pm

	debug($now);

		2014-09-22 17:43:21 (Europe/London)

	echo $now; // In UTC, typically for the datababse (see below)

		2014-09-22 16:43:21

You can clone (and modify) its value:

	$timestamp = $now->clone();

	$timestamp = $now->clone('+3 days');

And you can return a HTML version, with the HTML5 `<time>` tag:

	debug($timestamp->html('l jS F Y, g:ia'));

		<time datetime="2014-09-25T17:43:21+01:00">Thursday 25th September 2014, 5:43pm</time>

---

## Create from format

Like the PHP object, you can also do:

	$timestamp = timestamp::createFromFormat('d/m/y H:i:s', '23/06/08 09:47:47');

---

## Database usage

When using a value from the database:

	$timestamp = new timestamp($row['field'], 'db');

	echo $timestamp->format('l jS F Y, g:i:sa');

The timestamp helper will parse the UTC value (note the 'db' timezone), and the formatted output will then use "output.timezone".

---

## Database storage

When storing a 'datetime' value in the database, you can simply use the variable:

	$now = new timestamp();

	$db->insert(DB_PREFIX . 'table', array(
			'name'    => $name,
			'created' => $now,
		));

Or you can use the 'db' format:

	$timestamp->format('db');

Both of these methods use the ISO format "YYYY-MM-DD HH:MM:SS" in UTC.

But if you want to actually store NULL in the database (not "0000-00-00"), then you will need to use the format('db') method.

---

## NULL values

If the timestamp helper is initialised with the values:

	NULL
	'0000-00-00'
	'0000-00-00 00:00:00'

Then it will typically return NULL when you call the format() or html() functions, unless you provide a value to use instead:

	$timestamp = new timestamp('0000-00-00 00:00:00', 'db');

	echo $timestamp->format('jS F Y', 'N/A');
	echo $timestamp->html('jS F Y', 'N/A');

If you just want to test if the value is NULL:

	debug($timestamp->null());

This returns `false` if not NULL, or a truthy value if NULL (e.g. '0000-00-00').

---

## Site config

	output.timezone

		The timezone to format the dates (e.g. "Europe/London"),
		Defaults to the PHP date_default_timezone_get() function.

---

## Holiday support

You can store a list of holidays in a "system_holiday" table, returning them with:

	timestamp::holidays_get();

These dates are then used when calling:

	$timestamp->business_days_add(5);
		Return a new timestamp, 5 business days later.

	$timestamp->business_day_next();
		Return the next business day (rarely used).

	$timestamp->business_days_diff($end);
		The number of business days between two timestamps.

For example:

	$start = new timestamp('2014-09-20');

		Saturday 20th September 2014

	$day_1 = $start->business_day_next();

		Monday 22nd September 2014

	$day_2 = $day_1->business_days_add(5);

		Monday 29th September 2014

	debug($day_2->business_days_diff('2014-10-07'));

		6 days
