<?php
$VERSION = "1.41";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>