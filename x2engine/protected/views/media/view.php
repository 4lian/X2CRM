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
$this->menu=array(
	array('label'=>Yii::t('media','View Media')),
	array('label'=>Yii::t('media','Delete Media'), 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>Yii::t('media','Are you sure you want to delete this item?'))),
);
?>

<h2><?php echo Yii::t('media','View Attachment'); ?></h2>
<?php 

if($model->associationType!='feed') {
	// $list = $this->parseType($model->associationType);
	// $contact=$list[$model->associationId];
	$association = X2Model::getAssociationModel($model->associationType,$model->associationId);
} else
	$association = null;
	
$this->renderPartial('_detailView',array('model'=>$model,'association'=>$association));
/*
$this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'baseScriptUrl'=>Yii::app()->request->baseUrl.'/themes/'.Yii::app()->theme->name.'/css/detailview',
	'attributes'=>array(
		'fileName',
		'uploadedBy',
		array(
			'name'=>'createDate',
			'type'=>'raw',
			'value'=>date('Y-m-d g:i a',$model->createDate),
		),	
		array(
			'label'=>ucwords($model->associationType),
			'type'=>'raw',
			'value'=>$model->associationType!='feed' ? CHtml::link($association->name,array($model->associationType.'/view','id'=>$model->associationId)) : CHtml::link(Yii::t('social','Feed Post'),array('profile/'.$model->associationId)),
		),	
	),
)); */ ?>