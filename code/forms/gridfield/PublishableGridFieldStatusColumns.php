<?php

/**
 * PublishableGridFieldStatusColumn.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class PublishableGridFieldStatusColumns implements GridField_ColumnProvider
{

    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('PublishableStage', $columns)) $columns[] = 'PublishableStage';
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        $class = "col-$columnName ".($record->ExistsOnLive ? ' live' : ($record->IsAddedToStage ? ' stage' : ''));

        return array('class' => $class);
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        return null;
    }

    public function getColumnMetadata($gridField, $columnName)
    {
        return array('title' => '');
    }

    public function getColumnsHandled($gridField)
    {
        return array('PublishableStage');
    }
}