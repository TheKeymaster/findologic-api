<?php /** @noinspection PhpParamsInspection (Not relevant for tests) */

namespace FINDOLOGIC\Tests;

use FINDOLOGIC\Definitions\RequestType;
use FINDOLOGIC\Exceptions\ConfigException;
use FINDOLOGIC\Exceptions\ParamNotSetException;
use FINDOLOGIC\Exceptions\ServiceNotAliveException;
use FINDOLOGIC\FindologicApi;
use FINDOLOGIC\Objects\JsonResponse;
use FINDOLOGIC\Objects\XmlResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FindologicApiTest extends TestCase
{
    /** @var $httpClientMock MockObject */
    public $httpClientMock;

    /** @var $httpClientMock MockObject */
    public $responseMock;

    public function setUp()
    {
        $this->httpClientMock = $this->getMockBuilder(Client::class)
            ->setMethods(['request'])
            ->getMock();

        $this->responseMock = $this->getMockBuilder(Response::class)
            ->setMethods(['getBody', 'getStatusCode'])
            ->getMock();
    }

    /**
     * Sets default expectations that are required in basically every test that should work when requesting.
     * @param string $requestType
     */
    public function setDefaultExpectations($requestType = 'sendSearchRequest')
    {
        if ($requestType !== 'sendSuggestionRequest') {
            // Get contents from a real response locally.
            $realResponseData = file_get_contents(__DIR__ . '/../Mockdata/demoResponse.xml');
        } else {
            // Get contents from a real response locally.
            $realResponseData = file_get_contents(__DIR__ . '/../Mockdata/demoResponseSuggest.json');
        }

        // Alivetest.
        $this->responseMock->expects($this->at(0))->method('getBody')->willReturn('alive');
        $this->responseMock->expects($this->at(1))->method('getStatusCode')->willReturn(200);

        // Search, Navigation or Suggest request.
        $this->responseMock->expects($this->at(2))->method('getBody')->willReturn($realResponseData);
        $this->responseMock->expects($this->at(3))->method('getStatusCode')->willReturn(200);

        // Both requests should respond with the responseMock.
        $this->httpClientMock->expects($this->at(0))->method('request')->willReturn($this->responseMock);
        $this->httpClientMock->expects($this->at(1))->method('request')->willReturn($this->responseMock);
    }

    /**
     * @return FindologicApi FindologicApi
     */
    public function getDefaultFindologicApi()
    {
        return new FindologicApi([
            FindologicApi::SHOPKEY => '80AB18D4BE2654A78244106AD315DC2C',
            FindologicApi::HTTP_CLIENT => $this->httpClientMock,
            FindologicApi::API_URL => 'https://blubbergurken.io/%s/%s',
            FindologicApi::REQUEST_TIMEOUT => 1,
            FindologicApi::ALIVETEST_TIMEOUT => 1,
        ]);
    }

    public function requestProvider()
    {
        return [
            ['sendSearchRequest'],
            ['sendNavigationRequest'],
            ['sendSuggestionRequest'],
        ];
    }

    public function invalidConfigProvider()
    {
        return [
            'apiUrl as object' => [[FindologicApi::API_URL => new \stdClass()]],
            'apiUrl as integer' => [[FindologicApi::API_URL => 46]],
            'alivetest timeout as object' => [[FindologicApi::ALIVETEST_TIMEOUT => new \stdClass()]],
            'alivetest timeout as string' => [[FindologicApi::ALIVETEST_TIMEOUT => 'A timeout of 50 years pls!']],
            'request timeout as object' => [[FindologicApi::REQUEST_TIMEOUT => new \stdClass()]],
            'request timeout as string' => [[FindologicApi::REQUEST_TIMEOUT => 'A timeout of 90 quadrillion yrs pls!']],
        ];
    }

    /**
     * @dataProvider invalidConfigProvider
     * @param $config mixed
     */
    public function testInvalidFindologicApiConfigThrowsAnException($config)
    {
        try {
            new FindologicApi($config);
            $this->fail('An invalid FindologicApi config should throw an exception!');
        } catch (ConfigException $e) {
            $this->assertEquals('Invalid FindologicApi config.', $e->getMessage());
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testGuzzleFailsWillThrowAnException($requestType)
    {
        $expectedExceptionMessage = 'Guzzle is dying. Maybe it can be saved with a heart massage.';

        $this->httpClientMock->expects($this->at(0))
            ->method('request')
            ->willThrowException(new RequestException($expectedExceptionMessage, new Request('GET', 'a')));

        $findologicApi = $this->getDefaultFindologicApi()
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        try {
            $findologicApi->{$requestType}();
            $this->fail('If Guzzle fails a ServiceNotAliveException should occur!');
        } catch (ServiceNotAliveException $e) {
            $this->assertEquals(
                sprintf('The service is not alive. Reason: %s', $expectedExceptionMessage),
                $e->getMessage()
            );
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testAlivetestWorks($requestType)
    {
        $this->setDefaultExpectations($requestType);
        $findologicApi = $this->getDefaultFindologicApi();

        $findologicApi
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        if ($requestType === 'sendSuggestionRequest') {
            try {
                $findologicApi->{$requestType}();
                $this->fail('A suggestion request should not require an alivetest.');
            } catch (\Exception $exception) {
                // Send another request that will respond with real data.
            }
        }
        $response = $findologicApi->{$requestType}();

        if ($requestType === 'sendSearchRequest' || $requestType == 'sendNavigationRequest') {
            $this->assertInstanceOf(XmlResponse::class, $response);
        } elseif ($requestType === 'sendSuggestionRequest') {
            $this->assertInstanceOf(JsonResponse::class, $response);
        } else {
            $this->fail('Unknown request type.');
        }
    }

    public function failingAlivetestProvider()
    {
        return [
            'alivetest is not alive' => ['not alive', 200],
            'alivetest is alive but http code is 500' => ['alive', 500],
            'alivetest is not alive and http code is 500' => ['definitely not alive', 500],
        ];
    }

    /**
     * @dataProvider failingAlivetestProvider
     * @param string $responseBody
     * @param int $httpCode
     */
    public function testExceptionIsThrownIfServiceIsNotAlive($responseBody, $httpCode)
    {
        $this->responseMock->expects($this->any())->method('getBody')->willReturn($responseBody);
        $this->responseMock->expects($this->any())->method('getStatusCode')->willReturn($httpCode);

        $this->httpClientMock->expects($this->any())->method('request')->willReturn($this->responseMock);

        $findologicApi = $this->getDefaultFindologicApi();
        $findologicApi
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        $requestTypes = $this->requestProvider();

        foreach ($requestTypes as $requestType) {
            if ($requestType[0] === 'sendSuggestionRequest') {
                // A suggestion request does not need an alivetest.
                $this->assertTrue(true);
            } else {
                try {
                    $findologicApi->{$requestType[0]}();
                    $this->fail('A ServiceNotAliveException should occur if the service is not alive!');
                } catch (ServiceNotAliveException $e) {
                    if ($httpCode === 200 || $responseBody !== 'alive') {
                        $expectedErrorMessage = 'The service is not alive. Reason: %s';
                    } else {
                        $expectedErrorMessage = sprintf(
                            'The service is not alive. Reason: Unexpected status code %s.',
                            $httpCode
                        );
                    }
                    $this->assertEquals(sprintf($expectedErrorMessage, $responseBody), $e->getMessage());
                }
            }
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testRequiredParamShopurlMissingWillThrowAnException($requestType)
    {
        $findologicApi = $this->getDefaultFindologicApi();

        $findologicApi
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        try {
            $findologicApi->{$requestType}();
            $this->fail('A ParamException was expected to occur when the shopurl parameter is missing.');
        } catch (ParamNotSetException $e) {
            $this->assertEquals('Required param shopurl is not set.', $e->getMessage());
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testRequiredParamUseripMissingWillThrowAnException($requestType)
    {
        $findologicApi = $this->getDefaultFindologicApi();

        $findologicApi
            ->setShopurl('www.blubbergurken.io')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        try {
            $findologicApi->{$requestType}();
            $this->fail('A ParamException was expected to occur when the userip parameter is missing.');
        } catch (ParamNotSetException $e) {
            $this->assertEquals('Required param userip is not set.', $e->getMessage());
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testRequiredParamRefererMissingWillThrowAnException($requestType)
    {
        $findologicApi = $this->getDefaultFindologicApi();

        $findologicApi
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setRevision('1.0.0');

        try {
            $findologicApi->{$requestType}();
            $this->fail('A ParamException was expected to occur when the referer parameter is missing.');
        } catch (ParamNotSetException $e) {
            $this->assertEquals('Required param referer is not set.', $e->getMessage());
        }
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testRequiredParamRevisionMissingWillThrowAnException($requestType)
    {
        $findologicApi = $this->getDefaultFindologicApi();

        $findologicApi
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale');

        try {
            $findologicApi->{$requestType}();
            $this->fail('A ParamException was expected to occur when the revision parameter is missing.');
        } catch (ParamNotSetException $e) {
            $this->assertEquals('Required param revision is not set.', $e->getMessage());
        }
    }

    public function invalidShopkeyProvider()
    {
        return [
            'shopkey length not optimal' => ['INVALIDAF'],
            'shopkey contains invalid characters' => ['80AB18D4BE2654R78244106AD315DC2C'],
            'shopkey is lowercased' => ['80ab18d4be2654r78244106ad315dc2c'],
            'shopkey contains spaces' => ['80AB18D4BE2654A7 8244106AD315DC2C'],
            'shopkey contains special characters' => ['AAAAAA.AAAAAAÄAAAAAAAAAAAAAAAAA_'],
        ];
    }

    /**
     * @dataProvider invalidShopkeyProvider
     * @param $shopkey string
     */
    public function testExceptionIsThrownIfShopkeyInConfigIsInvalid($shopkey)
    {
        try {
            $findologicApi = new FindologicApi([
                FindologicApi::SHOPKEY => $shopkey
            ]);

            $this->fail('A ConfigException was expected to occur when the shopkey is invalid.');
        } catch (ConfigException $e) {
            $this->assertEquals('Invalid FindologicApi config.', $e->getMessage());
        }
    }

    public function testAllRequestTypesAreAvailable()
    {
        $expectedAvailableRequestTypes = [
            'alivetest.php',
            'index.php',
            'selector.php',
            'autocomplete.php',
        ];
        $availableRequestTypes = RequestType::getList();

        $this->assertEquals($expectedAvailableRequestTypes, $availableRequestTypes);
    }

    /**
     * @dataProvider requestProvider
     * @param $requestType string
     */
    public function testFindologicResponseTimeCanBeSeen($requestType)
    {
        $this->setDefaultExpectations($requestType);

        /** @var FindologicApi $findologicApi */
        $findologicApi = $this->getDefaultFindologicApi()
            ->setShopurl('www.blubbergurken.io')
            ->setUserip('127.0.0.1')
            ->setReferer('www.blubbergurken.io/blubbergurken-sale')
            ->setRevision('1.0.0');

        if ($requestType === 'sendSuggestionRequest') {
            try {
                $findologicApi->{$requestType}();
                $this->fail('A suggestion request should not require an alivetest.');
            } catch (\Exception $exception) {
                // Send another request that will respond with real data.
            }
        }
        $findologicApi->{$requestType}();

        // Please note that the response times in the tests are fast af, because they do load the response directly from
        // the file system.
        $this->assertEquals(0, $findologicApi->getResponseTime(), '', 0.01);
    }

    public function testWhenNoExplicitClientIsSetTheDefaultClientIsSet()
    {
        $findologicApi = new FindologicApi(['shopkey' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA']);
        $httpClient = $findologicApi->getConfigByKey('httpClient');

        $this->assertInstanceOf('GuzzleHttp\Client', $httpClient);
    }

    public function testConfigByKeyWillThrowAnExceptionWhenTheKeyIsUnknown()
    {
        $findologicApi = $this->getDefaultFindologicApi();
        try {
            $findologicApi->getConfigByKey('thisKeyDoesNotExist');
            $this->fail('An InvalidArgumentException should happen if a key does not exist.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Unknown or unset configuration value.', $e->getMessage());
        }
    }

    public function testGetAllParamsWillReturnAllSetParams()
    {
        $expectedShopurl = 'www.blubbergurken.io';
        $expectedUserIp = '127.0.0.1';
        $expectedReferer = 'www.blubbergurken.io/blubbergurken-sale';
        $expectedRevision = '1.0.0';

        $expectedParams = [
            'shopurl' => $expectedShopurl,
            'userip' => $expectedUserIp,
            'referer' => $expectedReferer,
            'revision' => $expectedRevision,
        ];

        $findologicApi = $this->getDefaultFindologicApi()
            ->setShopurl($expectedShopurl)
            ->setUserip($expectedUserIp)
            ->setReferer($expectedReferer)
            ->setRevision($expectedRevision);

        $this->assertEquals($expectedParams, $findologicApi->getAllParams());
    }

    public function testGetParamThatDoesNotExistWillThrowAnException()
    {
        $findologicApi = $this->getDefaultFindologicApi();
        try {
            $findologicApi->getParam('geilerParam');
            $this->fail('An InvalidArgumentException should happen if a key does not exist.');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Unknown or unset param.', $e->getMessage());
        }
    }
}
