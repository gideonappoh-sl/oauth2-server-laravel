<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streamlabs\OAuth2Server\Storage;

use Carbon\Carbon;
use LocalizedLeague\OAuth2\Server\Entity\AuthCodeEntity;
use LocalizedLeague\OAuth2\Server\Entity\ScopeEntity;
use LocalizedLeague\OAuth2\Server\Storage\AuthCodeInterface;

/**
 * This is the fluent auth code class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class FluentAuthCode extends AbstractFluentAdapter implements AuthCodeInterface
{
    /**
     * Get the auth code.
     *
     * @param  string $code
     *
     * @return \League\OAuth2\Server\Entity\AuthCodeEntity
     */
    public function get($code)
    {
        $result = $this->getConnection()
            ->table($this->getFullTableName('oauth_auth_codes'))
            ->where($this->getFullTableName('oauth_auth_codes.auth_code'), $code)
            ->where($this->getFullTableName('oauth_auth_codes.expire_time'), '>=', time())
            ->first();

        if (is_null($result)) {
            return;
        }

        return (new AuthCodeEntity($this->getServer()))
            ->setId($result->auth_code)
            ->setRedirectUri($result->client_redirect_uri)
            ->setExpireTime((int) $result->expire_time);
    }

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AuthCodeEntity $token The auth code
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AuthCodeEntity $token)
    {
        $result = $this->getConnection()
            ->table($this->getFullTableName('oauth_auth_code_scopes'))
            ->select($this->getFullTableName('oauth_scopes.*'))
            ->join(
                $this->getFullTableName('oauth_scopes'),
                $this->getFullTableName('oauth_auth_code_scopes.scope'),
                '=',
                $this->getFullTableName('oauth_scopes.id')
            )
            ->where($this->getFullTableName('oauth_auth_code_scopes.auth_code'), $token->getId())
            ->get();

        $scopes = [];

        foreach ($result as $scope) {
            $scopes[] = (new ScopeEntity($this->getServer()))->hydrate([
               'id' => $scope->id,
                'description' => $scope->description,
            ]);
        }

        return $scopes;
    }

    /**
     * Associate a scope with an access token.
     *
     * @param  \League\OAuth2\Server\Entity\AuthCodeEntity $token The auth code
     * @param  \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope(AuthCodeEntity $token, ScopeEntity $scope)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_auth_code_scopes'))
            ->insert([
                'auth_code' => $token->getId(),
                'scope' => $scope->getId(),
            ]);
    }

    /**
     * Delete an access token.
     *
     * @param  \League\OAuth2\Server\Entity\AuthCodeEntity $token The access token to delete
     *
     * @return void
     */
    public function delete(AuthCodeEntity $token)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_auth_codes'))
            ->where($this->getFullTableName('oauth_auth_codes.auth_code'), $token->getId())
            ->delete();
    }

    /**
     * Create an auth code.
     *
     * @param string $token The token ID
     * @param int $expireTime Token expire time
     * @param int $sessionId Session identifier
     * @param string $redirectUri Client redirect uri
     *
     * @return void
     */
    public function create($token, $expireTime, $sessionId, $redirectUri)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_auth_codes'))
            ->insert([
                'auth_code' => $token,
                'session_id' => $sessionId,
                'client_redirect_uri' => $redirectUri,
                'expire_time' => $expireTime,
            ]);
    }
}
