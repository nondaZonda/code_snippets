<?php
namespace App\Test\TestCase\Service\Supplier;

use App\Service\Supplier\StylizationsApiToken;
use App\Utils\Credentials;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use Cake\Cache\Cache;

class StylizationsApiTokenTest extends TestCase
{
    public $fixtures = [
        'app.warehouse_products',
    ];
    
    public function testGetSavedCredentialsReturnsCredentialsObject()
    {
        $credentials = [
            'token' => 'my token',
            'refresh_token' => 'refresh token',
            'created_at' => new Time()
        ];
    
        $cacheConfig = 'credentials';
        Cache::write('style_api', $credentials, $cacheConfig);
        
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        $apiToken = new StylizationsApiToken($httpClientMock);
        $actualResult = $apiToken->getSavedCredentials();
        
        $this->assertInstanceOf(Credentials::class, $actualResult);
    }
    
    public function testGetSavedCredentialsReturnsObjectWithDataSavedToCredentialsCache()
    {
        $expectedToken = 'myToken';
        $expectedRefreshToken = 'myRefreshToken';
        $expectedCreatedAt = new Date('1990-01-13 14:15:03');
        
        $credentials = [
            'token' => $expectedToken,
            'refresh_token' => $expectedRefreshToken,
            'created_at' => $expectedCreatedAt
        ];
        
        $cacheConfig = 'credentials';
        Cache::write('style_api', $credentials, $cacheConfig);
        
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        $apiToken = new StylizationsApiToken($httpClientMock);
        $actualCredentials = $apiToken->getSavedCredentials();
        
        $this->assertEquals($expectedToken, $actualCredentials->getToken());
        $this->assertEquals($expectedRefreshToken, $actualCredentials->getRefreshToken());
        $this->assertEquals($expectedCreatedAt, $actualCredentials->getCreatedAt());
    }
    
    public function testGetSavedCredentialsReturnsCredentialsFromApiWhenNoCredentialsSavedInCache()
    {
        $cacheConfig = 'credentials';
        Cache::clear(false, $cacheConfig);
        $savedCredentials = Cache::read('style_api', $cacheConfig);
        
        $this->assertFalse($savedCredentials, 'Niepoprawne dane wejściowe, cache zapisanych credentials musi być pusty!');
        
        $expectedToken = 'myToken';
        $expectedRefreshToken = 'myRefreshToken';
        $expectedTime = new Time();
        
        $responseMockData = [
            'token' => $expectedToken,
            'data' => [
                'roles' => [
                    "ROLE_API",
                    "ROLE_USER"
                ],
                'user' => [
                    'id' => 9,
                    'email' => 'studio@gmail.com',
                    'username' => 'studio'
                ]
            ],
            'refresh_token' => $expectedRefreshToken
        ];
        
        $responseMock = $this->getMockBuilder('Cake\Http\Client\Response')
            ->getMock();
        
        $responseMock->expects($this->once())
            ->method('body')
            ->will($this->returnValue(json_encode($responseMockData)));
        
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        
        $httpClientMock->expects($this->once())
            ->method('post')
            ->will($this->returnValue($responseMock));
        
        $apiToken = new StylizationsApiToken($httpClientMock);
        $actualCredentials = $apiToken->getSavedCredentials();
        
        $this->assertEquals($expectedToken, $actualCredentials->getToken());
        $this->assertEquals($expectedRefreshToken, $actualCredentials->getRefreshToken());
        
        $actualTime = $actualCredentials->getCreatedAt();
        $this->assertLessThan(2, ($actualTime->toUnixString() - $expectedTime->toUnixString()));
    }
    
    public function testGetSavedCredentialsSavesToCacheCredentialsFromApi()
    {
        $cacheConfig = 'credentials';
        Cache::clear(false, $cacheConfig);
        $savedCredentials = Cache::read('style_api', $cacheConfig);
        
        $this->assertFalse($savedCredentials, 'Niepoprawne dane wejściowe, cache zapisanych credentials musi być pusty!');
        
        $expectedToken = 'myToken';
        $expectedRefreshToken = 'myRefreshToken';
        
        $responseMockData = [
            'token' => $expectedToken,
            'data' => [
                'roles' => [
                    "ROLE_API",
                    "ROLE_USER"
                ],
                'user' => [
                    'id' => 9,
                    'email' => 'studio@gmail.com',
                    'username' => 'studio'
                ]
            ],
            'refresh_token' => $expectedRefreshToken
        ];
        
        $responseMock = $this->getMockBuilder('Cake\Http\Client\Response')
            ->getMock();
        
        $responseMock->expects($this->once())
            ->method('body')
            ->will($this->returnValue(json_encode($responseMockData)));
        
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        
        $httpClientMock->expects($this->once())
            ->method('post')
            ->will($this->returnValue($responseMock));
        
        $apiToken = new StylizationsApiToken($httpClientMock);
        $apiToken->getSavedCredentials();
        $actualCredentials = Cache::read('style_api', $cacheConfig);
        
        $this->assertNotFalse($actualCredentials, 'Brak danych zapisanych w cache!');
        $this->assertEquals($expectedToken, $actualCredentials['token']);
        $this->assertEquals($expectedRefreshToken, $actualCredentials['refresh_token']);
    }
    
    public function testRefreshCredentialsReturnsCredentialsObjectWithNewToken()
    {
        $expectedToken = 'my new happy token !';
        $expectedRefreshToken = 'fresh prince from Bel Air!';
        
        $responseMockData = [
            'token' => $expectedToken,
            'refresh_token' => $expectedRefreshToken
        ];
        $responseMock = $this->getMockBuilder('Cake\Http\Client\Response')
            ->getMock();
        $responseMock->expects($this->once())
            ->method('body')
            ->will($this->returnValue(json_encode($responseMockData)));
        
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        $httpClientMock->expects($this->once())
            ->method('post')
            ->will($this->returnValue($responseMock));

        $apiToken = new StylizationsApiToken($httpClientMock);
    
        $credentialData = [
            'token' => 'token',
            'refresh_token' => 'my refresh token',
            'created_at' => new Time('1990-08-05 12:14:01')
        ];
        $credentials = new Credentials($credentialData);
        
        $actualCredentials = $apiToken->refreshCredentials($credentials->getRefreshToken());
        
        $this->assertInstanceOf(Credentials::class, $actualCredentials);
        $this->assertEquals($expectedToken, $actualCredentials->getToken());
        $this->assertEquals($expectedRefreshToken, $actualCredentials->getRefreshToken());
    }
    
    public function testSaveCredentialsSavesDataToCache()
    {
        $cacheConfig = 'credentials';
        Cache::clear(false, $cacheConfig);
        $savedCredentials = Cache::read('style_api', $cacheConfig);
    
        $this->assertFalse($savedCredentials, 'Niepoprawne dane wejściowe, cache zapisanych credentials musi być pusty!');
    
        $expectedToken = 'myToken';
        $expectedRefreshToken = 'myRefreshToken';
        $expectedCreatedAt = new Date('1990-01-13 14:15:03');
    
        $credentialsData = [
            'token' => $expectedToken,
            'refresh_token' => $expectedRefreshToken,
            'created_at' => $expectedCreatedAt
        ];
    
        $httpClientMock = $this->createMock('Cake\Network\Http\Client');
        $apiToken = new StylizationsApiToken($httpClientMock);
        $credentials = new Credentials($credentialsData);
        $apiToken->saveCredentials($credentials);
        
        $actualCredentialsData = Cache::read('style_api', $cacheConfig);
        
        $this->assertEquals($expectedToken, $actualCredentialsData['token']);
        $this->assertEquals($expectedRefreshToken, $actualCredentialsData['refresh_token']);
        $this->assertEquals($expectedCreatedAt, $actualCredentialsData['created_at']);
    }
}