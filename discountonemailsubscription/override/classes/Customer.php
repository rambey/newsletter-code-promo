<?php

class Customer extends CustomerCore
{

    public $discountonemailsubscription;

    public function __construct($id = null)
    {
        self::$definition['fields']['discountonemailsubscription'] = array('type' => self::TYPE_INT, 'required' => false);

        return parent::__construct($id);
    }
}