<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\parentJobList;
use App\custData;
use Carbon\Carbon;
use Exception;

class Process_one extends Controller
{
    //
    public function index(){
        $conStatus = app(SftpConnection::class)->ping();
        if($conStatus !== true){
            Log::critical("Conroller: Process_one | ".$conStatus);
            die();
        }
        $taskKeys=[];
        $required_headers=[
            '2',
            '5',
            '4'
        ];
        // dump(" MAX NEW ".config('env_var.MAX_PROCESS'));
        //catch error
        try{
            $tasks = parentJobList::select('id','file_name')->where([['process_id','=',1],['task_status','<',1]])->get();
        }catch (Exception $e){
            Log::critical("Conroller: Process_one | ".$e->getMessage());
            die();
        }
        if($tasks->count() > 0){
            $sftp = Storage::disk('sftp');
            foreach($tasks as  $task){
                Log::info("Task Recived for ".$task->file_name);
                $content = $sftp->get($task->file_name);
                list($csv,$headers) = $this->csvToArray($content);
                $status = $this->insertToModel($csv);
                if($status == 'success'){
                    $updating = parentJobList::find($task->id);
                    if($updating->process_id == config('env_var.MAX_PROCESS'))
                        $updating->task_status = 1;
                    $updating->process_id = 2; //Next Proccess Identifier.
                    $updating->save();
                }else{
                    array_push($headers,'Comment',"From");
                    $headers = implode(',',$headers);
                    if(strpos($task->file_name,'/')){
                        $task->file_name = explode('/',$task->file_name)[1];
                    }
                    $sftp->append(config('env_var.ERROR_DIR')."/Read_Error_DC_".$task->file_name,$headers);
                    foreach($status as $key=>$msg){
                        array_push($csv[$key],$msg,"Process One");
                        // dump($this->str_putcsv($csv[$key],',','"'));
                        $sftp->append(config('env_var.ERROR_DIR')."/Read_Error_DC_".$task->file_name,$this->str_putcsv($csv[$key],',','"'));
                    }
                    // dump($status);//dump Process name as well
                    $updating = parentJobList::find($task->id);
                    if($updating->process_id == config('env_var.MAX_PROCESS'))
                        $updating->task_status = 1;
                    $updating->process_id = 2; //Next Proccess Identifier.
                    $updating->save();
                }
                // dd($csv);
                
                Log::info("Task Completed for ".$task->file_name);
            }

        }else{
            // $sftp->append('completed/file.log', Carbon::now()->toDateTimeString() .': No Task');
            Log::info("No Task");
        }
    }

    private function csvToArray($content){
        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', $content));
            $header = array_shift($rows);
            $csv    = array();
            foreach($rows as $row) {
                if(!in_array(null, $row, true))
                $csv[] = array_combine($header, $row);
            }
        return [$csv,$header];
    }
    private function insertToModel($csv){
        $flag = true;
        foreach($csv as $key=>$row){
            $insertData = new custData;
            foreach($row as $col=>$val){
                $insertData->$col=$val;
            }
            try{
            $insertData->save();
            }catch (Exception $e){
                $flag = false;
                $message[$key]=$e->getMessage();
            }
        }
        if($flag)
            return 'success';
        else
            return $message;
    }

    private function str_putcsv($input, $delimiter = ',', $enclosure = '"')
    {
        // Open a memory "file" for read/write...
        $fp = fopen('php://temp', 'r+');
        // ... write the $input array to the "file" using fputcsv()...
        fputcsv($fp, $input, $delimiter, $enclosure);
        // ... rewind the "file" so we can read what we just wrote...
        rewind($fp);
        // ... read the entire line into a variable...
        $data = fread($fp, 1048576);
        // ... close the "file"...
        fclose($fp);
        // ... and return the $data to the caller, with the trailing newline from fgets() removed.
        return rtrim($data, "\n");
    }
    
}
