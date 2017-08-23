<?php
/**
 * Sync myleavedays.com to the database
 * 1. login to myleavedays.com
 * 2. download export
 * 3. parse export
 * 4. compare export to db
 * 5. insert new leave time in db
 * 6. mark deleted leave time in db
 */


//exit();

require_once __DIR__ . '/base.php';

// 1. login to myleavedays.com
$mldClient = new \myleavedays\MyLeaveDaysClient();
echo "Logging in to myleavedays.com...";
if (!$mldClient->login($config['myleavedays']['username'], $config['myleavedays']['password'], $config['myleavedays']['organisation_code'])) {
  throw new Exception("Could not login to myleavedays.com");
}
echo "ok\n";

// 2. download export
// 3. parse export
echo "Fetching leave requests...";
$mldLeaveRequests = array();
for($year = date('Y'); $year <= date('Y')+$config['myleavedays']['years_future']; $year++) {
 $mldLeaveRequests = array_merge($mldLeaveRequests, $mldClient->getLeaveRequests($year, $config['myleavedays']['selection_id'], $config['myleavedays']['selection_option']));
}

echo "ok (" . count($mldLeaveRequests) . " found)\n";


$dbLeaveRequests = array();
foreach ($mldLeaveRequests as $mldLeaveRequest) {
  // ignore rejected leave requests, so they will be removed by syncing later
  if ($mldLeaveRequest->isRejected()) {
    continue;
  }
  $dbLeaveRequest = $manager->create('Leaverequest');
  $dbLeaveRequest->fromLeaveRequest($mldLeaveRequest);
  $dbLeaveRequests[] = $dbLeaveRequest;
}

// robustness check: this prevents all leave request in the database to be set to deleted
if (count($mldLeaveRequests) == 0) {
  throw new Exception("No leave requests found at myleavedays.com, did they make changes to their export?");
}

// 4. compare export to db
// 5. insert new leave time in db
// 6. mark deleted leave time in db
echo "Syncing leave requests with database...";
$startDate = date('Y') . '-01-01';
$endDate = (date('Y')+$config['myleavedays']['years_future']) . '-12-31';
$result = $manager->Leaverequest->sync($dbLeaveRequests, $startDate, $endDate);
echo "ok (" . count($result['created']) . ' created, ' . count($result['deleted']) . " deleted)\n";


echo "\nEND";
