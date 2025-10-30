<?php

namespace Softadastra\Application\Html;

class FormGenerator
{
    private $fields = [];
    private $action;
    private $method;
    private $enctype;

    public function __construct($action, $method = 'POST', $enctype = 'multipart/form-data')
    {
        $this->action = $action;
        $this->method = $method;
        $this->enctype = $enctype;
    }

    public function addField($type, $name, $label, $attributes = [], $defaultValue = null)
    {
        $this->fields[] = [
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'attributes' => $attributes,
            'defaultValue' => $defaultValue
        ];
    }

    public function generateForm()
    {
        echo '<form action="' . htmlspecialchars($this->action) . '" method="' . htmlspecialchars($this->method) . '" enctype="' . htmlspecialchars($this->enctype) . '" class="formulaire" id="uploadForm">';

        foreach ($this->fields as $field) {
            echo $this->generateField($field);
        }

        echo '<div style="text-align:center; margin-top:50px;">
                <button type="submit" class="softadastra-button" id="submitFormBtn" style="background-color: #007185; border: 1px solid #007185;">
                    <i class="fas fa-paper-plane"></i>
                    Upload Brand
                </button>
              </div>';

        echo '</form>';
    }

    private function generateField($field)
    {
        $html = '<label for="' . htmlspecialchars($field['name']) . '" class="mb-3">' . htmlspecialchars($field['label']) . ':</label>';

        $defaultValue = isset($field['defaultValue']) ? htmlspecialchars($field['defaultValue']) : '';

        if ($field['type'] == 'textarea') {
            $html .= '<div class="softadastra-textarea-field">';
            $html .= '<textarea name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '"';
            foreach ($field['attributes'] as $key => $value) {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
            $html .= '>' . $defaultValue . '</textarea>';
            $html .= '</div>';
        } elseif ($field['type'] == 'select') {
            $html .= '<select name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '">';
            if (isset($field['attributes']['options'])) {
                foreach ($field['attributes']['options'] as $optionValue => $optionLabel) {
                    $html .= '<option value="' . htmlspecialchars($optionValue) . '">' . htmlspecialchars($optionLabel) . '</option>';
                }
            }
            $html .= '</select>';
        } else {
            $html .= '<div class="softadastra-text-field">';
            $html .= '<input type="' . htmlspecialchars($field['type']) . '" id="' . htmlspecialchars($field['name']) . '" name="' . htmlspecialchars($field['name']) . '" value="' . $defaultValue . '"';
            foreach ($field['attributes'] as $key => $value) {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
            $html .= '>';
            $html .= '</div>';
        }

        return $html;
    }

    public function validateField($field)
    {
        if (isset($field['attributes']['required']) && empty($_POST[$field['name']])) {
            return $field['label'] . ' is required';
        }
        return null;
    }

    public function validateForm()
    {
        $errors = [];
        foreach ($this->fields as $field) {
            $error = $this->validateField($field);
            if ($error) {
                $errors[] = $error;
            }
        }
        return $errors;
    }
}
