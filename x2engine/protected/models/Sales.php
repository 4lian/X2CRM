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
 * Copyright © 2011-2012 by X2Engine Inc. www.X2Engine.com
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
 * This is the model class for table "x2_sales".
 *
 * The followings are the available columns in table 'x2_sales':
 * @property integer $id
 * @property string $name
 * @property string $accountName
 * @property integer $accountId
 * @property integer $quoteAmount
 * @property string $salesStage
 * @property string $expectedCloseDate
 * @property integer $probability
 * @property string $leadSource
 * @property string $description
 * @property string $assignedTo
 * @property integer $createDate
 * @property string $associatedContacts
 * @property integer $lastUpdated
 * @property string $updatedBy
 */
class Sales extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Sales the static model class
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
		return 'x2_sales';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
            
                $fields=Fields::model()->findAllByAttributes(array('modelName'=>get_class($this)));
                $arr=array(
                    'varchar'=>array(),
                    'text'=>array(),
                    'date'=>array(),
                    'dropdown'=>array(),
                    'int'=>array(),
                    'email'=>array(),
                    'currency'=>array(),
                    'url'=>array(),
                    'float'=>array(),
                    'boolean'=>array(),
                    'required'=>array(),
                    
                );
                $rules=array();
                foreach($fields as $field){
			$arr[$field->type][]=$field->fieldName;
			if($field->required)
				$arr['required'][]=$field->fieldName;
                        if($field->type!='date')
                            $arr['search'][]=$field->fieldName;
		}
                $arr['search'][]='name';
		foreach($arr as $key=>$array){
			switch($key){
				case 'email':
					$rules[]=array(implode(',',$array),$key);
					break;
				case 'required':
					$rules[]=array(implode(',',$array),$key);
					break;
                                case 'search':
                                        $rules[]=array(implode(",",$array),'safe','on'=>'search');
                                        break;
				case 'int':
					$rules[]=array(implode(',',$array),'numerical','integerOnly'=>true);
					break;
				case 'float':
					$rules[]=array(implode(',',$array),'type','type'=>'float');
					break;
				case 'boolean':
					$rules[]=array(implode(',',$array),$key);
					break;
				default:
					break;
				
			}
			
		}  
                return $rules;
		/*return array(
			array('name', 'required'),
			array('accountId, quoteAmount, probability, createDate, lastUpdated', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>40),
			array('accountName', 'length', 'max'=>100),
			array('salesStage, expectedCloseDate, updatedBy', 'length', 'max'=>20),
			array('leadSource', 'length', 'max'=>100),
			array('description, assignedTo, associatedContacts', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, accountName, accountId, quoteAmount, salesStage, expectedCloseDate, probability, leadSource, description, assignedTo, createDate, associatedContacts, lastUpdated, updatedBy', 'safe', 'on'=>'search'),
		);*/
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
	
	public function attributeLabels() {
		$fields=Fields::model()->findAllByAttributes(array('modelName'=>'Sales'));
                $arr=array();
                foreach($fields as $field){
                    $arr[$field->fieldName]=Yii::t('actions',$field->attributeLabel);
                }
                
                return $arr;
	}

	public static function getNames() {
		$arr=Sales::model()->findAll();
		$names=array(0=>"None");
		foreach($arr as $sale){
			$names[$sale->id]=$sale->name;
		}
		return $names;
	}

	public static function parseUsers($userArray){
		return implode(', ',$userArray);
	}

	public static function parseUsersTwo($arr){
		$str="";
		foreach($arr as $user=>$name){
			$str.=$user.", ";
		}
		$str=substr($str,0,strlen($str)-2);
						
		return $str;
	}

	public static function parseContacts($contactArray){
		return implode(' ',$contactArray);
	}

	public static function parseContactsTwo($arr){
		$str="";
		foreach($arr as $id=>$contact){
			$str.=$id." ";
		}
		return $str;
	}

	public static function getSalesLinks($accountId) {

		$salesList = CActiveRecord::model('Sales')->findAllByAttributes(array('accountName'=>$accountId));
		// $salesList = $this->model()->findAllByAttributes(array('accountId'),'=',array($accountId));
		
		$links = array();
		foreach($salesList as $model) {
			$links[] = CHtml::link($model->name,array('sales/view','id'=>$model->id));
		}
		return implode(', ',$links);
	}

	public static function editContactArray($arr, $model) {

		$pieces=explode(" ",$model->associatedContacts);
		unset($arr[0]);

		foreach($pieces as $contact){
			if(array_key_exists($contact,$arr)){
				unset($arr[$contact]);
			}
		}
		
		return $arr;
	}

	public static function editUserArray($arr, $model) {

		$pieces=explode(', ',$model->assignedTo);
		unset($arr['Anyone']);
		unset($arr['admin']);
		foreach($pieces as $user){
			if(array_key_exists($user,$arr)){
				unset($arr[$user]);
			}
		}
		return $arr;
	}

	public static function editUsersInverse($arr) {
		
		$data=array();
		
		foreach($arr as $username){
			if($username!='' && !is_numeric($username))
				$data[]=UserChild::model()->findByAttributes(array('username'=>$username));
                        elseif(is_numeric($username))
                            $data[]=Groups::model()->findByPK($username);
		}
		
		$temp=array();
		if(isset($data)){
			foreach($data as $item){
				if(isset($item)){
                                    if($item instanceof Users)
					$temp[$item->username]=$item->firstName.' '.$item->lastName;
                                    else
                                        $temp[$item->id]=$item->name;
                                }
			}
		}
		return $temp;
	}

	public static function editContactsInverse($arr) {
		$data=array();
		
		foreach($arr as $id){
			if($id!='')
				$data[]=Contacts::model()->findByPk($id);
		}
		$temp=array();
		
		foreach($data as $item){
			$temp[$item->id]=$item->firstName.' '.$item->lastName;
		}
		return $temp;
	}

	public function behaviors() {
		return array(
			'ERememberFiltersBehavior' => array(
				'class' => 'application.components.ERememberFiltersBehavior',
				'defaults'=>array(),           /* optional line */
				'defaultStickOnClear'=>false   /* optional line */
			),
		);
	}

	public function search() {
		$criteria=new CDbCriteria;
		$parameters=array("condition"=>"salesStage='Working'",'limit'=>ceil(ProfileChild::getResultsPerPage()));
		$criteria->scopes=array('findAll'=>array($parameters));

		return $this->searchBase($criteria);
	}

	public function searchAdmin() {
		$criteria=new CDbCriteria;

		return $this->searchBase($criteria);
	}
	
	private function searchBase($criteria) {
		
            $fields=Fields::model()->findAllByAttributes(array('modelName'=>'Sales'));
                foreach($fields as $field){
                    $fieldName=$field->fieldName;
                    switch($field->type){
                        case 'boolean':
                            $criteria->compare($field->fieldName,$this->compareBoolean($this->$fieldName), true);
                            break;
                        case 'link':
                            $criteria->compare($field->fieldName,$this->compareLookup($field, $this->$fieldName), true);
                            break;
                        case 'assignment':
                            $criteria->compare($field->fieldName,$this->compareAssignment($this->$fieldName), true);
                            break;
                        default:
                            $criteria->compare($field->fieldName,$this->$fieldName,true);
                    }
                    
                }
		
		return new SmartDataProvider(get_class($this), array(
			'sort'=>array(
				'defaultOrder'=>'createDate ASC',
			),
			'pagination'=>array(
				'pageSize'=>ProfileChild::getResultsPerPage(),
			),
			'criteria'=>$criteria,
		));
	}
        
        private function compareLookup($field, $data){
            if(is_null($data) || $data=="") return null; 
            $type=ucfirst($field->linkType);
            if($type=='Contacts'){
                eval("\$lookupModel=$type::model()->findAllBySql('SELECT * FROM x2_$field->linkType WHERE CONCAT(firstName,\' \', lastName) LIKE \'%$data%\'');");
            }else{
                eval("\$lookupModel=$type::model()->findAllBySql('SELECT * FROM x2_$field->linkType WHERE name LIKE \'%$data%\'');");
            }
            if(isset($lookupModel) && count($lookupModel)>0){
                $arr=array();
                foreach($lookupModel as $model){
                    $arr[]=$model->id;
                }
                return $arr;
            }else
                return -1;
        }
        
        private function compareBoolean($data){
            if(is_null($data) || $data=='') return null;
            if(is_numeric($data)) return $data;
            if($data==Yii::t('actions',"Yes"))
                return 1;
            elseif($data==Yii::t('actions',"No"))
                return 0;
            else
                return -1;
        }
        
        private function compareAssignment($data){
            if(is_null($data)) return null;
            if(is_numeric($data)){
                $models=Groups::model()->findAllBySql("SELECT * FROM x2_groups WHERE name LIKE '%$data%'");
                $arr=array();
                foreach($models as $model){
                    $arr[]=$model->id;
                }
                return count($arr)>0?$arr:-1;
            }else{
                $models=UserChild::model()->findAllBySql("SELECT * FROM x2_users WHERE CONCAT(firstName,' ',lastName) LIKE '%$data%'");
                $arr=array();
                foreach($models as $model){
                    $arr[]=$model->username;
                }
                return count($arr)>0?$arr:-1;
            }
        }
}