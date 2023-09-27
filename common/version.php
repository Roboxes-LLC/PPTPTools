<?php
$VERSION = "1.37";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>