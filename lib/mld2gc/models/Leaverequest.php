<?php

namespace mld2gc\models;

class Leaverequest extends \ultimo\orm\Model {

  public $id;
  public $name;
  public $hours;
  public $start_date;
  public $end_date;
  public $start_date_hours;
  public $end_date_hours;
  public $event_id = null;
  public $approved = false;
  public $deleted = false;
  
  static protected $fields = array('id', 'name', 'hours', 'start_date', 'end_date', 'start_date_hours', 'end_date_hours', 'event_id', 'approved', 'deleted');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $fetchers = array('sync');
  static protected $scopes = array('deleteFromGoogleCalendar', 'insertIntoGoogleCalendar', 'between');
  
  public function fromLeaveRequest(\myleavedays\LeaveRequest $request) {
    $this->name = $request->name;
    $this->hours = $request->hours;
    $this->start_date = $request->startDate;
    $this->end_date = $request->endDate;
    $this->start_date_hours = $request->startDateHours;
    $this->end_date_hours = $request->endDateHours;
    $this->approved = $request->isApproved();
  }
  
  static public function sync(\ultimo\orm\StaticModel $s, array $leaverequests, $startDate, $endDate) {
    $result = array('created' => 0, 'added' => 0);

    $dbLeaveRequests = $s->between($startDate, $endDate)->all();
    
    // filter $leaverequests by start and end date
    $leaverequests = array_filter($leaverequests, function($r) use ($startDate, $endDate) {
      return ($r->start_date >= $startDate && $r->start_date <= $endDate) || ($r->end_date >= $startDate && $r->end_date <= $endDate);
    });
    
    $deletedRequests = static::diff($dbLeaveRequests, $leaverequests);
    
    foreach($deletedRequests as $deletedRequest) {
      //echo "Marking #" . $deletedRequest->id . " as deleted...\n";
      $deletedRequest->deleted = true;
      $deletedRequest->save();
    }
    
    $newRequests = static::diff($leaverequests, $dbLeaveRequests);
    $s->getManager()->modelMultiInsert($newRequests);

    return array('created' => $newRequests, 'deleted' => $deletedRequests);
  }
  
  static protected function diff(array $leaveRequestsA, array $leaveRequestsB) {
    $diff = array();
    foreach ($leaveRequestsA as $leaveRequestA) {
      foreach ($leaveRequestsB as $leaveRequestB) {
        if ($leaveRequestA->equals($leaveRequestB)) {
          continue 2;
        }
      }
      
      $diff[] = $leaveRequestA;
    }
    
    return $diff;
  }
  
  public function equals(Leaverequest $leaveRequest) {
    if (strcmp($leaveRequest->name, $this->name) != 0) {
      return false;
    }
    
    if (strcmp($leaveRequest->start_date, $this->start_date) != 0) {
      return false;
    }
    
    if (strcmp($leaveRequest->end_date, $this->end_date) != 0) {
      return false;
    }
    
    if ($leaveRequest->hours != $this->hours) {
      return false;
    }
    
    if ($leaveRequest->start_date_hours != $this->start_date_hours) {
      return false;
    }
    
    if ($leaveRequest->end_date_hours != $this->end_date_hours) {
      return false;
    }
    
    if ($leaveRequest->approved != $this->approved) {
      return false;
    }
    
    return true;
    
  }
  
  static public function between($startDate, $endDate) {
    return function ($q) use($startDate, $endDate) {
      $q->where('(start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?)', array($startDate, $endDate, $startDate, $endDate));
    };
  }
  
  static public function deleteFromGoogleCalendar() {
    return function ($q) {
      $q->where('deleted = ?', array(true));
    };
  }
  
  static public function insertIntoGoogleCalendar() {
    return function ($q) {
      $q->where('event_id IS NULL');
    };
  }
  
  public function toGoogleCalendarEvent() {
    // endDate is exclusive in Google Calendar
    $endDate = new \DateTime($this->end_date);
    $endDate->modify('+1 day');
    $endDate = $endDate->format('Y-m-d');
    
    $summary = $this->getEmployeeName() . ' vrij';
    
    if (!$this->approved) {
      $summary .= '?';
    }
    
    if (strcmp($this->start_date, $this->end_date) == 0 && $this->hours < 8) {
      $summary .= " (dagdeel van {$this->hours} uur)";
    }
    
    return new \Google_Service_Calendar_Event(array(
      'summary' => $summary,
      'description' => 'Event generated by myleaverequests2googlecalendar, #' . $this->id,
      'start' => array(
        'date' => $this->start_date
      ),
      'end' => array(
        'date' => $endDate
      ),
      'reminders' => array(
        'useDefault' => false,
        'overrides' => array()
      )
    ));
  }
  
  public function getEmployeeName() {
    $nameElems = explode(',', $this->name);
    if (count($nameElems) == 1) {
      return $nameElems[0];
    } else {
      return trim($nameElems[1]) . ' ' . trim($nameElems[0]);
    }
  }
}
