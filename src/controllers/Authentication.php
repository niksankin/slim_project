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
use Valitron\Validator;
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
            $user = User::where("login", "=", $data["login"])->firstOrFail();
        }
        catch(ModelNotFoundException $e){
            $error = "User with this login does not exist!";
            $template = $this->container['twig']->load('login.html');
            $template->display(array("error" => $error));
            die();
        }

        if(!password_verify($data["password"], $user->password)){
            $error = "Incorrect password!";
            $template = $this->container['twig']->load('login.html');
            $template->display(array("error" => $error));
            die();
        }

        $_SESSION["id"] = $user->id;
        $user["status"] = "online";
        $user["last_online"] = null;

        User::where("id", "=", $user->id)->update($user->toArray());

        return $response->withRedirect("/posts");

    }

    public function register($request, $response, $args){

        if($request->getMethod() !== "POST"){
            $template = $this->container['twig']->load('registration.html');
            $template->display();
            die();
        }

        $data = $request->getParsedBody();

        $validator = new Validator($data);

        $rules = [
            'login' => [['required'], ['regex', '/^[a-zA-Z0-9]+$/'], ['lengthMax', 30]],
            'password' => [['required'], ['regex', '/^[a-zA-Z0-9]+$/'], ['lengthMax', 30]],
            'fullname' => [['required'], ['lengthMax', 255]],
            'email' => [['required'], ['email'], ['lengthMax', 255]]
        ];

        $validator->mapFieldsRules($rules);

        if(!$validator->validate()){
            $template = $this->container['twig']->load('registration.html');
            $template->display(array("errors" => $validator->errors()));//рендер страницы с ошибками
            die();
        }

        /*
        if(User::where("login", $data["login"])->get()->isNotEmpty()){
            array_push($error, "This login has already registered.");
        }*/

        $data["password"] = password_hash($data["password"], PASSWORD_BCRYPT);
        $data["ip"] = $request->getAttribute('ip_address');
        date_default_timezone_set('Europe/Moscow');
        $data["registered"] = date("Y-m-d H:i:s", time());

        User::insert($data);

        return $response->withRedirect("/login");
    }

    public function logout($request, $response, $args){

        $user = User::where("id", "=", $_SESSION["id"])->firstOrFail();

        $user->status = "offline";
        date_default_timezone_set('Europe/Moscow');
        $user->last_online = date("Y-m-d H:i:s", time());

        User::where("id", "=", $user->id)->update($user->toArray());

        session_destroy();
        unset($_SESSION["id"]);

        return $response->withRedirect("/login");
    }
}