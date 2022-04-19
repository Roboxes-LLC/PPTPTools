<?php
$VERSION = "1.1B";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>