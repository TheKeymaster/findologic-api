<?php

namespace FINDOLOGIC\Api\Definitions;

class Endpoint
{
    const
        ALIVETEST = 'alivetest.php',
        SEARCH = 'index.php',
        NAVIGATION = 'selector.php',
        SUGGESTION = 'autocomplete.php';

    private static $availableEndpoints = [
        self::ALIVETEST,
        self::SEARCH,
        self::NAVIGATION,
        self::SUGGESTION,
    ];

    /**
     * Returns an array of all available endpoints.
     * @return array
     */
    public static function getAvailableEndpoints()
    {
        return self::$availableEndpoints;
    }
}
