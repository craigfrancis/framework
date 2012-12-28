<?php

	class order_table_base extends check {

		//--------------------------------------------------
		// Variables

			protected $order_obj = NULL;
			protected $order_items = NULL;
			protected $order_totals = NULL;

		//--------------------------------------------------
		// Setup

			public function init() {
			}

			public function order_ref_set($order) {
				$this->order_obj = $order;
				$this->order_items = $order->items_get();
				$this->order_totals = $order->totals_get();
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
					$html .= '
						<p>' . html($item['item_code']) . '</p>';
				}

				return $html;

			}

			public function item_quantity_html($config, $item, $quantity) {

				//--------------------------------------------------
				// Config

					if (isset($config['quantity_edit'])) {

						$edit_config = $config['quantity_edit'];

						if (!is_array($edit_config)) {
							$edit_config = array('type' => $edit_config);
						}

						$edit_config = array_merge(array(
								'type' => 'select',
								'max' => 10,
								'delete_text' => 'Remove',
								'url' => NULL,
							), $edit_config);

					} else {

						$edit_config = array('type' => NULL);

					}

				//--------------------------------------------------
				// Types

					if ($edit_config['type'] == 'select') {

						$max = $edit_config['max'];
						if ($quantity > $max) {
							$max = $quantity;
						}

						$html = '<select name="item_quantity_' . html($item['id']) . '">';
						for ($k = 0; $k <= $max; $k++) {
							$html .= '<option value="' . html($k) . '"' . ($k == $quantity ? ' selected="selected"' : '') . '>' . html($k == 0 ? $edit_config['delete_text'] : $k) . '</option>';
						}
						$html .= '</select>';

					} else if ($edit_config['type'] == 'link') {

						$url = $edit_config['url'];
						if ($url === NULL) {
							$url = new url();
						}

						$html = html($quantity) . ' <br /><a href="' . $url->get(array('item_delete' => $item['id'])) . '">' . html($edit_config['delete_text']) . '</a>';

					} else {

						$html = html($quantity);

					}

				//--------------------------------------------------
				// Return

					return $html;

			}

		//--------------------------------------------------
		// Output

			public function table_get_html($config = NULL) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'url_prefix' => '',
							'email_mode' => false,
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
						<table class="order_table"' . ($config['email_mode'] ? ' cellspacing="0" cellpadding="1" border="1"' : '') . '>
							<thead>
								<tr>
									<th scope="col" class="item"' . ($show_image ? ' colspan="2"' : '') . '>Item</th>
									<th scope="col" class="quantity">Quantity</th>
									<th scope="col" class="price">Price</th>
									<th scope="col" class="total">Total</th>
								</tr>
							</thead>
							<tbody>';

				//--------------------------------------------------
				// Body

					if (count($this->order_items) == 0) {

						//--------------------------------------------------
						// Empty basket

								$html .= '
									<tr class="empty">
										<td colspan="' . html($show_image ? 5 : 4) . '">Your basket is empty</td>
									</tr>';

					} else {

						//--------------------------------------------------
						// Items

							$k = 0;

							foreach ($this->order_items as $item) {

								if ($show_item_url) {
									$item_url = $this->item_url($config, $item);
								} else {
									$item_url = NULL;
								}

								$html .= '
										<tr class="item ' . ($k++ % 2 ? 'even' : 'odd') . '">';

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
												<td class="image"><a href="' . html($item_url) . '">' . $image_html . '</a></td>';
									} else {
										$html .= '
												<td class="image">' . $image_html . '</td>';
									}

								}

								$html .= '
											<td class="item">' . $this->item_info_html($config, $item, $item_url) . '</td>
											<td class="quantity">' . $this->item_quantity_html($config, $item, $item['quantity']) . '</td>
											<td class="price">' . html(format_currency(($item['price_net']), $currency_char)) . '</td>
											<td class="total">' . html(format_currency(($item['quantity'] * $item['price_net']), $currency_char)) . '</td>
										</tr>';

							}

						//--------------------------------------------------
						// Totals

							//--------------------------------------------------
							// Pre tax items

								foreach ($this->order_totals['tax']['types'] as $type) {
									$amount = $this->order_totals['items'][$type]['net'];
									if ($type != 'item' && $amount > 0) {

										$html .= '
											<tr class="total ' . html($type) . ' ' . ($k++ % 2 ? 'even' : 'odd') . '">
												<td class="item" colspan="' . html($show_image ? 4 : 3) . '">' . ucfirst($type) . ':</td>
												<td class="total">' . html(format_currency($amount, $currency_char)) . '</td>
											</tr>';

									}
								}

							//--------------------------------------------------
							// Net/Tax values

								if (count($this->order_totals['tax']['types']) > 0 && $this->order_totals['tax']['percent'] > 0) { // Don't bother showing if no items show tax

									$html .= '
										<tr class="total net ' . ($k++ % 2 ? 'even' : 'odd') . '">
											<td class="item" colspan="' . html($show_image ? 4 : 3) . '">Net:</td>
											<td class="total">' . html(format_currency($this->order_totals['amount']['net'], $currency_char)) . '</td>
										</tr>';

									$html .= '
										<tr class="total tax ' . ($k++ % 2 ? 'even' : 'odd') . '">
											<td class="item" colspan="' . html($show_image ? 4 : 3) . '">VAT:</td>
											<td class="total">' . html(format_currency($this->order_totals['amount']['tax'], $currency_char)) . '</td>
										</tr>';

								}

							//--------------------------------------------------
							// Tax exempt items

								foreach ($this->order_totals['items'] as $type => $amount) {
									if ($type != 'item' && $amount['gross'] > 0 && !in_array($type, $this->order_totals['tax']['types'])) {

										$html .= '
											<tr class="total ' . html($type) . ' ' . ($k++ % 2 ? 'even' : 'odd') . '">
												<td class="item" colspan="' . html($show_image ? 4 : 3) . '">' . ucfirst($type) . ':</td>
												<td class="total">' . html(format_currency($amount['gross'], $currency_char)) . '</td>
											</tr>';

									}
								}

							//--------------------------------------------------
							// Total

								$html .= '
									<tr class="total gross ' . ($k++ % 2 ? 'even' : 'odd') . '">
										<td class="item" colspan="' . html($show_image ? 4 : 3) . '">Total:</td>
										<td class="total"><strong>' . html(format_currency($this->order_totals['amount']['gross'], $currency_char)) . '</strong></td>
									</tr>';

					}

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