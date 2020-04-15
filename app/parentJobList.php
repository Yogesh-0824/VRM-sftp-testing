<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class parentJobList extends Model
{
    //
    protected $table = 'parent_job_lists';
    protected $fillable = [ 'id',
                            'file_name', 'process_status','task_status',
                            'created_at','updated_at'];

}
