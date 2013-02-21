<?php

/**
 * PublishableGridField.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridField extends GridField
{

    public function __construct($name, $title = null, SS_List $dataList = null, PublishableGridFieldConfig $config = null)
    {
        $config = $config ? : PublishableGridFieldConfig_Base::create();

        parent::__construct($name, $title, $dataList, $config);

        Requirements::css('publishable/css/PublishableGridField.css');
        Requirements::javascript('publishable/javascript/PublishableGridField.js');
    }
}