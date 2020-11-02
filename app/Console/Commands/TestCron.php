<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Test;
use App\Library\FileCSV;
use App\Library\LibraryDB;


class TestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::channel('cron')->info("Cron Test start!");
        //Read Fie From public/data dir

        $file = public_path().'/data/test_data.csv';
        if (file_exists($file)) {

            #create empty output file
            FileCSV::createOutputCSV();

            #convert data from file to array
            $data = FileCSV::converterCSV($file);
            Log::channel('cron')->info('$data: '. var_export($data, true));

            #Get User id from file
            $fileUserId = FileCSV::getUserId($data);
            Log::channel('cron')->info('$fileUserId: '. var_export($fileUserId, true));

            #convert Data to array
            $arrayData = FileCSV::getArrayData($data);
            Log::channel('cron')->info('$arrayData: '. var_export($arrayData, true));

            #Get User id from DB
            $dbUserId = LibraryDB::getUserId();
            Log::channel('cron')->info('$dbUserId: '. var_export($dbUserId, true));

            #Get soft deleted User id from DB
            $dbSoftDeletedUserIdRaw = LibraryDB::getSoftDeletedUserId();
            Log::channel('cron')->info('$dbSoftDeletedUserIdRaw: '. var_export($dbSoftDeletedUserIdRaw, true));

            #Get User id Raw to add
            $addUserRaw = LibraryDB::getAddOrRemove($fileUserId, $dbUserId);
            Log::channel('cron')->info('$addUserRaw: '. var_export($addUserRaw, true));

            #Get User id  to add
            $addUser = LibraryDB::getAddOrRemove($addUserRaw, $dbSoftDeletedUserIdRaw);
            Log::channel('cron')->info('$addUser: '. var_export($addUser, true));

            #Get User id  to remove
            $removeUser = LibraryDB::getAddOrRemove($dbUserId, $fileUserId);
            Log::channel('cron')->info('$removeUser: '. var_export($removeUser, true));

            #Get User id  to restore
            $restoreUser = LibraryDB::getRestoreOrUpdate($dbSoftDeletedUserIdRaw, $addUserRaw);
            Log::channel('cron')->info('$restoreUser: '. var_export($restoreUser, true));

            #Get User id  to update
            $updateUser = LibraryDB::getRestoreOrUpdate($fileUserId, $dbUserId);
            Log::channel('cron')->info('$updateUser: '. var_export($updateUser, true));

            try {
                #Update and remove data on DB
                LibraryDB::doRemoveOrUpdateDB($updateUser, $removeUser, $arrayData);

                #Restore data on DB
                LibraryDB::doRestoreDB($restoreUser, $arrayData);

                #Add new data on DB
                LibraryDB::doAddDB($addUser, $arrayData);
            } catch (\Exception $e) {
                Log::channel('cron')->info('Exception: '. var_export($e->getMessage(), true));
            }

        } else {
            return false;
        }

        Log::channel('cron')->info("Cron Test end!");
    }
}

