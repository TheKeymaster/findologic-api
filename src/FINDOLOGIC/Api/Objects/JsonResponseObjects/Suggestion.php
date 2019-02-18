<?php

namespace FINDOLOGIC\Api\Objects\JsonResponseObjects;

class Suggestion
{
    /** @var string $label */
    private $label;

    /** @var string $block */
    private $block;

    /** @var string $frequency */
    private $frequency;

    /** @var string|null $imageUrl */
    private $imageUrl;

    /** @var float|null $price */
    private $price;

    /** @var string|null $identifier */
    private $identifier;

    /** @var float|null $basePrice */
    private $basePrice;

    /** @var string|null $basePriceUnit */
    private $basePriceUnit;

    /** @var string|null $basePriceUnit */
    private $url;

    /** @var string|null $ordernumber */
    private $ordernumber;

    public function __construct($response)
    {
        $this->label = (string)$response->label;
        $this->block = (string)$response->block;
        $this->frequency = (string)$response->frequency;
        $this->imageUrl = (string)$response->imageUrl;
        $this->price = (float)$response->price; // TODO: Set to NULL if it is zero
        $this->identifier = (string)$response->identifier;
        $this->basePrice = (float)$response->basePrice; // TODO: Set to NULL if it is zero
        $this->basePriceUnit = (string)$response->basePriceUnit;
        $this->url = (string)$response->url;
        $this->ordernumber = property_exists($response, 'ordernumber') ? (string)$response->ordernumber : null;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @return string
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @return string|null
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return float|null
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * @return string|null
     */
    public function getBasePriceUnit()
    {
        return $this->basePriceUnit;
    }

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function getOrdernumber()
    {
        return $this->ordernumber;
    }
}