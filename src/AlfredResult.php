<?php

namespace App;

class AlfredResult
{
    private $items;

    private $shared;

    public function __construct()
    {
        $this->items = [];
        $this->shared = [];
    }

    public function addItem($item)
    {
        $this->items[] = new AlfredResultItem($this, $item);
    }

    public function getShared()
    {
        return $this->shared;
    }

    public function setShared($key, $value)
    {
        $this->shared[$key] = $value;
    }

    public function __toString()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><items>';

        foreach ($this->items as $item) {
            $xml .= $item;
        }

        $xml .= '</items>';

        return $xml;
    }
}
