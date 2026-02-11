<?php

require_once 'databaseDefs.php';

// Uncomment one to select database type.
// Edit here:
$DATABASE_TYPE = DatabaseType::MY_SQL;
//$DATABASE_TYPE = DatabaseType::SQL_SERVER;

if ($DATABASE_TYPE == DatabaseType::SQL_SERVER)
{
   // Microsoft SQL Server database
   // Edit here:
   $SERVER = "JTOST-PC\SQLEXPRESS";
   $USER = "dbadmin";
   $PASSWORD = "dbadmin";
}
else
{
   // MySQL database (default)
   // Edit here:
   $SERVER = "db";
   $USER = "pptpdbadmin";
   $PASSWORD = "3sc4l4d3";
}

$DATABASE = "pptp"

?>