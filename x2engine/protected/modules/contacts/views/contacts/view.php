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
$this->setPageTitle(empty($model->name)?$model->firstName." ".$model->lastName:$model->name);


// find out if we are subscribed to this contact
$result = Yii::app()->db->createCommand()
	->select()
	->from('x2_subscribe_contacts')
	->where(array('and', 'contact_id=:contact_id', 'user_id=:user_id'), array(':contact_id'=>$model->id, 'user_id'=>Yii::app()->user->id))
	->queryAll();
$subscribed = !empty($result); // if we got any results then user is subscribed

$authParams['assignedTo'] = $model->assignedTo;
$menuItems = array(
	array('label'=>Yii::t('contacts','All Contacts'),'url'=>array('index')),
	array('label'=>Yii::t('contacts','Lists'),'url'=>array('lists')),
	array('label'=>Yii::t('contacts','Create Contact'),'url'=>array('create')),
	array('label'=>Yii::t('contacts','View')),
    array('label'=>Yii::t('contacts','Edit Contact'), 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>Yii::t('contacts','Share Contact'),'url'=>array('shareContact','id'=>$model->id)),
//	array('label'=>Yii::t('contacts','View Relationships'),'url'=>'#', 'linkOptions'=>array('onclick'=>'toggleRelationshipsForm(); return false;')),
    array('label'=>Yii::t('contacts','Delete Contact'),'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>Yii::t('app','Send Email'),'url'=>'#','linkOptions'=>array('onclick'=>'toggleEmailForm(); return false;')),
	array('label'=>Yii::t('app','Attach A File/Photo'),'url'=>'#','linkOptions'=>array('onclick'=>'toggleAttachmentForm(); return false;')),
	array('label'=>Yii::t('quotes','Quotes/Invoices'),'url'=>'#','linkOptions'=>array('onclick'=>'toggleQuotes(); return false;')),
	array('label'=>Yii::t('quotes',($subscribed? 'Unsubscribe' : 'Subscribe' )), 'url'=>'#', 'linkOptions'=>array('class'=>'x2-subscribe-button', 'onclick'=>'return subscribe($(this));', 'title'=>Yii::t('contacts', 'Receive email updates every time information for {name} changes', array('{name}'=>$model->firstName.' '.$model->lastName)))),
);

$opportunityModule = Modules::model()->findByAttributes(array('name'=>'opportunities'));
$accountModule = Modules::model()->findByAttributes(array('name'=>'accounts'));
$serviceModule = Modules::model()->findByAttributes(array('name'=>'services'));

if($accountModule->visible) {
	$createAccountButton = 	array(array('label'=>Yii::t('accounts','Create Account'), 'url'=>'#', 'linkOptions'=>array('onclick'=>'return false;', 'id'=>'create-account')));
	array_splice($menuItems, 6, 0, $createAccountButton);
}

if($opportunityModule->visible) {
	$createAccountButton = 	array(array('label'=>Yii::t('opportunities','Create Opportunity'), 'url'=>'#', 'linkOptions'=>array('onclick'=>'return false;', 'id'=>'create-opportunity')));
	array_splice($menuItems, 6, 0, $createAccountButton);
}

if($serviceModule->visible) {
	$createCaseButton = array(array('label'=>Yii::t('services','Create Case'), 'url'=>'#', 'linkOptions'=>array('onclick'=>'return false;', 'id'=>'create-case')));
	array_splice($menuItems, 6, 0, $createCaseButton);
}

if($opportunityModule->visible && $accountModule->visible)
	$menuItems[] = 	array('label'=>Yii::t('app', 'Quick Create'), 'url'=>array('/site/createRecords', 'ret'=>'contacts'), 'linkOptions'=>array('id'=>'x2-create-multiple-records-button', 'class'=>'x2-hint', 'title'=>Yii::t('app', 'Create a Contact, Account, and Opportunity.')));

$this->actionMenu = $this->formatMenu($menuItems, $authParams);

$modelType = json_encode("Contacts");
$modelId = json_encode($model->id);
Yii::app()->clientScript->registerScript('subscribe', "
$(function() {
	$('body').data('subscribed', ". json_encode($subscribed) .");
	$('body').data('subscribeText', ". json_encode(Yii::t('contacts', 'Subscribe')) .");
	$('body').data('unsubscribeText', ". json_encode(Yii::t('contacts', 'Unsubscribe')) .");
	$('body').data('modelType', $modelType);
	$('body').data('modelId', $modelId);
	
	
	$('.x2-subscribe-button').qtip();
});

// subscribe or unsubscribe from this contact
function subscribe(link) {
	$('body').data('subscribed', !$('body').data('subscribed')); // subscribe or unsubscribe
	$.post('subscribe', {ContactId: '{$model->id}', Checked: $('body').data('subscribed')}); // tell server to subscribe / unsubscribe
	if( $('body').data('subscribed') )
		link.html($('body').data('unsubscribeText'));
	else
		link.html($('body').data('subscribeText'));
	return false; // stop event propagation
}

",CClientScript::POS_HEAD);

// widget layout
$layout = Yii::app()->params->profile->getLayout();
?>
<div id="main-column" class="half-width">
<div class="page-title icon fixed" style="background-image:url(<?php echo Yii::app()->theme->baseUrl; ?>/images/contacts.png);">
	<?php //echo CHtml::link('['.Yii::t('contacts','Show All').']','javascript:void(0)',array('id'=>'showAll','class'=>'right hide','style'=>'text-decoration:none;')); ?>
	<?php //echo CHtml::link('['.Yii::t('contacts','Hide All').']','javascript:void(0)',array('id'=>'hideAll','class'=>'right','style'=>'text-decoration:none;')); ?>

	<h2><?php echo $this->ucwords_specific($model->name,array('-',"'"),'UTF-8'); ?></h2>
	<?php $this->renderPartial('_vcrControls', array('model'=>$model)); ?>
	<?php echo CHtml::link('<span></span>','#',array('class'=>'x2-button email right','onclick'=>'toggleEmailForm(); return false;'));
	if(Yii::app()->user->checkAccess('ContactsUpdate',$authParams))
		echo CHtml::link(Yii::t('app','Edit'),$this->createUrl('update',array('id'=>$model->id)),array('class'=>'x2-button right'));
	?>
</div>

<?php $this->renderPartial('application.components.views._detailView',array('model'=>$model,'modelName'=>'contacts')); ?>

<?php $this->widget('X2WidgetList', array('block'=>'center', 'model'=>$model, 'modelType'=>'contacts')); ?>

<?php
//$this->widget('InlineRelationships', array('model'=>$model, 'modelName'=>'contacts'));


/*** Begin Create Related models ***/

// bellow is the javascript code that is executed when clicking:
//		"Create Opportunity"
//		"Create Account"
//		"Create Case"
// It creates a new model with a relationship to this contact. The creation is
// done via ajax calls to each models actionCreate method.
// the full javascript functions can be found in relationships.js

// urls, contact info, etc, is json encoded and then added as parameters to the
// javascript function that calls ajax to create the new model
$linkModel = X2Model::model('Accounts')->findByPk($model->company);
if (isset($linkModel))
	$accountName = json_encode($linkModel->name);
else
	$accountName = json_encode('');
$createOpportunityUrl = $this->createUrl('/opportunities/create');
$createAccountUrl = $this->createUrl('/accounts/create');
$createCaseUrl = $this->createUrl('/services/create');
$assignedTo = json_encode($model->assignedTo);
$tooltip = json_encode(Yii::t('contacts', 'Create a new Opportunity associated with this Contact.'));
$accountsTooltip = json_encode(Yii::t('contacts', 'Create a new Account associated with this Contact.'));
$caseTooltip = json_encode(Yii::t('contacts', 'Create a new Service Case associated with this Contact.'));
$contactName = json_encode($model->firstName .' '. $model->lastName);
$phone = json_encode($model->phone);
$website = json_encode($model->website);
Yii::app()->clientScript->registerScript('create-model', "
	$(function() {
		// init create opportunity button
		$('#create-opportunity').initCreateOpportunityDialog('$createOpportunityUrl', 'Contacts', {$model->id}, $accountName, $assignedTo, $tooltip);

		// init create account button
		$('#create-account').initCreateAccountDialog2('$createAccountUrl', 'Contacts', {$model->id}, $accountName, $assignedTo, $phone, $website, $accountsTooltip);
		
		// init create case button
		$('#create-case').initCreateCaseDialog('$createCaseUrl', 'Contacts', {$model->id}, $contactName, $assignedTo, $caseTooltip);
	});
");
//*** End Create Related models ***/
?>


<?php $this->widget('Attachments',array('associationType'=>'contacts','associationId'=>$model->id,'startHidden'=>true)); ?>

<?php
$insertableAttributes = array();
foreach($model->attributeLabels() as $fieldName => $label) {
	$attr = trim($model->renderAttribute($fieldName,false));
	if($attr !== '')
		$insertableAttributes[$label] = $attr;
}

// echo CJSON::encode($insertableAttributes);
// var_dump($insertableAttributes);

$this->widget('InlineEmailForm',
	array(
		'attributes'=>array(
			'to'=>'"'.$model->name.'" <'.$model->email.'>, ',
			// 'subject'=>'hi',
			// 'redirect'=>'contacts/'.$model->id,
			'modelName'=>'Contacts',
			'modelId'=>$model->id,
		),
		'insertableAttributes'=>array(Yii::t('app','Contact Attributes')=>$insertableAttributes),
		'startHidden'=>true,
	)
);
?>
<div id="quote-form-wrapper">
<?php
 $this->widget('InlineQuotes',
	 array(
		 'startHidden'=>true,
		 'contactId'=>$model->id,
	 )
 );
?>
</div>
</div>
<div class="history half-width">
<?php

$this->widget('Publisher',
	array(
		'associationType'=>'contacts',
		'associationId'=>$model->id,
		'assignedTo'=>Yii::app()->user->getName(),
		'halfWidth'=>true
	)
);

$this->widget('History',array('associationType'=>'contacts','associationId'=>$model->id));
?>
</div>

