<?php
/**
 * Sync database to Google Calendar
 * 1. login to Google Calendar
 * 2. remove deleted leave requests from Google Calendar
 * 3. remove deleted leave requests from db
 * 4. add new leave requests to Google Calendar
 * 5. save Google Calendar event id to new leave request in db
 */

require_once __DIR__ . '/base.php';

// 1. login to Google Calendar
// Initialize Google Client
$gClient = new Google_Client();
$gClient->setApplicationName($config['google']['application_name']);
$gClient->setScopes(Google_Service_Calendar::CALENDAR);
$gClient->setAuthConfig($config['google']['client_secret']);
$gClient->setAccessType('offline');

// set access_token
$accessTokenModel = $manager->Setting->getByName('google.access_token');
if ($accessTokenModel === null) {
  throw new Exception("Missing setting 'google.access_token', please run install.php script");
}
$gClient->setAccessToken($accessTokenModel->value);

// refresh token if needed
if ($gClient->isAccessTokenExpired()) {
  echo 'Access token is expired, fetching new access token...';
  $gClient->refreshToken($gClient->getRefreshToken());
  $accessTokenModel->value = $gClient->getAccessToken();
  $accessTokenModel->save();
  echo $accessTokenModel->value . "\n";
} else {
  echo "Access token valid\n";
}

// Get the API client and construct the service object.
$calendarService = new Google_Service_Calendar($gClient);

// 2. remove deleted leave requests from Google Calendar
// 3. remove deleted leave requests from db
$deletedRequests = $manager->Leaverequest->deleteFromGoogleCalendar()->all();
foreach($deletedRequests as $leaverequest) {
  if ($leaverequest->event_id !== null) {
    echo "Deleting Google Calendar event (#{$leaverequest->event_id}) for Leaverequest #{$leaverequest->id}...";
    try {
      $calendarService->events->delete($config['google']['calendar_id'], $leaverequest->event_id);
    } catch (Google_Service_Exception $e) {
      if ($e->getCode() == 410) {
        // Calendar item is already deleted, this could be done manually by a user or this script did this but could not record this change
        echo "already deleted...";
      } else {
        throw $e;
      }
    }
    echo "ok\n";
  }
  $leaverequest->delete();
}

// 4. add new leave requests to Google Calendar
// 5. save Google Calendar event id to new leave request in db
$newRequests = $manager->Leaverequest->insertIntoGoogleCalendar()->all();    
foreach ($newRequests as $leaverequest) {
  echo "Inserting Google Calendar event for Leaverequest #{$leaverequest->id}...";
  $event = $leaverequest->toGoogleCalendarEvent();
  $event = $calendarService->events->insert($config['google']['calendar_id'], $event);
  echo "ok (event_id #{$event->id})\n";
  
  $leaverequest->event_id = $event->id;
  $leaverequest->save();
}

echo "\nEND";
