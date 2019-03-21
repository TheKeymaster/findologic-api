<?php

namespace FINDOLOGIC\Api\Validators;

use GuzzleHttp\Client;
use Valitron\Validator;

class ConfigValidator extends Validator
{
    public function __construct(array $data = [], array $fields = [], $lang = null, $langDir = null)
    {
        parent::__construct($data, $fields, $lang, $langDir);
        $this->addInstanceRule('shopkey', function ($field, $value) {
            return (is_string($value) && preg_match('/^[A-F0-9]{32}$/', $value));
        }, self::ERROR_DEFAULT);
        $this->addInstanceRule('httpClient', function ($field, $value) {
            return $value instanceof Client;
        }, self::ERROR_DEFAULT);
    }
}
