<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 15.08.2017
 * Time: 23:06
 */

namespace Controllers;


use Illuminate\Database\Eloquent\ModelNotFoundException;
use Models\Post;
use Models\Comment;
use Models\Subscription;
use Models\User;
use Psr\Container\ContainerInterface;
use Valitron\Validator;

class Posts
{
    protected $container;

    public function __construct(ContainerInterface $c){
        $this->container = $c;
        $this->container["twig"]->addGlobal("session", $_SESSION["id"]);
    }

    public function posts($request, $response, $args){

        $data = Subscription::select("posts.id as post_id", "users.id as user_id", "users.fullname", "posts.title", "posts.created_at")
            ->where("subscriptions.user_id", "=", $_SESSION["id"])
            ->leftJoin("users", "users.id", "=", "subscriptions.user_target_id")
            ->leftJoin("posts", "posts.user_id", "=", "users.id")
            ->where("posts.id", "<>", null)
            ->orderBy("posts.created_at", "desc")->get();

       /* $data = User::find($_SESSION["id"])
            ->select("posts.id as post_id", "users.id as user_id", "users.fullname", "posts.title", "posts.created_at")
            ->subscriptions()
            ->user_target()->has('posts')
            ->posts()
            ->orderBy("posts.created_at", "desc")->get();*/

        foreach ($data as &$post){
            $post["href"] = "/posts/".$post["post_id"];
        }

        $template = $this->container["twig"]->load("posts.html");
        $template->display(array("posts" => $data));

        return $response;
    }

    public function post($request, $response, $args){

        try{
            $data = Post::select("users.fullname", "posts.title", "posts.text", "posts.created_at", "posts.user_id")
                ->leftJoin("users", "posts.user_id", "=", "users.id")
                ->where("posts.id", "=", $args["id"])
                ->firstOrFail();
        }
        catch (ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        $comments = Comment::select("users.fullname", "comments.created_at", "comments.text", "comments.user_id", "comments.id")
            ->where("comments.post_id", "=", $args["id"])
            ->leftJoin("users", "comments.user_id", "=", "users.id")
            ->orderBy("comments.created_at", "desc")->get();

        foreach($comments as &$comment){
            if($comment->user_id == $_SESSION["id"]) {
                $comment->isEdit = true;
                $comment->edit = "/posts/comment/edit?" . http_build_query(array("post_id" => $args["id"], "comm_id" => $comment->id));
                $comment->delete = "/posts/comment/delete?" . http_build_query(array("comm_id" => $comment->id));
            }
            else
                $comment->isEdit = false;
        }

        $add_comment = "/posts/comment?".http_build_query(array("post_id" => $args["id"]));

        if($data->user_id == $_SESSION["id"]) {
            $edit["isEdit"] = true;
            $edit["edit"] = "/posts/edit?" . http_build_query(array("id" => $args["id"]));
            $edit["delete"] = "/posts/delete?" . http_build_query(array("id" => $args["id"]));
        }
        else
            $edit["isEdit"] = false;

        $template = $this->container["twig"]->load("post.html");
        $template->display(array("post" => $data, "edit" => $edit, "add_comment" => $add_comment, "comments" => $comments));

        return $response;
    }

    public function new($request, $response, $args){

        if($request->getMethod() !== "POST") {
            $template = $this->container["twig"]->load("edit_post.html");
            $template->display(array("edit" => $request->getUri()));
            die();
        }

        $data = $request->getParsedBody();

        $validator = new Validator($data);

        $rules = [
            'title' => [['required'], ['lengthMax', 32]],
            'text' => [['required'], ['lengthMax', 4096]]
        ];

        $validator->mapFieldsRules($rules);

        if(!$validator->validate()){
            $template = $this->container["twig"]->load("edit_post.html");
            $template->display(array("errors" => $validator->errors(), "post" => $data, "edit" => $request->getUri()));
            die();
        }

        $data["user_id"] = $_SESSION["id"];
        date_default_timezone_set('Europe/Moscow');
        $data["created_at"] = date("Y-m-d H:i:s", time());

        $id = $request->getAttribute("update");

        if(isset($id)) {
            Post::where("id", "=", $id)->update($data);
        }
        else
            $id = Post::insertGetId($data);

        return $response->withRedirect('/'.$_SESSION["id"]);
    }

    public function edit($request, $response, $args){

        $id = $request->getQueryParams()["id"];

        if(empty($id) || !settype($id, "int")){
            return $response->withRedirect("/posts");
        }

        try{
            $data = Post::where("id", "=", $id)->firstOrFail();
        }
        catch (ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        if($data->user_id !== $_SESSION["id"])
            return $response->withRedirect("/posts");

        if($request->getMethod() !== "POST") {
            $template = $this->container["twig"]->load("edit_post.html");
            $template->display(array("post" => $data, "edit" => $request->getUri()));
            die();
        }

        $request = $request->withAttribute("update", $id);
        return $this->new($request, $response, $args);
    }

    public function delete($request, $response, $args){

        $id = $request->getQueryParams()["id"];

        if(empty($id) || !settype($id, "int"))
            return $response->withRedirect("/posts");

        try{
            $data = Post::where("id", "=", $id)->firstOrFail();
        }
        catch(ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        if($data->user_id !== $_SESSION["id"])
            return $response->withRedirect("/posts");

        Post::where("id", "=", $id)->delete();
        Comment::where("post_id", "=", $id)->delete();

        return $response->withRedirect('/'.$_SESSION["id"]);
    }

    public function comments($request, $response, $args){

        $comments = Comment::select("comments.text", "comments.id as comm_id", "posts.id as post_id", "posts.title as post_title")
            ->leftJoin("posts", "posts.id", "=", "comments.post_id")
            ->where("comments.user_id", "=", $_SESSION["id"])
            ->get();

        foreach($comments as &$comment){
            $comment->post_href = "/posts/".$comment->post_id;
            $comment->edit = "/posts/comment/edit?".http_build_query(array("post_id" => $comment->post_id, "comm_id" => $comment->comm_id));
            $comment->delete = "/posts/comment/delete?".http_build_query(array("comm_id" => $comment->comm_id));
        }

        $template = $this->container["twig"]->load("comments.html");
        $template->display(array("comments" => $comments));

        return $response;
    }

    public function comment($request, $response, $args){

        $post_id = $request->getQueryParams()["post_id"];

        if(empty($post_id) || !settype($post_id, "int")){
            return $response->withRedirect("/posts");
        }

        $data = $request->getParsedBody();

        $validator = new Validator($data);

        $rules=[
            'text' => [['required'], ['lengthMax', 360]]
        ];

        $validator->mapFieldsRules($rules);

        if(!$validator->validate()){
            return $response->withRedirect("/posts/".$post_id);
        }

        $data["post_id"] = $post_id;
        $data["user_id"] = $_SESSION["id"];
        date_default_timezone_set('Europe/Moscow');
        $data["created_at"] = date("Y-m-d H:i:s", time());

        $id = $request->getAttribute("update");

        if(isset($id))
            Comment::where("id", "=", $id)->update($data);
        else
            Comment::insert($data);

        return $response->withRedirect("/posts/".$post_id);
    }

    public function edit_comment($request, $response, $args){

        $comm_id = $request->getQueryParams()["comm_id"];
        $post_id = $request->getQueryParams()["post_id"];

        if(empty($comm_id) || empty($post_id) || !settype($comm_id, "int") || !settype($post_id, "int"))
            return $response->withRedirect("/posts");

        try{
            $data = Comment::where("id", "=", $comm_id)->firstOrFail();
        }
        catch (ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        if($data->user_id !== $_SESSION["id"] || $data->post_id !== $post_id)
            return $response->withRedirect("/posts");

        if($request->getMethod() !== "POST") {
            $template = $this->container["twig"]->load("edit_comment.html");
            $template->display(array("comment" => $data, "edit" => $request->getUri()));
            die();
        }

        $request = $request->withAttribute("update", $data->id);
        return $this->comment($request, $response, $args);
    }

    public function delete_comment($request, $response, $args){

        $comm_id = $request->getQueryParams()["comm_id"];

        if(empty($comm_id) || !settype($comm_id, "int"))
            return $response->withRedirect("/posts");

        try{
            $data = Comment::where("id", "=", $comm_id)->firstOrFail();
        }
        catch (ModelNotFoundException $e){
            return $response->withStatus(404);
        }

        if($data->user_id !== $_SESSION["id"])
            return $response->withRedirect("/posts");

        $post_id = $data->post_id;

        Comment::where("id", "=", $comm_id)->delete();

        return $response->withRedirect("/posts/".$post_id);
    }
}