<?php

require_once '../common/authentication.php';
require_once '../common/database.php';
require_once '../common/filter.php';
require_once '../common/header.php';
require_once '../common/inspection.php';
require_once '../common/inspectionTemplate.php';
require_once '../common/navigation.php';
require_once '../common/newIndicator.php';

// *****************************************************************************
//                            InspectionTypeFilterComponent

class InspectionTypeFilterComponent extends FilterComponent
{
   public $selectedInspectionType;
   
   function __construct($label)
   {
      $this->label = $label;
   }
   
   public function getHtml()
   {
      $all = InspectionType::UNKNOWN;
      
      $selected = "";
      
      $options = "<option $selected value=\"$all\">All</option>";
      
      for ($inspectionType = InspectionType::FIRST; 
           $inspectionType != InspectionType::LAST; 
           $inspectionType++)
      {
         $label = InspectionType::getLabel($inspectionType);
         $selected = ($inspectionType == $this->selectedInspectionType) ? "selected" : "";
         $options .= "<option $selected value=\"$inspectionType\">$label</option>";
      }
      
      $html =
<<<HEREDOC
      <div class="flex-horizontal filter-component hide-on-tablet">
         <div>$this->label:&nbsp</div>
         <div><select id="filter-inspection-type-input" name="filterInspectionType">$options</select></div>
      </div>
HEREDOC;
      
      return ($html);
   }
   
   public function update()
   {
      if (isset($_POST['filterInspectionType']))
      {
         $this->selectedInspectionType = $_POST['filterInspectionType'];
      }
   }
}

// *****************************************************************************

function getNavBar()
{
   $navBar = new Navigation();
   
   $navBar->start();
   $navBar->mainMenuButton();
   $navBar->highlightNavButton("New Inspection", "location.replace('inspection.php?view=new_inspection');", true);
   $navBar->end();
   
   return ($navBar->getHtml());
}

function getFilter()
{
   $filter = null;
   
   if (isset($_SESSION["inspectionFilter"]))
   {
      $filter = $_SESSION["inspectionFilter"];
   }
   else
   {
      $user = Authentication::getAuthenticatedUser();
      
      $operators = null;
      $selectedOperator = null;
      $allowAll = false;
      if (Authentication::checkPermissions(Permission::VIEW_OTHER_USERS))
      {
         // Allow selection from all operators.
         $operators = UserInfo::getUsersByRole(Role::OPERATOR);
         $selectedOperator = "All";
         $allowAll = true;
      }
      else
      {
         // Limit to own logs.
         $operators = array($user);
         $selectedOperator = $user->employeeNumber;
         $allowAll = false;
      }
      
      $filter = new Filter();
      
      $filter->addByName("inspectionType", new InspectionTypeFilterComponent("Inspection Type"));
      $filter->addByName("operator", new UserFilterComponent("Operator", $operators, $selectedOperator, $allowAll));
      $filter->addByName('jobNumber', new JobNumberFilterComponent("Job Number", JobInfo::getJobNumbers(false), "All"));
      $filter->addByName('date', new DateFilterComponent());
      $filter->add(new FilterButton());
      $filter->add(new FilterDivider());
      $filter->add(new TodayButton());
      $filter->add(new YesterdayButton());
      //$filter->add(new ThisWeekButton());
      $filter->add(new FilterDivider());
      $filter->add(new PrintButton("inspectionReport.php"));
      
      $filter->add(new FilterButton());
      
      $_SESSION["inspectionFilter"] = $filter;
   }
   
   $filter->update();
   
   return ($filter);
}

function getTable($filter)
{
   $html = "";
   
   global $ROOT;
   
   $filter = getFilter();
   
   // Start date.
   $startDate = new DateTime($filter->get('date')->startDate, new DateTimeZone('America/New_York'));  // TODO: Function in Time class
   $startDateString = $startDate->format("Y-m-d");
   
   // End date.
   // Increment the end date by a day to make it inclusive.
   $endDate = new DateTime($filter->get('date')->endDate, new DateTimeZone('America/New_York'));
   $endDate->modify('+1 day');
   $endDateString = $endDate->format("Y-m-d");
   
   $result = PPTPDatabase::getInstance()->getInspections(
      $filter->get('operator')->selectedEmployeeNumber,
      $filter->get('jobNumber')->selectedJobNumber,
      $filter->get('inspectionType')->selectedInspectionType,
      $startDateString,
      $endDateString);
   
   if ($result && (MySqlDatabase::countResults($result) > 0))
   {
      $html =
<<<HEREDOC
      <div class="table-container">
         <table class="part-weight-log-table">
            <tr>
               <th>Inspection<br/>Type</th>               
               <th>Date</th>
               <th>Time</th>
               <th>Inspector</th>
               <th>Operator</th>
               <th>Job</th>
               <th>Work<br/>Center</th>
               <th>Success Rate</th>
               <th>PASS/FAIL</th>
               <th></th>
               <th></th>
            </tr>
HEREDOC;
      
      while ($row = $result->fetch_assoc())
      {
         $inspection = Inspection::load($row["inspectionId"]);
         
         $inspectionTemplate = InspectionTemplate::load($row["templateId"]);
         
         $jobInfo = JobInfo::load($row["jobId"]);
         
         if ($inspection && $inspectionTemplate && $jobInfo)
         {
            $inspectionTypeLabel = InspectionType::getLabel($inspectionTemplate->inspectionType);
            
            $dateTime = new DateTime($inspection->dateTime, new DateTimeZone('America/New_York'));  // TODO: Function in Time class
            $inspectionDate = $dateTime->format("m-d-Y");
            $inspectionTime = $dateTime->format("h:i A");
            
            $newIndicator = new NewIndicator($dateTime, 60);
            $new = $newIndicator->getHtml();
            
            $inspectorName = "unknown";
            $user = UserInfo::load($inspection->inspector);
            if ($user)
            {
               $inspectorName = $user->getFullName();
            }
            
            $operatorName = "unknown";
            $user = UserInfo::load($inspection->operator);
            if ($user)
            {
               $operatorName = $user->getFullName();
            }
            
            $passFail = ($inspection->pass() ? "PASS" : "FAIL");
            
            $viewEditIcon = "";
            $deleteIcon = "";
            if (Authentication::checkPermissions(Permission::EDIT_PART_WASHER_LOG))
            {
               $viewEditIcon =
                  "<a href=\"$ROOT/inspection/inspectionTemplate.php?templateId=$inspectionTemplate->templateId&view=edit_inspection_template\"><i class=\"material-icons table-function-button\">mode_edit</i></a>";
               $deleteIcon =
                  "<i class=\"material-icons table-function-button\" onclick=\"onDeleteInspectionTemplate($inspectionTemplate->templateId)\">delete</i>";
            }
            else
            {
               $viewEditIcon =
               "<a href=\"$ROOT/inspection/inspectionTemplate.php?templateId=$inspectionTemplate->templateId&view=edit_inspection_template&view=view_part_weight_entry\"><i class=\"material-icons table-function-button\">visibility</i></a>";
            }
            
            $html .=
<<<HEREDOC
            <tr>
               <td>$inspectionTypeLabel</td>
               <td>$inspectionDate $new</td>                        
               <td class="hide-on-tablet">$inspectionTime</td>
               <td class="hide-on-tablet">$inspectorName</td>
               <td class="hide-on-tablet">$operatorName</td>
               <td>$jobInfo->jobNumber</td>
               <td>$jobInfo->wcNumber</td>
               <td>{$inspection->getPassCount()}/{$inspection->getCount()}</td>
               <td>$passFail</td>
               <td>$viewEditIcon</td>
               <td>$deleteIcon</td>
            </tr>
HEREDOC;
         }  // end if ($inspection && $inspectionTemplate)
      }  // end while ($row = $result->fetch_assoc())
      
      $html .=
<<<HEREDOC
         </table>
      </div>
HEREDOC;
   }
   else
   {
      $html = "<div class=\"no-data\">No data is available for the selected range.  Use the filter controls above to select a new operator or date range.</div>";
   }  // end if ($result && (Database::countResults($result) > 0))
   
   return ($html);
}

?>

<!-- ********************************** BEGIN ********************************************* -->

<?php 
Time::init();

session_start();

if (!Authentication::isAuthenticated())
{
   header('Location: ../pptpTools.php');
   exit;
}

$filter = getFilter();
?>

<!DOCTYPE html>
<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <link rel="stylesheet" type="text/css" href="../common/flex.css"/>
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
   <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-blue.min.css"/>
   <link rel="stylesheet" type="text/css" href="../common/common.css"/>
   <link rel="stylesheet" type="text/css" href="../common/tooltip.css"/>
   <link rel="stylesheet" type="text/css" href="partWeightLog.css"/>
   
   <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
   <script src="partWeightLog.js"></script>
   <script src="../common/validate.js"></script>
   <script src="partWeightLog.js"></script>

</head>

<body>

   <?php Header::render("PPTP Tools"); ?>
   
   <div class="flex-horizontal main">
     
     <div class="flex-horizontal sidebar hide-on-tablet"></div> 
   
     <div class="flex-vertical content">

        <div class="heading">Inspection</div>

        <div class="description">Blah blah blah</div>

        <div class="flex-vertical inner-content">
        
           <?php echo $filter->getHtml(); ?>
           
           <?php echo getTable($filter); ?>
      
        </div>
         
        <?php echo getNavBar(); ?>
         
     </div>
     
   </div>

</body>

</html>