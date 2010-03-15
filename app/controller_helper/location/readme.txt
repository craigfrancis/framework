//--------------------------------------------------
// Overview
//--------------------------------------------------

A controller_helper that allows you to create a list of locations (e.g. stores), and allow visitors to your site to find the nearest locations using a simple "as the crow flies" distance calculation to each location.

By default this uses Google's GeoCoding service, but other services could be used.

How would this provide an admin control panel? - does it need one?

	Could just use the "crud" controller_template for the admin, and then the website just calls this... the table can have a field for when the coordinates have been last checked (indexed?), and on search this helper looks for OLD records, records with this date-time of 0, or where it is less than the "edited" date, and then do an update... but the admin control panel may want a mapping feature to specify a more specific location.

//--------------------------------------------------
// Example
//--------------------------------------------------


