<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

/*
Parameters:
    massActions - array of strings - list of available mass actions to select from
        ('delete' | 'newList' | 'addToList' | 'tag' | 'updateField')
    gridId - the id property of the X2GridView instance
    modelName - the modelName property of the X2GridView instance
    selectedAction - string - if set, used to select option from mass actions dropdown
    gridObj - object - the x2gridview instance
*/

Yii::app()->clientScript->registerScriptFile (
    Yii::app()->getBaseUrl().'/js/X2GridView/X2GridViewMassActionsManager.js', CClientScript::POS_END);



$massActionLabels = array (
    'completeAction' => Yii::t ('app', 'Complete selected actions'),
    'uncompleteAction' => Yii::t ('app', 'Uncomplete selected actions'),
    'newList' => Yii::t ('app', 'New list from selection'),
    'addToList' => Yii::t ('app', 'Add selected to list'),
    'removeFromList' => Yii::t ('app', 'Remove selected from list'),
    
);

AuxLib::registerTranslationsScript ('massActions', array (
    'deleteprogressBarDialogTitle' => 'Mass Deletion in Progress',
    'updateFieldprogressBarDialogTitle' => 'Mass Update in Progress',
    'progressBarDialogTitle' => 'Mass Action in Progress',
    'deleted' => 'deleted',
    'tagged' => 'tagged',
    'added' => 'added',
    'updated' => 'updated',
    'removed' => 'removed',
    'doubleConfirmDialogTitle' => 'Confirm Deletion',
    'addedItems' => 'Added items to list',
    'addToList' => 'Add selected to list',
    'removeFromList' => 'Remove selected from list',
    'newList' => 'Create new list from selected',
    'add' => 'Add to list',
    'remove' => 'Remove from list',
    'noticeFlashList' => 'Mass action exectuted with',
    'errorFlashList' => 'Mass action exectuted with',
    'noticeItemName' => 'warnings',
    'errorItemName' => 'errors',
    'successItemName' => 'Close',
    'blankListNameError' => 'Cannot be left blank',
    'passwordError' => 'Password cannot be left blank',
    'close' => 'Close',
    'cancel' => 'Cancel',
    'create' => 'Create',
    'pause' => 'Pause',
    'stop' => 'Stop',
    'resume' => 'Resume',
    'complete' => 'Complete',
    'tag' => 'Tag',
    'update' => 'Update',
    'tagSelected' => 'Tag selected',
    'deleteSelected' => 'Delete selected',
    'delete' => 'Delete',
    'updateField' => 'Update fields of selected',
    'emptyTagError' => 'At least one tag must be included',
));

Yii::app()->clientScript->registerCss ('massActionsCss', "

.x2-gridview-mass-action-outer {
    position: relative;
}

@media (max-width: 820px) and (min-width: 658px) {
    .grid-view.fullscreen .x2-gridview-top-pager {
        display: none;
    }
}


/*
Check all records in data provider feature
*/
.grid-view .select-all-records-on-all-pages-strip-container {
    margin-right: -1px;
}
.grid-view .x2-gridview-fixed-top-bar-outer .select-all-records-on-all-pages-strip-container {
    margin-right: 6px;
    margin-left: 6px;
}

.grid-view .select-all-records-on-all-pages-strip-container {
    text-align: center;
    border-right: 1px solid rgb(207, 207, 207);
    border-bottom: 1px solid rgb(199, 199, 199);
    position: relative;
    z-index: 1;
}

.grid-view .select-all-records-on-all-pages-strip-container .select-all-notice,
.grid-view .select-all-records-on-all-pages-strip-container .all-selected-notice {
    padding: 4px;
}

.grid-view .select-all-records-on-all-pages-strip-container .select-all-notice {
    background: rgb(255, 255, 185);
}

.grid-view .select-all-records-on-all-pages-strip-container .all-selected-notice {
    background: rgb(203, 255, 201);
}

body.no-widgets .grid-view .x2-gridview-fixed-top-bar-outer .select-all-records-on-all-pages-strip-container {
    margin-right: 0;
}

.x2-mobile-layout .select-all-records-on-all-pages-strip-container {
    margin-left: 0;
    margin-right: -1px;
}

.grid-view .container-clone {
    visibility: hidden;
}

.x2-mobile-layout .x2grid-body-container .container-clone,
.x2grid-body-container.x2-gridview-body-without-fixed-header .container-clone {
    display: none !important;
}

/*
Flashes container
*/

.super-mass-action-feedback-box {
    margin: 5px 0;
    border: 1px solid rgb(176, 176, 176);
    background: rgb(250, 250, 250);
    box-shadow: inset 1px 1px rgb(219, 219, 219);
    padding: 4px;
    height: 76px;
    overflow-y: scroll;
}

.super-mass-action-feedback-box .success-flash {
    color: green;
}
.super-mass-action-feedback-box .error-flash {
    color: red;
}



#x2-gridview-flashes-container.fixed-flashes-container {
    position: fixed;
    opacity: 0.9;
    bottom: 5px;
}

#x2-gridview-flashes-container {
    margin-top: 5px;
    margin-right: 5px;
}

#x2-gridview-flashes-container > div {
    margin-top: 5px;
    margin-left: 4px;
}

#x2-gridview-flashes-container .flash-list-header {
    margin-bottom: 4px;
}

#x2-gridview-flashes-container .x2-gridview-flashes-list {
    clear: both;
    margin-bottom: 5px;
}

#x2-gridview-flashes-container .flash-list-left-arrow,
#x2-gridview-flashes-container .flash-list-down-arrow {
    margin-left: 6px;
    margin-top: 3px;
}



/*
buttons 
*/

.mass-action-more-button-container .x2-down-arrow {
    margin-left: 30px;
    margin-top: 11px;
}

.mass-action-more-button-container .more-button-arrow {
    height: 5px;
}

.mass-action-more-button-container .more-button-label {
    display: inline !important;
    float: left;
    margin-right:5px;
}

.mass-action-more-button-container {
    margin: 0 5px 0 0;
    display: inline-block;
}

.mass-action-more-button-container button {
    display: inline;
    height: 26px;
}




/*
more drop down list
*/

.x2-gridview-mass-action-buttons .more-drop-down-list.stuck {
    position: absolute !important;
    /*top: 74px !important;*/
}

.x2-gridview-mass-action-buttons .more-drop-down-list {
    position: absolute;
    top: 67px;
    z-index: 99;
    list-style-type: none;
    background: #fff;
    border: 1px solid #999;
    -moz-box-shadow: 0 0 15px 0 rgba(0,0,0,0.5);
    -webkit-box-shadow: 0 0 15px 0 rgba(0,0,0,0.5);
    box-shadow: 0 0 15px 0 rgba(0,0,0,0.5);
    padding: 5px 0px 5px 0px;
    clip: rect(0px,1000px,1000px,-10px);
}

.x2-gridview-mass-action-buttons .more-drop-down-list li {
    line-height: 17px;
    padding: 0 10px 0 10px;
    cursor: default;
}
.x2-gridview-mass-action-buttons .more-drop-down-list li:hover {
    background: #eee;
}

/*
general mass actions styling
*/

#mass-action-dialog-loading-anim {
    margin-right: 30px;
}

.x2-gridview-mass-action-buttons .dialog-help-text {
    margin-bottom: 5px;
}

.x2-gridview-mass-action-buttons {
    margin: 0 5px 0 0;
    display: inline-block;
}
");

Yii::app()->clientScript->registerResponsiveCss ('massActionsCssResponsive', "

@media (max-width: 657px) {
    .x2-gridview-mass-action-buttons {
        position: absolute;
        width: 137px;
        top: -41px;
        right: -179px;
        margin: 0px;
    }
    .show-top-buttons .x2-gridview-mass-action-buttons {
        right: -183px; 
    }
}

@media (min-width: 658px) {
    .x2-gridview-mass-action-buttons .more-drop-down-list.fixed-header {
        /*position: fixed;*/
    }
}

");

// destroy mass action dialogs, save checks so that can be preserved through grid update
$beforeUpdateJSString = "
    x2.DEBUG && console.log ('beforeUpdateJSString');
    
    
    $('.mass-action-dialog').each (function () {
        //x2.massActions.DEBUG && console.log ('destroying dialog loop');
        if ($(this).closest ('.ui-dialog').length) {
            //x2.massActions.DEBUG && console.log ('destroying dialog');
            $(this).dialog ('destroy');
            $(this).hide ();
        }
    });

    // save to preserve checks
    x2.".$namespacePrefix."MassActionsManager.saveSelectedRecords ();

    // show loading overlay to prevent grid view user interaction
    $('#".$gridId." .x2-gridview-updating-anim').show ();
";

$gridObj->addToBeforeAjaxUpdate ($beforeUpdateJSString);

// reapply event handlers and checks
$afterUpdateJSString = "
    x2.DEBUG && console.log ('afterUpdateJSSTring');
    if (typeof x2.".$namespacePrefix."MassActionsManager !== 'undefined') 
        x2.".$namespacePrefix."MassActionsManager.reinit (); 
    $('#".$gridId." .x2-gridview-updating-anim').hide ();
";

$gridObj->addToAfterAjaxUpdate ($afterUpdateJSString);

Yii::app()->clientScript->registerScript($namespacePrefix.'massActionsInitScript',"
    if (typeof x2.".$namespacePrefix."MassActionsManager === 'undefined') {
        x2.DEBUG && console.log ('new X2GridViewMassActionsManager');
        x2.".$namespacePrefix."MassActionsManager = new x2.GridViewMassActionsManager ({
            massActions: ".CJSON::encode ($massActions).",
            gridId: '".$gridId."',
            namespacePrefix: '".$namespacePrefix."',
            gridSelector: '#".$gridId."',
            fixedHeader: ".($fixedHeader ? 'true' : 'false').",
            massActionUrl: '".Yii::app()->request->getScriptUrl () . '/' . 
                lcfirst ($gridObj->moduleName) .  '/x2GridViewMassAction'."',
             
            modelName: '".$modelName."',
            translations: x2.massActions.translations,
            expandWidgetSrc: '".Yii::app()->getTheme()->getBaseUrl().
                '/images/icons/Expand_Widget.png'."',
            collapseWidgetSrc: '".Yii::app()->getTheme()->getBaseUrl().
                '/images/icons/Collapse_Widget.png'."',
            closeWidgetSrc: '".Yii::app()->getTheme()->getBaseUrl().
                '/images/icons/Close_Widget.png'."',
            progressBarDialogSelector: '#$namespacePrefix-progress-dialog',
            enableSelectAllOnAllPages: ".
                ($gridObj->enableSelectAllOnAllPages ? 'true' : 'false').",
            totalItemCount: {$gridObj->dataProvider->totalItemCount},
            idChecksum: '$idChecksum',
        });
    } else {
        // grid was refreshed, total item count may have changed
        x2.{$namespacePrefix}MassActionsManager.totalItemCount = 
            {$gridObj->dataProvider->totalItemCount};
        x2.{$namespacePrefix}MassActionsManager.idChecksum = 
            '$idChecksum';
    }
", CClientScript::POS_READY);

?>

<span class='x2-gridview-mass-action-outer'>

<div id='<?php echo $gridId; ?>-mass-action-buttons' class='x2-gridview-mass-action-buttons'>
     
    <?php
    
    ?>

    <?php
    $moreActions = array_diff ($massActions, array ('tag', 'delete'));
    if ($moreActions) {
        ?>
        <div class='mass-action-more-button-container'>
            <button class='mass-action-more-button x2-button'  
             title='<?php echo Yii::t('app', 'More Mass Actions'); ?>'>
                <span class='more-button-label'>
                    <?php echo Yii::t('app', 'More'); ?>
                </span>
                <img class='more-button-arrow' 
                 src='<?php echo Yii::app()->getTheme()->getBaseUrl().
                    '/images/icons/Collapse_Widget.png'; ?>' />
            </button>
        </div>
        <ul style='display: none;'
         class="more-drop-down-list<?php echo ($fixedHeader ? ' fixed-header' : ''); ?>"> 
        <?php
        foreach ($massActions as $action) {
        ?>
            <li class='mass-action-button mass-action-<?php echo $action; ?>'
             <?php echo ($action === 'tag' || $action === 'delete' ? 
              'style="display: none;"' : ''); ?>>
              <?php echo $massActionLabels[$action]; ?>
            </li>
        <?php
        }
        ?>
        </ul>
    <?php
    }
    ?>

    <!--mass action dialog contents-->    
    <?php
    if (in_array ('newList', $massActions)) {
    ?>
    <div class='mass-action-dialog' id="<?php echo $gridId; ?>-new-list-dialog" style="display: none;">
        <span>
            <?php echo Yii::t('app', 'What should the list be named?'); ?>
        </span>
        <br/>
        <input class='left new-list-name'></input>
    </div>
    <?php
    }
    if (in_array ('addToList', $massActions)) {
    ?>
    <div class='mass-action-dialog' id="<?php echo $gridId; ?>-add-to-list-dialog" style="display: none;">
        <span>
            <?php echo Yii::t('app', 'Select a list to which the selected records will be added.');
            ?>
        </span>
        <?php
        $listNames  = X2List::getAllStaticListNames ($this);
        echo empty($listNames)
            ? '<br><br>'.Yii::t('app','There are no static lists to which '
                    . 'contacts can be added.').' '.
                CHtml::link(Yii::t('contacts','Create a List'),
                            array('/contacts/contacts/createList'))
            : CHtml::dropDownList ('addToListTarget',null, $listNames); ?>
    </div>
    <?php
    }
    if (in_array ('removeFromList', $massActions)) {
    ?>
    <div class='mass-action-dialog' id="<?php echo $gridId; ?>-remove-from-list-dialog" 
     style="display: none;">
        <span>
            <?php echo Yii::t('app', 'Remove all selected records from this list?'); ?> 
        </span>
    </div>
    <?php
    }
    
    ?>
</div>

</span>

<div class='mass-action-dialog double-confirmation-dialog' style='display: none;'>
    <span><?php 
        echo Yii::t(
            'app', 'You are about to delete {count} {recordType}.', 
            array (
                '{recordType}' => X2Model::getRecordName ($gridObj->modelName, true),
                '{count}' => '<b>'.$gridObj->dataProvider->totalItemCount.'</b>',
            ));
        echo Yii::t('app', 'This action cannot be undone.'); 
        ?>
        </br>
        </br>
        <?php
        echo Yii::t('app', 'Please enter your password to confirm that you want to '.
            'delete the selected records.'); 
    ?></span>
    </br>
    </br>
    <?php
    echo CHtml::label (Yii::t('app', 'Password:'), 'LoginForm[password]');
    ?>
    <input name="password" type='password'>
</div>
<div id='<?php echo $namespacePrefix; ?>-progress-dialog' class='progress-dialog mass-action-dialog'
 style='display: none;'>
<?php
    $this->widget ('X2ProgressBar', array (
        'uid' => $gridObj->namespacePrefix,
        'max' => $gridObj->dataProvider->totalItemCount,
        'label' => '',
    ));
?>
<div class='super-mass-action-feedback-box'>
</div>
</div>
