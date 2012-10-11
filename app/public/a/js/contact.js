
	//--------------------------------------------------
	// Form validation rules have been added 
	// from the other JavaScript file
	//--------------------------------------------------

//--------------------------------------------------
// Errors on a single field

	// console.log(form_1_validation.fld_name().errors);

//--------------------------------------------------
// Errors as a list

	function error_list_html(form_validation) {
		var errors_list_html = [], field = null;
		for (field in form_validation) {
			field = form_validation[field]();
			for (k in field.errors) {
				errors_list_html = errors_list_html.concat(field.errors[k].html);
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
