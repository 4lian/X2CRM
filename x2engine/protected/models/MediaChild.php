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

/**
 * This is the model class for table "x2_media".
 *
 * The followings are the available columns in table 'x2_media':
 * @property integer $id
 * @property string $associationType
 * @property integer $associationId
 * @property string $fileName
 * @property string $uploadedBy
 * @property string $createDate
 */
class MediaChild extends Media {

	// Takes actionDescrption formatted as [filename]:[id]
	// Generates description message with link and preview image (optional)
	public static function attachmentActionText($actionDescription,$makeLink = false,$makeImage = false) {
	
		$data = explode(':',$actionDescription);
		if(count($data) == 2 && is_numeric($data[1])) {

			$str = Yii::t('media','File:') . ' ';
			
			$file = Yii::app()->file->set('uploads/'.$data[0]);
			
			if($makeLink && $file->exists)
				$str .= CHtml::link($data[0],array('media/view','id'=>$data[1]));
			else
				$str .= $data[0];
			if (!$file->exists)
				$str .= ' '.Yii::t('media','(deleted)');

			if($makeImage && $file->exists) {	// to render an image, first check file extension

				$file_ext = $file->getExtension();
				$legal_extensions = array('jpg','gif','png','bmp','jpeg','jpe');
				
				if(in_array($file_ext,$legal_extensions))
					$str .= CHtml::image(Yii::app()->request->baseUrl.'/uploads/'.$data[0],'',array('class'=>'attachment-img'));
			}
			
			
			return $str;
			
		} else
			return $actionDescription;
	}


	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('media','ID'),
			'associationType' => Yii::t('media','Association Type'),
			'associationId' => Yii::t('media','Association'),
			'fileName' => Yii::t('media','File Name'),
			'uploadedBy' => Yii::t('media','Uploaded By'),
			'createDate' => Yii::t('media','Create Date'),
		);
	}
}