<?php

/**
 * PublishableGridFieldStage.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldStage implements GridField_DataManipulator
{
    protected $currentStage = 'Latest';

    public function __construct($currentStage = 'Latest')
    {
        $this->currentStage = $currentStage;
    }

    protected function getPublishableGridFieldState(GridField $gridField)
    {
        $state = $gridField->State->PublishableGridField;

        // Force the state to the initial page if none is set
        if (empty($state->currentStage)) $state->currentStage = 'Latest';

        return $state;
    }

    /**
     * Manipulate the datalist as needed by this grid modifier.
     *
     * @param GridField
     * @param SS_List
     * @return DataList
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        if (is_a($dataList, 'DataList')) {
            $dataQuery = $dataList->dataQuery();

            $state = $this->getPublishableGridFieldState($gridField);

            if ($state->currentStage == 'Stage') {
                $dataQuery->setQueryParam('Versioned.mode', 'stage_unique');
                $dataQuery->setQueryParam('Versioned.stage', 'Stage');
            } elseif ($state->currentStage == 'Live') {
                $dataQuery->setQueryParam('Versioned.mode', 'stage');
                $dataQuery->setQueryParam('Versioned.stage', 'Live');
            } else {
                $dataQuery->setQueryParam('Versioned.mode', 'latest_versions');
            }

            return $dataList->setDataQuery($dataQuery);
        }

        return $dataList;
    }
}