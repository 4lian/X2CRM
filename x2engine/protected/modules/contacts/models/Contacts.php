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

Yii::import('application.models.X2Model');

/**
 * This is the model class for table "x2_contacts".
 * 
 * @package X2CRM.modules.contacts.models
 */
class Contacts extends X2Model {

	public $name;

	/**
	 * Returns the static model of the specified AR class.
	 * @return Contacts the static model class
	 */
	public static function model($className=__CLASS__) { return parent::model($className); }

	/**
	 * @return string the associated database table name
	 */
	public function tableName() { return 'x2_contacts'; }
	
	public function behaviors() {
		return array_merge(parent::behaviors(),array(
			'X2LinkableBehavior'=>array(
				'class'=>'X2LinkableBehavior',
				'baseRoute'=>'/contacts'
			),
			'ERememberFiltersBehavior' => array(
				'class'=>'application.components.ERememberFiltersBehavior',
				'defaults'=>array(),
				'defaultStickOnClear'=>false
			)
		));
	}

	/**
	 * Sets the name field (full name) on record lookup
	 */
	public function afterFind() {
		parent::afterFind();
		
		if(isset(Yii::app()->params->admin)) {
			$admin=Yii::app()->params->admin;
			if(!empty($admin->contactNameFormat)) {
				$str = $admin->contactNameFormat;
				$str = str_replace('firstName',$this->firstName,$str);
				$str = str_replace('lastName',$this->lastName,$str);
			} else {
				$str = $this->firstName.' '.$this->lastName;
			}
			if($admin->properCaseNames)
				$str = $this->ucwords_specific($str,array('-',"'",'.'),'UTF-8');
			
			$this->name = $str;
		}
	}

	/**
	 * Sets the name field (full name) before saving
	 * Return boolean whether or not to save
	 */
	public function beforeSave() {
		if(isset(Yii::app()->params->admin)) {
			$admin = Yii::app()->params->admin;
			if(!empty($admin->contactNameFormat)) {
				$str = $admin->contactNameFormat;
				$str = str_replace('firstName',$this->firstName,$str);
				$str = str_replace('lastName',$this->lastName,$str);
			} else {
				$str = $this->firstName.' '.$this->lastName;
			}
			if($admin->properCaseNames)
				$str = $this->ucwords_specific($str,array('-',"'",'.'),'UTF-8');
			
			$this->name = $str;
		}
		
		return parent::beforeSave();
	}

	/**
	 * Returns full human-readable address, using all available address fields
	 */
	public function getCityAddress() {
		$address = '';
		if(!empty($this->address)){
			$address.=$this->address." ";
		}
		if(!empty($this->city))
			$address .= $this->city . ', ';
		
		if(!empty($this->state))
			$address .= $this->state . ' ';
		
		if(!empty($this->zipcode))
			$address .= $this->zipcode . ' ';
			
		if(!empty($this->country))
			$address .= $this->country;
			
		return $address;
	}
	
	public static function getNames() {
	
		$criteria = $this->getAccessCriteria();
	
        // $condition = 'visibility="1" OR assignedTo="Anyone"  OR assignedTo="'.Yii::app()->user->getName().'"';
		// /* x2temp */
		// $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
		// if(!empty($groupLinks))
			// $condition .= ' OR assignedTo IN ('.implode(',',$groupLinks).')';

		// $condition .= 'OR (visibility=2 AND assignedTo IN 
			// (SELECT username FROM x2_group_to_user WHERE groupId IN
				// (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().')))';
		$contactArray = X2Model::model('Contacts')->findAll($condition);
		$names=array(0=>'None');
		foreach($contactArray as $user){
			$first = $user->firstName;
			$last = $user->lastName;
			$name = $first . ' ' . $last;
			$names[$user->id]=$name;
		}
		return $names;
	}

	/**
	 *	Returns all public contacts.
	 *	@return $names An array of strings containing the names of contacts.
	 */
	public static function getAllNames() {
		$contactArray = X2Model::model('Contacts')->findAll($condition='visibility=1');
		$names=array(0=>'None');
		foreach($contactArray as $user){
			$first = $user->firstName;
			$last = $user->lastName;
			$name = $first . ' ' . $last;
			$names[$user->id]=$name;
		}
		return $names;
	}

	public static function getContactLinks($contacts) {
		if(!is_array($contacts))
			$contacts = explode(' ',$contacts);
		
		$links = array();
		foreach($contacts as &$id){
			if($id !=0 ) {
				$model = X2Model::model('Contacts')->findByPk($id);
				$links[] = CHtml::link($model->name,array('/contacts/contacts/view','id'=>$id));
				//$links.=$link.', ';
				
			}
		}
		//$links=substr($links,0,strlen($links)-2);
		return implode(', ',$links);
	}
	
	public static function getMailingList($criteria) {
		
		$mailingList=array();
		
		$arr=X2Model::model('Contacts')->findAll();
		foreach($arr as $contact){
			$i=preg_match("/$criteria/i",$contact->backgroundInfo);
			if($i>=1){
				$mailingList[]=$contact->email;
			}
		}
		return $mailingList;
	}
	
	public function searchAll() {
		$criteria = new CDbCriteria;
		// $condition = 'visibility="1" OR assignedTo="Anyone" OR assignedTo="'.Yii::app()->user->getName().'"';
		// $parameters = array('limit'=>ceil(ProfileChild::getResultsPerPage()));

		// $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
		// if(!empty($groupLinks))
			// $condition .= ' OR assignedTo IN ('.implode(',',$groupLinks).')';

		// $condition .= ' OR (visibility=2 AND assignedTo IN 
			// (SELECT username FROM x2_group_to_user WHERE groupId IN
			// (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().')))';

        // if(Yii::app()->user->getName()!='admin' && !Yii::app()->user->checkAccess('AdminIndex'))
            // $parameters['condition']=$condition;
		// $criteria->scopes=array('findAll'=>array($parameters));
				
		if(isset($_GET['tagField']) && !empty($_GET['tagField'])) {	// process the tags filter
			
			$tags = explode(',',preg_replace('/\s?,\s?/',',',trim($_GET['tagField'])));	//remove any spaces around commas, then explode to array
			for($i=0; $i<count($tags); $i++) {
				if(empty($tags[$i])) {
					unset($tags[$i]);
					$i--;
					continue;
				} else {
					if($tags[$i][0] != '#')
						$tags[$i] = '#'.$tags[$i];
					$tags[$i] = 'b.tag = "'.$tags[$i].'"';
				}
			}
			// die($str);
			$tagConditions = implode(' OR ',$tags);
			
			$criteria->distinct = true;
			$criteria->join .= ' RIGHT JOIN x2_tags b ON (b.itemId=t.id AND b.type="Contacts" AND ('.$tagConditions.'))';
            $criteria->condition='t.id IS NOT NULL';
            $criteria->order='b.timestamp DESC';
		}
		return $this->searchBase($criteria);
	}

	public function searchMyContacts() {
		$criteria = new CDbCriteria;

		$accessLevel = Yii::app()->user->checkAccess('ContactsView')? 1 : 0;
		
		$criteria->addCondition(X2Model::getAccessConditions($accessLevel));

		// $condition = 'assignedTo="'.Yii::app()->user->getName().'"';
		// $parameters=array('limit'=>ceil(ProfileChild::getResultsPerPage()));

		// $parameters['condition']=$condition;
		// $criteria->scopes=array('findAll'=>array($parameters));
		
		return $this->searchBase($criteria);
	}

	public function searchNewContacts() {
		$criteria=new CDbCriteria;
		// $condition = 'assignedTo="'.Yii::app()->user->getName().'" AND createDate > '.mktime(0,0,0);
		$condition = 'createDate > '.mktime(0,0,0);
		$accessLevel = Yii::app()->user->checkAccess('ContactsView')? 1 : 0;
		$criteria->addCondition(X2Model::getAccessConditions($accessLevel));
		
		$parameters=array('limit'=>ceil(ProfileChild::getResultsPerPage()));

		$parameters['condition']=$condition;
		$criteria->scopes=array('findAll'=>array($parameters));
		
		return $this->searchBase($criteria);
	}
	
	
	public function search() {
		$criteria = new CDbCriteria;
		// $condition = 'assignedTo="'.Yii::app()->user->getName().'"';
		// $parameters = array('limit'=>ceil(ProfileChild::getResultsPerPage()));
		/* x2temp */

		// if(Yii::app()->user->checkAccess('AdminIndex'))
			// $accessLevel = 3;
		// elseif(Yii::app()->user->checkAccess('ContactsView'))
			// $accessLevel = 2;
		// elseif(Yii::app()->user->checkAccess('ContactsViewPrivate'))
			// $accessLevel = 1;
			
		// $condition = Yii::app()->user->searchAccessConditions($accessLevel);

		// $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
		// if(!empty($groupLinks))
			// $condition .= ' OR assignedTo IN ('.implode(',',$groupLinks).')';
		/* end x2temp */
		// $parameters['condition'] = $condition;
		// $criteria->scopes=array('findAll'=>array($parameters));
		
		return $this->searchBase($criteria);
	}
	
	public function searchAdmin() {
		$criteria=new CDbCriteria;
		return $this->searchBase($criteria);
	}

	public function searchAccount($id) {
		$criteria = new CDbCriteria;
		$criteria->compare('company',$id);
		
		return $this->searchBase($criteria);
	}

	/**
	 * Returns a DataProvider for all the contacts in the specified list,
	 * using this Contact model's attributes as a search filter
	 */
	public function searchList($id, $pageSize=null) {
		$list = X2List::model()->findByPk($id);

		if(isset($list)) {
			$search = $list->queryCriteria();
				
				
			$this->compareAttributes($search);

			return new SmartDataProvider('Contacts',array(
				'criteria'=>$search,
				'sort'=>array(
					'defaultOrder'=>'lastUpdated DESC'	// true = ASC
				),
				'pagination'=>array(
					'pageSize'=>isset($pageSize)? $pageSize : ProfileChild::getResultsPerPage(),
				),
			));
			
		} else {	//if list is not working, return all contacts
			return $this->searchBase();
		}
	}
	
	/**
	 * Base search method for all data providers.
	 * Sets up record-level security checks.
	 * 
	 * @param CDbCriteria $criteria starting criteria for this search
	 * @return SmartDataProvider data provider using the provided criteria and any conditions added by {@link X2Model::compareAttributes}
	 */
	public function searchBase($criteria=null) {
		if($criteria === null)
			$criteria = $this->getAccessCriteria();
		else
			$criteria->mergeWith($this->getAccessCriteria());

		return parent::searchBase($criteria);
	}
	
	/**
	 * Generates a random tracking key and guarantees uniqueness
	 * @return String $key a unique random tracking key
	 */
	public static function getNewTrackingKey() {
	
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		
		// try up to 100 times to guess a unique key
		for($i=0; $i<100; $i++) {
			$key = '';
			for($j=0; $j<32; $j++)	// generate a random 32 char alphanumeric string
				$key .= substr($chars,rand(0,strlen($chars)-1), 1);
		
			if(X2Model::model('Contacts')->exists('trackingKey="'.$key.'"'))	// check if this key is already used
				continue;
			else
				return $key;
		}
		return null;
	}
    
    function ucwords_specific ($string, $delimiters = '', $encoding = NULL) 
    { 
        
        if ($encoding === NULL) { $encoding = mb_internal_encoding();} 

        if (is_string($delimiters)) 
        { 
            $delimiters =  str_split( str_replace(' ', '', $delimiters)); 
        } 

        $delimiters_pattern1 = array(); 
        $delimiters_replace1 = array(); 
        $delimiters_pattern2 = array(); 
        $delimiters_replace2 = array(); 
        foreach ($delimiters as $delimiter) 
        { 
            $ucDelimiter=$delimiter;
            $delimiter=strtolower($delimiter);
            $uniqid = uniqid(); 
            $delimiters_pattern1[]   = '/'. preg_quote($delimiter) .'/'; 
            $delimiters_replace1[]   = $delimiter.$uniqid.' '; 
            $delimiters_pattern2[]   = '/'. preg_quote($ucDelimiter.$uniqid.' ') .'/'; 
            $delimiters_replace2[]   = $ucDelimiter; 
            $delimiters_cleanup_replace1[]   = '/'. preg_quote($delimiter.$uniqid).' ' .'/'; 
            $delimiters_cleanup_pattern1[]   = $delimiter; 
        } 
        $return_string = mb_strtolower($string, $encoding); 
        //$return_string = $string; 
        $return_string = preg_replace($delimiters_pattern1, $delimiters_replace1, $return_string);

        $words = explode(' ', $return_string); 
        
        foreach ($words as $index => $word) 
        { 
            $words[$index] = mb_strtoupper(mb_substr($word, 0, 1, $encoding), $encoding).mb_substr($word, 1, mb_strlen($word, $encoding), $encoding); 
        } 
        $return_string = implode(' ', $words); 
        
        $return_string = preg_replace($delimiters_pattern2, $delimiters_replace2, $return_string);
        $return_string = preg_replace($delimiters_cleanup_replace1, $delimiters_cleanup_pattern1, $return_string);

        return $return_string; 
    }
}
