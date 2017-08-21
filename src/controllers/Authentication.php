<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 13.08.2017
 * Time: 0:13
 */

namespace Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Container\ContainerInterface;
use Models\User;

class Authentication
{
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
    }

    public function login($request, $response, $args){

        if($request->getMethod() !== "POST"){
            $template = $this->container['twig']->load('login.html');
            $template->display();
            die();
        }

        $data = $request->getParsedBody();

        try{
            $user = User::where("login", "=", $data["login"])->firstOrFail()->toArray();
        }
        catch(ModelNotFoundException $e){
            $error = "User with this login does not exist!";
            $template = $this->container['twig']->load('login.html');
            $template->display(array("error" => $error));
            die();
        }

        if(!password_verify($data["password"], $user["password"])){
            $error = "Incorrect password!";
            $template = $this->container['twig']->load('login.html');
            $template->display(array("error" => $error));
            die();
        }

        $_SESSION["id"] = $user["id"];
        $user["status"] = "online";
        $user["last_online"] = null;

        User::where("id", "=", $user["id"])->update($user);

        return $response->withRedirect("/posts");

    }

    public function register($request, $response, $args){

        if($request->getMethod() !== "POST"){
            $template = $this->container['twig']->load('registration.html');
            $template->display();
            die();
        }

        $error = array();

        $data = $request->getParsedBody();

        if(empty($data["login"])){
            array_push($error, "Enter login!");
        }

        if(empty($data["password"])){
            array_push($error, "Enter password!");
        }

        if(empty($data["fullname"])){
            array_push($error, "Enter full name!");
        }

        if(empty($data["email"])){
            array_push($error, "Enter e-mail!");
        }

        if(!empty($error)){
            $template = $this->container['twig']->load('registration.html');
            $template->display(array("errors" => $error));//рендер страницы с ошибками
            die();
        }

        if(!preg_match("/^[a-zA-Z0-9]+$/", $data["login"])){
            array_push($error, "Login must contain lowercase and uppercase letters and numbers.");
        }

        if(strlen($data["login"]) < 3 or strlen($data["login"]) > 30){
            array_push($error, "Login length must be between 3 and 30 chars.");
        }

        if(User::where("login", $data["login"])->get()->isNotEmpty()){
            array_push($error, "This login has already registered.");
        }

        if(!preg_match("/^[a-zA-Z0-9]+$/", $data["password"])){
            array_push($error, "Password must contain lowercase and uppercase letters and numbers.");
        }

        if(strlen($data["password"]) < 3 or strlen($data["password"]) > 30){
            array_push($error, "Password length must be between 3 and 30 chars.");
        }

        if(strlen($data["fullname"]) > 255){
            array_push($error, "Full name length must be not above 255 chars.");
        }

        if(strlen($data["email"]) > 255){
            array_push($error, "E-mail length must be not above than 255 chars.");
        }

        if(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)){
            array_push($error, "Wrong e-mail form!");
        }

        if(!empty($error)){
            $template = $this->container['twig']->load('registration.html');
            $template->display(array("errors" => $error));//рендер страницы с ошибками
            die();
        }

        $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
        $data["ip"] = $request->getAttribute('ip_address');
        date_default_timezone_set('Europe/Moscow');
        $data["registered"] = date("Y-m-d H:i:s", time());

        User::insert($data);

        return $response->withRedirect("/login");
    }

    public function logout($request, $response, $args){

        $user = User::where("id", "=", $_SESSION["id"])->firstOrFail()->toArray();

        $user["status"] = "offline";
        date_default_timezone_set('Europe/Moscow');
        $user["last_online"] = date("Y-m-d H:i:s", time());

        User::where("id", "=", $user["id"])->update($user);

        session_destroy();
        unset($_SESSION["id"]);

        return $response->withRedirect("/login");
    }
}