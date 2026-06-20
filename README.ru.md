# TexHub · Instagram Graph API

[English](README.md) · **Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Полноценный, не привязанный к фреймворку PHP SDK для **Instagram Graph API** (Instagram API with Instagram Login) — OAuth и долгоживущие токены, публикация контента, истории, комментарии, **личные сообщения с кнопками**, инфо о пользователе и **вебхуки** — с полной поддержкой **Laravel**.

> Всё необходимое для интеграции с Instagram: от подключения до каждой функции.

Документация: <https://developers.facebook.com/docs/instagram-platform>

---

## Что покрыто

| Раздел | Методы |
|------|---------|
| **OAuth и токены** | URL авторизации, short-lived → **long-lived (60 дней)** → **refresh** |
| **Инфо о пользователе** | профиль, **аватар (фото профиля)**, число подписчиков/публикаций |
| **Публикация** | фото, **reels/видео**, **истории**, **карусели**, публикация, список, получить |
| **Комментарии** | список, комментировать, **ответить**, скрыть/показать, вкл/выкл, удалить |
| **Сообщения** | текст, изображение, **кнопки**, **быстрые ответы**, реакции, индикатор набора, диалоги |
| **Вебхуки** | проверка challenge, **проверка `X-Hub-Signature-256`**, разбор событий комментариев и сообщений |
| **Запасной выход** | `->http()` для вызова *любого* эндпоинта Graph |

---

## Установка

```bash
composer require texhub/instagram-graph-api
```

Требования: **PHP ≥ 8.2** с расширениями `curl`, `json` и `hash`.

---

## 1. Подключение (OAuth и токены)

```php
use TexHub\InstagramGraphApi\Instagram;

$ig = Instagram::make('APP_ID', 'APP_SECRET');

// a) Отправляем пользователя на авторизацию:
$url = $ig->oauth()->authorizationUrl(
    scopes: ['instagram_business_basic', 'instagram_business_content_publish',
             'instagram_business_manage_comments', 'instagram_business_manage_messages'],
    state: 'csrf-token',
    redirectUri: 'https://shop.tj/instagram/callback',
);
// redirect($url)

// b) В callback обмениваем ?code= на токены:
$short = $ig->oauth()->requestShortLivedToken($_GET['code'], 'https://shop.tj/instagram/callback');
$long  = $ig->oauth()->exchangeForLongLivedToken($short->token); // действует ~60 дней

echo $long->token;
echo $long->expiresAt();        // unix-таймстамп
$long->expiresWithinDays(7);    // скоро обновлять?

// c) Обновляем до истечения:
$refreshed = $ig->oauth()->refreshLongLivedToken($long->token);
```

Затем используем токен для вызовов API:

```php
$ig = Instagram::make('APP_ID', 'APP_SECRET', accessToken: $long->token, igUserId: '17841...');
// или из существующего экземпляра:
$ig = $ig->withAccessToken($long->token);
```

---

## 2. Инфо о пользователе и аватар

```php
$me = $ig->users()->me();
$me->get('username');
$me->get('followers_count');

$ig->users()->avatarUrl();   // URL фото профиля
$ig->users()->username();
```

---

## 3. Публикация контента

```php
// Фото (создание контейнера + публикация одним вызовом):
$ig->media()->publishPhoto('https://cdn.shop.tj/photo.jpg', 'Новинка! 🔥');

// Reel:
$ig->media()->publishReel('https://cdn.shop.tj/reel.mp4', 'Смотрите 👀');

// История:
$ig->media()->publishStory('https://cdn.shop.tj/story.jpg');

// Карусель (пост из нескольких фото):
$a = $ig->media()->createCarouselItem('https://cdn/1.jpg')->id();
$b = $ig->media()->createCarouselItem('https://cdn/2.jpg')->id();
$carousel = $ig->media()->createCarousel([$a, $b], 'Подборка')->id();
$ig->media()->publish($carousel);

// Чтение:
$ig->media()->list();
$ig->media()->get($mediaId);
$ig->media()->publishingLimit();   // использование дневной квоты
```

> URL медиа должны быть публично доступны — Instagram их скачивает.

---

## 4. Комментарии

```php
$ig->comments()->forMedia($mediaId);             // список комментариев
$ig->comments()->commentOnMedia($mediaId, 'Спасибо за внимание!');
$ig->comments()->reply($commentId, 'Ответ на комментарий');
$ig->comments()->hide($commentId);               // скрыть / показать
$ig->comments()->delete($commentId);
```

---

## 5. Личные сообщения (с кнопками)

Id получателя — это **IGSID**, который приходит в вебхуках сообщений.

```php
use TexHub\InstagramGraphApi\Builders\Button;

$ig->messages()->sendText($igsid, 'Привет! Чем помочь?');
$ig->messages()->sendImage($igsid, 'https://cdn/promo.jpg');

// Кнопки:
$ig->messages()->sendButtons($igsid, 'Выберите действие:', [
    Button::url('Открыть сайт', 'https://texhub.pro'),
    Button::postback('Связаться', 'CONTACT'),
]);

// Быстрые ответы:
$ig->messages()->sendQuickReplies($igsid, 'Ваш выбор?', [
    Button::quickReply('Да', 'YES'),
    Button::quickReply('Нет', 'NO'),
]);

// Индикатор набора / прочтение / реакции:
$ig->messages()->senderAction($igsid, 'typing_on');
$ig->messages()->react($igsid, $messageId, 'love');

// Диалоги и история:
$ig->messages()->conversations();
$ig->messages()->messages($conversationId);
```

---

## 6. Вебхуки

**Проверка (GET)** — вернуть challenge:

```php
$challenge = $ig->webhooks()->verifyChallenge($_GET);
if ($challenge !== null) { echo $challenge; exit; } // HTTP 200
```

**События (POST)** — проверить подпись, затем разобрать:

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

## Обработка ошибок

```php
use TexHub\InstagramGraphApi\Exceptions\ApiException;

try {
    $ig->media()->publishPhoto($url, $caption);
} catch (ApiException $e) {
    $e->httpStatus; $e->errorCode; $e->errorType; $e->errorSubcode; $e->fbtraceId;
    $e->isTokenError();   // код 190 — токен истёк/недействителен
    $e->isRateLimit();
}
```

## Любой эндпоинт (запасной выход)

```php
$ig->http()->get('17841.../insights', ['metric' => 'impressions,reach']);
$ig->http()->post($mediaId, ['comment_enabled' => 'false']);
```

---

## <a name="laravel"></a> Laravel

Регистрируется автоматически. Опубликуйте конфиг:

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

Фасад:

```php
use TexHub\InstagramGraphApi\Laravel\Instagram;

Instagram::media()->publishPhoto($url, 'Привет из Laravel!');
Instagram::messages()->sendText($igsid, 'Ответ');
```

### Контроллер вебхука

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

Много клиентов могут подключить **свои** аккаунты Instagram через одно приложение Meta. Каждый арендатор проходит OAuth и получает свой долгоживущий токен; один webhook-URL обслуживает всех.

```php
// Онбординг: каждый арендатор проходит OAuth → сохраняете его long-lived токен.
$long = $ig->oauth()->exchangeForLongLivedToken($short->token);
// → сохраните {token, user_id} для этого арендатора

// Действуем от любого арендатора — клиент с его сохранённым токеном:
$ig->withAccessToken($tenant->ig_token)->media()->publishPhoto($url, $caption);

// Один вебхук на всех — роутинг по аккаунту-получателю:
foreach ($ig->webhooks()->parse($raw) as $event) {
    $tenant = Tenant::where('ig_account_id', $event->accountId())->first();
    if ($event->isMessage()) { /* $event->senderId(), $event->messageText() */ }
}
```

`$event->accountId()` (подключённый аккаунт / `entry.id`) и `$event->recipientId()` — ключи маршрутизации по арендатору. Подпись проверяется одним общим app secret.

## Тестирование

```php
use TexHub\InstagramGraphApi\Instagram;
use TexHub\InstagramGraphApi\Config;
use TexHub\InstagramGraphApi\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['id' => 'MEDIA_1']);
$ig = new Instagram(new Config('APP', 'SECRET', accessToken: 'TOKEN'), $t);
// проверяйте $t->history / $t->lastUrl()
```

```bash
composer install
composer test
```

---

## Архитектура

```
src/
├── Instagram.php            # точка входа — oauth()/webhooks()/users()/media()/comments()/messages()
├── Config.php               # неизменяемая конфигурация
├── Http/                    # Transport, CurlTransport, HttpClient, RawResponse
├── OAuth/                   # OAuthClient, AccessToken
├── Webhook/                 # WebhookHandler (challenge + подпись + разбор), WebhookEvent
├── Resources/               # Users, Media, Comments, Messages
├── Builders/                # Message, Button (кнопки и быстрые ответы)
├── Responses/               # Response (ArrayAccess), ListResponse
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
