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
use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

/**
 * This is the fluent refresh token class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class FluentRefreshToken extends AbstractFluentAdapter implements RefreshTokenInterface
{
    /**
     * Return a new instance of \League\OAuth2\Server\Entity\RefreshTokenEntity.
     *
     * @param string $token
     *
     * @return \League\OAuth2\Server\Entity\RefreshTokenEntity
     */
    public function get($token)
    {
        $result = $this->getConnection()
                ->table($this->getFullTableName('oauth_refresh_tokens'))
                ->where($this->getFullTableName('oauth_refresh_tokens.refresh_token'), $token)
                ->first();

        if (is_null($result)) {
            return;
        }

        return (new RefreshTokenEntity($this->getServer()))
               ->setId($result->refresh_token)
               ->setAccessTokenId($result->access_token)
               ->setExpireTime(time()+3600);
               //->setExpireTime((int) $result->expire_time); // Do not expire refresh token
    }

    /**
     * Create a new refresh token_name.
     *
     * @param  string $token
     * @param  int $expireTime
     * @param  string $accessToken
     *
     * @return \League\OAuth2\Server\Entity\RefreshTokenEntity
     */
    public function create($token, $expireTime, $accessToken)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_refresh_tokens'))
            ->insert([
                'refresh_token' => $token,
                'expire_time' => $expireTime,
                'access_token' => $accessToken,
            ]);

        return (new RefreshTokenEntity($this->getServer()))
               ->setId($token)
               ->setAccessTokenId($accessToken)
               ->setExpireTime((int) $expireTime);
    }

    /**
     * Delete the refresh token.
     *
     * @param  \League\OAuth2\Server\Entity\RefreshTokenEntity $token
     *
     * @return void
     */
    public function delete(RefreshTokenEntity $token)
    {
        $this->getConnection()
            ->table($this->getFullTableName('oauth_refresh_tokens'))
            ->where($this->getFullTableName('oauth_refresh_tokens.refresh_token'), $token->getId())
            ->delete();
    }
}
