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
        if (!in_array('PublishableStage', $columns)) {
            $columns[] = 'PublishableStage';
        }
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        $return = array('class' => "col-$columnName");
        if ($record->ExistsOnLive) {
            $return['class'] .= " live";
            $return['title'] = _t('PublishableGridFieldStatusColumns.Live', 'Published');
            if ($record->IsModifiedOnStage) {
                $return['class'] .= " modified-stage";
                $return['title'] = _t('PublishableGridFieldStatusColumns.ModifiedStage', 'Published, but modified');
            }
        } elseif ($record->IsAddedToStage) {
            $return['class'] .= " stage";
            $return['title'] = _t('PublishableGridFieldStatusColumns.Stage', 'Draft');
        }
        return $return;
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
