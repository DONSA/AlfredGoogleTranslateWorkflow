<?php

namespace App;

class AlfredResultItem
{
    private $result;
    private $item;

    /**
     * AlfredResultItem constructor.
     *
     * @param $result AlfredResult
     * @param $item
     */
    public function __construct($result, $item)
    {
        $this->result = $result;
        $this->item = $item;
    }

    public function __toString()
    {
        $shared = $this->result->getShared();
        $options = array_merge($shared, $this->item);

        $xml = '<item';
        foreach (['uid', 'arg', 'valid', 'autocomplete'] as $key) {
            if (array_key_exists($key, $options)) {
                $xml .= ' ' . $key . '="' . $options[$key] . '"';
            }
        }
        $xml .= '>';

        foreach (['title', 'subtitle', 'icon'] as $key) {
            if (array_key_exists($key, $options)) {
                $xml .= "<{$key}>" . $options[$key] . "</{$key}>";
            }
        }

        $xml .= '</item>';

        return $xml;
    }
}
