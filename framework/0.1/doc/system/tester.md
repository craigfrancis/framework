
# Tester helper

You can view the helper source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/tester.php).

Download standalone server from:

	http://code.google.com/p/selenium/downloads/list

Run server with:

	java -jar selenium-server-standalone-*.jar

	java -jar /opt/selenium/server-standalone-2.28.0.jar

You may view the admin panel at:

	http://localhost:4444/wd/hub/static/resource/hub.html

---

## Session shortcut functions

Open or close a session:

	$this->session_open();
	$this->session_close();

Loading a URL, kind of like the `$session->open()` method from the WebDriver class, but accepts a [url](../../doc/helpers/url.md) object:

	$this->url_load(http_url('/'));

Get the a parameter from the currently loaded url. Useful if you have just created a record on the website, and want to know the new id:

	$id = $this->url_param_get('id');

---

## Element shortcut functions

Please note that where these examples use 'id', that is the type of selector, and could also be 'css'.

Shortcut to get an object:

	$this->element_get('id', 'item_id');
	$this->element_get('id', 'item_id')->click();

	$element = $this->element_get('id', 'item_id', array('test' => true));
	if ($element) {
		$element->click();
	} else {
		// Cannot find element.
	}

The text value of an element, for example a span or an input field:

	$value = $this->element_text_get('id', 'item_id');

	$this->element_text_check('id', 'item_id', 'required value');

The name of an element, for example "input" for an input field:

	$value = $this->element_name_get('id', 'item_id');

	$this->element_name_check('id', 'item_id', 'required value');

The value of an element, typically this is for a form field, such as an `<input>` or `<select>` field:

	$value = $this->element_value_get('id', 'item_id');

	$this->element_value_check('id', 'item_id', 'required value');

The value of an elements attribute, for example checking a `<label>`'s @for attribute.

	$value = $this->element_attribute_get('id', 'item_id', 'attribute');

	$this->element_attribute_check('id', 'item_id', 'attribute', 'required value');

Sending some key strokes (text) to an element (see notes below for select fields):

	$this->element_send_keys('id', 'item_id', 'Keys to send');

	$this->element_send_keys('id', 'item_id', 'Keys to send', array('clear' => true));

	$lorem = $this->element_send_lorem('id', 'item_id'); // A paragraph of lorem, 10 to 50 words

	$this->select_value_set('id', 'item_id', $value);

Shortcut to submit a particular form button:

	$this->form_button_submit('Save');

---

## Select fields

With a select field like:

	<select name="example" id="fld_example">
		<option value="a">Option A</option>
		<option value="b">Option B</option>
		<option value="c">Option C</option>
	</select>

You should use something like the following to set the value:

	$this->select_value_set('id', 'fld_example', 'b');

It is possible to also use the following to use the options text value:

	$this->element_send_keys('id', 'fld_example', 'Option B');

However this second approach does not always work if your using Firefox in the background... and it can be a little hit and miss if the page is slow to load.

---

## Select fields (form helper)

If you are using the [form helper](../../doc/helpers/form.md), and you notice that your select fields are using an indexed array, such as:

	$field_example = new form_field_select($form, 'Example');
	$field_example->options_set(...);

	<select name="example" id="fld_example">
		<option value="0">Option A</option>
		<option value="1">Option B</option>
		<option value="2">Option C</option>
	</select>

Change the `options_set()` method to `option_values_set()`, and it will change the value attributes to match the text value.

	<select name="example" id="fld_example">
		<option value="Option A">Option A</option>
		<option value="Option B">Option B</option>
		<option value="Option C">Option C</option>
	</select>
