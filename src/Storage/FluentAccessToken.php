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
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;

/**
 * This is the fluent access token class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class FluentAccessToken extends AbstractFluentAdapter implements AccessTokenInterface
{
    /**
     * Get an instance of Entities\AccessToken.
     *
     * @param string $token The access token
     *
     * @return null|AbstractTokenEntity
     */
    public function get($token)
    {
        $result = $this->getConnection()
                ->table($this->getFullTableName('oauth_access_tokens'))
                ->where($this->getFullTableName('oauth_access_tokens.access_token'), $token)
                ->first();

        if (is_null($result)) {
            return;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->access_token)
               ->setExpireTime(time()+3600);
               //->setExpireTime((int) $result->expire_time);
    }

    /*
    public function getByRefreshToken(RefreshTokenEntity $refreshToken)
    {
        $result = $this->getConnection()
                ->table($this->getFullTableName('oauth_access_tokens'))
                ->select($this->getFullTableName('oauth_access_tokens.*'))
                ->join(
                    $this->getFullTableName('oauth_refresh_tokens'),
                    $this->getFullTableName('oauth_access_tokens.id'),
                    '=',
                    $this->getFullTableName('oauth_refresh_tokens.access_token_id')
                )
                ->where($this->getFullTableName('oauth_refresh_tokens.id'), $refreshToken->getId())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setExpireTime((int)$result->expire_time);
    }
    */

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AccessTokenEntity $token)
    {
        $result = $this->getConnection()
                ->table($this->getFullTableName('oauth_access_token_scopes'))
                ->select($this->getFullTableName('oauth_scopes.*'))
                ->join(
                    $this->getFullTableName('oauth_scopes'),
                    $this->getFullTableName('oauth_access_token_scopes.scope'),
                    '=',
                    $this->getFullTableName('oauth_scopes.id')
                )
                ->where($this->getFullTableName('oauth_access_token_scopes.access_token'), $token->getId())
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
     * Creates a new access token.
     *
     * @param string $token The access token
     * @param int $expireTime The expire time expressed as a unix timestamp
     * @param string|int $sessionId The session ID
     *
     * @return \League\OAuth2\Server\Entity\AccessTokenEntity
     */
    public function create($token, $expireTime, $sessionId)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_access_tokens'))
            ->insert([
                'access_token' => $token,
                'expire_time' => $expireTime,
                'session_id' => $sessionId,
            ]);

        return (new AccessTokenEntity($this->getServer()))
               ->setId($token)
               ->setExpireTime((int) $expireTime);
    }

    /**
     * Associate a scope with an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_access_token_scopes'))
            ->insert([
                'access_token' => $token->getId(),
                'scope' => $scope->getId(),
            ]);
    }

    /**
     * Delete an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token to delete
     *
     * @return void
     */
    public function delete(AccessTokenEntity $token)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_access_tokens'))
            ->where($this->getFullTableName('oauth_access_tokens.access_token'), $token->getId())
            ->delete();
    }
}
