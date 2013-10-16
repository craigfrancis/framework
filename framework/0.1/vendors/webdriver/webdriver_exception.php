<?php

/**
 * WebDriverException
 */
class WebDriverException extends Exception {

    public function __construct($message, $code, $previous = null) {
        parent::__construct($message, $code);
    }
}
/**
 * Description of NoSuchElementException
 *
 * @author kolec
 */
class NoSuchElementException extends WebDriverException {
    private $json_response;
    public function __construct($json_response) {
        parent::__construct("No such element exception", WebDriverResponseStatus::NoSuchElement, null);
        $this->json_response = $json_response;
    }
}

?>