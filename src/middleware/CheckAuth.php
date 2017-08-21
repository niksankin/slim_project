<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 15.08.2017
 * Time: 19:56
 */

namespace Middleware;


use Psr\Container\ContainerInterface;

class CheckAuth
{
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    public function __invoke($request, $response, $next){

        if(!isset($_SESSION["id"])
            && $request->getUri()->getPath() !== "/login"
            && $request->getUri()->getPath() !== "/login/register")

            return $response->withRedirect("/login");

        if(isset($_SESSION["id"])
            && ($request->getUri()->getPath() == "/login"
            || $request->getUri()->getPath() == "/login/register"))

            return $response->withRedirect("/posts");

        $response = $next($request, $response);

        return $response;
    }
}