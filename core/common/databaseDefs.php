<?php

abstract class DatabaseType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const MY_SQL = DatabaseType::FIRST;
   const SQL_SERVER = 2;
   const LAST = 3;
   const COUNT = DatabaseType::LAST - DatabaseType::FIRST;
   
   public static $values = array(DatabaseType::MY_SQL, DatabaseType::SQL_SERVER);
   
   public static function getLabel($databaseType)
   {
      $labels = array("---", "MY_SQL", "SQL_SERVER");
      
      return ($labels[$databaseType]);
   }
   
   public static function getConnectString($databaseType, $server, $database)
   {
      $dsn = null;
      
      switch ($databaseType)
      {
         case DatabaseType::MY_SQL:
         {
            $dsn = "mysql:host=$server;dbname=$database";
            break;
         }
            
         case DatabaseType::SQL_SERVER:
         {
            $dsn = "sqlsrv:server=$server;database=$database";
            break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($dsn);
   }
   
   public static function getOptions($databaseType)
   {
      $options = null;
      
      switch ($databaseType)
      {
         case DatabaseType::MY_SQL:
         {
            $options = [
               PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false
            ];
            break;
         }
            
         case DatabaseType::SQL_SERVER:
         {
            $options = [
               //PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES => false
            ];
            break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($options);
   }
   
   public static function reservedName($name, $databaseType)
   {
      $reservedName = $name;
      
      switch ($databaseType)
      {
         case DatabaseType::MY_SQL:
         {
            $reservedName = "`$name`";
            break;
         }
            
         case DatabaseType::SQL_SERVER:
         {
            $reservedName = "[$name]";            
            break;
         }
            
         default:
         {
            break;
         }
      }
      
      return ($reservedName);
   }      
}

?>