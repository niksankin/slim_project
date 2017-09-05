<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 18.08.2017
 * Time: 12:05
 */

namespace Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;

    public function post(){
        return $this->belongsTo('Models\Post');
    }

    public function user(){
        return $this->belongsTo('Models\User');
    }
}