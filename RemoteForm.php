<?php

class RemoteForm {

    private $_action = '';
    private $_method = 'get';
    private $_form = null;
    private $_navigator = null;
    private $_attributes = array();

    public function __construct(\DOMElement $form) {

        $doc = new \DOMDocument();
        $this->_form = $doc->importNode($form, true);
        $doc->appendChild($this->_form);
        $this->_navigator = new \DOMXpath($doc);
        if (trim($this->_form->getAttribute('action')) != '') {
            $this->_action = trim($this->_form->getAttribute('action'));
        }

        $method = strtolower(trim($this->_form->getAttribute('method')));
        if (in_array($method, array('get', 'post'))) {
            $this->_method = $method;
        }
        $this->_discoverParameters();
    }

    private function _discoverParameters() {

        foreach ($this->_navigator->query('//input | //select | //textarea') as $element) {
            switch (strtolower($element->tagName)) {
                case 'input':
                    switch (strtolower($element->getAttribute('type'))) {
                        case 'submit':
                            break;
                        case 'button':
                            break;
                        case 'text':
                        case 'password':
                        case 'hidden':
                            $this->_setAttributeByString($element->getAttribute('name'), $element->getAttribute('value'));
                            break;
                        case 'checkbox':
                        case 'radio':
                            if (trim($element->getAttribute('checked')) != '') {
                                $this->_setAttributeByString($element->getAttribute('name'), $element->getAttribute('value'));
                            }
                            break;
                    }
                    break;
                case 'select':
                    foreach ($this->_navigator->query('//option[@selected != ""]', $element) as $option) {
                        $this->_setAttributeByString($element->getAttribute('name'), $option->hasAttribute('value') ? $option->getAttribute('value') : $option->nodeValue );
                    }
                    break;
                case 'textarea':
                    $this->_setAttributeByString($element->getAttribute('name'), $element->nodeValue);
                    break;
            }
        }
    }

    public function setAttributeByName($fieldName, $value) {
        return $this->_setAttributeByString($fieldName, $value);
    }

    private function _setAttributeByString($fieldName, $fieldValue) {
      $this->_attributes[$fieldName] = $fieldValue;
    }

    public function getParameters() {
        return $this->_attributes;
    }

    public function getAction() {
        return $this->_action;
    }

    public function setAction($action) {
        $this->_action = $action;
    }

    public function getMethod() {
        return $this->_method;
    }

}
