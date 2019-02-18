<?php

namespace FINDOLOGIC\Api\Objects\XmlResponseObjects;

use SimpleXMLElement;

class Filter
{
    /** @var string $name */
    private $name;

    /** @var string $display */
    private $display;

    /** @var string $select */
    private $select;

    /** @var int $selectedItems */
    private $selectedItems;

    /** @var string $type */
    private $type;

    /** @var Attributes $attributes */
    private $attributes;

    /** @var Item[] $items */
    private $items;

    /** @var bool $hasItems */
    private $hasItems = false;

    /** @var int $itemAmount */
    private $itemAmount = 0;

    /**
     * Filter constructor.
     * @param SimpleXMLElement $response
     */
    public function __construct($response)
    {
        $this->name = (string)$response->name;
        $this->display = (string)$response->display;
        $this->select = (string)$response->select;
        $this->selectedItems = (int)$response->selectedItems;
        $this->type = (string)$response->type;

        if ($response->attributes) {
            $this->attributes = new Attributes($response->attributes);
        }

        if ($response->items) {
            // Get the first <items> element, containing all <item> elements.
            foreach ($response->items[0] as $item) {
                $itemName = (string)$item->name;
                $this->items[$itemName] = new Item($item);
                $this->hasItems = true;
                $this->itemAmount++;
            }
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @return string
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return int
     */
    public function getSelectedItems()
    {
        return $this->selectedItems;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Attributes|null
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getItemAmount()
    {
        return $this->itemAmount;
    }

    /**
     * @return bool
     */
    public function hasItems()
    {
        return $this->hasItems;
    }
}