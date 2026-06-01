<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\InstagramGraphApi\Builders\Button;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Exceptions\ApiException;
use TexHub\InstagramGraphApi\Instagram;
use TexHub\InstagramGraphApi\Tests\Support\FakeTransport;

final class ApiTest extends TestCase
{
    private function ig(FakeTransport $t): Instagram
    {
        return new Instagram(
            new Config(appId: 'APP', appSecret: 'SECRET', accessToken: 'TOKEN', igUserId: '17841400000000000'),
            $t,
        );
    }

    public function test_me_adds_version_token_and_fields(): void
    {
        $t = (new FakeTransport())->push(['id' => '1', 'username' => 'texhub', 'profile_picture_url' => 'https://x/a.jpg']);

        $me = $this->ig($t)->users()->me(['username', 'profile_picture_url']);

        $this->assertSame('texhub', $me->get('username'));
        $url = $t->lastUrl();
        $this->assertStringContainsString('https://graph.instagram.com/v23.0/me', $url);
        $this->assertStringContainsString('access_token=TOKEN', $url);
        $this->assertStringContainsString('fields=username%2Cprofile_picture_url', $url);
    }

    public function test_avatar_url_helper(): void
    {
        $t = (new FakeTransport())->push(['profile_picture_url' => 'https://x/avatar.jpg']);

        $this->assertSame('https://x/avatar.jpg', $this->ig($t)->users()->avatarUrl());
    }

    public function test_publish_photo_two_step_flow(): void
    {
        $t = new FakeTransport();
        $t->push(['id' => 'CONTAINER_1'])   // create container
          ->push(['id' => 'MEDIA_1']);      // media_publish

        $media = $this->ig($t)->media()->publishPhoto('https://cdn/p.jpg', 'Привет!');

        $this->assertSame('MEDIA_1', $media->id());

        // First call: create container with image_url + caption
        $create = $t->history[0];
        $this->assertStringContainsString('/17841400000000000/media', $create['url']);
        $this->assertSame('https://cdn/p.jpg', $create['form']['image_url']);
        $this->assertSame('Привет!', $create['form']['caption']);

        // Second call: publish with creation_id
        $publish = $t->history[1];
        $this->assertStringContainsString('/media_publish', $publish['url']);
        $this->assertSame('CONTAINER_1', $publish['form']['creation_id']);
    }

    public function test_reply_to_comment(): void
    {
        $t = (new FakeTransport())->push(['id' => 'REPLY_1']);

        $this->ig($t)->comments()->reply('COMMENT_1', 'Спасибо!');

        $this->assertStringContainsString('/COMMENT_1/replies', $t->lastUrl());
        $this->assertSame('Спасибо!', $t->last()['form']['message']);
    }

    public function test_send_text_message_uses_json_body(): void
    {
        $t = (new FakeTransport())->push(['recipient_id' => 'USER_1', 'message_id' => 'mid_1']);

        $this->ig($t)->messages()->sendText('USER_1', 'Привет!');

        $json = $t->last()['json'];
        $this->assertSame('USER_1', $json['recipient']['id']);
        $this->assertSame('Привет!', $json['message']['text']);
        $this->assertStringContainsString('/17841400000000000/messages', $t->lastUrl());
    }

    public function test_send_buttons_builds_template(): void
    {
        $t = (new FakeTransport())->push(['message_id' => 'mid_2']);

        $this->ig($t)->messages()->sendButtons('USER_1', 'Choose:', [
            Button::url('Open', 'https://texhub.pro'),
            Button::postback('Ping', 'PING_PAYLOAD'),
        ]);

        $payload = $t->last()['json']['message']['attachment']['payload'];
        $this->assertSame('button', $payload['template_type']);
        $this->assertSame('web_url', $payload['buttons'][0]['type']);
        $this->assertSame('PING_PAYLOAD', $payload['buttons'][1]['payload']);
    }

    public function test_api_error_is_parsed(): void
    {
        $t = (new FakeTransport())->push([
            'error' => ['message' => 'Invalid OAuth token', 'type' => 'OAuthException', 'code' => 190],
        ], 401);

        try {
            $this->ig($t)->users()->me();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(190, $e->errorCode);
            $this->assertTrue($e->isTokenError());
            $this->assertSame('OAuthException', $e->errorType);
        }
    }
}
