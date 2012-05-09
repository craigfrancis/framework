
This is a basic PHP Framework being developed in a currently private SVN repository and copied over to Git periodically (hence the basic commit comments), the intention is to move to Git entirely in the future.

The framework is intended for rapid development of websites, where focus is on the elements that appear on the pages (forms, data tables, shopping carts, etc).

For example, a typical website will just have a number of forms, and they are the primary/only dynamic elements. The framework focuses on these rather than an overly complicated Object Orientated setup.

While the framework does use a form of MVC, it is more so for code organisation rather than trying to be "theoretically perfect"... I want to build websites/systems fast and well, rather than spending weeks debating if a OO Singleton should be allowed (I do use singletons for things like the website config, because it makes the code easier to read/write).

--------------------------------------------------

Notes to self:

	Perhaps a route could also trigger a function to be called... for admin authentication perhaps?

	Can a service register a gateway... e.g. payments in /a/api/payment/(st|wp|pp|gc)/

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
