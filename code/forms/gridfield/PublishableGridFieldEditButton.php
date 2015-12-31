<?php

/**
 * PublishableGridFieldEditButton.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldEditButton extends GridFieldEditButton
{
    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        // No permission checks, handled through GridFieldDetailForm,
        // which can make the form readonly if no edit permissions are available.

        $data = new ArrayData(array(
            'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'version', $record->Version, 'edit')
        ));

        return $data->renderWith('GridFieldEditButton');
    }
}
