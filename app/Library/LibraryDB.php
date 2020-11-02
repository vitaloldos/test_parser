<?php

namespace App\Library;

use Illuminate\Support\Facades\Log;
use App\Models\Test;
//use App\Library\FileCSV;

class LibraryDB
{
    /**
     * Get user id for Add or Remove data
     * @param array $arrayOne Input data one
     * @param array $arrayTwo Input data two
     */
    static public function getAddOrRemove($arrayOne, $arrayTwo)
    {
        $data = array_diff($arrayOne, $arrayTwo);
        return $data;
    }

    /**
     * Get user id data for restore or Update
     * @param array $arrayOne Input data one
     * @param array $arrayTwo Input data two
     */
    static public function getRestoreOrUpdate($arrayOne, $arrayTwo)
    {
        $data = array_intersect($arrayOne, $arrayTwo);
        return $data;
    }

    /**
     * Get User ID from DB
     */
    static public function getUserId()
    {
        $usersId = Test::pluck('user_id');
        //Log::channel('cron')->info('$usersId: '. var_export($usersId, true));
        return $usersId->toArray();
    }

    /**
     * Get soft deleted User ID from DB
     */
    static public function getSoftDeletedUserId()
    {
        $usersId = Test::onlyTrashed()->pluck('user_id');
        //Log::channel('cron')->info('$usersId: '. var_export($usersId, true));
        return $usersId->toArray();
    }

    /**
     * Remove Or Update data in DB
     * @param array $updateUser Input users Id to Add
     * @param array $removeUser Input users Id to Add
     * @param array $arrayData Input data
     */
    static public function doRemoveOrUpdateDB($updateUser, $removeUser, $arrayData)
    {
        Test::chunk(100, function($data) use($updateUser, $removeUser, $arrayData)
        {
            foreach ($data as $d)
            {
               // Log::channel('cron')->info('$d user_id: '. var_export($d->user_id, true));
                if (in_array($d->user_id, $updateUser)) {
                    self::updateDataDB($d, $arrayData);
                    //Log::channel('cron')->info('$d user_id update: '. var_export($d->user_id, true));
                } else if(in_array($d->user_id, $removeUser)) {
                    self::softDeleteDataDB($d);
                }
            }
        });

        return true;
    }

    /**
     * Update DB
     * @param object $dbData Input DB data
     * @param array $arrayData Input data trom file
     */
    static public function updateDataDB($dbData, $arrayData)
    {
        #Save data to output csv file
        $dataForFile = $dbData->user_id.','.$dbData->first_name.','.$dbData->last_name.','.$dbData->card_number.','.$dbData->user_email;
        FileCSV::setReportCSV('Update Old', 'Update Data From File ',$dataForFile);

        $dbData->first_name = $arrayData[$dbData->user_id][1];
        $dbData->last_name = $arrayData[$dbData->user_id][2];
        $dbData->card_number = $arrayData[$dbData->user_id][3];
        $dbData->user_email = $arrayData[$dbData->user_id][4];
        $dbData->save();

        #Save data to output csv file
        $dataForFile = $arrayData[$dbData->user_id][0].','.$arrayData[$dbData->user_id][1].','.$arrayData[$dbData->user_id][2].','.$arrayData[$dbData->user_id][3].','.$arrayData[$dbData->user_id][4];
        FileCSV::setReportCSV('Update New', 'Update Data From File',$dataForFile);

        return true;
    }

    /**
     * Soft Delete data in DB
     * @param object $dbData Input DB data
     */
    static public function softDeleteDataDB($dbData)
    {
        #Save data to output csv file
        $dataForFile = $dbData->user_id.','.$dbData->first_name.','.$dbData->last_name.','.$dbData->card_number.','.$dbData->user_email;
        FileCSV::setReportCSV('Delete', 'Soft Delete ',$dataForFile);

        $dbData->delete();
        return true;
    }

    /**
     * Restore and Update data in DB
     * @param array $dataId Input restore data id
     * @param array $arrayData Input data trom file
     */
    static public function doRestoreDB($dataId, $arrayData)
    {
        Log::channel('cron')->info('$dataId for restore: '. var_export($dataId, true));
        foreach ($dataId as $key => $d) {
            $dbData =  Test::withTrashed()->where('user_id', '=', $d)->first();

            #Save data to output csv file
            $dataForFile = $dbData->user_id.','.$dbData->first_name.','.$dbData->last_name.','.$dbData->card_number.','.$dbData->user_email;
            FileCSV::setReportCSV('Restore Old', 'Restore data ',$dataForFile);

            //Log::channel('cron')->info('$dbData for restore: '. var_export($dbData, true));
            $dbData->restore();

            $dbData->first_name = $arrayData[$d][1];
            $dbData->last_name = $arrayData[$d][2];
            $dbData->card_number = $arrayData[$d][3];
            $dbData->user_email = $arrayData[$d][4];
            $dbData->save();

            #Save data to output csv file
            $dataForFile = $arrayData[$d][0].','.$arrayData[$d][1].','.$arrayData[$d][2].','.$arrayData[$d][3].','.$arrayData[$d][4];
            FileCSV::setReportCSV('Restore New', 'Restore data ',$dataForFile);
            return true;
        }

        return true;
    }

    /**
     * Restore and Update data in DB
     * @param array $dataId Input  data ID
     * @param array $arrayData Input data trom file
     */
    static public function doAddDB($dataId, $arrayData)
    {

        Log::channel('cron')->info('$d $dataId: '. var_export($dataId, true));
        foreach ($dataId as $d) {
            $newData = new Test();
            $newData->user_id = $arrayData[$d][0];
            $newData->first_name = $arrayData[$d][1];
            $newData->last_name = $arrayData[$d][2];
            $newData->card_number = $arrayData[$d][3];
            $newData->user_email = $arrayData[$d][4];
            $newData->save();
        }

        #Save data to output csv file
        $dataForFile = $arrayData[$d][0].','.$arrayData[$d][1].','.$arrayData[$d][2].','.$arrayData[$d][3].','.$arrayData[$d][4];
        FileCSV::setReportCSV('Add', 'Add new row',$dataForFile);

        return true;
    }
}
