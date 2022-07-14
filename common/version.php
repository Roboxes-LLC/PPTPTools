<?php
$VERSION = "1.1F";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>