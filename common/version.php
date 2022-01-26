<?php
$VERSION = "1.17";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>