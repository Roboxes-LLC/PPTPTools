<?php
$VERSION = "1.31";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>