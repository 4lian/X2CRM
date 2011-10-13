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

class DocChild extends Docs {

	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		// $criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		// $criteria->compare('text',$this->text,true);
		$criteria->compare('createdBy',$this->createdBy,true);
		$criteria->compare('createDate',$this->createDate);
		$criteria->compare('updatedBy',$this->updatedBy,true);
		$criteria->compare('lastUpdated',$this->lastUpdated);
		// $criteria->compare('editPermissions',$this->editPermissions,true);

		$dateRange = Yii::app()->controller->partialDateRange($this->createDate);
		if($dateRange !== false)
			$criteria->addCondition('createDate BETWEEN '.$dateRange[0].' AND '.$dateRange[1]);
			
		$dateRange = Yii::app()->controller->partialDateRange($this->lastUpdated);
		if($dateRange !== false)
			$criteria->addCondition('lastUpdated BETWEEN '.$dateRange[0].' AND '.$dateRange[1]);
		
		return new CActiveDataProvider(get_class($this), array(
			'pagination'=>array(
				'pageSize'=>ProfileChild::getResultsPerPage(),
			),
			'criteria'=>$criteria,
		));
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('docs','ID'),
			'title' => Yii::t('docs','Title'),
			'text' => Yii::t('docs','Text'),
			'createdBy' => Yii::t('docs','Created By'),
			'createDate' => Yii::t('docs','Create Date'),
			'updatedBy' => Yii::t('docs','Updated By'),
			'lastUpdated' => Yii::t('docs','Last Updated'),
			'editPermissions' => Yii::t('docs','Edit Permissions'),
		);
	}
}
?>
