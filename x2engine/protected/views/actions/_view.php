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

Yii::app()->clientScript->registerScript('deleteActionJs',"
function deleteAction(actionId) {

	if(confirm('".Yii::t('app','Are you sure you want to delete this item?')."')) {
		$.ajax({
			url: '" . CHtml::normalizeUrl(array('actions/delete')) . "/'+actionId+'?ajax=1',
			type: 'POST',
			//data: 'id='+actionId,
			success: function(response) {
				if(response=='Success')
					$('#history-'+actionId).fadeOut(200,function() { $('#history-'+actionId).remove(); });
				}
		});
	}
}
",CClientScript::POS_HEAD);






?>



<div class="view" id="history-<?php echo $data->id; ?>">
	<!--<div class="deleteButton">
		<?php //echo CHtml::link('[x]',array('deleteNote','id'=>$data->id)); //,array('class'=>'x2-button') ?>
	</div>-->
	<div class="header">
		<?php
		if(empty($data->type)) {
			if ($data->complete=='Yes') {
				echo CHtml::link(Yii::t('actions','Action').':',array('actions/view','id'=>$data->id)).' ';
				echo Yii::t('actions','Completed {date}',array('{date}'=>ActionChild::formatDate($data->completeDate)));
			} else {
				echo '<b>'.CHtml::link(Yii::t('actions','Action').':',array('actions/view','id'=>$data->id)).' ';
				echo ActionChild::parseStatus($data->dueDate).'</b>';
			}
				
		} else if ($data->type == 'attachment') {
			if($data->completedBy=='Email')
				echo Yii::t('actions','Email Message:').' '.ActionChild::formatDate($data->completeDate);
			else
				echo Yii::t('actions','Attachment:').' '.ActionChild::formatDate($data->completeDate);
				//UserChild::getUserLinks($data->completedBy);
				
			echo ' ';
			
			//if ($data->complete=='Yes')
				//echo ActionChild::formatDate($data->completeDate);
			//else
				//echo ActionChild::parseStatus($data->dueDate);
		} else if ($data->type == 'workflow') {
			$actionData = explode(':',$data->actionDescription);
			
			$workflowRecord = CActiveRecord::model('Workflow')->findByPk($actionData[0]);
			$stageRecords = CActiveRecord::model('WorkflowStage')->findAllByAttributes(array('workflowId'=>$actionData[0]),new CDbCriteria(array('order'=>'id ASC')));
			
			echo Yii::t('workflow','Workflow:').'<b> '.$workflowRecord->name .'/'.$stageRecords[$actionData[1]-1]->name.'</b> ';
		}
		?>
		<div class="buttons">
			<?php
			if (empty($data->type)) {
				if ($data->complete=='Yes')
					echo CHtml::link('['.Yii::t('actions','Uncomplete').']',array('actions/uncomplete','id'=>$data->id,'redirect'=>1),array());
				else {
					echo CHtml::link('['.Yii::t('actions','Complete').']',array('actions/complete','id'=>$data->id,'redirect'=>1),array());
				}
				
				echo ' '.CHtml::link('['.Yii::t('actions','Update').']',array('actions/update','id'=>$data->id,'redirect'=>1),array()) . ' ';
			}
			if ($data->type != 'workflow')
				echo ' '.CHtml::link('[x]','#',array('onclick'=>'deleteAction('.$data->id.'); return false'));
			?>
		</div>
	</div>
	<div class="description">
		<?php
		if($data->type=='attachment' && $data->completedBy!='Email')
			echo MediaChild::attachmentActionText($this->convertUrls($data->actionDescription),true,true);
		else if($data->type=='workflow') {
		
			if(count($actionData) == 2) {
				if(count($workflowRecord) > 0 && $actionData[1] <= count($stageRecords)) {
					
					if($data->complete == 'Yes')
						echo ' <b>'.Yii::t('workflow','Completed').'</b> '.date('Y-m-d',$data->completeDate);
					else
						echo ' <b>'.Yii::t('workflow','Started').'</b> '.date('Y-m-d',$data->createDate);
				}
			}
		} else
			echo $this->convertUrls($data->actionDescription);	// convert LF and CRLF to <br />
		?>
	</div>
	<div class="footer">
	<?php if(empty($data->type) || $data->type=='workflow') {
		if ($data->complete == 'Yes') {
			echo Yii::t('actions','Completed by {name}',array('{name}'=>UserChild::getUserLinks($data->completedBy)));
		} else {
			$userLink = UserChild::getUserLinks($data->assignedTo);
			$userLink = empty($userLink)? Yii::t('actions','Anyone') : $userLink;
			echo Yii::t('actions','Assigned to {name}',array('{name}'=>$userLink));
		}
	} else if ($data->type == 'note') {
		echo UserChild::getUserLinks($data->completedBy);
		echo ' '.ActionChild::formatDate($data->completeDate);
	} else if ($data->type == 'attachment' && $data->completedBy!='Email') {
		echo Yii::t('media','Uploaded by {name}',array('{name}'=>UserChild::getUserLinks($data->completedBy)));
	}
	?>
	</div>

</div>