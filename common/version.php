<?php
$VERSION = "1.3F";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>