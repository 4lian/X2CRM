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

class AccountsController extends x2base {

	public $modelClass = 'Accounts';

	public function accessRules() {
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index','view','create','update','search','addUser','addContact','removeUser','removeContact',
					'addNote','deleteNote','saveChanges','delete','shareAccount'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','testScalability'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id) {
		$model=$this->loadModel($id);
		
		$model->assignedTo=UserChild::getUserLinks($model->assignedTo);
		
		$str = '';
		$contacts = array_keys(Accounts::getContacts($id));	//Contacts::model()->findAllByAttributes(array('company'=>$model->name));
		// foreach($contacts as $contact){
			// $str.=$contact->id.' ';
		// }
		//$model->associatedContacts=$str;
		
		$model->associatedContacts = Contacts::getContactLinks($contacts);
		
		$type='accounts';
		parent::view($model, $type);
	}
	
	public function actionShareAccount($id){
		
		$model=$this->loadModel($id);
		$body="\n\n\n\n".Yii::t('accounts','Account Record Details')." \n
".Yii::t('accounts','Name').": $model->name
".Yii::t('accounts','Description').": $model->description
".Yii::t('accounts','Revenue').": $model->annualRevenue
".Yii::t('accounts','Phone').": $model->phone
".Yii::t('accounts','Website').": $model->website
".Yii::t('accounts','Type').": $model->type
".Yii::t('app','Link').": ".'http://'.Yii::app()->request->getServerName().$this->createUrl('accounts/view/'.$model->id);
		if(isset($_POST['email']) && isset($_POST['body'])){
			$email=$_POST['email'];
			$body=$_POST['body'];
			
			$count=preg_match("/[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/",$email);
			if($count==0){
				$this->redirect(array('shareAccount','id'=>$model->id));
			}
			
			$subject=Yii::t('accounts',"Account Record").": $model->name";
			

			
			mail($email,$subject,$body);
			$this->redirect(array('view','id'=>$model->id));
		}
		$this->render('shareAccount',array(
			'model'=>$model,
			'body'=>$body,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
        
        public function create($model,$oldAttributes, $api){
            
            $model->annualRevenue = $this->parseCurrency($model->annualRevenue,false);
            $arr=$model->assignedTo;
            if(isset($model->assignedTo) && $model->assignedTo!="")
                $model->assignedTo = Accounts::parseUsers($model->assignedTo);
            $model->createDate=time();
            Accounts::setContacts($model->associatedContacts,$model->id);
            if($api==0)
                parent::create($model,$oldAttributes,$api);
            else
                return parent::create($model,$oldAttributes,$api);
        }
        
	public function actionCreate() {
		$model=new Accounts;
		$users=UserChild::getNames();
		unset($users['admin']);
		unset($users['']);
		$availableContacts = Accounts::getAvailableContacts();

		if(isset($_POST['Accounts'])) {
                        $temp=$model->attributes;
			foreach($model->attributes as $field=>$value){
                            if(isset($_POST['Accounts'][$field])){
                                $model->$field=$_POST['Accounts'][$field];
                            }
                        }
                        $this->create($model,$temp, '0');
		}

		$this->render('create',array(
			'model'=>$model,
			'users'=>$users,
			'contacts'=>$availableContacts,
		));
	}
        
        public function update($model, $oldAttributes,$api){
            // process currency into an INT
            $model->annualRevenue = $this->parseCurrency($model->annualRevenue,false);

            $arr=$model->assignedTo;
            if(isset($model->assignedTo) && $model->assignedTo!="")
                    $model->assignedTo=Accounts::parseUsers($arr);

            if($model->associatedContacts!=null)
                Accounts::setContacts($model->associatedContacts,$model->id);
            if($api==0)
                parent::update($model,$oldAttributes,$api);
            else
                return parent::update($model,$oldAttributes,$api);
        }

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id) {
		$model=$this->loadModel($id);
		$users=UserChild::getNames();
		unset($users['admin']);
		unset($users['']);
		$availableContacts = Accounts::getAvailableContacts($id);
		$selectedContacts = Accounts::getContacts($id);

		$model->associatedContacts = array_keys($selectedContacts);
		
		$curUsers=$model->assignedTo;
		$userPieces=explode(', ',$curUsers);
		$arr=array();
		foreach($userPieces as $piece){
			$arr[]=$piece;
		}
		
		$model->assignedTo=$arr;

		if(isset($_POST['Accounts'])) {
			$temp=$model->attributes;
			foreach($model->attributes as $field=>$value){
                            if(isset($_POST['Accounts'][$field])){
                                $model->$field=$_POST['Accounts'][$field];
                            }
                        }
			
			$this->update($model,$temp,'0');
		}

		$this->render('update',array(
			'model'=>$model,
			'users'=>$users,
			'contacts'=>$availableContacts,
		));
	}
	
	public function actionSaveChanges($id) {
		$account=$this->loadModel($id);
		if(isset($_POST['Accounts'])) {
			$temp=$account->attributes;
			foreach($account->attributes as $field=>$value){
                            if(isset($_POST['Accounts'][$field])){
                                $account->$field=$_POST['Accounts'][$field];
                            }
                        }

			// process currency into an INT
			$account->annualRevenue = $this->parseCurrency($account->annualRevenue,false);
			$changes=$this->calculateChanges($temp,$account->attributes, $account);
			$account=$this->updateChangelog($account,$changes);
			$account->update();
			$this->redirect(array('view','id'=>$account->id));
		}
	}

	public function actionAddUser($id) {
		$users=UserChild::getNames();
		$contacts=Contacts::getAllNames();
		$model=$this->loadModel($id);
		$users=Accounts::editUserArray($users,$model);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Accounts'])) {
			$temp=$model->assignedTo; 
			$tempArr=$model->attributes;
			$model->attributes=$_POST['Accounts'];  
			$arr=$model->assignedTo;

			$model->assignedTo=Accounts::parseUsers($arr);
			if($temp!="")
				$temp.=", ".$model->assignedTo;
			else
				$temp=$model->assignedTo;
			$model->assignedTo=$temp;
			$changes=$this->calculateChanges($tempArr,$model->attributes);
			$model=$this->updateChangelog($model,$changes);
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('addUser',array(
			'model'=>$model,
			'users'=>$users,
			'contacts'=>$contacts,
			'action'=>'Add'
		));
	}

	public function actionRemoveUser($id) {

		$model=$this->loadModel($id);

		$pieces=explode(', ',$model->assignedTo);
		$pieces=Accounts::editUsersInverse($pieces);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Accounts'])) {
			$temp=$model->attributes;
			$model->attributes=$_POST['Accounts'];  
			$arr=$model->assignedTo;

			
			foreach($arr as $id=>$user){
				unset($pieces[$user]);
			}
			
			$temp=Accounts::parseUsersTwo($pieces);

			$model->assignedTo=$temp;
			$changes=$this->calculateChanges($temp,$model->attributes);
			$model=$this->updateChangelog($model,$changes);
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('addUser',array(
			'model'=>$model,
			'users'=>$pieces,
			'action'=>'Remove'
		));
	}
        
        public function delete($id){
            
            $model=$this->loadModel($id);
            $dataProvider=new CActiveDataProvider('Actions', array(
                'criteria'=>array(
                    'condition'=>'associationId='.$id.' AND associationType=\'account\'',
            )));

            $actions=$dataProvider->getData();
            foreach($actions as $action){
                    $action->delete();
            }
            $this->cleanUpTags($model);
            $model->delete();
        }

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id) {
		$model=$this->loadModel($id);
		if(Yii::app()->request->isPostRequest) {
			$dataProvider=new CActiveDataProvider('Actions', array(
				'criteria'=>array(
					'condition'=>'associationId='.$id.' AND associationType=\'account\'',
			)));

			$actions=$dataProvider->getData();
			foreach($actions as $action){
				$action->delete();
			}
                        $this->cleanUpTags($model);
			$model->delete();
		} else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex() {
		
		$model=new Accounts('search');
		$name='Accounts';
		parent::index($model,$name);
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin() {
		$model=new Accounts('search');
		$name='Accounts';
		parent::admin($model,$name);
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id) {
		$model=Accounts::model()->findByPk((int)$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
}