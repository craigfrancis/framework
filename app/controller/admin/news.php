<?php

	class admin_news_controller extends controller {

		public function route() {
			return array(
				'route_path_reset_prefix' => '/news/admin/'
			);
		}

	}




	// http://fuelphp.com/docs/general/controllers/rest.html - more thoughts?

	class controller_crud extends check {
	}

	class example extends controller_crud {

		private $crud_config = array(
				'table' => 'news', // What about multiple tables
				'index_list_size' => 30, // Default
				'index_nav_variable' => 'offset',
				'index_nav_back_html' => '[&laquo;]',
				'index_nav_next_html' => '[&raquo;]',
				'index_nav_number_padding' => 2,
				'index_nav_url_variables' => array(
					),
				'index_column' => array(
						'title' => array(
								'field' => 'title',
							),
						'date' => array(
								'field' => 'date',
								'date_format' => '',
							),
						'edit' => array(
								'title' => '',
								'format_field' => array(
										'url' => './edit/?id=X', // ???
									),
								'format_html' => '<a href="">edit</a>', // ???
							),
					),
				'item_label' => 'article', // default "item"
				'item_name' => array(
						'field' => 'title', // To say things like "Use the fields below to edit the X item" (could be array for first/last name, inc space).
					),
				'edit_field' => array(
						'title' => array(
							),
						'date' => array(
							),
						'body' => array(
							),
					),
			);

	}

?>