<?php
$VERSION = "1.51";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>