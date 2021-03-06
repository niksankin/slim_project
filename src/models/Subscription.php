<?php
/**
 * Created by PhpStorm.
 * User: Lunary
 * Date: 20.08.2017
 * Time: 12:10
 */

namespace Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $timestamps = false;

    public function user(){
        return $this->belongsTo('Models\User');
    }

    public function user_target(){
        return $this->belongsTo('Models\User');
    }
}