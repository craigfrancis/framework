<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example extension



***************************************************/

	class order_table_base extends check {

		//--------------------------------------------------
		// Variables

			protected $order_obj = NULL;
			protected $order_items = NULL;
			protected $order_totals = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($order) {
				$this->setup($order);
			}

			protected function setup($order) {

				$this->order_obj = $order;
				$this->order_items = $order->items_get();
				$this->order_totals = $order->totals_get();

				$this->init();

			}

			protected function init() {
			}

			protected function db_get() {
				return $this->order_obj->db_get();
			}

		//--------------------------------------------------
		// Item information

			// public function item_image_info($config, $item) {
			// 	return $config['url_prefix'] . '/path/';
			// 	return array(
			// 			'url' => $config['url_prefix'] . '/path/',
			// 			'width' => 35,
			// 			'height' => 35,
			// 		);
			// }

			// public function item_image_html($config, $item) {
			// 	return '<img src="..." />';
			// }

			// public function item_url($config, $item) {
			// 	return $config['url_prefix'] . '/path/';
			// }

			public function item_info_html($config, $item, $item_url) {

				if ($item_url) {
					$html = '
							<h3><a href="' . html($item_url) . '">' . $item['item_name'] . '</a></h3>';
				} else {
					$html = '
							<h3>' . $item['item_name'] . '</h3>';
				}

				if (isset($item['item_code']) && $item['item_code'] != '') {
					$html = '
						<p>' . html($item['item_code']) . '</p>';
				}

				return $html;

			}

			public function item_quantity_html($config, $item, $quantity) {

				if (isset($config['quantity_edit_field'])) {

				} else if (isset($config['quantity_edit_name'])) {


				} else {

					$html = html($quantity);

				}

				if (isset($config['quantity_delete_name'])) {

					$html .= '<a href="' . url(array($config['quantity_delete_name'] => $item['id'])) . '">Delete</a>';

				}

				return $html;

			}

		//--------------------------------------------------
		// Output

			public function table_get_html($config = NULL) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'url_prefix' => '',
						);

					if (!is_array($config)) {
						$config = array();
					}

					$config = array_merge($defaults, $config);

				//--------------------------------------------------
				// Details

					$currency_char = $this->order_obj->currency_char_get();

					$show_image_info = method_exists($this, 'item_image_info');
					$show_image_html = method_exists($this, 'item_image_html');
					$show_image = ($show_image_info || $show_image_html);

					$show_item_url = method_exists($this, 'item_url');

				//--------------------------------------------------
				// Start

					$html = '
						<table class="order_table">
							<thead>
								<tr>
									<th scope="col"' . ($show_image ? ' colspan="2"' : '') . '>Item</th>
									<th scope="col">Quantity</th>
									<th scope="col">Price</th>
									<th scope="col">Total</th>
								</tr>
							</thead>
							<tbody>';

				//--------------------------------------------------
				// Items

					foreach ($this->order_items as $item) {

						if ($show_item_url) {
							$item_url = $this->item_url($config, $item);
						} else {
							$item_url = NULL;
						}

						$html .= '
								<tr>';

						if ($show_image) {

							if ($show_image_info) {

								$image_info = $this->item_image_info($config, $item);

								if (is_array($image_info)) {
									$image_html = '<img src="' . html($image_info['url']) . '" width="' . html($image_info['width']) . '" height="' . html($image_info['height']) . '" />';
								} else {
									$image_html = '<img src="' . html($image_info) . '" />';
								}

							} else {

								$image_html = $this->item_image_html($config, $item);

							}

							if ($item_url) {
								$html .= '
										<td><a href="' . html($item_url) . '">' . $image_html . '</a></td>';
							} else {
								$html .= '
										<td>' . $image_html . '</td>';
							}

						}

						$html .= '
									<td>' . $this->item_info_html($config, $item, $item_url) . '</td>
									<td>' . $this->item_quantity_html($config, $item, $item['quantity']) . '</td>
									<td>' . html(format_currency(($item['price']), $currency_char)) . '</td>
									<td>' . html(format_currency(($item['quantity'] * $item['price']), $currency_char)) . '</td>
								</tr>';

					}

				//--------------------------------------------------
				// Total

debug($this->order_totals);

				//--------------------------------------------------
				// End

					$html .= '
							</tbody>
						</table>';

					return $html;

			}

			public function table_get_text() {
			}

	}

?>