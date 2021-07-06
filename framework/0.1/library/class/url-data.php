<?php

	class url_data extends check implements JsonSerializable {

		private $value = NULL;

		public function __construct($value, $mime = NULL) {
			if (!is_array($value)) {
				$value = ['path' => $value];
			}
			if (!isset($value['mime'])) {
				$value['mime'] = ($mime ?? http_mime_type($value['path']));
			}
			if (isset($value['data'])) {
				$value['path'] = NULL; // Drop 'path' if 'data' has been provided (used with debug_dump).
			} else {
				$value['data'] = file_get_contents($value['path']);
			}
			$this->value = $value;
		}

		public function mime_get() {
			return $this->value['mime'];
		}

		public function _debug_dump() {
			$path = $this->value['path'];
			if ($path) {
				$path = str_replace(ROOT, '', $path);
			} else {
				$path = 'data';
			}
			return 'url_data("' . $path . '", "' . $this->value['mime'] . '")';
		}

		public function __toString() {
			return 'data:' . $this->value['mime'] . ';base64,' . base64_encode($this->value['data']);
		}

		#[ReturnTypeWillChange]
		public function jsonSerialize() { // If JSON encoded, fall back to being a simple string (typically going to the browser or API)
			return $this->__toString();
		}

	}

?>