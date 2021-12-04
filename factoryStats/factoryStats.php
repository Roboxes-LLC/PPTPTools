<?php

require_once 'factoryStatsKey.php';

class FactoryStats
{
   const UNKNOWN_STATION_ID = 0;
   
   const UNKNOWN_SHIFT_ID = 0;
   
   public function __construct()
   {      
      global $FACTORY_STATS_API_URL;
      global $FACTORY_STATS_API_TOKEN;
      
      $this->apiURL = $FACTORY_STATS_API_URL;
      $this->authToken = $FACTORY_STATS_API_TOKEN;
      
      // Cached Factory Stats data.
      $this->users = null;
      $this->stations = null;
      $this->shifts = null;
   }
   
   public function getUsers($forceRefresh = false)
   {
      if (!$this->users)
      {
         $results = $this->fetchData("apiUser");
         
         if (($json = json_decode($results)) &&
             ($json->success == true))
         {
            $this->users = $json->users;
         }
      }
      
      return ($this->users);
   }
   
   public function getStations($forceRefresh = false)
   {
      if (!$this->stations)
      {
         $results = $this->fetchData("apiStation");
         
         if (($json = json_decode($results)) &&
             ($json->success == true))
         {
            $this->stations = $json->stations;
         }
      }
      
      return ($this->stations);
   }
   
   public function getShifts($forceRefresh = false)
   {
      if (!$this->shifts)
      {
         $results = $this->fetchData("apiShift");
         
         if (($json = json_decode($results)) &&
             ($json->success == true))
         {
            $this->shifts = $json->shifts;
         }
      }
      
      return ($this->shifts);
   }
   
   public function getStationId($stationName)
   {
      $stations = $this->getStations();
      
      foreach ($stations as $station)
      {
         if ($station->name == $stationName)
         {
            $stationId = intval($station->stationId);
            break;
         }
      }
      
      return ($stationId);
   }
   
   
   public function getShiftId($time)
   {
      $shiftId = FactoryStats::UNKNOWN_SHIFT_ID;
      
      return ($shiftId);
   }
   
   public function getCount($stationId, $dateTime, $shiftId)
   {
      $counts = null;
      
      $results = $this->fetchData("apiCount", ["stationId" => $stationId, "shiftId" => $shiftId, "date" => urlencode($dateTime)]);
      
      if (($json = json_decode($results)) &&
          ($json->success == true))
      {
         $counts = $json->counts;
      }
      
      return ($counts);
   }
   
   private function fetchData($command, $params = null)
   {
      $params["authToken"] = $this->authToken;
      
      $apiCall = $this->apiURL . "/" . $command . "/";
      
      if (is_array($params))
      {
         $apiCall .= "?";
         
         $apiCall .= http_build_query($params, '', '&');
      }
      
      //echo $apiCall . "<br>";
      
      $results = file_get_contents($apiCall);
      
      return ($results);
   }
}

/*
$factoryStats = new FactoryStats();

$counts = $factoryStats->getCount(3, null, 1);
foreach ($counts as $count)
{
   var_dump($count);
}

$users = $factoryStats->getUsers();
var_dump($users);

$stations = $factoryStats->getStations();
var_dump($stations);

$shifts = $factoryStats->getShifts();
var_dump($shifts);
*/
