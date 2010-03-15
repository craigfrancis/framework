If a model does not exist when being created, a generic one is created linking it to a table of that name.

Models can link to a model_behaviour, one of which will be a generic table handler... this will provide the following methods:

	- create
	- get
	- delete

Basically an active record implementation.

But a more complicated model_behaviour could be created... perhaps one for a basket/order? as in, add_item(), add_voucher()? or should this be a controller_helper.



