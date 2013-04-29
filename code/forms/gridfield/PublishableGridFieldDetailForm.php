<?php

/**
 * PublishableGridFieldDetailForm.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldDetailForm extends GridFieldDetailForm
{

    public function getURLHandlers($gridField)
    {
        return array(
            'item/$ID/version/$Version' => 'handleItem',
            'item/$ID'                  => 'handleItem',
            'autocomplete'              => 'handleAutocomplete',
        );
    }

    public function handleItem($gridField, $request)
    {
        $controller = $gridField->getForm()->Controller();

        if (is_numeric($request->param('ID')) && is_numeric($request->param('Version'))) {
            $record = Versioned::get_version($gridField->getModelClass(), $request->param('ID'),
                    $request->param('Version'));
        } elseif (is_numeric($request->param('ID'))) {
            $record = Versioned::get_latest_version($gridField->getModelClass(), $request->param('ID'));
        } else {
            $record = Object::create($gridField->getModelClass());
        }

        $class = $this->getItemRequestClass();

        $handler = Object::create($class, $gridField, $this, $record, $controller, $this->name);
        $handler->setTemplate($this->template);

        return $handler->handleRequest($request, DataModel::inst());
    }
}

/**
 * PublishableGridFieldDetailForm_ItemRequest.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{
    protected $message;

    function ItemEditForm()
    {
        $form = parent::ItemEditForm();

        if (!$this->record->isNew() && !$this->record->isLatestVersion()) {
            $currentRecord = $this->record;
            $this->record = Versioned::get_latest_version('NewsEntry', $currentRecord->ID);
            $link = "<a href=\"{$this->Link()}\">{$this->record->Title}</a>";
            $this->record = $currentRecord;
            $form->sessionMessage(_t('PublishableGridFieldDetailForm.NOT_LATEST_VERSION',
                    '<strong>Warning!</strong> There is a newer version of this {name} ({link})',
                    array('name' => $this->record->i18n_singular_name(), 'link' => $link)), 'warning');
        }

        $minorActions = CompositeField::create()->setTag('fieldset')->addExtraClass('ss-ui-buttonset');
        $actions = new FieldList($minorActions);

        if ($this->record->hasMethod('isPublished')
            && $this->record->isPublished()
            && $this->record->canPublish()
            && !$this->record->IsDeletedFromStage
            && $this->record->canDeleteFromLive()) {
            // "unpublish"
            $minorActions->push(
                FormAction::create('doUnpublish', _t('PublishableGridFieldDetailForm.UNPUBLISH', 'Unpublish'), 'delete')
                    ->setDescription(_t('PublishableGridFieldDetailForm.UNPUBLISH_DESC',
                            'Remove this page from the published site'))
                    ->addExtraClass('ss-ui-action-destructive')->setAttribute('data-icon', 'unpublish')
                    ->setUseButtonTag(true)
            );
        }

        if ($this->record->hasMethod('canEdit') && $this->record->canEdit()) {
            if ($this->record->IsDeletedFromStage) {
                if ($this->record->ExistsOnLive) {
                    // "restore"
                    $minorActions->push(
                        FormAction::create('doRevert', _t('PublishableGridFieldDetailForm.RESTORE', 'Restore'))
                            ->setUseButtonTag(true)
                    );

                    if ($this->record->canDelete() && $this->record->canDeleteFromLive()) {
                        // "delete from live"
                        $minorActions->push(
                            FormAction::create('doDeleteFromLive', _t('PublishableGridFieldDetailForm.DELETE', 'Delete'))
                                ->addExtraClass('ss-ui-action-destructive')
                                ->setUseButtonTag(true)
                        );
                    }
                } elseif ($this->record->ID === 0) {
                    // "save"
                    $minorActions->push(
                        FormAction::create('doSave', _t('PublishableGridFieldDetailForm.SAVEDRAFT', 'Save Draft'))
                            ->setAttribute('data-icon', 'addpage')
                            ->setUseButtonTag(true)
                    );
                    if ($this->record->hasMethod('canPublish') && $this->record->canPublish()) {
                        // "publish"
                        $actions->push(
                            FormAction::create('doPublish',
                                    _t('PublishableGridFieldDetailForm.PUBLISH', 'Save & Publish'))
                                ->addExtraClass('ss-ui-action-constructive')
                                ->setAttribute('data-icon', 'accept')
                                ->setUseButtonTag(true)
                        );
                    }
                } else {
                    // "restore"
                    $minorActions->push(
                        FormAction::create('doRestore', _t('PublishableGridFieldDetailForm.RESTORE', 'Restore'))
                            ->setAttribute('data-icon', 'decline')
                            ->setUseButtonTag(true)
                    );
                }
            } else {
                if ($this->record->canDelete()) {
                    // "delete"
                    $minorActions->push(
                        FormAction::create('doDelete', _t('PublishableGridFieldDetailForm.DELETEDRAFT', 'Delete draft'))
                            ->addExtraClass('delete ss-ui-action-destructive')
                            ->setAttribute('data-icon', 'decline')
                            ->setUseButtonTag(true)
                    );
                }

                // "save"
                $minorActions->push(
                    FormAction::create('doSave', _t('PublishableGridFieldDetailForm.SAVEDRAFT', 'Save Draft'))
                        ->setAttribute('data-icon', 'addpage')
                        ->setUseButtonTag(true)
                );
            }
        }

        if ($this->record->hasMethod('canPublish')
            && $this->record->canPublish()
            && !$this->record->IsDeletedFromStage) {
            // "publish"
            $actions->push(
                FormAction::create('doPublish', _t('PublishableGridFieldDetailForm.PUBLISH', 'Save & Publish'))
                    ->addExtraClass('ss-ui-action-constructive')
                    ->setAttribute('data-icon', 'accept')
                    ->setUseButtonTag(true)
            );
        }

        $form->setActions($actions);
        return $form;
    }

    public function Link($action = null)
    {
        if ($this->record->ID) {
            return Controller::join_links($this->gridField->Link('item'), $this->record->ID, 'version',
                    $this->record->Version, $action);
        } else {
            return Controller::join_links($this->gridField->Link('item'), 'new', $action);
        }
    }

    public function doSave($data, $form)
    {
        $controller = Controller::curr();

        try {
            $form->saveInto($this->record);
            $this->record->doSaveDraft();
            $this->gridField->getList()->add($this->record);
        }
        catch (PermissionFailureException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->getResponseNegotiator($form, $controller)->respond($controller->getRequest());
        }
        catch (ValidationException $e) {
            $form->sessionMessage($e->getResult()->message(), 'bad');
            return $this->getResponseNegotiator($form, $controller)->respond($controller->getRequest());
        }

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.SAVE_SUCCESS', 'Saved {name} {link}');

	    if (isset($data['publish']) && $data['publish'] == true) {
            try {
                $this->record->doPublish();
            }
            catch (PermissionFailureException $e) {
                $form->sessionMessage($e->getMessage(), 'bad');
                return $this->getResponseNegotiator($form, $controller)->respond($controller->getRequest());
            }

            $this->message = $this->buildMessage('PublishableGridFieldDetailForm.PUBLISH_SUCCESS',
                'Published {name} {link}');
        }

        return $this->onAfterAction($data, $form, $controller);
    }

    public function doPublish($data, $form)
    {
        $data['publish'] = true;
        return $this->doSave($data, $form);
    }

    public function doUnpublish($data, $form)
    {
        try {
            $this->record->doUnpublish();
        }
        catch (PermissionFailureException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->edit(Controller::curr()->getRequest());
        }

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.UNPUBLISH_SUCCESS',
            'Unpublished {name} {link}');

        return $this->onAfterAction($data, $form);
    }

    public function doDelete($data, $form)
    {
        try {
            $this->record->doDeleteDraft();
        }
        catch (PermissionFailureException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->edit(Controller::curr()->getRequest());
        }

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.DELETE_SUCCESSS', 'Deleted {name} {link}');

        return $this->onAfterAction($data, $form);
    }

    public function doDeleteFromLive($data, $form)
    {
        return $this->doUnpublish($data, $form);
    }

    public function doRestore($data, $form)
    {
        $this->record->doRestore();

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.RESTORE_SUCCESS', 'Restored {name} {link}');

        return $this->onAfterAction($data, $form);
    }

    public function doRevert($data, $form)
    {
        $this->record->doRevert();

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.RESTORE_SUCCESS', 'Restored {name} {link}');

        return $this->onAfterAction($data, $form);
    }

    protected function onAfterAction(&$data, &$form, &$controller = null)
    {
        $controller = $controller ? : Controller::curr();

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $form->sessionMessage($this->message, 'good');

            // Redirect to the current version
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($this->Link(), 302);
        } else {
            $toplevelController = $this->getToplevelController();
            if ($toplevelController && $toplevelController instanceof LeftAndMain) {
                $backForm = $toplevelController->getEditForm();
                $backForm->sessionMessage($this->message, 'good');
            } else {
                $form->sessionMessage($this->message, 'good');
            }

            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }

    protected function buildMessage($entity, $string, $name = null, $link = null)
    {
        $name = $name ? : $this->record->i18n_singular_name();
        $link = $link ? : '<a href="'.$this->Link('edit').'">"'.htmlspecialchars($this->record->Title, ENT_QUOTES).'"</a>';

        return _t($entity, $string, array('name' => $name, 'link' => $link));
    }

    protected function getResponseNegotiator(&$form, &$controller)
    {
        $responseNegotiator = new PjaxResponseNegotiator(array(
                'CurrentForm' => function() use(&$form) {
                    return $form->forTemplate();
                },
                'default' => function() use(&$controller) {
                    return $controller->redirectBack();
                }
            ));

        if ($controller->getRequest()->isAjax()) {
            $controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
        }

        return $responseNegotiator;
    }
}