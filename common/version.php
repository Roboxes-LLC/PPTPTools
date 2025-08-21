<?php
$VERSION = "1.6D";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>