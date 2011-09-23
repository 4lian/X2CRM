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

$attributeLabels = ActionChild::attributeLabels();

$template="<a href=".$this->createUrl('search/search?term=%23\\2')."> #\\2</a>";
		$info=$model->actionDescription;
		$info=mb_ereg_replace('(^|\s)#(\w\w+)',$template,$info);
if($model->complete=='Yes')
	$status = Yii::t('actions','FINISHED');
else {
	if($model->dueDate > time())
		$status = Yii::t('actions','INCOMPLETE');
	else
		$status = Yii::t('actions','OVERDUE');
}
if($model->type=='note' || $model->type=='attachment') {
?>
<table class="details">
	<tr>
		<td class="label">
			<?php echo $attributeLabels['actionDescription']; ?>
		</td>
		<td colspan="3" class="text-field"><div class="spacer"></div>
			<?php
			if($model->type=='attachment')
				echo MediaChild::attachmentActionText($model->actionDescription,true,true);
			else
				echo $this->convertLineBreaks($info,true);	// convert LF and CRLF to <br />
			?>
		</td>
	</tr>
	<tr>
		<td class="label" width="20%"><?php echo $attributeLabels['completedBy']; ?></td>
		<td width="25%"><?php echo ($model->completedBy=="Email") ? "Email" : UserChild::getUserLinks($model->completedBy) ?></td>
		<td class="label" width="15%"><?php echo $attributeLabels['createDate']; ?></td>
		<td><b><?php echo date('Y-m-d',$model->createDate); ?></b> <?php echo date('g:ia',$model->createDate); ?></td>
	</tr>
</table>

<?php
} else {
?>
<table class="details">
	<tr>
		<td class="label" width="20%">
			<?php echo $attributeLabels['actionDescription']; ?>
		</td>
		<td colspan="3" class="text-field"><div class="spacer"></div>
			<?php echo $this->convertLineBreaks($info); ?>
		</td>
	</tr>
<?php
if ($model->associationType!="none") {
?>
	<tr>
		<td class="label" width="20%">
			<?php echo $attributeLabels['associationName']; ?>
		</td>
		<td colspan="3">
			<?php echo CHtml::link($model->associationName,array("./".$model->associationType."/view","id"=>$model->associationId)); ?>
		</td> 
	</tr>
	<tr>
<?php } ?>
		<td class="label"><?php echo $attributeLabels['assignedTo']; ?></td>
		<td><?php echo ($model->assignedTo=='Anyone')? $model->assignedTo : UserChild::getUserLinks($model->assignedTo); ?></td>
		<td class="label" width="20%"><?php echo $attributeLabels['dueDate']; ?>
		<td><b><?php echo date('Y-m-d',$model->dueDate); ?></b> <?php echo date('g:ia',$model->dueDate); ?></td>
	</tr>
	<tr>
		<td class="label"><?php echo $attributeLabels['priority']; ?></td>
		<td><b><?php echo Yii::t('actions',$model->priority); ?></b></td>
		<td class="label"><?php echo $attributeLabels['createDate']; ?></td>
		<td><b><?php echo date('Y-m-d',$model->createDate); ?></b> <?php echo date('g:ia',$model->createDate); ?></b></td>
	</tr>
	<tr>
		<td class="label"><?php echo Yii::t('actions','Status'); ?></td>
		<td><b><?php echo $status; ?></b></td>
		<td class="label"><?php echo $attributeLabels['lastUpdated']; ?></td>
		<td><b><?php echo date('Y-m-d',$model->lastUpdated); ?></b> <?php echo date('g:ia',$model->lastUpdated); ?></b></td>
	</tr>
</table>
<?php } ?>