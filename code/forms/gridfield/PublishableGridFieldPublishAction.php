<?php

/**
 * PublishableGridFieldPublishAction.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldPublishAction implements GridField_ColumnProvider, GridField_ActionProvider
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
        return array('unpublish', 'publish');
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
        $return = '';
        if ($record->isPublished() && $record->canPublish() && $record->canDeleteFromLive()) {
            $field = GridField_FormAction::create($gridField, 'UnPublish'.$record->ID, false, "unpublish",
                    array('RecordID' => $record->ID))
                ->addExtraClass('gridfield-button-unpublish')
                ->setAttribute('title', _t('SiteTree.BUTTONUNPUBLISH', 'Unpublish'))
                ->setAttribute('data-icon', 'unpublish')
                ->setDescription(_t('SiteTree.BUTTONUNPUBLISHDESC', 'Remove this page from the published site'));
            $return .= $field->Field();
        }
        if ($record->canPublish() && (!$record->IsDeletedFromStage || $record->IsModifiedOnStage)) {
            $field = GridField_FormAction::create($gridField, 'Publish'.$record->ID, false, "publish",
                    array('RecordID' => $record->ID))
                ->addExtraClass('gridfield-button-publish')
                ->setAttribute('title', _t('SiteTree.BUTTONSAVEPUBLISH', 'Save & Publish'))
                ->setAttribute('data-icon', 'accept')
                ->setDescription(_t('PublishableGridFieldAction.BUTTONUNPUBLISHDESC',
                    'Save this page to the published site'));
            $return .= $field->Field();
        }

        return $return;
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
        if ($actionName == 'unpublish' || $actionName == 'publish') {
            $item = Versioned::get_latest_version($gridField->getModelClass(), $arguments['RecordID']);
            if (!$item) {
                return;
            }
            if ($actionName == 'unpublish') {
                if (!$item->canPublish()) {
                    throw new ValidationException(
                        _t('GridFieldAction_Delete.DeletePermissionsFailure', "No delete permissions"), 0);
                }

                $item->doUnpublish();
            }
            if ($actionName == 'publish') {
                if (!$item->canPublish()) {
                    throw new ValidationException(
                        _t('GridFieldAction_Delete.DeletePermissionsFailure', "No delete permissions"), 0);
                }

                $item->doPublish();
            }
        }
    }
}
