<?php
$VERSION = "1.69";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>