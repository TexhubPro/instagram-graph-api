<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\InvalidSignatureException;
use TexHub\InstagramGraphApi\Instagram;
use TexHub\InstagramGraphApi\Tests\Support\FakeTransport;

final class OAuthAndWebhookTest extends TestCase
{
    private function ig(FakeTransport $t): Instagram
    {
        return new Instagram(
            new Config(
                appId: 'APP',
                appSecret: 'SECRET',
                redirectUri: 'https://shop.tj/callback',
                webhookVerifyToken: 'verify-123',
            ),
            $t,
        );
    }

    public function test_authorization_url(): void
    {
        $url = $this->ig(new FakeTransport())->oauth()->authorizationUrl(
            ['instagram_business_basic', 'instagram_business_content_publish'],
            state: 'xyz',
        );

        $this->assertStringStartsWith('https://www.instagram.com/oauth/authorize?', $url);
        $this->assertStringContainsString('client_id=APP', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=instagram_business_basic%2Cinstagram_business_content_publish', $url);
        $this->assertStringContainsString('state=xyz', $url);
    }

    public function test_short_lived_token_exchange(): void
    {
        $t = (new FakeTransport())->push(['access_token' => 'SHORT', 'user_id' => '17841400000000000', 'permissions' => 'a,b']);

        $token = $this->ig($t)->oauth()->requestShortLivedToken('CODE');

        $this->assertSame('SHORT', $token->token);
        $this->assertSame('17841400000000000', $token->userId);
        $this->assertSame(['a', 'b'], $token->permissions);

        $form = $t->last()['form'];
        $this->assertSame('authorization_code', $form['grant_type']);
        $this->assertSame('CODE', $form['code']);
        $this->assertSame('https://api.instagram.com/oauth/access_token', $t->lastUrl());
    }

    public function test_long_lived_exchange_and_refresh(): void
    {
        $t = new FakeTransport();
        $t->push(['access_token' => 'LONG', 'token_type' => 'bearer', 'expires_in' => 5184000]) // exchange
          ->push(['access_token' => 'LONG2', 'token_type' => 'bearer', 'expires_in' => 5184000]); // refresh

        $oauth = $this->ig($t)->oauth();

        $long = $oauth->exchangeForLongLivedToken('SHORT');
        $this->assertSame('LONG', $long->token);
        $this->assertTrue($long->isLongLived());
        $this->assertStringContainsString('ig_exchange_token', $t->history[0]['url']);

        $refreshed = $oauth->refreshLongLivedToken('LONG');
        $this->assertSame('LONG2', $refreshed->token);
        $this->assertStringContainsString('ig_refresh_token', $t->history[1]['url']);
    }

    public function test_webhook_challenge_verification(): void
    {
        $handler = $this->ig(new FakeTransport())->webhooks();

        $challenge = $handler->verifyChallenge([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify-123',
            'hub.challenge' => 'CHALLENGE_ME',
        ]);
        $this->assertSame('CHALLENGE_ME', $challenge);

        $this->assertNull($handler->verifyChallenge([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'WRONG',
            'hub.challenge' => 'X',
        ]));
    }

    public function test_webhook_signature_and_parsing(): void
    {
        $handler = $this->ig(new FakeTransport())->webhooks();

        $body = json_encode([
            'object' => 'instagram',
            'entry' => [[
                'id' => '1',
                'messaging' => [[
                    'sender' => ['id' => 'USER_1'],
                    'message' => ['mid' => 'm1', 'text' => 'Привет'],
                ]],
                'changes' => [[
                    'field' => 'comments',
                    'value' => ['id' => 'c1', 'text' => 'nice'],
                ]],
            ]],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $body, 'SECRET');
        $this->assertTrue($handler->verifySignature($body, $signature));
        $this->assertFalse($handler->verifySignature($body, 'sha256=deadbeef'));

        $events = $handler->parse($body);
        $this->assertCount(2, $events);

        $comment = array_values(array_filter($events, fn ($e) => $e->isComment()))[0];
        $this->assertSame('c1', $comment->get('id'));

        $message = array_values(array_filter($events, fn ($e) => $e->isMessage()))[0];
        $this->assertSame('USER_1', $message->senderId());
        $this->assertSame('Привет', $message->messageText());
    }

    public function test_assert_invalid_signature_throws(): void
    {
        $this->expectException(InvalidSignatureException::class);
        $this->ig(new FakeTransport())->webhooks()->assertValidSignature('{}', 'sha256=bad');
    }
}
