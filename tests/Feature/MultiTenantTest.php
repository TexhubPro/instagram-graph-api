<?php

declare(strict_types=1);

namespace TexHub\InstagramGraphApi\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Instagram;
use TexHub\InstagramGraphApi\Tests\Support\FakeTransport;

final class MultiTenantTest extends TestCase
{
    private function ig(): Instagram
    {
        return new Instagram(
            new Config(appId: 'APP', appSecret: 'SECRET', webhookVerifyToken: 'v'),
            new FakeTransport(),
        );
    }

    public function test_webhook_events_carry_account_id_for_routing(): void
    {
        $payload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => 'IG_ACCOUNT_TENANT_1',
                'messaging' => [[
                    'sender' => ['id' => 'USER_1'],
                    'recipient' => ['id' => 'IG_ACCOUNT_TENANT_1'],
                    'message' => ['mid' => 'm1', 'text' => 'Привет'],
                ]],
                'changes' => [[
                    'field' => 'comments',
                    'value' => ['id' => 'c1', 'text' => 'nice'],
                ]],
            ]],
        ];

        $events = $this->ig()->webhooks()->parse($payload);

        foreach ($events as $event) {
            // The tenant key: which connected account this event belongs to.
            $this->assertSame('IG_ACCOUNT_TENANT_1', $event->accountId());
        }

        $message = array_values(array_filter($events, fn ($e) => $e->isMessage()))[0];
        $this->assertSame('USER_1', $message->senderId());
        $this->assertSame('IG_ACCOUNT_TENANT_1', $message->recipientId());
    }

    public function test_per_tenant_clients_use_their_own_token(): void
    {
        $t1 = new FakeTransport();
        $base = new Instagram(new Config(appId: 'A', appSecret: 'S'), $t1);

        // Each SaaS tenant gets a client bound to their own access token.
        $tenant = $base->withAccessToken('TENANT_TOKEN');
        $tenant->users()->me(['username']);

        $this->assertStringContainsString('access_token=TENANT_TOKEN', $t1->lastUrl());
    }
}
