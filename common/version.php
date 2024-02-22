<?php
$VERSION = "1.48";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>