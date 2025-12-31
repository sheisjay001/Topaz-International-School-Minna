<?php
/**
 * Validator Class
 * A simple, dependency-free validation helper.
 */
class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function required($field, $label = null) {
        $label = $label ?? ucfirst($field);
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email($field, $label = null) {
        $label = $label ?? ucfirst($field);
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function min($field, $length, $label = null) {
        $label = $label ?? ucfirst($field);
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "$label must be at least $length characters.";
        }
        return $this;
    }

    public function max($field, $length, $label = null) {
        $label = $label ?? ucfirst($field);
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = "$label must not exceed $length characters.";
        }
        return $this;
    }

    public function match($field, $matchField, $label = null, $matchLabel = null) {
        $label = $label ?? ucfirst($field);
        $matchLabel = $matchLabel ?? ucfirst($matchField);
        if (isset($this->data[$field]) && isset($this->data[$matchField]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = "$label does not match $matchLabel.";
        }
        return $this;
    }

    public function isValid() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
