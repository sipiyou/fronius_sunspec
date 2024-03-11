<?php
/*
  dumpCSV2json.php (c),(w) Nima Ghassemi Nejad (sipiyou@hotmail.com)
  
  v 1.0  NG - 28.02.2024 - Initial release
  
  this file reads prepared csv files and converts them to json format for later processing

  run php dumpCSV2json.php

  this will generate inverter.json and smartMeter.json
*/

$path = "./extracted_xls/";

$processFiles =   array ("inverter.json"    => array ( "protocol"   => "TCP",
                                                       "port"       => 502,
                                                       "address"    => 4,
                                                       "ip"         => "192.168.1.180",
                                                       "deviceName" => "froniusGEN24",
                                                       "elements"   => array ( 'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_common.csv'         => [40003, "common"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_inverter11x.csv'    => [40070, "inverter_11x"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_nameplate_120.csv'  => [40132, "nameplate_120"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_settings_121.csv'   => [40160, "settings_121"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_status_122.csv'     => [40192, "status_122"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_controls_123.csv'   => [40238, "controls_123"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_mppt_160.csv'       => [40264, "mppt_160"],
                                                                               'Gen24_Primo_Symo_Inverter_Register_Map_Float_storage_storage_124.csv'    => [40354, "storage_124"],
                                                       )),
                         "smartMeter.json"  => array ( "protocol"   => "TCP",
                                                       "port"       => 502,
                                                       "address"    => 1,
                                                       "ip"         => "192.168.1.180",
                                                       "deviceName" => "smartMeter",
                                                       "elements"   => array ('Meter_Register_Map_Float_v1.0_c001_common.csv'                            => [40001, "C001_common"],
                                                                              'Meter_Register_Map_Float_v1.0_m21x_meter.csv'                             => [40070, "M21X_meter"],
                                                       )
                         ),
);

$dArray = [];

$lastFName = '';
foreach ($processFiles as $outputName => $data) {
    flushJson ($lastFName, $dArray);
    
    $fArray = array();

    $deviceName = $data['deviceName'];

    foreach ($data['elements'] as $fName => $settings) {
        list ($firstID, $groupName) = $settings;
        
        $myArray = parseFile ($fName, $firstID, $deviceName, $groupName);

        $fArray[] = array (
            "firstID"  => $firstID,
            "group"    => $groupName,
            "elements" => $myArray
        );
    }

    $dArray[] = array (
        "device"   => $deviceName,
        "protocol" => $data['protocol'],
        "port"     => $data['port'],
        "address"  => $data['address'],
        "ip"       => $data['ip'],
        "elements" => $fArray,
        
    );
    $lastFName = $outputName;
}

flushJson ($lastFName, $dArray);

function flushJson ($outputName, &$arr) {
    if (!empty ($arr)) {
        $jsonString = json_encode($arr /*, JSON_PRETTY_PRINT*/);
        file_put_contents($outputName, $jsonString);
    }
    $arr = [];
}

function parseFile ($fileName, $begin, $devName, $arrayName) {
    global $path;
    
    if (($handle = fopen($path.$fileName, 'r')) !== false) {
        // Initialize an empty array to store the formatted data

        $myArray = [];
        $line = 0;
        // Read each line of the CSV file
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            
            if ($line++ == 0) continue;

            for ($i=0;$i<count($data);$i++) {
                $data[$i] = str_replace(array("\n", "\r"), " ", $data[$i]);
            }

            $_notSupported = false;

            if (strpos($data[10], 'not supported') !== false) {
                $_notSupported = true;
            }

            list ($_start, $_end, $_size, $_rw, $_function, $_name, $_desc, $_type, $_unit, $_scaleFactor, $_range) = $data;

            $_type = str_replace("string", "string32", $_type);
            $_type = str_replace("count", "uint16", $_type);
            $_type = str_replace("pad", "uint16", $_type);
            
            $_start += $begin -1;

            $myArray[$line] = array ("start"        => intval($_start),
                                     "size"         => intval($_size),
                                     "rw"           => $_rw,
                                     "function"     => $_function,
                                     "name"         => $_name,
                                     "desc"         => $_desc,
                                     "type"         => $_type,
                                     "unit"         => $_unit,
                                     "scaleFactor"  => $_scaleFactor,
                                     "range"        => $_range,
                                     "notsupported" => intval($_notSupported),
            );
        }

        fclose($handle);

        return ($myArray);
    } else {
        echo "Error opening the file.";
    }
}

?>
