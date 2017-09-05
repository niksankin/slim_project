<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 13.08.2017
 * Time: 0:27
 */

namespace Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamps = false;

    public function subscriptions(){
        return $this->hasMany('Models\Subscription');
    }

    public function posts(){
        return $this->hasMany('Models\Post');
    }
}