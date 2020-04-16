<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\parentJobList;
use Exception;

class PostProcessingTask extends Controller
{
    //
    public function index(){
        $conStatus = app(SftpConnection::class)->ping();
        if($conStatus === true){
            $tasks =[];
            try{
                $tasks = parentJobList::select('id','file_name')->where('task_status','=',1)->get();
            }catch (Exception $e){
                Log::critical("Conroller: PostProcessingTask | ".$e->getMessage());
                die();
            }
            if($tasks->count() > 0){
                $sftp = Storage::disk('sftp');
                foreach($tasks as  $task){
                    $sftp->move($task->file_name,str_ireplace(config('env_var.PENDING_DIR'),config('env_var.COMPLETED_DIR'),$task->file_name));
                    $updating = parentJobList::find($task->id);
                    $updating->task_status = 2;
                    $updating->save();
                    Log::info("Conroller: PostProcessingTask | Movement Completed For ".$task->id);
                }
            }
        }else{
            Log::critical("Conroller: PostProcessingTask | ".$conStatus);
        }
    }
}
