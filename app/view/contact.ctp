
	<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

	<?= $form->html(); ?>

	<script type="text/javascript">
	//<![CDATA[

		//--------------------------------------------------
		// Form validation rules

			<?= $form->validation_js(); ?>

		//--------------------------------------------------
		// Errors on a single field

			// console.log(form_1_validation.fld_name().errors_html);

		//--------------------------------------------------
		// Errors as a list

			function error_list_html(form_validation) {
				var errors_list_html = [], field = null;
				for (field in form_validation) {
					field = form_validation[field]();
					if (field.errors_html.length > 0) {
						errors_list_html = errors_list_html.concat(field.errors_html);
					}
				}
				return errors_list_html;
			}

			// console.log(error_list_html(form_1_validation));

		//--------------------------------------------------
		// Details for all fields

			function all_fields(form_validation) {
				var fields = {}, name = null;
				for (field in form_validation) {
					fields[field] = form_validation[field]();
				}
				return fields;
			}

			// console.log(all_fields(form_1_validation));

	//]]>
	</script>
