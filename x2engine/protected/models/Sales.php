<?php

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
		return array(
			array('name', 'required'),
			array('accountId, quoteAmount, probability, createDate, lastUpdated', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>40),
			array('accountName', 'length', 'max'=>100),
			array('salesStage, expectedCloseDate, updatedBy', 'length', 'max'=>20),
			array('leadSource', 'length', 'max'=>10),
			array('description, assignedTo, associatedContacts', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, accountName, accountId, quoteAmount, salesStage, expectedCloseDate, probability, leadSource, description, assignedTo, createDate, associatedContacts, lastUpdated, updatedBy', 'safe', 'on'=>'search'),
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

		$salesList = CActiveRecord::model('Sales')->findAllByAttributes(array('accountId'=>$accountId));
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
			if($username!='')
				$data[]=UserChild::model()->findByAttributes(array('username'=>$username));
		}
		
		$temp=array();
		if(isset($data)){
			foreach($data as $item){
				if(isset($item))
					$temp[$item->username]=$item->firstName.' '.$item->lastName;
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
		// $criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('accountName',$this->accountName,true);
		$criteria->compare('accountId',$this->accountId);
		$criteria->compare('quoteAmount',$this->quoteAmount);
		$criteria->compare('salesStage',$this->salesStage,true);
		// $criteria->compare('expectedCloseDate',$this->expectedCloseDate,true);
		$criteria->compare('probability',$this->probability);
		$criteria->compare('leadSource',$this->leadSource,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('assignedTo',$this->assignedTo,true);
		// $criteria->compare('createDate',$this->createDate);
		$criteria->compare('associatedContacts',$this->associatedContacts,true);
		// $criteria->compare('lastUpdated',$this->lastUpdated);
		$criteria->compare('updatedBy',$this->updatedBy,true);

		$dateRange = Yii::app()->controller->partialDateRange($this->expectedCloseDate);
		if($dateRange !== false)
			$criteria->addCondition('expectedCloseDate BETWEEN '.$dateRange[0].' AND '.$dateRange[1]);
		
		$dateRange = Yii::app()->controller->partialDateRange($this->createDate);
		if($dateRange !== false)
			$criteria->addCondition('createDate BETWEEN '.$dateRange[0].' AND '.$dateRange[1]);
			
		$dateRange = Yii::app()->controller->partialDateRange($this->lastUpdated);
		if($dateRange !== false)
			$criteria->addCondition('lastUpdated BETWEEN '.$dateRange[0].' AND '.$dateRange[1]);
		
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
}