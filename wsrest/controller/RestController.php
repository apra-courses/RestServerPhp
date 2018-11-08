<?php

abstract class RestController {

    const ERROR_PRECONDITION = 412;

    private $lastErrorCode;
    private $lastErrorDescription;
    private $lastAction;

    public function handleError($code, $description) {
        $this->setLastErrorCode($code);
        $this->setLastErrorDescription($description);
        error_log($this->getLastErrorCode() . " - " . $this->getLastErrorDescription());
    }

    public function resetLastError() {
        $this->setLastErrorCode(0);
        $this->setLastErrorDescription("");
    }

    public function getLastErrorCode() {
        return $this->lastErrorCode;
    }

    public function getLastErrorDescription() {
        return $this->lastErrorDescription;
    }

    public function setLastErrorCode($lastErrorCode) {
        $this->lastErrorCode = $lastErrorCode;
    }

    public function setLastErrorDescription($lastErrorDescription) {
        $this->lastErrorDescription = $lastErrorDescription;
    }

    public function getLastAction() {
        return $this->lastAction;
    }

    public function setLastAction($lastAction) {
        $this->lastAction = $lastAction;
    }

}

?>