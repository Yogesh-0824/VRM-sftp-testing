Current Structure : 3 Stage model :
    A table to maintain task status (parent_job_list)
    3 - Folder Structure (Pending/New, Processed/Completd, Errors)

    The table has 2 flags for maintaining the current status of the task:
        process_status: Records the process which is marked for this task
        task_status : 0 -> Marked For Processing, 1 -> Processing Completed, 2 -> File movement completed

Cron 1) : schedular :
    This Cron checks the target folder for available files to be marked for testing.
    constraints (Before making a new processing request, The cron checks for any existing entry with the same file name. 
    If a task is already there, Nothing is done, else a new task is created with :
    process_status = 1 and task_status =0)

    Table Columns : id','file_name', 'process_status','task_status','created_at','updated_at'

 2) : Prcess_one :
    This is the first processCron. (There can be any number of processCron as per requirement)
    A processCron checks the task table for its tasks. For example, if a task has process_status==1 that means the task is marked for the first processCron.
    On finding all tasks marked for it, It starts processing (foreach(result))
    Validation :    1) SFTP connection Validation
                    2) Database connection Validation
    if validation fails, logs are maintained inside laravel logs (storage/logs/laravel.log) and cron is killed.
    if the process fails (Data insertion in the current case), logs are maintained on sftp server (Inside Error Folder)
 
    After completing its process, the task is marked for the next process (if any) and task status is updated (if required).

 3) : PostProcessingTask
    This cron checks the task table for completed tasks (task_status ==11).
    On finding any, It moves the completed files from the Pending/New folder to Processed/Completed folder.
    Validation :    1) SFTP connection Validation
                    2) Database connection Validation
    if vadidation fails, logs are maintained inside laravel logs (storage/logs/laravel.log) and cron is killed.