
# Paginator helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/paginator.php).

	//--------------------------------------------------
	// Details

		Restricted nav creation, makes a navigation
		bar like:

		[<]  1  2  3  4  [>]

	//--------------------------------------------------
	// Site config

		paginator.item_limit
		paginator.item_count
		paginator... see below for the rest

	//--------------------------------------------------
	// Example setup

		$result_count = 123;

		$paginator = new paginator($result_count);

		$paginator = new paginator(array(
				'item_limit' => 3,
				'item_count' => $result_count,
			));

	//--------------------------------------------------
	// Example usage

		$page_size = $paginator->page_size_get();
		$page_number = $paginator->page_number_get();

		$limit_sql = $paginator->limit_get_sql();

		<?= $paginator->html(); ?>
