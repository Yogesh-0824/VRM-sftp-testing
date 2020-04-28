<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\parentJobList;
use Carbon\Carbon;
use Exception;

class SftpConnection extends Controller
{
    //
    public function index(){
        $conStatus = app(SftpConnection::class)->ping();
        if($conStatus !== true){
            Log::critical("Conroller: SftpConnection | ".$conStatus);
            die();
        }
        $sftp = Storage::disk('sftp');
        $files=$sftp->files(config('env_var.PENDING_DIR'));
        if($files)
        {
            foreach($files as $key => $file){
                $data = parentJobList::select('id','task_status')->where('file_name',$file)->first();
                if($data){
                    $sftp->append('completed/file.log',Carbon::now()->toDateTimeString() . ": Task for ".$file." already present with id: ".$data->id." and task_status: ".$data->task_status);
                    dump("Task for ".$file." already present with id: ".$data->id." and task_status: ".$data->task_status);
                }else{
                    $insertData = new parentJobList;
                    $insertData->file_name = $file;
                    if(stripos($file,'Partial'))
                        $insertData->process_id = 2;
                    else if(stripos($file,'Master'))
                        $insertData->process_id = 1;
                    $insertData->task_status = 0;
                    $insertData->save();
                }
            }
        }else{
            $sftp->append('completed/file.log',Carbon::now()->toDateTimeString() . ": No File Present");
            dump("No File present");
        }
        echo '<<<<< END >>>>>>';
    }

    public function process1(){
        $taskKeys=[];
        $required_headers=[
            '2',
            '5',
            '4'
        ];
        // dump(" MAX NEW ".config('env_var.MAX_PROCESS'));
        $tasks = parentJobList::select('id','file_name')->where([['process_id','=',1],['task_status','<',1]])->get();
        if($tasks->count() > 0){
            foreach($tasks as  $task){
                $sftp = Storage::disk('sftp');
                $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .": Task Recived for ".$task->file_name);
                dump("Task Recived for ".$task->file_name);
                $content = $sftp->get($task->file_name);
                $content = explode("\n",$content,2);
                $file_headers = explode(',',reset($content));
                foreach($required_headers as $key => $value)
                    if(array_search($value,$file_headers) !== false)
                        array_push($taskKeys,array_search($value,$file_headers));
                if($taskKeys){
                    $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': Task Processing');
                    dump("Task Processing");
                }
                else{
                    $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': Task not for Process1');
                    dump("Task not for Process1");
                }
            $updating = parentJobList::find($task->id);
            if($updating->process_id == config('env_var.MAX_PROCESS'))
                $updating->task_status = 1;
            $updating->process_id = 2; //Next Proccess Identifier.
            $updating->save();
            }
        }else{
            $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': No Task');
            dump("No Task");
        }
    }

    public function copyCompletedFiles(){
        $tasks = parentJobList::select('id','file_name')->where('task_status','=',1)->get();
        if($tasks->count() > 0){
            $sftp = Storage::disk('sftp');
            foreach($tasks as  $task){
                $sftp->move($task->file_name,str_ireplace(config('env_var.PENDING_DIR'),config('env_var.COMPLETED_DIR'),$task->file_name));
                $updating = parentJobList::find($task->id);
                $updating->task_status = 2;
                $updating->save();
            }
            $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': Movement Complete');
            // dump("Movement Complete");
        }else{
            $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': No files found to move');
            dump("No files found to move"); 
        }
    }

    public function testConnection(){
        $sftp = Storage::disk('sftp');
        try {
            $files = count($sftp->files(config('env_var.PENDING_DIR')));
            $message ="Success. There are $files files in ".config('env_var.PENDING_DIR')." folder";
        }catch (Exception $e){
            $message=$e->getMessage();
        }
    return back()->withErrors([$message]);
    }

    public function ping(){
        $sftp = Storage::disk('sftp');
        try {
            $files = count($sftp->files(config('env_var.PENDING_DIR')));
            return true;
        }catch (Exception $e){
            return $e->getMessage();
        }
    }
}
