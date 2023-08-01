<?php

require_once 'databaseDefs.php';
require_once 'databaseKey.php';

interface DatabaseAlt
{
   public function connect();

   public function disconnect();

   public function isConnected();

   public function query($query);
   
   public function countResults($result);
   
   public function rowsAffected();
   
   public function lastInsertId();
}

class PDODatabase implements DatabaseAlt
{
   public function __construct(
      $databaseType,
      $server,
      $user,
      $password,
      $database)
   {
      $this->databaseType = $databaseType;
      $this->server = $server;
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;
      
      $this->isConnected = false;
      $this->pdo = null;
   }
   
   public function connect()
   {
      try
      {
         $this->pdo = new PDO($this->getDSN(), $this->user, $this->password, $this->getOptions());
         
         $this->isConnected = true;
      }
      catch (PDOException $exception)
      {
         throw new PDOException($exception->getMessage(), (int)$exception->getCode());
         
         // TODO: Database error handling.
         echo "Database error: " . $exception->getMessage() . ", code(" . (int)$exception->getCode() . ")";
      }
   }
   
   public function disconnect()
   {
      $this->pdo = null;
      $this->isConnected = false;
   }
   
   public function isConnected()
   {
      return ($this->isConnected);
   }
   
   public function query($query)
   {
      $result = null;
      
      if ($this->isConnected())
      {
         $result = $this->pdo->query($query);
      }
      
      return ($result);
   }
   
   public function countResults($result)
   {
      return (count($result));
   }
   
   public function rowsAffected()
   {
      return($this->pdo->rowCount());
   }
   
   public function lastInsertId()
   {
      return ($this->pdo->lastInsertId());
   }
   
   private function getDSN()
   {
      $dsn = DatabaseType::getConnectString($this->databaseType, $this->server, $this->database);     

      return ($dsn);
   }
   
   private function getOptions()
   {
      $dsn = DatabaseType::getOptions($this->databaseType);
      
      return ($dsn);
   }
   
   protected $databaseType;
   
   protected $server;
   
   protected $user;
   
   protected $password;
   
   protected $database;
   
   protected $isConnected;
   
   protected $pdo;
}

?>