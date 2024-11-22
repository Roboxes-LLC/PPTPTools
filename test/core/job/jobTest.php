<?php

if (!defined('ROOT')) require_once '../../../root.php';
require_once ROOT.'/core/job/job.php';

class DummyJob extends Job
{
   public function run()
   {
      
   }
}

class JobTest
{
   public static function run()
   {
      echo "Running JobTest ...<br>";
      
      $test = new JobTest(); 
      
      $test->testGetThisPeriod();
   }
   
   // Note: Must edit Job and set getThisPeriod() to public.
   public function testGetThisPeriod()
   {
      echo "Job::getThisPeriod()<br>";
      
      $job = new DummyJob();
      
      $now = Time::now();
      
      // Hourly
      $job->jobPeriod = JobPeriod::HOURLY;
      echo "Hourly: Now [$now], This Period [" . $job->getThisPeriod($now)->format(Time::STANDARD_FORMAT) . "]<br><br>";
      
      // Daily
      $job->jobPeriod = JobPeriod::DAILY;
      $job->hour = 6;
      echo "Daily: Now [$now], This Period [" . $job->getThisPeriod($now)->format(Time::STANDARD_FORMAT) . "]<br><br>";
      
      // Weekly
      $job->jobPeriod = JobPeriod::WEEKLY;
      $job->day = 4;  // Wednesday (1-based)
      $job->hour = 6;
      echo "Weekly: Now [$now], This Period [" . $job->getThisPeriod($now)->format(Time::STANDARD_FORMAT) . "]<br><br>";
      
      // Monthly
      $job->jobPeriod = JobPeriod::MONTHLY;
      $job->day = 13;
      $job->hour = 6;
      echo "Monthly: Now [$now], This Period [" . $job->getThisPeriod($now)->format(Time::STANDARD_FORMAT) . "]<br><br>";
      
      // Annually
      $job->jobPeriod = JobPeriod::ANNUALLY;
      $job->month = 12;
      $job->day = 13;
      $job->hour = 6;
      echo "Annually: Now [$now], This Period [" . $job->getThisPeriod($now)->format(Time::STANDARD_FORMAT) . "]<br><br>";
   }
}

JobTest::run();

?>