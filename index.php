<?php

use \Defuse\Crypto\Key;

/**
 * Реализации сервисов
 */

$encryptionKey  = 'def00000d9cb82f457f26903d9b31d612c7edd3f317e87e9ec0a26c85a645973440c55359e54b1a02c0b1d03a9a0f998eaa5670ec417ef7c4d7d16576e2c48ca3975e08f';
$privateKeyPath = 'file://keys/private.key';
$publicKeyPath  = 'file://keys/public.key';

$accessTokenRepository = new AccessTokenRepository();
$clientRepository = new ClientRepository();
$scopeRepository = new ScopeRepository();

$authServer = new \League\OAuth2\Server\AuthorizationServer(
      $clientRepository,
      $accessTokenRepository,
      $scopeRepository,
      $privateKeyPath,
      Key::loadFromAsciiSafeString($encryptionKey)
);


/**
 * для Client credentials grant
 */
$authServer->enableGrantType(
    new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
    new \DateInterval('PT1H') // access tokens will expire after 1 hour
);
$app->post('/access_token', function ($request, $response) use ($app, $authServer) {
    try {
        return $authServer->respondToAccessTokenRequest($request, $response);

    } catch (OAuthServerException $exception) {

        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response);

    } catch (\Exception $exception) {
        return $app->errorResponse($exception);
    }
});
/**
 * Authorization code grant
 */
$grant = new \League\OAuth2\Server\Grant\AuthCodeGrant(
    new AuthCodeRepository(),
    new RefreshTokenRepository(),
    new \DateInterval('PT10M') // authorization codes will expire after 10 minutes
);
$grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

$authServer->enableGrantType(
    $grant,
    new \DateInterval('PT1H') // access tokens will expire after 1 hour
);
$app->get('/authorize', function ($request, $response) use ($app, $authServer) {

    try {
        $authRequest = $authServer->validateAuthorizationRequest($request);
        // ... redirect to login
        $authRequest->setUser(new UserEntity());
        // .. redirect to approve
        // Once the user has approved or denied the client update the status
        // (true = approved, false = denied)
        $authRequest->setAuthorizationApproved(true);

        return $authServer->completeAuthorizationRequest($authRequest, $response);

    } catch (OAuthServerException $exception) {
        return $exception->generateHttpResponse($response);

    } catch (\Exception $exception) {
        return $app->errorResponse($exception);
    }
});
$app->post('/access_token', function ($request, $response) use ($app, $authServer) {
   // то же, что и в Client credentials grant
});









$resServer = new \League\OAuth2\Server\ResourceServer(
    $accessTokenRepository,
    $publicKeyPath
);

// add as middlewares
//->add(new \League\OAuth2\Server\Middleware\ResourceServerMiddleware($authServer));
//->add(new \League\OAuth2\Server\Middleware\ResourceServerMiddleware($resServer));
// в аттрибутах появляются:
// oauth_access_token_id / oauth_client_id / oauth_user_id / oauth_scopes[]