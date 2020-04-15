<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class custData extends Model
{
    //
    protected $table = 'cust_data';
    protected $fillable = [ 'id',
                            'user_id','unser_name','email','phone','pb_code',
                            'created_at','updated_at'];

}
