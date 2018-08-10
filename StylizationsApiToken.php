<?php
namespace App\Service\Supplier;

use App\Utils\Credentials;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\Network\Http\Client;

class StylizationsApiToken
{
    private $httpClient;
    
    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    
    public function getToken()
    {
        /** @var Credentials $credentials */
        $credentials = $this->getSavedCredentials();
        
        if (!$credentials->isValid()) {
            $credentials = $this->refreshCredentials($credentials->getRefreshToken());
            $this->saveCredentials($credentials);
        }
        
        return $credentials->getToken();
    }
    
    public function getSavedCredentials()
    {
        $cacheConfig = 'credentials';
        $credits = Cache::read('style_api', $cacheConfig);
        
        if (!$credits) {
            $credits = $this->getCredentialDataFromApi();
            Cache::write('style_api', $credits, $cacheConfig);
        }
        $credentials = new Credentials($credits);
        
        return $credentials;
    }
    
    public function refreshCredentials($refreshToken)
    {
        $url = Configure::read('StylizationsApi.refreshUrl');
        try {
            $response = $this->httpClient->post($url, ['refresh_token' => $refreshToken]);
        } catch (\Exception $exception) {
            Log::write('error', 'Problem z połączeniem z API stylizacji: ' . $exception->getMessage());
            throw new Exception('Aplikacja nie może połączyć się usługą obłsugi stylizacji. Spróbuj ponownie za chwilę lub skontaktuj się z Service Desk.', 408);
        }
        $credentialsData = json_decode($response->body(), true);
        $credentialsData['created_at'] = new Time();
        
        $credentials = new Credentials($credentialsData);
        
        return $credentials;
    }
    
    public function saveCredentials(Credentials $credentials)
    {
        $credentialData = [
            'token' => $credentials->getToken(),
            'refresh_token' => $credentials->getRefreshToken(),
            'created_at' => $credentials->getCreatedAt()
        ];
        Cache::write('style_api', $credentialData, 'credentials');
    }
    
    private function getCredentialDataFromApi()
    {
        $url = Configure::read('StylizationsApi.tokenUrl');
        $username = Configure::read('StylizationsApi.tokenUsername');
        $password = Configure::read('StylizationsApi.tokenPassword');
        try {
            $response = $this->httpClient->post($url, [
                '_username' => $username,
                '_password' => $password
            ]);
        } catch (\Exception $exception) {
            Log::write('error', 'Problem z połączeniem z API stylizacji: ' . $exception->getMessage());
            throw new Exception('Aplikacja nie może połączyć się usługą obłsugi stylizacji. Spróbuj ponownie za chwilę lub skontaktuj się z Service Desk.', 408);
        }
        $credentialsData = json_decode($response->body(), true);
        $credentialsData['created_at'] = new Time();
        
        return $credentialsData;
    }
}