<?php

//--------------------------------------------------
// Contents

	$php = file_get_contents($_SERVER['argv'][1]);

//--------------------------------------------------
// Nav support

	preg_match_all('/(\\$\w+) = new nav\(/', $php, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		$php = str_replace($match[1] . '->addLink(', $match[1] . '->link_add(', $php);
		$php = str_replace($match[1] . '->getHtmlNav()', $match[1] . '', $php);
	}

	$php = str_replace('require_once(ROOT . \'/a/php/nav.php\');', '', $php);

//--------------------------------------------------
// Form support

	//--------------------------------------------------
	// Main

		$replacements = array(
			'setFormMethod' => 'form_method_set',
			'setDatabaseTable' => 'db_table_set_sql',
			'setDatabaseRecordSelect' => 'db_where_set_sql',
			'setDatabaseFieldValue' => 'db_value_set',
			'databaseSave' => 'db_save',
			'getFields' => 'fields_get',
		);

		preg_match_all('/(\\$\w+) = new form\(/', $php, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {

			foreach ($replacements as $from => $to) {
				$php = str_replace($match[1] . '->' . $from . '(', $match[1] . '->' . $to . '(', $php);
			}

			$php = str_replace($match[1] . '->setRequiredMarkPosition(FORM_REQ_MARK_POS_NONE', $match[1] . '->required_mark_position_set(\'none\'', $php);
			$php = str_replace($match[1] . '->setRequiredMarkPosition(FORM_REQ_MARK_POS_LEFT', $match[1] . '->required_mark_position_set(\'left\'', $php);
			$php = str_replace($match[1] . '->setRequiredMarkPosition(FORM_REQ_MARK_POS_RIGHT', $match[1] . '->required_mark_position_set(\'right\'', $php);
			$php = str_replace($match[1] . '->getDatabaseRecordValues();', '', $php);

		}

		$php = str_replace('require_once(ROOT . \'/a/php/form.php\');', '', $php);

		$php = str_replace('$v[\'htmlErrorList\'] = $form->getHtmlErrorList();', '', $php);
		$php = str_replace('FORM_DATABASE_FIELD_KEY', '\'key\'', $php);
		$php = preg_replace('/\n[ \t]*\/\/--+\n[ \t]*\/\/ Return the error list\n[\n\t ]*(\n[ \t]*})/', '$1', $php);

	//--------------------------------------------------
	// Basic field

		$replacements = array(
			'setDatabaseField' => 'db_field_set',
			'setFieldClass' => 'input_class_set',
			'setCols' => 'cols_set',
			'setRows' => 'rows_set',
			'setLabelOption' => 'label_option_set',

			'setRequiredError' => 'required_error_set',
			'setMinLength' => 'min_length_set',
			'setMaxLength' => 'max_length_set',
			'setMaxDate' => 'max_date_set',
			'setMinValue' => 'min_value_set',
			'setMaxValue' => 'max_value_set',
			'setInvalidError' => 'invalid_error_set',
			'setFormatError' => 'format_error_set',

			'getValue' => 'value_get',
			'getValueTimeStamp' => 'value_time_stamp_get',

			'quickPrintShow' => 'print_show_set',
			'setQuickPrintInfoHtml' => 'info_set_html',
			'setQuickPrintInfo' => 'info_set',
		);

		preg_match_all('/(\\$\w+) = new (formField[A-Za-z]+)\(/', $php, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {

			$php = str_replace($match[2], strtolower(preg_replace('/[A-Z]/', '_$0', $match[2])), $php);

			foreach ($replacements as $from => $to) {
				$php = str_replace($match[1] . '->' . $from . '(', $match[1] . '->' . $to . '(', $php);
			}

		}

		$php = str_replace('form_field_textArea', 'form_field_text_area', $php);

	//--------------------------------------------------
	// Variables

		preg_match_all('/\\$fld[A-Z]\w+/', $php, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$php = str_replace($match[0], strtolower(preg_replace('/[A-Z]/', '_$0', $match[0])), $php);
		}

		$php = str_replace('$act = returnSubmittedValue(\'act\');', '', $php);
		$php = str_replace('if ($act == \'\') {', 'if (!$form->submitted()) {', $php);
		$php = str_replace('if ($act != \'\') {', 'if ($form->submitted()) {', $php);
		$php = str_replace('if (!$form->hasError()) {', 'if ($form->valid()) {', $php);
		$php = preg_replace('/[ \t]*\\$v\\[\'formAction\'\\] = \\$GLOBALS\\[\'tplHttpsUrl\'\\];\n/', '', $php);

		$php = preg_replace('/\$v\[\'([^\']+)\'\] = ([^;]+)/', '$this->set(\'$1\', $2)', $php);

//--------------------------------------------------
// Assets

	$php = str_replace('require_once(ROOT . \'/a/php/assets.php\');', '', $php);
	$php = str_replace('<?= $GLOBALS[\'tplCssLinksHtml\'] ?>', '<?= $this->head_get_html() ?>', $php);
	$php = str_replace('<?= $GLOBALS[\'tplJavaScriptHtml\'] ?>', '', $php);
	$php = str_replace('<?= $GLOBALS[\'tplExtraHeadHtml\'] ?>', '', $php);
	$php = str_replace('<?= $GLOBALS[\'tplSkipLinksHtml\'] ?>', '', $php);
	$php = str_replace('<?= $GLOBALS[\'tplCssSwitcherHtml\'] ?>', '', $php);
	$php = str_replace('<?= html($GLOBALS[\'tplPageId\']) ?>', 'p_<?= html($this->page_ref_get()) ?>', $php);
	$php = str_replace('<meta http-equiv="content-type" content="<?= html($GLOBALS[\'pageMimeType\']) ?>; charset=<?= html($GLOBALS[\'pageCharset\']) ?>" />', '', $php);

	$php = preg_replace('/<link rel="shortcut icon" type="image\/x-icon" href="[^"]+\/favicon.ico" \/>/', '', $php);

//--------------------------------------------------
// Miscellaneous

	$php = str_replace('$GLOBALS[\'webAddress\']', 'config::get(\'url.prefix\')', $php);
	$php = str_replace('$GLOBALS[\'webDomain\']', 'config::get(\'request.domain_http\')', $php);
	$php = str_replace('$GLOBALS[\'ipAddress\']', 'config::get(\'request.ip\')', $php);
	$php = str_replace('$GLOBALS[\'pageCharset\']', 'config::get(\'output.charset\')', $php);
	$php = str_replace('$GLOBALS[\'emailFromName\']', 'config::get(\'email.from_name\')', $php);
	$php = str_replace('$GLOBALS[\'emailFromAddress\']', 'config::get(\'email.from_email\')', $php);
	$php = str_replace('$GLOBALS[\'tplTrackingHtml\']', '$this->tracking_get_html();', $php);

	$php = str_replace('returnSubmittedValue(', 'request(', $php);

	$php = str_replace('DB_T_PREFIX(', 'DB_PREFIX', $php);

	$php = preg_replace('/\$GLOBALS\[\'tplFolder\'\]\[\'?([0-9]+)\'?\]/', '\$this->route_folder($1)', $php);

//--------------------------------------------------
// Non required files

	$php = preg_replace('/require_once\(\'[^\']+\/a\/php\/core.php\'\);/', '', $php);

	$php = str_replace('require_once(ROOT . \'/a/inc/pageTop.php\');', '', $php);
	$php = str_replace('require_once(ROOT . \'/a/inc/pageBottom.php\');', '', $php);

//--------------------------------------------------
// Empty include php file comments

	$php = preg_replace('/\n[ \t]*\/\/--+\n[ \t]*\/\/ Include required PHP files\n[\n\t ]*(\n[ \t]*\/\/--+)/', '$1', $php);
	$php = preg_replace('/\n[ \t]*\/\/--+\n[ \t]*\/\/ Return the required values for this script\n[\n\t ]*(\n[ \t]*\/\/--+)/', '$1', $php);

//--------------------------------------------------
// Save

	file_put_contents($_SERVER['argv'][1], $php);

?>