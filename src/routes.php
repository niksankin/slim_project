<?php
// Routes

$app->get('/', function ($request, $response, $args) {
    return $response->withRedirect("/post");
});

$app->group('/{id:[0-9]+}', function(){
    $this->get('', Controllers\Users::class.":profile");
    $this->get('/follow', Controllers\Users::class.":follow");
    $this->get('/unfollow', Controllers\Users::class.":unfollow");
});

$app->get('/subscriptions', Controllers\Users::class.":subscriptions");

$app->group('/login', function(){
    $this->map(['GET', 'POST'], '', Controllers\Authentication::class.":login")->setArgument('skipAuth', true);
    $this->map(['GET', 'POST'], '/register', Controllers\Authentication::class.":register")->setArgument('skipAuth', true);
    $this->get('/logout', Controllers\Authentication::class.":logout");
});

$app->group('/posts', function(){
    $this->get('', Controllers\Posts::class.":posts");
    $this->map(['GET', 'POST'], '/new', Controllers\Posts::class.":new");
    $this->get('/{id:[0-9]+}', Controllers\Posts::class.":post");
    $this->map(['GET', 'POST'], '/edit', Controllers\Posts::class.":edit");
    $this->get('/delete', Controllers\Posts::class.":delete");
    $this->group('/comment', function(){
            $this->post('', Controllers\Posts::class.":comment");
            $this->map(['GET', 'POST'], '/edit', Controllers\Posts::class.":edit_comment");
            $this->get('/delete', Controllers\Posts::class.":delete_comment");
    });
});

$app->get('/comments', Controllers\Posts::class.":comments");

/*

$app->map(['GET', 'POST'],'/settings', function($request, $response, $args));
*/

$container = $app->getContainer();
$app->add(new Middleware\CheckAuth($container));

$checkProxyHeaders = true;
$trustedProxies = ['10.0.0.1', '10.0.0.2'];
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));