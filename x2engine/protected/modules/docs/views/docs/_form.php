<?php
/*********************************************************************************
 * The X2CRM by X2Engine Inc. is free software. It is released under the terms of 
 * the following BSD License.
 * http://www.opensource.org/licenses/BSD-3-Clause
 * 
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95067 USA
 * 
 * Company website: http://www.x2engine.com 
 * Community and support website: http://www.x2community.com 
 * 
 * Copyright (C) 2011-2012 by X2Engine Inc. www.X2Engine.com
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * - Redistributions of source code must retain the above copyright notice, this 
 *   list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this 
 *   list of conditions and the following disclaimer in the documentation and/or 
 *   other materials provided with the distribution.
 * - Neither the name of X2Engine or X2CRM nor the names of its contributors may be 
 *   used to endorse or promote products derived from this software without 
 *   specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 ********************************************************************************/

// editor javascript files
Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/ckeditor/ckeditor.js');
Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/ckeditor/adapters/jquery.js');
Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/emailEditor.js');



$autosaveUrl = $this->createUrl('autosave').'?id='.$model->id;

$js = '';

if($model->type==='email') {
	$insertableAttributes = array();
	foreach(X2Model::model('Contacts')->attributeLabels() as $fieldName => $label)
		$insertableAttributes[$label] = '{'.$fieldName.'}';
		
	$js = 'x2.insertableAttributes = '.CJSON::encode(array(Yii::t('contacts','Contact Attributes')=>$insertableAttributes)).';';
}

$js .='
var typingTimer;

function autosave() {
	window.docEditor.updateElement();
	$("#savetime").html("'.addslashes(Yii::t('app','Saving...')).'");
	$.post("'.$autosaveUrl.'", $("form").serializeArray(), function(response) {
		$("#savetime").html(response);
	});
}

if(window.docEditor)
	window.docEditor.destroy(true);
window.docEditor = createCKEditor("input",{
	'.($model->type==='email'? 'insertableAttributes:x2.insertableAttributes,' : '').'
	// toolbar:"Full",
	fullPage:true,
	height:600
}'.($model->isNewRecord? '' : ',setupAutosave').');
function setupAutosave() {
	if($.browser.msie)
		return;
	// save after 1.5 seconds when the user is done typing
	
	window.docEditor.document.on("keyup",function(e) {
		clearTimeout(typingTimer);
		typingTimer = setTimeout(autosave, 1500);
	});
	window.docEditor.on("saveSnapshot",function(e) {
		clearTimeout(typingTimer);
		typingTimer = setTimeout(autosave, 1500);
	});
	window.docEditor.document.on("keydown",function(){ clearTimeout(typingTimer); });
}';

Yii::app()->clientScript->registerScript('doc-editor',$js,CClientScript::POS_READY);

$form=$this->beginWidget('CActiveForm', array(
	'id'=>'docs-form',
	'enableAjaxValidation'=>false,
)); ?>
<div class="form no-border">
	<div class="row">
		<div class="cell">
			<?php echo $form->errorSummary($model); ?>
			<?php echo $form->label($model,'name'); ?>
			<?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>100)); ?>
			<?php echo $form->error($model,'name'); ?>
		</div>
		<div class="cell">
			<?php echo $form->label($model,'visibility'); ?>
			<?php echo $form->dropDownList($model,'visibility',array(1=>Yii::t('app','Public'),0=>Yii::t('app','Private'))); ?>
			<?php echo $form->error($model,'visibility'); ?>
		</div>
		<div class="cell right">
			<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('app','Create') : Yii::t('app','Save'),array('class'=>'x2-button float')); ?>
		</div>
	</div>
	<div class="row">
        <?php if($this->action->id=='createEmail' || ($this->action->id=='update' && $model->type=='email')){ ?>
            <?php echo $form->label($model,'subject'); ?>
            <?php echo $form->textField($model,'subject',array('size'=>60,'maxlength'=>255)); ?>
            <?php echo $form->error($model,'subject'); ?>
        <?php } ?>
		<span id="savetime">
			<?php if(isset($_GET['saved'])){
				$date=date("g:i:s A",$_GET['time']);
				echo Yii::t('Docs', 'Saved at') ." $date";
			} ?>
		</span>
	</div><?php if($this->route=='docs/createEmail') { ?>
	<div class="row">
		<?php echo Yii::t('docs','<b>Note:</b> You can use dynamic variables such as {firstName}, {lastName} or {phone} in your template. When you email a contact, these will be replaced by the appropriate value.'); ?>
	</div><?php } ?>
	<div class="row" style="margin-top:5px;">
		<?php 
		if($model->isNewRecord && isset($users)){
			echo $form->label($model,'editPermissions');
			echo $form->dropDownList($model,'editPermissions',$users,array('multiple'=>'multiple','size'=>'5'));
			echo $form->error($model,'editPermissions');
		}
		echo $form->error($model,'text');
		echo $form->textArea($model,'text',array('id'=>'input'));
		?>
	</div>

</div>
<?php echo $form->error($model,'text'); ?>

<?php $this->endWidget(); ?>