<?php

namespace mld2gc\models;

class Setting extends \ultimo\orm\Model {

  public $name;
  public $value;
  
  static protected $fields = array('name', 'value');
  static protected $primaryKey = array('name');
  static protected $fetchers = array('getByName');
 
  static public function getByName(\ultimo\orm\StaticModel $staticModel, $name) {
    return $staticModel->query()->where('@name = ?', array($name))->first(array(), false);
  }
}