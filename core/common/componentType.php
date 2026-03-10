<?php

abstract class ComponentType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const CORRECTIVE_ACTION = ComponentType::FIRST;
   const QUOTE = 2;
   const MAINTENANCE_TICKET = 3;
   const LAST = 4;
   const COUNT = ComponentType::LAST - ComponentType::FIRST;
}

?>