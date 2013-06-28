<?php
/*****************************************************************************************
 * X2CRM Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2013 X2Engine Inc.
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

$groups=Groups::getUserGroups(Yii::app()->user->getId());
$tempUserList=array();
foreach($groups as $groupId){
    $userLinks=GroupToUser::model()->findAllByAttributes(array('groupId'=>$groupId));
    foreach($userLinks as $link){
        $user=User::model()->findByPk($link->userId);
        if(isset($user)){
            $tempUserList[]=$user->username;
        }
    }
}

Yii::app()->clientScript->registerScriptFile(
    Yii::app()->getBaseUrl().'/js/whatsNew.js', CClientScript::POS_END);
Yii::app()->clientScript->registerCssFile(Yii::app()->getTheme()->getBaseUrl().'/css/whatsNew.css');
Yii::app()->clientScript->registerScriptFile(
    Yii::app()->getBaseUrl().'/js/spectrumSetup.js', CClientScript::POS_END);

// used for rich editing in new post text field
Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/ckeditor/ckeditor.js');
Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/ckeditor/adapters/jquery.js');
Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl . '/js/emailEditor.js');


$passVarsToClientScript = "
    x2.whatsNew = {};
    x2.whatsNew.minimizeFeed = ".(Yii::app()->params->profile->minimizeFeed==1?'true':'false').";
    x2.whatsNew.commentFlag = false;
    x2.whatsNew.lastEventId = ".(!empty($lastEventId)?$lastEventId:0).";
    x2.whatsNew.lastTimestamp = ".(!empty($lastTimestamp)?$lastTimestamp:0).";
    x2.whatsNew.deletePostUrl = '".$this->createUrl('profile/deletePost')."';
    x2.whatsNew.translations = {};
";

$translations = array (
    'Uncheck Filters' => Yii::t('app','Uncheck Filters'),
    'Check Filters' => Yii::t('app','Check Filters'),
    'Enter text here...' => Yii::t('app','Enter text here...')
);
    

// pass array of predefined theme uploadedBy attributes to client
foreach ($translations as $key=>$val) {
  $passVarsToClientScript .= "x2.whatsNew.translations['".
    $key. "'] = '" . $val . "';";
}

Yii::app()->clientScript->registerScript(
    'passVarsToClientScript', $passVarsToClientScript,
    CClientScript::POS_HEAD);


$userList=array_keys(User::getNames());
$tempUserList=array_diff($userList,$tempUserList);
$usersGroups=implode(",",$tempUserList);


?>

<div class="page-title icon activity-feed"><h2><?php echo Yii::t('app','Activity Feed'); ?></h2>
	<div id="menu-links" class="title-bar">
		<?php
        echo CHtml::link(Yii::t('app','Toggle Comments'),'#',array('id'=>'toggle-all-comments','class'=>'x2-button right'));
        echo CHtml::link(Yii::t('app','My Groups'),'#',array('id'=>'my-groups-filter','class'=>'x2-button right'));
		echo CHtml::link(Yii::t('app','Just Me'),'#',array('id'=>'just-me-filter','class'=>'x2-button right'));
        echo CHtml::link(Yii::t('app','Uncheck Filters'),'#',array('id'=>'toggle-filters-link','class'=>'x2-button right'));
        echo CHtml::link(Yii::t('app','Restore Posts'),'#',array('id'=>'restore-posts','style'=>'display:none;','class'=>'x2-button right'));
        echo CHtml::link(Yii::t('app','Minimize Posts'),'#',array('id'=>'min-posts','class'=>'x2-button right'));

		?>
	</div>
</div>
<div class="form" id="post-form" style="clear:both">
	<?php $feed=new Events; ?>
	<?php $form = $this->beginWidget('CActiveForm', array(
	'id'=>'feed-form',
	'enableAjaxValidation'=>false,
	'method'=>'post',
    'htmlOptions'=>array(
        'onsubmit'=>'publishPost();return false;'
    ),

	)); ?>
	<div class="float-row" style='overflow:visible;'>
		<?php
		echo $form->textArea($feed,'text',array('style'=>'width:99%;height:25px;color:#aaa;display:block;clear:both;'));
		echo "<div id='post-buttons' style='display:none;'>";
        echo $form->dropDownList($feed,'associationId',$users);
        $feed->visibility=1;
		echo $form->dropDownList($feed,'visibility',array(1=>Yii::t('actions','Public'),0=>Yii::t('actions','Private')));
        function translateOptions($item){
            return Yii::t('app',$item);
        }
        echo $form->dropDownList($feed,'subtype',array_map('translateOptions',json_decode(Dropdowns::model()->findByPk(113)->options,true)));
		echo CHtml::submitButton(Yii::t('app','Post'),array('class'=>'x2-button','id'=>'save-button'));
		echo CHtml::button(Yii::t('app','Attach A File/Photo'),array('class'=>'x2-button','onclick'=>"$('#attachments').toggle();", 'id'=>"toggle-attachment-menu-button"));
		echo "</div>";
        ?>
	</div>
	<?php $this->endWidget(); ?>
</div>



<div id="attachments" style="display:none;">
<?php $this->widget('Attachments',array('associationType'=>'feed','associationId'=>Yii::app()->user->getId())); ?>
</div>
<?php
$this->widget('zii.widgets.CListView', array(
    'dataProvider'=>$stickyDataProvider,
    'itemView'=>'_viewEvent',
    'id'=>'sticky-feed',
    'pager' => array(
                    'class' => 'ext.infiniteScroll.IasPager',
                    'rowSelector'=>'.view.top-level',
                    'listViewId' => 'sticky-feed',
                    'header' => '',
                    'options'=>array(
                        'onRenderComplete'=>'js:function(){
                            if(x2.whatsNew.minimizeFeed){
                                minimizePosts();
                            }
                            if(x2.whatsNew.commentFlag){
                                $(".comment-link").click();
                            }
                        }'
                    ),

                ),
    'baseScriptUrl'=>Yii::app()->request->baseUrl.'/themes/'.Yii::app()->theme->name.'/css/listview',
    'template'=>'{pager} {items}'
));
$this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_viewEvent',
    'id'=>'activity-feed',
    'pager' => array(
                    'class' => 'ext.infiniteScroll.IasPager',
                    'rowSelector'=>'.view.top-level',
                    'listViewId' => 'activity-feed',
                    'header' => '',
                    'options'=>array(
                        'onRenderComplete'=>'js:function(){
                            if(x2.whatsNew.minimizeFeed){
                                minimizePosts();
                            }
                            if(x2.whatsNew.commentFlag){
                                $(".comment-link").click();
                            }
                            $.each($(".comment-count"),function(){
                                if($(this).attr("val")>0){
                                    $(this).parent().click();
                                }
                            });
                        }'
                    ),

                  ),
	'baseScriptUrl'=>Yii::app()->request->baseUrl.'/themes/'.Yii::app()->theme->name.'/css/listview',
	'template'=>'{pager} {items}',
));

?>
<div id="broadcast-dialog">
    <div>
        <?php echo CHtml::label('Do you want to email all users?','emailUsers'); ?>
        <?php echo CHtml::checkBox('emailUsers'); ?>
    </div>
    <div>
        <br><?php echo Yii::t('app','Leave colors blank for defaults.');?>
    </div>
    <div>
        <br>
        <?php echo CHtml::label('What color should the broadcast be?','broadcastColor'); ?>
        <br />
        <?php echo CHtml::textField('broadcastColor',''); ?>
    </div>
    <div>
        <?php echo CHtml::label('What color should the font be?','fontColor'); ?>
        <br />
        <?php echo CHtml::textField('fontColor',''); ?>
    </div>
    <div>
        <?php echo CHtml::label('What color should the links be?','linkColor'); ?>
        <br />
        <?php echo CHtml::textField('linkColor',''); ?>
    </div>
</div>
<style>

</style>

