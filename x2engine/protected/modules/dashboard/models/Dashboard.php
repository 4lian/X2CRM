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

/**
 * This is the model class for table "x2_widgets".
 *
 * The followings are the available columns in table 'x2_widgets':
 * @property integer $id(auto-incremented)
 * @property string $name
 * @property integer $showPROFILE
 * @property integer $adminALLOWS
 * @property integer $showDASH
 * @property integer $userID
 * @property integer $position
 */
class Dashboard extends CActiveRecord{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Roles the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'x2_widgets';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name,position', 'required'),
			array('name', 'length', 'max'=>250),
			array('id', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('name,showPROFILE,adminALLOWS,showDASH,userID,position', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
    {
        return array(
            "showProfile"=>"Shown in Profile",
            "adminALLOWS"=>"Admin Allows Display",
            "userID"=>"User ID",
            "posPROF"=>"Position in Profile",
            "posDASH"=>"Position in Dashboard",
            "dispNAME"=>"Display Name",
            "userALLOWS"=>"User Allows Display"
        );
    }
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search($id){
		// Warning: Please modify the following code to remove attributes that
        // should not be searched.
        $uid = Yii::app()->user->getId();
        if ($id == 'dash'){
            if ($uid != 1){
                $sql = "SELECT * FROM x2_widgets WHERE adminALLOWS = 1 AND userID = $uid AND userALLOWS = 1 AND needUSER = 0";
                $command = Yii::app()->db->createCommand($sql);
                $rows = $command->queryAll();
            }else {
                $query = "SELECT * FROM x2_widgets WHERE userID = $uid AND userALLOWS = 1 AND needUSER = 0";
                $command = Yii::app()->db->createCommand($query);
                $rows = $command->queryAll();
            }
            return $rows;
        }else{ 
           $criteria = new CDbCriteria;
           if ($uid != 1) $criteria->compare('adminALLOWS',1,true);
           $criteria->compare('userID',$uid,true);
           $criteria->compare('showPROFILE',1,true);
           if ($id != "prof") $criteria->compare('id',$id,true);
           $data =  new CActiveDataProvider(get_class($this),array(
               'sort'=>array('defaultOrder'=>'posPROF ASC'),
               'pagination'=>array(
                   'pageSize'=>12,
               ),
               'criteria'=>$criteria
           ));
           return $data;
        }
    }

}
