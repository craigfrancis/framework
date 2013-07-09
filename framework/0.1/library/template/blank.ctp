<!DOCTYPE html>
<html lang="<?= html($response->lang_get()) ?>" xml:lang="<?= html($response->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $response->head_get_html(); ?>

</head>
<body id="<?= html($response->page_id_get()) ?>">

	<?= $response->view_get_html(); ?>

	<?= $response->foot_get_html(); ?>

</body>
</html>