<?php

	class form_explorer_unit extends unit {

		protected $config = array(
			);

		protected function authenticate($config) {
			return true;
		}

		protected function setup($config) {

			//--------------------------------------------------
			// Config

				$show = request('show');

				$form_folder = FRAMEWORK_ROOT . '/library/class/form/';

			//--------------------------------------------------
			// Form classes

				$form_classes = array();
				$form_classes[] = 'form';

				foreach (glob($form_folder . '/*.php') as $path) {
					$name = str_replace('-', '_', str_replace(array($form_folder . '/', '.php'), '', $path));
					if (prefix_match('form_field_', $name)) { // Ignore 'form' (goes first), and generic 'form_field'
						$form_classes[] = $name;
					}
				}

			//--------------------------------------------------
			// Selected class

				$class_methods = array();

				if (in_array($show, $form_classes)) {

					$methods = get_class_methods($show);
					foreach ($methods as $method) {
						if (substr($method, 0, 1) != '_') {

							$reflect = new ReflectionMethod($show, $method);

							$parameters = array();
							foreach ($reflect->getParameters() as $i => $param) {

								$name = '$' . $param->getName();

								if ($param->isDefaultValueAvailable()) {
									$default = $param->getDefaultValue();
									if (is_string($default)) {
			    						$name .= ' = \'' . $default . '\'';
									} else if (is_null($default)) {
			    						$name .= ' = NULL';
									} else if (is_array($default)) {
			    						$name .= ' = array(' . implode(', ', $default) . ')';
									} else {
			    						$name .= ' = ' . $default;
									}
								}

								$parameters[] = $name;

							}

							$class_methods[] = array('name' => $method, 'parameters' => $parameters);

						}
					}

				}

				sort($class_methods);

			//--------------------------------------------------
			// Variables

				$this->set('form_classes', $form_classes);
				$this->set('class_methods', $class_methods);
				$this->set('show', $show);

		}

	}

?>