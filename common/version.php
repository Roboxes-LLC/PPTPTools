<?php
$VERSION = "1.39";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>