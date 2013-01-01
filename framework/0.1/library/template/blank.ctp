<!DOCTYPE html>
<html lang="<?= html($this->lang_get()) ?>" xml:lang="<?= html($this->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_get_html(); ?>

</head>
<body id="<?= html($this->page_id_get()) ?>">

	<?= $this->message_get_html(); ?>

	<?= $this->view_get_html(); ?>

	<?= $this->foot_get_html(); ?>

</body>
</html>