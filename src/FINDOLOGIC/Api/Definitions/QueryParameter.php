<?php

namespace FINDOLOGIC\Api\Definitions;

class QueryParameter
{
    const
        SERVICE_ID = 'shopkey',
        SHOP_URL = 'shopurl',
        USER_IP = 'userip',
        REFERER = 'referer',
        REVISION = 'revision',
        QUERY = 'query',
        ATTRIB = 'attrib',
        ORDER = 'order',
        PROPERTIES = 'properties',
        PUSH_ATTRIB = 'pushAttrib',
        COUNT = 'count',
        FIRST = 'first',
        IDENTIFIER = 'identifier',
        GROUP = 'group',
        FORCE_ORIGINAL_QUERY = 'forceOriginalQuery',
        OUTPUT_ATTRIB = 'outputAttrib',
        SELECTED = 'selected';

    private static $list = [
        self::SERVICE_ID,
        self::SHOP_URL,
        self::USER_IP,
        self::REFERER,
        self::REVISION,
        self::QUERY,
        self::ATTRIB,
        self::ORDER,
        self::PROPERTIES,
        self::PUSH_ATTRIB,
        self::COUNT,
        self::FIRST,
        self::IDENTIFIER,
        self::GROUP,
        self::FORCE_ORIGINAL_QUERY,
        self::OUTPUT_ATTRIB,
        self::SELECTED,
    ];

    /**
     * Returns an array of all available query parameters.
     * @return array
     */
    public static function getList()
    {
        return self::$list;
    }
}
