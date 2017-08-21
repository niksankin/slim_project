<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 19.08.2017
 * Time: 19:12
 */

namespace Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Models\Post;
use Models\Subscription;
use Models\User;
use Psr\Container\ContainerInterface;

class Users
{
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
        $this->container["twig"]->addGlobal("user_page", '/'.$_SESSION["id"]);
    }

    public function profile($request, $response, $args){

        try{
            $user = User::where("id", "=", $args["id"])->firstOrFail()->toArray();
        }
        catch (ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        if($user["id"] == $_SESSION["id"])
            $isEdit = true;
        else {
            $isEdit = false;

            $sub = Subscription::where("user_id", "=", $_SESSION["id"])
                    ->where("user_target_id", "=", $args["id"])
                    ->get()->toArray();

            if(empty($sub))
                $user["follow"] = "/".$args["id"]."/follow";
            else
                $user["unfollow"] = "/" . $args["id"] . "/unfollow";
        }

        $posts = Post::where("user_id", "=", $args["id"])->orderBy("created_at", "desc")->get()->toArray();

        if($isEdit)
            foreach($posts as &$post){
                $post["href"] = "/posts/".$post["id"];
                $post["edit"] = "/posts/edit?" . http_build_query(array("id" => $post["id"]));
                $post["delete"] = "/posts/delete?" . http_build_query(array("id" => $post["id"]));
            }

        $template = $this->container["twig"]->load("user.html");
        $template->display(array("user" => $user, "posts" => $posts, "isEdit" => $isEdit));

        return $response;
    }

    public function follow($request, $response, $args){

        $data["user_id"] = $_SESSION["id"];
        $data["user_target_id"] = $args["id"];

        Subscription::insert($data);

        return $response->withRedirect("/".$args["id"]);
    }

    public function unfollow($request, $response, $args){

        Subscription::where("user_id", "=", $_SESSION["id"])
            ->where("user_target_id", "=", $args["id"])->delete();

        return $response->withRedirect("/".$args["id"]);
    }

    public function subscriptions($request, $response, $args){

        $users = Subscription::select("users.fullname", "users.status", "users.last_online", "users.id")
            ->where("subscriptions.user_id", "=", $_SESSION["id"])
            ->leftJoin("users", "users.id", "=", "subscriptions.user_target_id")->get()->toArray();

        foreach($users as &$user) {
            $user["href"] = "/" . $user["id"];
            $user["unfollow"] = "/" . $user["id"] . "/unfollow";
        }

        $template = $this->container["twig"]->load("subscriptions.html");
        $template->display(array("users" => $users));

        return $response;
    }
}