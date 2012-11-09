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

Yii::app()->clientScript->registerScript('LockedQuoteDialog', "
function dialogStrictLock() {
var denyBox = $('<div></div>')
    .html('This quote is locked.')
    .dialog({
    	title: 'Locked', 
    	autoOpen: false,
    	resizable: false,
    	buttons: {
    		'OK': function() {
    			$(this).dialog('close');
    		},
    	},
    });
        
denyBox.dialog('open');
}

function dialogLock() {
var confirmBox = $('<div></div>')
    .html('This quote is locked. Are you sure you want to update this quote?')
    .dialog({
    	title: 'Locked', 
    	autoOpen: false,
    	resizable: false,
    	buttons: {
    		'Yes': function() {
    			window.location = '". Yii::app()->createUrl('quotes/quotes/update/', array('id'=>$model->id)) ."';
    			$(this).dialog('close');
    		},
    		'No': function() {
    			$(this).dialog('close');
    		}
    	},
    });
confirmBox.dialog('open');
}

",CClientScript::POS_HEAD);

$authParams['assignedTo']=$model->assignedTo;
$this->actionMenu = $this->formatMenu(array(
	array('label'=>Yii::t('quotes','Quotes List'), 'url'=>array('index')),
	array('label'=>Yii::t('quotes','Create'), 'url'=>array('create')),
	array('label'=>Yii::t('quotes','View')),
),$authParams);

$strict = Yii::app()->params['admin']['quoteStrictLock'];
if($model->locked)
	if($strict && Yii::app()->user->name != 'admin')
		$this->actionMenu[] = array('label'=>Yii::t('quotes','Update'), 'url'=>'#', 'linkOptions'=>array('onClick'=>'dialogStrictLock();'));
	else
		$this->actionMenu[] = array('label'=>Yii::t('quotes','Update'), 'url'=>'#', 'linkOptions'=>array('onClick'=>'dialogLock();'));
else
	$this->actionMenu[] = array('label'=>Yii::t('quotes','Update'), 'url'=>array('update', 'id'=>$model->id));

$this->actionMenu[] = array('label'=>Yii::t('quotes','Delete'), 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?'));
$this->actionMenu[] = array('label'=>Yii::t('app','Attach A File/Photo'),'url'=>'#','linkOptions'=>array('onclick'=>'toggleAttachmentForm(); return false;'));
$this->actionMenu[] = array('label'=>Yii::t('quotes','Print Quote'), 'url'=>'#', 'linkOptions'=>array('onClick'=>"window.open('". Yii::app()->createUrl('quotes/quotes/print', array('id'=>$model->id)) ."')"));


?>
<div id="main-column" class="half-width">
<div class="record-title">
<?php echo CHtml::link('['.Yii::t('contacts','Show All').']','javascript:void(0)',array('id'=>'showAll','class'=>'right hide','style'=>'text-decoration:none;')); ?>
<?php echo CHtml::link('['.Yii::t('contacts','Hide All').']','javascript:void(0)',array('id'=>'hideAll','class'=>'right','style'=>'text-decoration:none;')); ?>
<h2><?php echo Yii::t('quotes','Quote:'); ?> <b><?php echo $model->name; ?></b>

<?php if($model->locked) { ?>
	<?php if($strict && Yii::app()->user->name != 'admin') { ?>
		<a class="x2-button" href="#" onClick="dialogStrictLock();">Edit</a>
	<?php } else { ?>
		<a class="x2-button" href="#" onClick="dialogLock();">Edit</a>
	<?php } ?>
<?php } else { ?>
	<a class="x2-button" href="update/<?php echo $model->id;?>">Edit</a>
<?php } ?>

</h2>
</div>
<?php
$form = $this->beginWidget('CActiveForm', array(
	'id'=>'quotes-form',
	'enableAjaxValidation'=>false,
	'action'=>array('saveChanges','id'=>$model->id),
));


$this->renderPartial('application.components.views._detailView',array('model'=>$model,'modelName'=>'Quote'));

$productField = Fields::model()->findByAttributes(array('modelName'=>'Quote', 'fieldName'=>'products'));

?>
<div class="x2-layout form-view" style="margin-bottom: 0;">
	<div class="formSection">
		<div class="formSectionHeader">
			<span class="sectionTitle"><?php echo $productField->attributeLabel; ?></span>
		</div>
	</div>
</div>
<div class="form" style="border:1px solid #ccc; border-top: 0; padding: 0; margin-top:-1px; border-radius:0;-webkit-border-radius:0; background:#fafafa;">
<?php
$this->widget('zii.widgets.grid.CGridView', array(
	'id'=>"quote-products-grid",
	'baseScriptUrl'=>Yii::app()->theme->getBaseUrl().'/css/gridview',
	'summaryText'=>'',
	'dataProvider'=>$dataProvider,
	'columns'=>array(
		array(
			'name'=>'name',
			'header'=>Yii::t('products','Line Item'),
			'value'=>'$data["name"]',
			'type'=>'raw',
		),
		array(
			'name'=>'unit',
			'header'=>Yii::t('products','Unit Price'),
			'value'=>'Yii::app()->locale->numberFormatter->formatCurrency($data["unit"],"'.$model->currency.'")',
			'type'=>'raw',
		),
		array(
			'name'=>'quantity',
			'header'=>Yii::t('products','Quantity'),
			'value'=>'$data["quantity"]',
			'type'=>'raw',
		),
		array(
			'name'=>'adjustment',
			'header'=> Yii::t('products', 'Adjustment'),
			'value'=>'$data["adjustment"]',
			'type'=>'raw',
			'footer'=>'<b>Total</b>',
		),
		array(
			'name'=>'price',
			'header'=>Yii::t('products', "Price"),
			'value'=>'Yii::app()->locale->numberFormatter->formatCurrency($data["price"],"'.$model->currency.'")',
			'type'=>'raw',
			'footer'=>'<b>'. Yii::app()->locale->numberFormatter->formatCurrency($total,$model->currency) .'</b>',
		),
	),
));
?>
</div>
<?php
/*
$this->renderPartial('_detailView',
	array(
		'model'=>$model,
		'form'=>$form,
		'currentWorkflow'=>$currentWorkflow,
		'dataProvider'=>$dataProvider,
		'total'=>$total
	)
);
*/
$this->endWidget();

$this->widget('InlineTags', array('model'=>$model, 'modelName'=>'Quote'));
?>

<br /><br />
<?php $this->widget('Attachments',array('associationType'=>'quotes','associationId'=>$model->id,'startHidden'=>true)); ?>
</div>
<div class="history half-width">
<?php

$this->widget('Publisher',
	array(
		'associationType'=>'quotes',
		'associationId'=>$model->id,
		'assignedTo'=>Yii::app()->user->getName(),
		'halfWidth'=>true
	)
);
$this->widget('History',array('associationType'=>'quotes','associationId'=>$model->id));
?>