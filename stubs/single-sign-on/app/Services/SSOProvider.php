<?php

namespace App\Services;

use App\Models\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class SSOProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    private $responseError = 'error';

    /**
     * @var string
     */
    private $responseCode;

    public function __construct(array $options = [], array $collaborators = [])
    {
        $options['clientId'] = config('services.sso_server.client_id');
        $options['clientSecret'] = config('services.sso_server.client_secret');
        $options['redirectUri'] = config('services.sso_server.redirect');

        $possible = $this->getConfigurableOptions();
        $configured = array_intersect_key($options, array_flip($possible));

        foreach ($configured as $key => $value) {
            $this->$key = $value;
        }

        // Remove all options that are only used locally
        $options = array_diff_key($options, $configured);

        parent::__construct($options, $collaborators);
    }

    /**
     * Returns all options that can be configured.
     *
     * @return array
     */
    protected function getConfigurableOptions()
    {
        return [
            'accessTokenMethod',
            'accessTokenResourceOwnerId',
            'scopeSeparator',
            'responseError',
            'responseCode',
            'responseResourceOwnerId',
            'scopes',
            'pkceMethod',
        ];
    }

    public function getBaseAuthorizationUrl()
    {
        return config('services.sso_server.authorization_url');
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return config('services.sso_server.token_url');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return config('services.sso_server.resource_url');
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException($data, $response->getStatusCode(), $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, 'id');
    }

    public function mappingUser(AccessToken $token)
    {
        try {
            $resourceOwner = $this->getResourceOwner($token);
            $userInfo = $resourceOwner->toArray();
        } catch (\Throwable $th) {
            throw $th;
        }

        $user = User::where('sso_id', $userInfo['id'])->first();

        if (! $user) {
            $user = User::where('email', $userInfo['email'])->first();

            if (! $user) {
                $user = new User();
            }

            $user->sso_id = $userInfo['id'];
        }

        $user->name = $userInfo['name'];
        $user->email = $userInfo['email'];
        $user->email_verified_at = $userInfo['email_verified_at'];
        $user->save();

        return $user;
    }
}
