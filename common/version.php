<?php
$VERSION = "1.78";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>