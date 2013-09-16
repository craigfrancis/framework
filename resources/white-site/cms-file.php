<?php

//--------------------------------------------------
// File info

	$file = new file('cms-files');

	$file_id = request('file_id');

	$db = db_get();

	$sql = 'SELECT
				cf.file_name,
				cf.file_mime
			FROM
				' . DB_PREFIX . 'cms_file AS cf
			WHERE
				cf.id = "' . $db->escape($file_id) . '" AND
				cf.deleted = "0000-00-00 00:00:00"';

	if ($row = $db->fetch($sql)) {

		$file_name = $row['file_name'];
		$file_mime = $row['file_mime'];
		$file_path = $file->file_path_get($file_id);

	} else {

		exit_with_error('Invalid file "' . $file_id . '"');

	}

//--------------------------------------------------
// Download

	http_download_file($file_path, $file_mime, $file_name, 'inline');

?>