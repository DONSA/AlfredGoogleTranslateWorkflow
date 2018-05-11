<?php

class AlfredResult
{
    private $items;

    private $shared;

    public function __construct() {
        $this->items = [];
        $this->shared = [];
    }

    public function addItem($item) {
        array_push($this->items, new AlfredResultItem($this, $item));
    }

    public function getShared() {
        return $this->shared;
    }

    public function setShared($key, $value) {
        $this->shared[$key] = $value;
    }

    public function __toString() {
		$xml = '<?xml version="1.0" encoding="utf-8"?><items>';

		foreach ($this->items as $item) {
			$xml .= $item;
		}

		$xml .= "</items>";

		return $xml;
	}
}

class AlfredResultItem
{
	private $result;
	private $item;

	public function __construct($result, $item) {
		$this->result = $result;
		$this->item = $item;
	}

	public function __toString() {
		$shared = $this->result->getShared();
		$options = array_merge($shared, $this->item);

		$xml = '<item';
		foreach (['uid', 'arg', 'valid', 'autocomplete'] as $key) {
			if (array_key_exists($key, $options)) $xml .= ' '.$key.'="'.$options[$key].'"';
		}
		$xml .= '>';

		foreach (['title', 'subtitle', 'icon'] as $key) {
			if (array_key_exists($key, $options)) $xml .= "<$key>".$options[$key]."</$key>";
		}

		$xml .= '</item>';

        return $xml;
	}
}
