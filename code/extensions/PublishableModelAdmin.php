<?php

/**
 * PublishableModelAdmin extends ModelAdmin to replace the GridField with PublishableGridField.
 * It also removes the PublishableGridFieldStageFilter component and adds a ModelAdmin style filter instead.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableModelAdmin extends Extension
{

    /**
     * Replace the GridField with a PublishableGridField if the DataObject being managed is Publishable.
     *
     * @param type $form
     */
    public function updateEditForm($form)
    {
        $modelClass = $this->owner->modelClass;
        if ($modelClass::has_extension('Publishable')) {
            $params = $this->owner->request->getVar('q');
            $fieldName = str_replace('\\', '-', $modelClass);
            $list = $this->owner->getList();
            $exportButton = new GridFieldExportButton('before');
            $exportButton->setExportColumns($this->owner->getExportFields());
            $listField = PublishableGridField::create(
                    $fieldName, false, $list,
                    $fieldConfig = PublishableGridFieldConfig_RecordEditor::create($this->owner->stat('page_length'))
                    ->addComponent($exportButton)
                    ->removeComponentsByType('GridFieldFilterHeader')
                    ->removeComponentsByType('PublishableGridFieldStageFilter')
                    ->addComponents(new GridFieldPrintButton('before'))
            );

            if (isset($params['Stage']) && !empty($params['Stage'])) {
                $listField->State->PublishableGridField->currentStage = $params['Stage'];
            }

            $listField->setForm($form);

            $form->Fields()->replaceField($fieldName, $listField);

            // Validation
            if (singleton($modelClass)->hasMethod('getCMSValidator')) {
                $detailValidator = singleton($modelClass)->getCMSValidator();

                $listField->getConfig()->getComponentByType('PublishableGridFieldDetailForm')
                    ->setValidator($detailValidator);
            }
        }
    }

    /**
     * Adds a dropdown for selecting the Stage to the SearchForm.
     *
     * @param type $form
     */
    public function updateSearchForm($form)
    {
        $modelClass = $this->owner->modelClass;
        if ($modelClass::has_extension('Publishable')) {
            $stageTitle = _t('PublishableGridField.STAGE', 'Version');

            $stages = array(
                'Latest' => _t('PublishableGridField.LATEST_VERSION', 'Latest'),
                'Live'   => _t('PublishableGridField.STAGE_LIVE', 'Published'),
                'Stage'  => _t('PublishableGridField.STAGE_STAGE', 'Draft')
            );

            $params = $this->owner->request->getVar('q');

            $stage = isset($params['Stage']) ? $params['Stage'] : null;

            $stageDropdownField = new DropdownField('q[Stage]', $stageTitle, $stages, $stage);

            $form->Fields()->push($stageDropdownField);
        }
    }
}
