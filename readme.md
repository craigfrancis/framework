
The framework is intended for rapid development of websites, where focus is on the elements that appear on the pages (forms, data tables, shopping carts, etc).

For example, a typical website will just have a number of forms, and they are the primary/only dynamic elements. The framework focuses on these rather than an overly complicated Object Orientated setup.

While the framework does use a form of MVC, it is more so for code organisation rather than trying to be "theoretically perfect"... I want to build websites/systems fast and well, rather than spending weeks debating if a OO Singleton should be allowed (I do use singletons for things like the website config, because it makes the code easier to read/write).

For details, see the [PHP Prime website](http://www.phpprime.com/).

--------------------------------------------------

Original suggested folder structure (only here for reference)

	/app/
		/controller/
		/controller_template/
		/controller_helper/ <-- e.g. a store locator
		/view/
		/view_element/ <-- Elements that can be used on a page, e.g. a map for a store
		/view_layout/ <-- Main HTML layouts
		/model/
		/model_behaviour/
