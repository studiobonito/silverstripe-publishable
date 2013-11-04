<?php

/**
 * PublishableGridFieldConfig.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldConfig extends GridFieldConfig
{

}

/**
 * PublishableGridFieldConfig_Base.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldConfig_Base extends PublishableGridFieldConfig
{

    /**
     *
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null, $currentStage = 'Latest')
    {
        $this->addComponent(new PublishableGridFieldStage($currentStage));
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);
    }
}

/**
 * PublishableGridFieldConfig_RecordViewer.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldConfig_RecordViewer extends PublishableGridFieldConfig
{

    /**
     *
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null, $currentStage = 'Latest')
    {
        $this->addComponent(new PublishableGridFieldStage($currentStage));
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new PublishableGridFieldViewButton());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
        $this->addComponent(new PublishableGridFieldDetailForm());

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);
    }
}

/**
 * PublishableGridFieldConfig_RecordEditor.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldConfig_RecordEditor extends PublishableGridFieldConfig
{

    /**
     *
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null, $currentStage = 'Latest')
    {
        $this->addComponent(new PublishableGridFieldStage($currentStage));
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new PublishableGridFieldDeleteAction());
        $this->addComponent(new PublishableGridFieldPublishAction());
        $this->addComponent(new PublishableGridFieldEditButton());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
        $this->addComponent(new PublishableGridFieldDetailForm());

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);
    }
}