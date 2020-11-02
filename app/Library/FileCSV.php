<?php

namespace App\Library;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileCSV
{

    /**
     * Convert File CSV to array
     * @param string $path Path to file or folder
     */
    static public function converterCSV($path)
    {
        //Log::channel('cron')->info('$path 1: '. var_export($path, true));
        if (file_exists($path) === true) {

            #Check validade on the same rows
            $csv = self::validateSameRow($path);

            //Log::channel('cron')->info('$csv: '. var_export($csv, true));
            $header_csv = str_getcsv($csv[0]);

            foreach ($csv as $key => $row) {

                if ($key == 0)
                    continue;

                $a = str_getcsv($row);
                if (count($a) == count($header_csv)) {

                    $validator = Validator::make($a, [
                        0 => 'required',
                        1 => 'required|max:100',
                        2 => 'required|max:100',
                        3 => 'required|max:16',
                        4 => 'required|email:rfc|max:320',
                    ]);
                    //Log::channel('cron')->info('$validator: '. $validator->fails());
                    if ($validator->fails()) {
                        //Log::channel('cron')->info('$validator: '. $validator->fails());
                        self::setReportCSV('failed validate', 'Failed validation row: '.$validator->errors()->first(),$csv[$key]);
                        unset($csv[$key]);
                    }

                } else {
                    unset($csv[$key]);
                }
            }
            //Log::channel('cron')->info('$csv 1: '. var_export($csv, true));
            return $csv;
        } else  {
            Log::channel('cron')->info('File Is Not Exist');
        }

        return false;
    }


    /**
     * Validate for same row
     * @param string $path Path to file or folder
     */
    static public function validateSameRow($path)
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        //Log::channel('cron')->info('$lines: '. var_export($lines, true));
        $data = array_count_values($lines);

        foreach ($data as $key => $value) {
            if ($value > 1) {
                foreach (array_keys($lines, $key, true) as $k) {
                    self::setReportCSV('remove', 'File have the same row',$lines[$k]);

                    //Log::channel('cron')->info('$k: '. var_export($k, true));
                    unset($lines[$k]);
                }
            }
        }
        //Log::channel('cron')->info('$lines: '. var_export($lines, true));
        return $lines;
    }

    /**
     * Get User ID from File Data
     * @param array $data Input data
     */
    static public function getUserId($data)
    {
        $returnData = array();
        foreach ($data as $key => $row) {
            if ($key == 0)
                continue;
            $a = str_getcsv($row);
            $returnData[] = $a[0];
        }
        return $returnData;
    }

    /**
     * Get array data from File Data
     * @param array $data Input data
     */
    static public function getArrayData($data)
    {
        $returnData = array();
        foreach ($data as $key => $row) {
            if ($key == 0)
                continue;
            $a = str_getcsv($row);
            $returnData[$a[0]] = $a;
        }
        return $returnData;
    }

    /**
     * Create report CSV file
     * @param string $type Input type report
     * @param string $message Input message
     * @param array $data Input data
     */
    static public function setReportCSV($type, $message, $data)
    {
        $path = public_path().'/data/data_output.csv';

        $line = array($type, $message, $data);

        $fp = fopen($path, 'a');
        fputcsv($fp, $line, ',');
        fclose($fp);

        return true;
    }

    /**
     * Create empty report CSV file
     */
    static public function createOutputCSV()
    {
        $path = public_path().'/data/data_output.csv';
        $fp = fopen($path, 'wb');
        fclose($fp);
        return true;
    }

}
