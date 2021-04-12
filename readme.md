oauth-server - позволяет выдавать/обновлять токены доступа + защищает api токенами доступа
oauth-client - использование токенов доступа для получения данных

### grants:

Основные:

- Client credentials grant - из кронов, или не требующее разрешения пользователей
- Authorization code grant

Устаревшие:

- Password Grant
- Implicit grant

Если владелец ресурса машина (не нужно разрешение пользователя) - Client credentials grant
Если клиент серверный (может хранить секрет) - Authorization code grant
Иначе - Authorization code grant with PKCE

First Party - "свои" клиенты, Third Party - сторонние.

### Terms

Access Token - токен для доступа к защищенным ресурсам
Auth Code    - временный токен для получения access token
Auth Server  - сервер, выдающий токены (имеет закрытый ключ для подписи токенов)
Res Server   - выдает данные, проверяя запросы с токенами (имеет открытый ключ для проверки подписи)
Client       - приложение, запрашивающее защищенные ресурсы от имени пользователя
Grant        - метод получения access token
Res Owner    - пользователь
Scope        - права
JWT          - формат токена для безопасного использования данных 2 сторонами

Также есть ключ симметричного шифрования - строка или \Defuse\Crypto\Key из Secure PHP Encryption Library

### Интеграция

Репозитории - поставщики данных. Для разных grant нужен разный набор репозиториев.
Для удобства, все необходимые методы реализованы трейтами.
Нет никакого жёсткого ограничения, где и как хранить данные.
Также нет привязки к конкретному фреймворку, однако есть готовые интеграции.

Обработчики - поставляются в соотвествии с PSR-7, пример объединения с HttpFoundation
https://symfony.com/doc/current/components/psr7.html

    // пример
    $app->group('/api', function () {
        $this->get('/user', function (ServerRequestInterface $request, ResponseInterface $response) {
            $params = [];
    
            if (\in_array('basic', $request->getAttribute('oauth_scopes', []))) {
                $params = [
                    'id'   => 1,
                    'name' => 'Alex',
                    'city' => 'London',
                ];
            }
    
            if (\in_array('email', $request->getAttribute('oauth_scopes', []))) {
                $params['email'] = 'alex@example.com';
            }
    
            $body = new Stream('php://temp', 'r+');
            $body->write(\json_encode($params));
    
            return $response->withBody($body);
        });
    })->add(new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)));

### События

    $authServer->getEmitter()->addListener(
        'client.authentication.failed',
        function (\League\OAuth2\Server\RequestEvent $event) {
            // do something
        }
    );
    $authServer->getEmitter()->addListener(
        'user.authentication.failed',
        function (\League\OAuth2\Server\RequestEvent $event) {
            // do something
        }
    );


### Есть что попроще?

Да, вот токены контролируемые пользователем, как на gitHub
https://laravel.com/docs/8.x/sanctum#how-it-works