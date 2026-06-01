<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\ConfigurationException;
use TexHub\InstagramGraphApi\OAuth\AccessToken;

final class ConfigTest extends TestCase
{
    public function test_requires_app_credentials(): void
    {
        $this->expectException(ConfigurationException::class);
        new Config(appId: '', appSecret: 'x');
    }

    public function test_url_prefixes_version(): void
    {
        $config = new Config(appId: 'a', appSecret: 'b');
        $this->assertSame('https://graph.instagram.com/v23.0/me', $config->url('me'));
    }

    public function test_url_can_omit_version(): void
    {
        $config = new Config(appId: 'a', appSecret: 'b', version: '');
        $this->assertSame('https://graph.instagram.com/me', $config->url('me'));
    }

    public function test_require_ig_user_id_defaults_to_me(): void
    {
        $this->assertSame('me', (new Config('a', 'b'))->requireIgUserId());
        $this->assertSame('123', (new Config('a', 'b', igUserId: '123'))->requireIgUserId());
    }

    public function test_with_access_token_clones(): void
    {
        $config = new Config('a', 'b', accessToken: 'OLD');
        $new = $config->withAccessToken('NEW');

        $this->assertSame('OLD', $config->accessToken);
        $this->assertSame('NEW', $new->accessToken);
    }

    public function test_access_token_expiry_helpers(): void
    {
        $token = new AccessToken('t', 'bearer', expiresIn: 5184000, obtainedAt: time());

        $this->assertTrue($token->isLongLived());
        $this->assertFalse($token->expiresWithinDays(7));
        $this->assertTrue($token->expiresWithinDays(90));
    }
}
