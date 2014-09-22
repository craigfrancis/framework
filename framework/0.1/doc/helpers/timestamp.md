
# Timestamp helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/timestamp.php).

---

## Basic usage

The timestamp helper is basically an extended version of the base PHP [DateTime](http://php.net/datetime) object:

	$timestamp = new timestamp('2014W04-2');
	$timestamp = new timestamp('2014-09-22 17:43:21', 'Europe/London');
	$timestamp = new timestamp();
	$timestamp = new timestamp(time());

	debug($timestamp->format('l jS F Y, g:i:sa'));

		Monday 22nd September 2014, 5:43:21pm

You can also modify its value in the same way:

	$timestamp->modify('+3 days');

	debug($timestamp->format('l jS F Y, g:i:sa'));

		Thursday 25th September 2014, 5:43:21pm

And you can return a HTML version, with the `<time>` tag:

	debug($timestamp->html('l jS F Y, g:ia'));

		<time datetime="2014-09-25T17:43:21+01:00">Thursday 25th September 2014, 5:43pm</time>

---

## Database usage

When storing a datetime in the database, simply use the value from:

	$timestamp->format('db');

This will use the ISO format "YYYY-MM-DD HH:MM:SS" in the UTC timezone.

When returning the value from the database, simply run:

	$timestamp = new timestamp($row['field'], 'db');

The timestamp helper will then parse the UTC value, and start using the "output.timezone".

---

## Site config

	output.timezone

		The timezone to format the dates (e.g. "Europe/London")

---

## Holiday support

You can store a list of holidays in a "system_holiday" table, returning them with:

	timestamp::holidays_get();

These dates are then used when calling:

	$timestamp->business_days_add(5);
		Add 5 business days.

	$timestamp->business_day_select();
		Move forward to the next business day.

	$timestamp->business_days_diff($end);
		Calculate the number of business days between two timestamps.

For example:

	$timestamp = new timestamp('2014-09-20');

		Saturday 20th September 2014

	$timestamp->business_day_select();

		Monday 22nd September 2014

	$timestamp->business_days_add(5);

		Monday 29th September 2014

	$end = new timestamp('2014-10-07'); // or just '2014-10-07'

	debug($timestamp->business_days_diff($end));

		6 days



