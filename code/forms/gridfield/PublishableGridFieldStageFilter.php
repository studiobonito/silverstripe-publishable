<?php

/**
 * PublishableGridFieldStageFilter.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldStageFilter implements GridField_HTMLProvider
{
    /**
     * Which template to use for rendering
     *
     * @var string $itemClass
     */
    protected $itemClass = 'PublishableGridFieldStageFilter';

    /**
     * The HTML fragment to write this component into
     */
    protected $targetFragment;

    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Returns a map where the keys are fragment names and the values are pieces of HTML to add to these fragments.
     *
     * Here are 4 built-in fragments: 'header', 'footer', 'before', and 'after', but components may also specify
     * fragments of their own.
     *
     * To specify a new fragment, specify a new fragment by including the text "$DefineFragment(fragmentname)" in the
     * HTML that you return.  Fragment names should only contain alphanumerics, -, and _.
     *
     * If you attempt to return HTML for a fragment that doesn't exist, an exception will be thrown when the GridField
     * is rendered.
     *
     * @return Array
     */
    public function getHTMLFragments($gridField)
    {
        $forTemplate = new ArrayData(array());
        $forTemplate->Fields = new ArrayList();

        $stageTitle = _t('PublishableGridFieldAction.STAGE', 'Stage');

        $stages = array(
            'Latest' => _t('PublishableGridFieldAction.LATEST_VERSION', 'All'),
            'All'    => _t('PublishableGridFieldAction.LATEST_VERSION', 'All (Including Deleted)'),
            'Live'   => _t('PublishableGridFieldAction.STAGE_LIVE', 'Published'),
            'Stage'  => _t('PublishableGridFieldAction.STAGE_STAGE', 'Draft')
        );

        $currentStage = $gridField->State->PublishableGridField->currentStage;

        $stageDropdownField = new DropdownField('PublishableStage', $stageTitle, $stages, $currentStage);
        $stageDropdownField->addExtraClass('no-change-track');

        $forTemplate->Fields->push($stageDropdownField);

        return array(
            $this->targetFragment => $forTemplate->renderWith($this->itemClass)
        );
    }
}
