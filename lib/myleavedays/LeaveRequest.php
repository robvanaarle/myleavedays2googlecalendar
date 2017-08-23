<?php

namespace myleavedays;

class LeaveRequest {
  public $name;
  public $startDate;
  public $endDate;
  public $hours;
  public $startDateHours;
  public $endDateHours;
  public $status;

  const STATUS_APPROVED = 'approved';
  const STATUS_REJECTED = 'rejected';
  const STATUS_OPEN = 'open';
  
  static public function fromResponse($response) {
    $leaveRequests = array();
    
    preg_match_all("/(create gegevens).*?<tr>(.*?)<\/tr>/s", $response, $matches);
    
    foreach ($matches[2] as $match) {
      
      preg_match_all("/<td>(.*?)<\/td>/", $match, $values);
      $values = $values[1];
      
      $request = new static();
      $request->name = $values[1];
      $request->startDate = static::toDate($values[2]);
      $request->endDate = static::toDate($values[4]);
      $request->startDateHours = static::toNumericValue($values[3]);
      $request->endDateHours = static::toNumericValue($values[5]);
      $request->hours = static::toNumericValue($values[6]);
      $request->status = static::detectStatus($values[10]);
      
      $leaveRequests[] = $request;
    }
    
    return $leaveRequests;
  }
 
  public function isRejected() {
    return $this->status == static::STATUS_REJECTED;
  }

  public function isApproved() {
    return $this->status == static::STATUS_APPROVED;
  }

  static protected function detectStatus($status) {
    if (strpos($status, 'goedgekeurd') !== false) {
      return static::STATUS_APPROVED;
    } elseif (strpos($status, 'afgekeurd') !== false) {
      return static::STATUS_REJECTED;
    } else {
      return static::STATUS_OPEN;
   }
  }
 
  static protected function toNumericValue($value) {
    return floatval(str_replace(',', '.', $value));
  }
  
  static protected function toDate($value) {
    $dateElems = explode('-', substr($value, 2, -1));
    return $dateElems[2] . '-' . $dateElems[1] . '-' . $dateElems[0];
  }
}
