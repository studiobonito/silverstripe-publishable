<?php

/**
 * Publishable provides methods for publishing, saving drafts, deleting, etc for DataObjects extended with Versioned.
 *
 * @author Tom Densham <tom.densham@studiobonito.co.uk>
 * @copyright (c) 2012, Studio Bonito Ltd.
 * @version 1.0
 */
class Publishable extends DataExtension
{

    /**
     * Compares current draft with live version,
     * and returns TRUE if no draft version of this page exists,
     * but the page is still published (after triggering "Delete from draft site" in the CMS).
     *
     * @return boolean
     */
    public function getIsDeletedFromStage()
    {
        if (!$this->owner->ID) {
            return true;
        }
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage($this->owner->class, 'Stage', $this->owner->ID);

        // Return true for both completely deleted pages and for pages just deleted from stage.
        return !($stageVersion);
    }

    /**
     * Return true if this page exists on the live site
     */
    public function getExistsOnLive()
    {
        return (bool) Versioned::get_versionnumber_by_stage($this->owner->class, 'Live', $this->owner->ID);
    }

    /**
     * Compares current draft with live version,
     * and returns TRUE if these versions differ,
     * meaning there have been unpublished changes to the draft site.
     *
     * @return boolean
     */
    public function getIsModifiedOnStage()
    {
        // new unsaved pages could be never be published
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage($this->owner->class, 'Stage', $this->owner->ID);
        $liveVersion = Versioned::get_versionnumber_by_stage($this->owner->class, 'Live', $this->owner->ID);

        return ($stageVersion && $stageVersion != $liveVersion);
    }

    /**
     * Compares current draft with live version,
     * and returns true if no live version exists,
     * meaning the page was never published.
     *
     * @return boolean
     */
    public function getIsAddedToStage()
    {
        // new unsaved pages could be never be published
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage($this->owner->class, 'Stage', $this->owner->ID);
        $liveVersion = Versioned::get_versionnumber_by_stage($this->owner->class, 'Live', $this->owner->ID);

        return ($stageVersion && !$liveVersion);
    }

    public function isNew()
    {
        /**
         * This check was a problem for a self-hosted site, and may indicate a
         * bug in the interpreter on their server, or a bug here
         * Changing the condition from empty($this->owner->ID) to
         * !$this->owner->ID && !$this->owner->record['ID'] fixed this.
         */
        if (empty($this->owner->ID)) {
            return true;
        }

        if (is_numeric($this->owner->ID)) {
            return false;
        }

        return stripos($this->owner->ID, 'new') === 0;
    }

    public function isPublished()
    {
        if ($this->isNew()) {
            return false;
        }

        $baseClass = ClassInfo::baseDataClass($this->owner->class);

        $result = DB::query("SELECT \"ID\" FROM \"{$baseClass}_Live\" WHERE \"ID\" = {$this->owner->ID}");

        return ($result->value()) ? true : false;
    }

    public function canPublish($member = null)
    {
        return $this->owner->canEdit($member);
    }

    public function canDeleteFromLive($member = null)
    {
        return $this->owner->canPublish($member);
    }

    /**
     * Publishes the DataObject by writting a record to the Stage and the Live tables.
     *
     * @throws PermissionFailureException
     */
    public function doPublish()
    {
        if (!$this->owner->canPublish()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'publish')
            ));
        }

        $this->owner->publish('Stage', 'Live');

        // Handle activities undertaken by extensions
        $this->owner->invokeWithExtensions('onAfterPublish', $this->owner);
    }

    /**
     * Unpublish the DataObject by deleting the record from the Live table.
     *
     * @throws PermissionFailureException
     */
    public function doUnpublish()
    {
        if (!$this->owner->canPublish()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'unpublish')
            ));
        }

        $this->owner->deleteFromStage('Live');

        // Handle activities undertaken by extensions
        $this->owner->invokeWithExtensions('onAfterUnPublish', $this->owner);
    }

    /**
     * Save a draft of the DataObject by writting the record to the Stage table
     * and creating a new record in the Versions table if the DataObject has changed.
     *
     * @throws PermissionFailureException
     */
    public function doSaveDraft()
    {
        if (!$this->owner->canEdit()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'save a draft')
            ));
        }

        if (count($this->owner->getChangedFields(true, 2)) === 0) {
            $this->owner->migrateVersion($this->owner->Version);
        } else {
            $this->owner->write();
        }
    }

    /**
     * Delete the current draft of the DataObject by deleting the record from the Stage table.
     *
     * @throws PermissionFailureException
     */
    public function doDeleteDraft()
    {
        if (!$this->owner->canEdit()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'delete')
            ));
        }

        $record = DataObject::get_one(
            $this->owner->CLassName,
            sprintf("\"{$this->owner->ClassName}\".\"ID\" = %d", $this->owner->ID)
        );
        if ($record && !$record->canDelete()) {
            return Security::permissionFailure();
        }
        if (!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #$this->owner->ID", 404);
        }

        // save ID and delete record
        $record->delete();
    }

    /**
     * Restore the current draft DataObject by writting the current DataObject to the Stage table.
     *
     * @throws PermissionFailureException
     */
    public function doRestore()
    {
        if (!$this->owner->canEdit()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'restore')
            ));
        }

        $this->owner->writeToStage('Stage', true);
    }

    /**
     * Revert the current draft DataObject by writting the current record from the Live table to the Stage table.
     *
     * @throws PermissionFailureException
     */
    public function doRevert()
    {
        if (!$this->owner->canEdit()) {
            throw new PermissionFailureException(_t(
                    'VersionedDataObject.PERMISSION_ERROR', 'You do not have permission to {action}!',
                    array('action' => 'restore')
            ));
        }

        $this->owner->publish('Live', 'Stage', false);

        // Use a clone to get the updates made by $this->publish
        $clone = DataObject::get_by_id($this->owner->class, $this->owner->ID);
        $clone->writeWithoutVersion();
    }
}
