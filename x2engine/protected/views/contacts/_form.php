<?php
/*********************************************************************************
 * The X2CRM by X2Engine Inc. is free software. It is released under the terms of 
 * the following BSD License.
 * http://www.opensource.org/licenses/BSD-3-Clause
 * 
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95066 USA
 * 
 * Company website: http://www.x2engine.com 
 * Community and support website: http://www.x2community.com 
 * 
 * Copyright � 2011-2012 by X2Engine Inc. www.X2Engine.com
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

$showSocialMedia = Yii::app()->params->profile->showSocialMedia;

Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/x2forms.js');
Yii::app()->clientScript->registerScript('showSocialMedia', "
function showSocialMedia() {
	$('#social-media-1, #social-media-2, #social-media-3').show();
	$('#social-media-toggle').hide();
}
function hideSocialMedia() {
	$('#social-media-1, #social-media-2, #social-media-3').hide();
	$('#social-media-toggle').show();
}
$(function() {
".($showSocialMedia? "showSocialMedia(); });" : "hideSocialMedia(); });"),CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScript('highlightSaveContact',"
$(function(){
	$('#contacts-form input, #contacts-form select, #contacts-form textarea').change(function(){
		$('#save-button, #save-button1, #save-button2').css('background','yellow');
	}
	);
}
);");

if (!isset($isQuickCreate)) {	//check if this form is being recycled in the quickCreate view

	echo '<div class="form no-border">';
	$form=$this->beginWidget('CActiveForm', array(
		'id'=>'contacts-form',
		'enableAjaxValidation'=>false,
	));
	echo '<em>'.Yii::t('app','Fields with <span class="required">*</span> are required.')."</em>\n";
}

$attributeLabels = Contacts::attributeLabels();

$fields=Fields::model()->findAllByAttributes(array('modelName'=>'Contacts'));
$nonCustom=array();
$custom=array();
foreach($fields as $field){
    if($field->custom==0){
        $nonCustom[$field->fieldName]=$field;
    }else{
        $custom[$field->fieldName]=$field;
    }
}
/*
 * 
 * 
 * Hey Matthew, look here!
 * 
 * 
 * 
 * 
 * 
 */

if(isset($editor)){
    if($editor){
        // Put stuff here to make fields disabled and movable.  Also, disable the create button.
    }
}

?>
<?php
echo $form->errorSummary($contactModel);
?>

<table class="details">
	<tr>
		<td class="label"><label for="Contacts_firstName"><?php echo Yii::t('contacts','Name'); ?><span class="required">*</span></label></td>
                
		<td colspan="3" id="firstName">
                <?php if($nonCustom['firstName']->visible==1){ ?>
			<?php
			$default = empty($contactModel->firstName);
			if($default)
				$contactModel->firstName = Yii::t('contacts','First');
			echo $form->textField($contactModel, 'firstName', array(
				'maxlength'=>40,
				'style'=>'width:120px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>1,
			)); ?>
                        <?php } ?>
                        <?php if($nonCustom['lastName']->visible==1){ ?>
			<?php
			$default = empty($contactModel->lastName);
			if($default)
				$contactModel->lastName = Yii::t('contacts','Last');
			echo $form->textField($contactModel,'lastName',array(
				'maxlength'=>40,
				'style'=>'width:140px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>2
			)); ?>
                        <?php } ?>
			</td>
                        <?php if($nonCustom['rating']->visible==1){ ?>
			<td class="label"><?php echo $form->label($contactModel,'rating'); ?></td>
			<td>
			<?php
			$this->widget('CStarRating',array(
				'model'=>$contactModel,
				'attribute'=>'rating',
				//'callback'=>'highlightSave',
				'minRating'=>1, //minimal valuez
				'maxRating'=>5,//max value
				'starCount'=>5, //number of stars
				'cssFile'=>Yii::app()->theme->getBaseUrl().'/css/rating/jquery.rating.css',
			)); ?>
                    </td>
                    <?php } ?>
	</tr>
	<tr>
                
		<td class="label"><?php echo Yii::t('contacts','Position'); ?></td>
		<td id="title" colspan="5">
                <?php if($nonCustom['title']->visible==1){ ?>
			<?php
			if($default)
				$contactModel->title = $contactModel->getAttributeLabel('title');
			echo $form->textField($contactModel,'title',array(
				'size'=>15,
				'maxlength'=>40,
				'style'=>'width:150px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>3,
			)); ?>
                    <?php } ?>
                    <?php if($nonCustom['company']->visible==1){ ?>
			<?php //echo $form->hiddenField($contactModel, 'company');
			if($default)
				$contactModel->company = $contactModel->getAttributeLabel('company');
			$this->widget('zii.widgets.jui.CJuiAutoComplete', array(
			'model'=>$contactModel,
			'attribute'=>'company',
			//'value'=>$contactModel->company,
			'source' => $this->createUrl('contacts/getTerms'),
			'htmlOptions'=>array(
				'size'=>30,
				'maxlength'=>100,
				'style'=>'width:240px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>4,
			),
			'options'=>array(
				'minLength'=>1,
				'select'=>'js:function( event, ui ) {
					//$("#'.CHtml::activeId($contactModel,'accountId').'").val(ui.item.id);
					$(this).val(ui.item.value);
					//$("#'.CHtml::activeId($contactModel,'company').'").val(ui.item.value);
					return false;
				}',
			),
		));
		?>
                <?php } ?>
		</td>
                
	</tr>
	<tr>
                
		<td class="label"><?php echo Yii::t('contacts','Contact'); ?></td>
		<td colspan="5" id="contact">
                    <?php if($nonCustom['phone']->visible==1){ ?>
			<?php
			$default = empty($contactModel->phone);
			if($default)
				$contactModel->phone = $contactModel->getAttributeLabel('phone');
			echo $form->textField($contactModel, 'phone', array(
				'maxlength'=>20,
				'style'=>'width:120px;margin-bottom:4px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>5,
			)); ?>
                        <?php } ?>
                        <?php if($nonCustom['email']->visible==1){ ?>
			<?php
			$default = empty($contactModel->email);
			if($default)
				$contactModel->email = $contactModel->getAttributeLabel('email');
			echo $form->textField($contactModel, 'email', array(
				'maxlength'=>250,
				'style'=>'width:240px;margin-bottom:4px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>6,
			)); ?>
			<br />
                        <?php } ?>
                        <?php if($nonCustom['phone2']->visible==1){ ?>
			<?php
			$default = empty($contactModel->phone2);
			if($default)
				$contactModel->phone2 = $contactModel->getAttributeLabel('phone2');
			echo $form->textField($contactModel, 'phone2', array(
				'maxlength'=>20,
				'style'=>'width:120px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>7,
			)); ?>
                        <?php } ?>
                        <?php if($nonCustom['website']->visible==1){ ?>
			<?php
			$default = empty($contactModel->website);
			if($default)
				$contactModel->website = $contactModel->getAttributeLabel('website');
			echo $form->textField($contactModel, 'website', array(
				'maxlength'=>100,
				'style'=>'width:240px;'.($default?'color:#aaa;':''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>8
			)); ?>
                        <?php } ?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php echo $form->labelEx($contactModel,'address'); ?></td>
		<td id="address" colspan="5">
                    <?php if($nonCustom['address']->visible==1){ ?>
			<?php
			$default = empty($contactModel->address);
				$contactModel->address = $attributeLabels['address'];
			echo $form->textField($contactModel, 'address', array(
				'maxlength'=>100,
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'style'=>'width:240px;'.($default? 'color:#aaa;' : ''),
				'tabindex'=>9,
			)); ?>
                        <?php } ?>
                        <?php if($nonCustom['city']->visible==1){ ?>
			<?php
			$default = empty($contactModel->city);
			if($default)
				$contactModel->city = $attributeLabels['city'];
			echo $form->textField($contactModel, 'city', array(
				'maxlength'=>40,
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'style'=>'width:120px;'.($default? 'color:#aaa;' : ''),
				'tabindex'=>10,
			)).' '; ?><br />
                        <?php } ?>
                        <?php if($nonCustom['state']->visible==1){ ?>
			<?php
			$default = empty($contactModel->state);
			if($default)
				$contactModel->state = $attributeLabels['state'];
			echo $form->textField($contactModel, 'state', array(
				'maxlength'=>40,
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'style'=>'width:120px;'.($default? 'color:#aaa;' : ''),
				'tabindex'=>11,
			)); ?>
                        <?php } ?>
                        <?php if($nonCustom['zipcode']->visible==1){ ?>
			<?php
			$default = empty($contactModel->zipcode);
			if($default)
				$contactModel->zipcode = $attributeLabels['zipcode'];
			echo $form->textField($contactModel, 'zipcode', array(
				'maxlength'=>20,
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'style'=>'width:80px;'.($default? 'color:#aaa;' : ''),
				'tabindex'=>12,
			)).' ';
			?>
                        <?php } ?>
                        <?php if($nonCustom['country']->visible==1){ ?>
			<?php
			$default = empty($contactModel->country);
			if($default)
				$contactModel->country = $attributeLabels['country'];
			echo $form->textField($contactModel, 'country', array(
				'maxlength'=>100,
				'style'=>'width:115px;'.($default? 'color:#aaa;' : ''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>13,
			)); ?>
                        <?php } ?>
		</td>
	</tr>
	<tr>
		<td class="label"><?php echo $form->labelEx($contactModel,'backgroundInfo'); ?></td>
		<td id="background" colspan="5"><div class="spacer"></div>
                    <?php if($nonCustom['leadSource']->visible==1){ ?>
			<?php
			$default = empty($contactModel->leadSource);
			if($default)
				$contactModel->leadSource = $attributeLabels['leadSource'];
			echo $form->textField($contactModel,'leadSource',array(
				'maxlength'=>100,
				'style'=>'width:200px;margin-bottom:5px;'.($default? 'color:#aaa;' : ''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>14,
				)); ?><br />
                                <?php } ?>
                        <?php if($nonCustom['backgroundInfo']->visible==1){ ?>
			<?php
			$default = empty($contactModel->backgroundInfo);
			if($default)
				$contactModel->backgroundInfo = $contactModel->getAttributeLabel('backgroundInfo');
			echo $form->textArea($contactModel, 'backgroundInfo', array(
				'style'=>'width:440px;height:60px;'.($default? 'color:#aaa;' : ''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				'tabindex'=>15
			)); ?>
                        <?php } ?>
		</td>
	</tr>
	<tr id="social-media-toggle">
		<td class="label"><label><?php echo Yii::t('contacts','Social Media'); ?></label></td>
		<td colspan="5"><a href="#" onclick="showSocialMedia(); return false;"><?php echo Yii::t('app','Show'); ?></a></td>
	</tr>
	<tr id="social-media-1">
                <?php if($nonCustom['skype']->visible==1){ ?>
		<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/skype.png','Skype',array('title'=>'Skype')); ?></td>
		<td>
			<?php
			echo $form->textField($contactModel, 'skype', array(
				'size'=>10,
				'maxlength'=>32,
				'tabindex'=>16,
				'style'=>'width:135px;'
			));?>
		</td>
                <?php } ?>
                <?php if($nonCustom['facebook']->visible==1){ ?>
		<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/facebook.png','Facebook',array('title'=>'Facebook')); ?></td>
		<td colspan="3">
			<?php
			echo $form->textField($contactModel, 'facebook', array(
				'size'=>10,
				'maxlength'=>100,
				'tabindex'=>17,
				'style'=>'width:220px;'
			)); ?>
		</td>
                <?php } ?>
	</tr>
	<tr id="social-media-2">
                <?php if($nonCustom['twitter']->visible==1){ ?>
            	<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/twitter.png','Twitter',array('title'=>'Twitter')); ?></td>
		<td>
			<?php
			echo $form->textField($contactModel, 'twitter', array(
				'size'=>10,
				'maxlength'=>20,
				'tabindex'=>18,
				'style'=>'width:135px;'
			)); ?>
		</td>
                <?php } ?>
                <?php if($nonCustom['googleplus']->visible==1){ ?>
		<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/googleplus.png','Google+',array('title'=>'Google+')); ?></td>
		<td colspan="3">
			<?php
			echo $form->textField($contactModel, 'googleplus', array(
				'size'=>10,
				'maxlength'=>100,
				'tabindex'=>19,
				'style'=>'width:220px;'
			)); ?>
		</td>
                <?php } ?>
	</tr>
	<tr id="social-media-3">
                <?php if($nonCustom['linkedin']->visible==1){ ?>
		<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/linkedin.png','LinkedIn',array('title'=>'LinkedIn')); ?></td>
		<td>
			<?php
			echo $form->textField($contactModel, 'linkedin', array(
				'size'=>10,
				'maxlength'=>100,
				'tabindex'=>20,
				'style'=>'width:135px;'
			));?>
		</td>
                <?php } ?>
                <?php if($nonCustom['otherUrl']->visible==1){ ?>
		<td class="label"><?php echo CHtml::image(Yii::app()->theme->getBaseUrl().'/images/etc/other.png',Yii::t('contacts','Other'),array('title'=>Yii::t('contacts','Other'))); ?></td>
		<td colspan="3">
			<?php
			echo $form->textField($contactModel, 'otherUrl', array(
				'size'=>10,
				'maxlength'=>100,
				'tabindex'=>21,
				'style'=>'width:220px;'
			)); ?><br />
		</td>
                <?php } ?>
	</tr>
	<tr>
                <?php if($nonCustom['assignedTo']->visible==1){ ?>
		<td class="label"><?php echo $form->labelEx($contactModel,'assignedTo'); ?></td>
		<td id="assignedTo">
			
				<?php
				if(empty($contactModel->assignedTo))
					$contactModel->assignedTo = Yii::app()->user->getName();
				echo $form->dropDownList($contactModel,'assignedTo',$users,array('tabindex'=>22)); ?>

		</td>
                <?php } ?>
                <?php if($nonCustom['priority']->visible==1){ ?>
		<td class="label"><?php echo $form->labelEx($contactModel,'priority'); ?></td>
		<td>
			<?php
			if(empty($contactModel->priority))
				$contactModel->priority = 'Medium';
			echo $form->dropDownList($contactModel, 'priority', array(
				'Low'=>Yii::t('contacts','Low'),
				'Medium'=>Yii::t('contacts','Medium'),
				'High'=>Yii::t('contacts','High')
			),array('tabindex'=>23)); ?>
		</td>
                <?php } ?>
                <?php if($nonCustom['visibility']->visible==1){ ?>
		<td class="label"><?php echo $form->label($contactModel,'visibility'); ?></td>
		<td>
			<?php 
			echo $form->dropDownList($contactModel,'visibility',array(
				1=>Yii::t('contacts','Public'),
				0=>Yii::t('contacts','Private')
			),array('tabindex'=>24));
			// $contactModel->createDate = time();
			// echo date("Y-m-d",$contactModel->createDate);
			?>
		</td>
                <?php } ?>
	</tr>
        <?php 
        
            foreach($custom as $fieldName=>$field){
                
                if($field->visible==1){ 
                    ?>
                    <tr>
                    <td class="label"><?php echo $form->label($contactModel,$fieldName); ?></td>
                    <td colspan="5">
                    <?php
			$default = empty($contactModel->$fieldName);
			if($default)
				$contactModel->$fieldName = $attributeLabels[$fieldName];
			echo $form->textField($contactModel,$fieldName,array(
                                'size'=>'82',
				'style'=>($default? 'color:#aaa;' : ''),
				'onfocus'=>$default? 'toggleText(this);' : null,
				'onblur'=>$default? 'toggleText(this);' : null,
				));
                        ?></td>
                    </tr><?php
                        }
                }
        
        ?>
</table>


<?php

if (!isset($isQuickCreate)) {	//if we're not in quickCreate, end the form
echo '	<div class="row buttons">'."\n";
echo '		'.CHtml::submitButton($contactModel->isNewRecord ? Yii::t('app','Create'):Yii::t('app','Save'),array('class'=>'x2-button','id'=>'save-button','tabindex'=>24))."\n";
echo "	</div>\n";

$this->endWidget();
echo "</div>\n";
}
?>






