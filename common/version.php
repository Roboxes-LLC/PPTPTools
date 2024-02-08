<?php
$VERSION = "1.46";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>