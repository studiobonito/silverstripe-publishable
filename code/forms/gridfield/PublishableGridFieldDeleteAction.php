<?php

/**
 * PublishableGridFieldDeleteAction.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldDeleteAction implements GridField_ColumnProvider, GridField_ActionProvider
{

    /**
     * Add a column 'Delete'
     *
     * @param type $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::createTag()
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'col-buttons');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return array('title' => '');
        }
    }

    /**
     * Which columns are handled by this component
     *
     * @param type $gridField
     * @return type
     */
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return array('deletefromstage');
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if (!$record->IsDeletedFromStage && $record->canDelete()) {
            $field = GridField_FormAction::create($gridField, 'DeleteFromStage'.$record->ID, false, "deletefromstage",
                    array('RecordID' => $record->ID))
                ->addExtraClass('gridfield-button-deletedraft')
                ->setAttribute('title', _t('PublishableGridFieldAction.DELETE', 'Delete draft'))
                ->setAttribute('data-icon', 'decline')
                ->setDescription(_t('PublishableGridFieldAction.DELETE_DESC', 'Remove this page from the draft site'));
            return $field->Field();
        }

        return;
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'deletefromstage') {
            $item = Versioned::get_latest_version($gridField->getModelClass(), $arguments['RecordID']);
            if (!$item) {
                return;
            }
            if ($actionName == 'deletefromstage') {
                if (!$item->canDelete()) {
                    throw new ValidationException(
                        _t('PublishableGridFieldAction.DELETE_PERM', 'You do not have permission to delete this draft'),
                        0);
                }

                $item->doDeleteDraft();
            }
        }
    }
}
