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

        if(empty($request->getAttribute('route')))
            return $next($request, $response);

        $skipAuth = $request->getAttribute('route')->getArgument('skipAuth');

        if(!isset($_SESSION["id"]) && !$skipAuth)
            return $response->withRedirect("/login");
        else if(isset($_SESSION["id"]) && $skipAuth)
            return $response->withRedirect("/posts");
        else
            return $next($request, $response);

    }
}