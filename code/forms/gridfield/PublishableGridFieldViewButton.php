<?php

/**
 * PublishableGridFieldViewButton.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldViewButton extends GridFieldViewButton
{

    public function getColumnContent($field, $record, $col)
    {
        if ($record->canView()) {
            $data = new ArrayData(array(
                    'Link' => Controller::join_links(
                        $field->Link('item'), $record->ID, 'version', $record->Version, 'view'
                    )
                ));
            return $data->renderWith('GridFieldViewButton');
        }
    }
}
