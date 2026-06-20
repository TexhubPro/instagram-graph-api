# TexHub · Instagram Graph API

**English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A complete, framework-agnostic PHP SDK for the **Instagram Graph API** (Instagram API with Instagram Login) — OAuth & long-lived tokens, content publishing, stories, comments, **direct messaging with buttons**, user info and **webhooks** — with first-class **Laravel** support.

> Everything you need to integrate Instagram, from connecting to every feature.

Reference: <https://developers.facebook.com/docs/instagram-platform>

---

## What's covered

| Area | Methods |
|------|---------|
| **OAuth & tokens** | authorization URL, short-lived → **long-lived (60-day)** → **refresh** |
| **User info** | profile, **avatar (profile picture)**, followers/media counts |
| **Publishing** | photos, **reels/video**, **stories**, **carousels**, publish, list, get |
| **Comments** | list, comment, **reply**, hide/unhide, enable/disable, delete |
| **Messaging** | text, image, **buttons**, **quick replies**, reactions, typing, conversations |
| **Webhooks** | verify challenge, **verify `X-Hub-Signature-256`**, parse comment & DM events |
| **Escape hatch** | `->http()` to call *any* Graph endpoint |

---

## Installation

```bash
composer require texhub/instagram-graph-api
```

Requirements: **PHP ≥ 8.2** with `curl`, `json` and `hash`.

---

## 1. Connect (OAuth & tokens)

```php
use TexHub\InstagramGraphApi\Instagram;

$ig = Instagram::make('APP_ID', 'APP_SECRET');

// a) Send the user to authorize:
$url = $ig->oauth()->authorizationUrl(
    scopes: ['instagram_business_basic', 'instagram_business_content_publish',
             'instagram_business_manage_comments', 'instagram_business_manage_messages'],
    state: 'csrf-token',
    redirectUri: 'https://shop.tj/instagram/callback',
);
// redirect($url)

// b) On the callback, exchange the ?code= for tokens:
$short = $ig->oauth()->requestShortLivedToken($_GET['code'], 'https://shop.tj/instagram/callback');
$long  = $ig->oauth()->exchangeForLongLivedToken($short->token); // valid ~60 days

echo $long->token;
echo $long->expiresAt();        // unix timestamp
$long->expiresWithinDays(7);    // refresh soon?

// c) Refresh before it expires:
$refreshed = $ig->oauth()->refreshLongLivedToken($long->token);
```

Then use the token for API calls:

```php
$ig = Instagram::make('APP_ID', 'APP_SECRET', accessToken: $long->token, igUserId: '17841...');
// or, from an existing instance:
$ig = $ig->withAccessToken($long->token);
```

---

## 2. User info & avatar

```php
$me = $ig->users()->me();
$me->get('username');
$me->get('followers_count');

$ig->users()->avatarUrl();   // profile picture URL
$ig->users()->username();
```

---

## 3. Publish content

```php
// Photo (create container + publish in one call):
$ig->media()->publishPhoto('https://cdn.shop.tj/photo.jpg', 'Новинка! 🔥');

// Reel:
$ig->media()->publishReel('https://cdn.shop.tj/reel.mp4', 'Смотрите 👀');

// Story:
$ig->media()->publishStory('https://cdn.shop.tj/story.jpg');

// Carousel (multi-image post):
$a = $ig->media()->createCarouselItem('https://cdn/1.jpg')->id();
$b = $ig->media()->createCarouselItem('https://cdn/2.jpg')->id();
$carousel = $ig->media()->createCarousel([$a, $b], 'Подборка')->id();
$ig->media()->publish($carousel);

// Read:
$ig->media()->list();
$ig->media()->get($mediaId);
$ig->media()->publishingLimit();   // daily quota usage
```

> Media URLs must be publicly reachable — Instagram fetches them.

---

## 4. Comments

```php
$ig->comments()->forMedia($mediaId);             // list comments
$ig->comments()->commentOnMedia($mediaId, 'Спасибо за внимание!');
$ig->comments()->reply($commentId, 'Ответ на комментарий');
$ig->comments()->hide($commentId);               // hide / unhide
$ig->comments()->delete($commentId);
```

---

## 5. Direct messaging (with buttons)

The recipient id is the **IGSID** you receive in messaging webhooks.

```php
use TexHub\InstagramGraphApi\Builders\Button;

$ig->messages()->sendText($igsid, 'Привет! Чем помочь?');
$ig->messages()->sendImage($igsid, 'https://cdn/promo.jpg');

// Buttons:
$ig->messages()->sendButtons($igsid, 'Выберите действие:', [
    Button::url('Открыть сайт', 'https://texhub.pro'),
    Button::postback('Связаться', 'CONTACT'),
]);

// Quick replies:
$ig->messages()->sendQuickReplies($igsid, 'Ваш выбор?', [
    Button::quickReply('Да', 'YES'),
    Button::quickReply('Нет', 'NO'),
]);

// Typing indicator / read receipts / reactions:
$ig->messages()->senderAction($igsid, 'typing_on');
$ig->messages()->react($igsid, $messageId, 'love');

// Conversations & history:
$ig->messages()->conversations();
$ig->messages()->messages($conversationId);
```

---

## 6. Webhooks

**Verification (GET)** — echo the challenge:

```php
$challenge = $ig->webhooks()->verifyChallenge($_GET);
if ($challenge !== null) { echo $challenge; exit; } // HTTP 200
```

**Events (POST)** — verify the signature, then parse:

```php
$raw = file_get_contents('php://input');
$ig->webhooks()->assertValidSignature($raw, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

foreach ($ig->webhooks()->parse($raw) as $event) {
    if ($event->isMessage()) {
        $ig->messages()->sendText($event->senderId(), 'Получили: ' . $event->messageText());
    }
    if ($event->isComment()) {
        // $event->get('id'), $event->get('text'), ...
    }
}
http_response_code(200);
```

---

## Error handling

```php
use TexHub\InstagramGraphApi\Exceptions\ApiException;

try {
    $ig->media()->publishPhoto($url, $caption);
} catch (ApiException $e) {
    $e->httpStatus; $e->errorCode; $e->errorType; $e->errorSubcode; $e->fbtraceId;
    $e->isTokenError();   // code 190 — token expired/invalid
    $e->isRateLimit();
}
```

## Any endpoint (escape hatch)

```php
$ig->http()->get('17841.../insights', ['metric' => 'impressions,reach']);
$ig->http()->post($mediaId, ['comment_enabled' => 'false']);
```

---

## <a name="laravel"></a> Laravel

Auto-discovered. Publish config:

```bash
php artisan vendor:publish --tag=instagram-config
```

`.env`:

```dotenv
INSTAGRAM_APP_ID=...
INSTAGRAM_APP_SECRET=...
INSTAGRAM_ACCESS_TOKEN=long-lived-token
INSTAGRAM_USER_ID=17841...
INSTAGRAM_REDIRECT_URI=https://shop.tj/instagram/callback
INSTAGRAM_WEBHOOK_VERIFY_TOKEN=your-verify-token
INSTAGRAM_API_VERSION=v23.0
```

Facade:

```php
use TexHub\InstagramGraphApi\Laravel\Instagram;

Instagram::media()->publishPhoto($url, 'Привет из Laravel!');
Instagram::messages()->sendText($igsid, 'Ответ');
```

### Webhook controller

```php
public function verify(Request $request) {
    return response(Instagram::webhooks()->verifyChallenge($request->query()) ?? '', 200);
}

public function handle(Request $request) {
    Instagram::webhooks()->assertValidSignature(
        $request->getContent(),
        $request->header('X-Hub-Signature-256'),
    );

    foreach (Instagram::webhooks()->parse($request->getContent()) as $event) {
        // ...
    }
    return response('', 200);
}
```

---

## Multi-tenant / SaaS

Many customers can connect **their own** Instagram accounts through one Meta app. Each tenant authorizes via OAuth and gets their own long-lived token; one webhook URL serves everyone.

```php
// Onboarding: each tenant runs the OAuth flow → store their long-lived token.
$long = $ig->oauth()->exchangeForLongLivedToken($short->token);
// → save {token, user_id} for this tenant

// Act as any tenant — bind a client to their stored token:
$ig->withAccessToken($tenant->ig_token)->media()->publishPhoto($url, $caption);

// One webhook for everyone — route by the account that received it:
foreach ($ig->webhooks()->parse($raw) as $event) {
    $tenant = Tenant::where('ig_account_id', $event->accountId())->first();
    if ($event->isMessage()) { /* $event->senderId(), $event->messageText() */ }
}
```

`$event->accountId()` (the connected account / `entry.id`) and `$event->recipientId()` are the tenant routing keys. Signatures are verified with your single app secret.

## Testing

```php
use TexHub\InstagramGraphApi\Instagram;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['id' => 'MEDIA_1']);
$ig = new Instagram(new Config('APP', 'SECRET', accessToken: 'TOKEN'), $t);
// assert on $t->history / $t->lastUrl()
```

```bash
composer install
composer test
```

---

## Architecture

```
src/
├── Instagram.php            # entry — oauth()/webhooks()/users()/media()/comments()/messages()
├── Config.php               # immutable configuration
├── Http/                    # Transport, CurlTransport, HttpClient, RawResponse
├── OAuth/                   # OAuthClient, AccessToken
├── Webhook/                 # WebhookHandler (challenge + signature + parse), WebhookEvent
├── Resources/               # Users, Media, Comments, Messages
├── Builders/                # Message, Button (buttons & quick replies)
├── Responses/               # Response (ArrayAccess), ListResponse
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
