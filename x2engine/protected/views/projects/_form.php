<?php
/*********************************************************************************
 * X2Engine is a contact management program developed by
 * X2Engine, Inc. Copyright (C) 2011 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2Engine, X2Engine DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. at P.O. Box 66752,
 * Scotts Valley, CA 95067, USA. or at email address contact@X2Engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 ********************************************************************************/
?>
<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'projects-form',
	'enableAjaxValidation'=>false,
)); ?>

	<em><?php echo Yii::t('app','Fields with <span class="required">*</span> are required.'); ?></em><br />

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>30,'maxlength'=>60)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'status'); ?>
		<?php echo $form->dropDownList($model,'status',array('Staging'=>'Staging','In Progress'=>'In Progress','Complete'=>'Complete')); ?>
		<?php echo $form->error($model,'status'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'type'); ?>
		<?php echo $form->textField($model,'type',array('size'=>20,'maxlength'=>20)); ?>
		<?php echo $form->error($model,'type'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'priority'); ?>
		<?php echo $form->dropDownList($model,'priority',array('Low'=>'Low','Medium'=>'Medium','High'=>'High')); ?>
		<?php echo $form->error($model,'priority'); ?>
	</div>

	<div class="row">
                <?php $model->assignedTo="Anyone";?>
		<?php echo $form->labelEx($model,'assignedTo'); ?>
		<?php echo $form->dropDownList($model,'assignedTo',$users,array("multiple"=>"multiple",'size'=>7)); ?>
		<?php echo $form->error($model,'assignedTo'); ?>
	</div>
        
        <div class="row">
                <?php $model->associatedContacts="0";?>
		<?php echo $form->labelEx($model,'associatedContacts'); ?>
		<?php echo $form->dropDownList($model,'associatedContacts',$contacts,array("multiple"=>"multiple", 'size'=>8)); ?>
		<?php echo $form->error($model,'associatedContacts'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'timeframe'); ?>
		<?php echo $form->textField($model,'timeframe',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'timeframe'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'description'); ?>
		<?php echo $form->textArea($model,'description',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'description'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? Yii::t('app','Create'):Yii::t('app','Save')); ?>
	</div>

<?php $this->endWidget(); ?> 

</div><!-- form -->