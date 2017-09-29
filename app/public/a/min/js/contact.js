function error_list_html(form_validation){var errors_list_html=[],field=null;for(field in form_validation){field=form_validation[field]();for(k in field.errors){errors_list_html=errors_list_html.concat(field.errors[k].html);}}
return errors_list_html;}
function all_fields(form_validation){var fields={},name=null;for(field in form_validation){fields[field]=form_validation[field]();}
return fields;}