
# Order helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/order/order.php).

Will probably also want to look at the [payment](../../doc/helpers/payment.md) helper.

---

## Example setup

	$order = new order();
	$order->select_open();

Item count - quick summary for a basket count

	echo $order->item_count();

Add an item

	$order->item_add(array(
			'item_id' => $id,
			'item_code' => $code,
			'item_name' => $name,
			'price' => $price,
		));

Edit basket with 'delete' links (CSRF issue)

	$order->items_update();

	$table_html = $order->table_get_html(array(
			'quantity_edit' => 'link',
		));

Edit basket with 'quantity' select fields

	//--------------------------------------------------
	// Controller

		$form = new form();

		if ($form->submitted() && $form->valid()) {

			$order->items_update();

			if (strtolower(trim(request('button'))) == 'update totals') {
				redirect(url('/basket/'));
			} else {
				redirect(url('/basket/checkout/'));
			}

		}

		$table_html = $order->table_get_html(array(
				'quantity_edit' => array('type' => 'select'),
			));

		$response->set('form', $form);
		$response->set('table_html', $table_html);
		$response->set('empty_basket', ($order->item_count() == 0));

	//--------------------------------------------------
	// View

		<?= $form->html_start(); ?>
			<fieldset>

				<?= $form->html_error_list(); ?>

				<?= $order_table_html; ?>

				<?php if (!$empty_basket) { ?>

					<div class="submit">
						<input type="submit" name="button" value="Update totals" />
						<input type="submit" name="button" value="Checkout" />
					</div>

				<?php } ?>

			</fieldset>
		<?= $form->html_end(); ?>

Checkout page

	$order = new order();

	if (!$order->select_open()) {
		redirect(url('/basket/'));
	}

	$form = $order->form_get();
	$form->form_class_set('basic_form');
	$form->form_button_set('Continue');

	$form->print_group_start('Payment details');
	$form->field_get('payment_name');
	$form->field_get('payment_address_1');
	$form->field_get('payment_address_2');
	$form->field_get('payment_address_3');
	$form->field_get('payment_town_city');
	$form->field_get('payment_region');
	$form->field_get('payment_postcode');
	$form->field_get('payment_country');
	$form->field_get('payment_telephone');

	$form->print_group_start('Delivery details');
	$form->field_get('delivery_different');
	$form->field_get('delivery_name');
	$form->field_get('delivery_address_1');
	$form->field_get('delivery_address_2');
	$form->field_get('delivery_address_3');
	$form->field_get('delivery_town_city');
	$form->field_get('delivery_region');
	$form->field_get('delivery_postcode');
	$form->field_get('delivery_country');
	$form->field_get('delivery_telephone');

	if ($form->submitted()) {

		$result = $order->save();

		if ($result) {
			redirect(url('/basket/payment/'));
		}

	} else {

		// Defaults

	}

	$response->set('form', $form);

Admin access

	config::set('order.user_privileged', ADMIN_LOGGED_IN);
