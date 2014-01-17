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
    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );

    protected $message;

    function ItemEditForm()
    {
        $form = parent::ItemEditForm();

        $actions = $form->Actions();

        $majorActions = CompositeField::create()
            ->setName('MajorActions')
            ->setTag('fieldset')
            ->addExtraClass('ss-ui-buttonset');

        $rootTabSet = new TabSet('ActionMenus');
        $moreOptions = new Tab(
            'MoreOptions',
            _t('SiteTree.MoreOptions', 'More options', 'Expands a view for more buttons')
        );
        $rootTabSet->push($moreOptions);
        $rootTabSet->addExtraClass('ss-ui-action-tabset action-menus');

        // Render page information into the "more-options" drop-up, on the top.
        $live = Versioned::get_one_by_stage($this->record->class, 'Live', "\"{$this->record->class}\".\"ID\"='{$this->record->ID}'");
        $existsOnLive = $this->record->getExistsOnLive();
        $published = $this->record->isPublished();
        $moreOptions->push(
            new LiteralField('Information',
                $this->record->customise(array(
                    'Live' => $live,
                    'ExistsOnLive' => $existsOnLive
                ))->renderWith('SiteTree_Information')
            )
        );

        $actions->removeByName('action_doSave');
        $actions->removeByName('action_doDelete');

        if ($this->record->canEdit()) {
            if ($this->record->IsDeletedFromStage) {
                if($existsOnLive) {
                    $majorActions->push(FormAction::create('revert',_t('CMSMain.RESTORE','Restore')));
                    if($this->record->canDelete() && $this->record->canDeleteFromLive()) {
                        $majorActions->push(
                            FormAction::create('unpublish',_t('CMSMain.DELETEFP','Delete'))->addExtraClass('ss-ui-action-destructive')
                        );
                    }
                } else {
                    $majorActions->push(
                        FormAction::create('restore',_t('CMSMain.RESTORE','Restore'))->setAttribute('data-icon', 'decline')
                    );
                }
            } else {
                if ($this->record->canDelete() && !$published) {
                    $moreOptions->push(
                        FormAction::create('delete', _t('SiteTree.BUTTONDELETE', 'Delete draft'))
                            ->addExtraClass('ss-ui-action-destructive')
                            ->setUseButtonTag(true)
                    );
                }

                $majorActions->push(
                    FormAction::create('save', _t('SiteTree.BUTTONSAVED', 'Saved'))
                        ->setAttribute('data-icon', 'accept')
                        ->setAttribute('data-icon-alternate', 'addpage')
                        ->setAttribute('data-text-alternate', _t('CMSMain.SAVEDRAFT', 'Save draft'))
                        ->setUseButtonTag(true)
                );
            }
        }

        $publish = FormAction::create(
            'publish',
            $published ? _t('SiteTree.BUTTONPUBLISHED', 'Published') : _t('SiteTree.BUTTONSAVEPUBLISH', 'Save & publish')
        )
            ->setAttribute('data-icon', 'accept')
            ->setAttribute('data-icon-alternate', 'disk')
            ->setAttribute('data-text-alternate', _t('SiteTree.BUTTONSAVEPUBLISH', 'Save & publish'))
            ->setUseButtonTag(true);

        if (!$published || ($this->record->stagesDiffer('Stage', 'Live') && $published)) {
            $publish->addExtraClass('ss-ui-alternate');
        }

        if ($this->record->canPublish() && !$this->record->IsDeletedFromStage) $majorActions->push($publish);

        if ($published && $this->record->canPublish() && !$this->record->IsDeletedFromStage && $this->record->canDeleteFromLive()) {
            $moreOptions->push(
                FormAction::create('unpublish', _t('SiteTree.BUTTONUNPUBLISH', 'Unpublish'), 'delete')
                    ->addExtraClass('ss-ui-action-destructive')
                    ->setUseButtonTag(true)
            );
        }

        if ($this->record->stagesDiffer('Stage', 'Live') && !$this->record->IsDeletedFromStage
            && $this->record->isPublished() && $this->record->canEdit()) {
            $moreOptions->push(
                FormAction::create('rollback', _t('SiteTree.BUTTONCANCELDRAFT', 'Cancel draft changes'))
                    ->setDescription(_t('SiteTree.BUTTONCANCELDRAFTDESC', 'Delete your draft and revert to the currently published page'))
                    ->setUseButtonTag(true));
        }

        $actions->push($majorActions);
        $actions->push($rootTabSet);

        if($this->record->hasMethod('getCMSValidator')) {
            $form->setValidator($this->record->getCMSValidator());
        }

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

    public function save($data, $form)
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

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.SAVE_SUCCESS', 'Saved {name} "{title}"');

	    if (isset($data['publish']) && $data['publish'] == true) {
            try {
                $this->record->doPublish();
            }
            catch (PermissionFailureException $e) {
                $form->sessionMessage($e->getMessage(), 'bad');
                return $this->getResponseNegotiator($form, $controller)->respond($controller->getRequest());
            }

            $this->message = $this->buildMessage('PublishableGridFieldDetailForm.PUBLISH_SUCCESS',
                'Published {name} "{title}"');
        }

        return $this->onAfterAction($data, $form, $controller);
    }

    public function publish($data, $form)
    {
        $data['publish'] = true;
        return $this->save($data, $form);
    }

    public function unpublish($data, $form)
    {
        try {
            $this->record->doUnpublish();
        }
        catch (PermissionFailureException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->edit(Controller::curr()->getRequest());
        }

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.UNPUBLISH_SUCCESS',
            'Unpublished {name} "{title}"');

        return $this->onAfterAction($data, $form);
    }

    public function delete($data, $form)
    {
        try {
            $this->record->doDeleteDraft();
        }
        catch (PermissionFailureException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->edit(Controller::curr()->getRequest());
        }

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.DELETE_SUCCESSS', 'Deleted {name} "{title}"');

        return $this->onAfterAction($data, $form);
    }

    public function rollback($data, $form)
    {
        $this->record->doRevert();

        $this->record = DataObject::get_by_id($this->record->class, $this->record->ID);

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.RESTORE_SUCCESS', 'Restored {name} "{title}"');

        return $this->onAfterAction($data, $form);
    }

    public function revert($data, $form)
    {
        $this->record->doRevert();

        $this->record = DataObject::get_by_id($this->record->class, $this->record->ID);

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.RESTORE_SUCCESS', 'Restored {name} "{title}"');

        return $this->onAfterAction($data, $form);
    }

    public function restore($data, $form)
    {
        $this->record->doRestore();

        $this->record = DataObject::get_by_id($this->record->class, $this->record->ID);

        $this->message = $this->buildMessage('PublishableGridFieldDetailForm.RESTORE_SUCCESS', 'Restored {name} "{title}"');

        return $this->onAfterAction($data, $form);
    }

    protected function onAfterAction(&$data, &$form, &$controller = null)
    {
        $form->sessionMessage($this->message, 'good');

        return $this->edit(Controller::curr()->getRequest());
    }

    protected function buildMessage($entity, $string, $name = null, $title = null)
    {
        $name = $name ? : $this->record->i18n_singular_name();
        $title = $title ? : htmlspecialchars($this->record->Title, ENT_QUOTES);

        return _t($entity, $string, array('name' => $name, 'title' => $title));
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
