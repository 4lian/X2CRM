<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/


Yii::import('application.components.X2LinkableBehavior');
Yii::import('application.components.X2ChangeLogBehavior');
Yii::import('application.components.x2flow.X2FlowTriggerBehavior');
Yii::import('application.components.X2TimestampBehavior');
Yii::import('application.components.TagBehavior');

Yii::import('application.modules.users.models.*');
Yii::import('application.models.X2Flow');

/**
 * General model class that uses dynamic fields
 *
 * @property array $fieldPermissions Associative array of field names to
 *  permissions: 0 for no access, 1 for read access, and 2 for read/write
 * @property string $myModelName (read-only) Model name of the instance.
 * @property array $relatedX2Models (read-only) Models associated via the
 *  associations table
 * @property array $readableAttributeNames (read-only) Names of attributes that
 *  can be accessed, per the field-level security settings, by the current user.
 * @property boolean $isExemptFromFieldLevelPermissions True if the user is
 *  admin or has no roles (in which case field-level permissions do not apply)
 * @package application.models
 */
abstract class X2Model extends CActiveRecord {

    /**
     * @var true if this model can have workflows associated with it, false otherwise 
     */
    public $supportsWorkflow = true;

    /**
     * @var true if this model can be related to other models via a Relationships model
     */
    public $supportsRelationships = true;

    protected $_oldAttributes = array();

    /**
     * A flag for disabling the automatic setting of fields in events like find,
     * update, validate (etc) to reduce overhead during queries.
     * @var type
     */
    public static $autoPopulateFields = true;


    /**
     * List of mapping between module names/associationType values and model class names
     */
    public static $associationModels = array(
        'media' => 'Media',
        'actions' => 'Actions',
        'calendar' => 'X2Calendar',
        'contacts' => 'Contacts',
        'accounts' => 'Accounts',
        'product' => 'Product',
        'products' => 'Product',
        'Campaign' => 'Campaign',
        'x2Leads' => 'X2Leads',
        'marketing' => 'Campaign',
        'quote' => 'Quote',
        'quotes' => 'Quote',
        'opportunities' => 'Opportunity',
        'social' => 'Social',
        'services' => 'Services',
        
        '' => ''
    );

    /**
     * 1-1 mapping between model names and the names of the modules they belong to  
     */
    public static $modelNameToModuleName = array (
        'Accounts' => 'Accounts',
        'Actions' => 'Actions',
        'Campaign' => 'Marketing',
        'Contacts' => 'Contacts',
        'Groups' => 'Groups',
        'Product' => 'Products',
        'Media' => 'Media',
        'Opportunity' => 'Opportunities',
        'Quote' => 'Quotes',
        'Services' => 'Services',
        'User' => 'Users',
        'WebForm' => 'Marketing',
        'Workflow' => 'Workflow',
        'X2Calendar' => 'Calendar',
        'X2Leads' => 'X2Leads',
    );

    public static $modelTitles = array (
        'X2Leads' => 'Leads',
        'X2List' => 'Contact Lists',
        'BugReports' => 'Bug Reports',
        'Accounts' => 'Accounts',
        'Actions' => 'Actions',
        'Campaign' => 'Campaigns',
        'Contacts' => 'Contacts',
        'Groups' => 'Groups',
        'Product' => 'Products',
        'Media' => 'Media',
        'Opportunity' => 'Opportunities',
        'Quote' => 'Quotes',
        'Services' => 'Services',
        'User' => 'Users',
        'WebForm' => 'Web Forms',
        'Workflow' => 'Processes',
        'X2Calendar' => 'Calendars',
    );

    public static $translatedModelTitles = array();

    protected static $_editableFieldNames = array();

    /**
     * Stores one copy of fields for all instances of this model
     * @var type
     */
    protected static $_fields;

    /**
     * Stores, for the current user, the permissions of the fields (1 for read,
     * 2 for read/write, 0 for no access)
     * 
     * @var type
     */
    protected static $_fieldPermissions = array();
    
    /**
     * Stores possible references to models via lookup fields. The structure of
     * this array is:
     *
     * 1st level (array):
     * [model class key] => [array value]
     *
     * 2nd level (array):
     * [table name key] => [array value]
     *
     * So for each model name, there is an array of corresponding tables (and
     * for each table, a list of columns) that need to be updated if the nameId
     * attribute changes.
     * @var type
     */
    protected static $_nameIdRefs; 

    // cache for models loaded for link field attributes (used by automation system)
    protected static $_linkedModels; 

    private static $_modelNames;

    /**
     * Mapping from model name to record name of module associated with that model
     */
    private static $recordNames = array (
        'Actions' => 'action',
        'Contacts' => 'contact',
        'Accounts' => 'account',
        'Product' => 'product',
        'Campaign' => 'campaign',
        'Quote' => 'quote',
        'Opportunity' => 'opportunity',
        'Services' => 'case',
        'Groups' => 'group',
        'Docs' => 'doc',
        'X2List' => 'list item',
    );

    protected $_runAfterCreate;   // run afterCreate before afterSave, but only for new records
    private $_relatedX2Models;   // Relationship models stored in here

    /**
     * Initialize the model.
     *
     * Calls {@link queryFields()} before CActiveRecord::__constructo() is
     * called, and populates the model with default values, if any.
     */
    public function __construct($scenario = 'insert'){
        $this->queryFields();
        parent::__construct($scenario);
        if($this->getIsNewRecord() && $scenario == 'insert') {
            foreach($this->getFields() as $field) {
                if($field->defaultValue != null && !$field->readOnly) {
                    $this->{$field->fieldName} = $field->defaultValue;
                }
            }
        }
    }

    public static function model($className = 'CActiveRecord'){
        $modelName = self::getModelName($className);
        if(class_exists($modelName)){
            return parent::model($modelName);
        }else{
            throw new CHttpException(500, 'Class: '.$className." not found.");
        }
    }

    /**
     * Runs specified function without the specified behavior
     * @param string $behaviorName 
     * @param function $fn 
     */
    public function runWithoutBehavior ($behaviorName, $fn) {
        $this->disableBehavior ($behaviorName); 
        $fn ();
        $this->enableBehavior ($behaviorName); 
    }

    /**
     * Returns name of records associated with model type or $type if none could be found
     * @param string $type class name of subclass of X2Model
     * @param bool $plural if true, the record name will be pluralized
     * @return string 
     */
    public static function getRecordName ($type, $plural=false) {
        if (isset (self::$recordNames[$type])) {
            $recordName = self::$recordNames[$type];
            if ($plural) {
                if (preg_match ("/y$/", $recordName)) {
                    $recordName = preg_replace ("/y$/", 'ies', $recordName);
                } else {
                    $recordName .= 's';
                }
            }
            return self::$recordNames[$type];
        } else {
            return $type;
        }
    }

    public static function getAllRecordNames () {
        return self::$recordNames;
    }

    public static function getAssociationType ($modelName) {
        $modelsToTypes = array_flip (X2Model::$associationModels);
        if (isset ($modelsToTypes[$modelName])) {
            return $modelsToTypes[$modelName];
        } else {
            return strtolower ($modelName);
        }
    }

    public static function getModelName($typeOrModuleName){
        if(array_key_exists(strtolower($typeOrModuleName), X2Model::$associationModels)){
            return X2Model::$associationModels[strtolower($typeOrModuleName)];
        }else{
            if(class_exists(ucfirst($typeOrModuleName))){
                return ucfirst($typeOrModuleName);
            }elseif(class_exists($typeOrModuleName)){
                return $typeOrModuleName;
            }else{
                return false;
            }
        }
    }

    /**
     * Retrieves a list of model names.
     *
     * Obtains model names as an associative array with model names as the keys
     * and human-readable model names as their values. This is used in place of
     * {@link getDisplayedModelNamesList()} (formerly Admin::getModelList) where
     * specifying values for {@link modelName}, because the value of that should
     * ALWAYS be the name of the actual class, and {@link X2Model::getModelName()}
     * is guaranteed to return a class name (or false, if the class does not
     * exist).
     *
     * @return array module titles indexed by associated model class names
     */
    public static function getModelNames() {
        if (!isset (self::$_modelNames)) {
            self::$_modelNames = array ();
            foreach(X2Model::model('Modules')
                ->findAllByAttributes(array ('visible' => 1, 'editable' => true)) as $module) {

                if($modelName = X2Model::getModelName($module->name))
                    self::$_modelNames[$modelName] = Yii::t('app',$module->title);
                else // Custom module most likely
                    self::$_modelNames[ucfirst($module->name)] = self::getModelTitle($modelName);
            }
        }
        return self::$_modelNames;
    }


    /**
     * Returns the translated module titles indexed by association type
     * @return array 
     */
    public static function getAssociationTypeOptions () {
        $modelNames = array_keys (self::getModelNames ());
         

        $associationTypes = array ();
        foreach ($modelNames as $modelName) {
            $associationTypes[self::getAssociationType ($modelName)] = self::getModelTitle (
                $modelName);
        }
        return $associationTypes;
    }



    /**
     * Returns the title of the model to display in the UI
     */
    public static function getModelTitle ($modelClass, $singular=false){
        if(!isset(self::$translatedModelTitles[$modelClass])){
            $title = isset(self::$modelTitles[$modelClass])
                    ? self::$modelTitles[$modelClass]
                    : $modelClass;
            if ($singular) {
                $title = preg_replace ("/ies$/", 'y', $title);
                $title = preg_replace ("/s$/", '', $title);
            }
            self::$translatedModelTitles[$modelClass] = Yii::t(
                isset (self::model($modelClass)->module) 
                ? self::model($modelClass)->module : 'app', $title);
        }
        return self::$translatedModelTitles[$modelClass];
    }

    public static function getTranslatedModelTitles ($singular=false) {
        $modelTitles = array ();
        foreach (self::$modelTitles as $model => $title) {
            $modelTitles[$model] = self::getModelTitle ($model, $singular);  
        }
        return $modelTitles;
    }

    /**
     * returns module name for given model name, or $modelName if none could be found 
     * @param string $modelName the name of a model
     * @return string the name of the module associated with the model
     */
    public static function getModuleName ($modelName) {
        if (isset (self::$modelNameToModuleName[$modelName])) {
            return self::$modelNameToModuleName[$modelName];
        } else {
            return strtolower ($modelName);
        }
    }

    public static function getModelNameFromModuleName ($moduleName) {
        
    }

    /**
     * Returns model name of module associated with current controller
     * Precondition: model is an instance of X2Model
     * @return string model name
     */
    public static function getModuleModelName () {
        return X2Model::getModelName (Yii::app()->controller->module->name);
    }

    /**
     * Returns model of module associated with current controller
     * Precondition: model is an instance of X2Model
     * @return object model
     */
    public static function getModuleModel () {
        return X2Model::model (X2Model::getModuleModelName ());
    }

    /**
     * Updates action timer sum fields in X2Model.
     * 
     * @todo write a proper unit test for this method
     */
    public static function updateTimerTotals($modelId,$modelName=null) {
        Yii::import('application.modules.actions.models.*');
        $modelName = empty($modelName) ? get_called_class() : $modelName;
        $model = self::model($modelName)->findByPk($modelId);
        if(empty($model) || $model->asa('X2LinkableBehavior') == null) return;
        // All fields of type "timerSum":
        $fields = array_filter($model->fields,function($f){return $f->type=='timerSum';});
        foreach($fields as $field) {
            if($field->linkType == null) {
                // "all types" specified, so take the shortcut of summing over
                // timeSpent field of all actions, which already itself 
                // contain sums over timer records, all of which are also
                // associated with the current model:
                $model->{$field->fieldName} = Yii::app()->db->createCommand()
                    ->select("SUM(timeSpent)")
                    ->from(Actions::model()->tableName())
                    ->where("associationId=:id 
                             AND associationType=:module
                             AND type IN ('call','time')")
                    ->queryScalar(array(
                        ':module' => $model->module,
                        ':id' => $modelId
                    ));
            } else {
                // Sum over all *published* timer records of the given type:
                $model->{$field->fieldName} = Yii::app()->db->createCommand()
                    ->select("SUM(endtime-timestamp)")
                    ->from(ActionTimer::model()->tableName())
                    ->where("associationId=:id
                        AND associationType=:modelName
                        AND actionId IS NOT NULL
                        AND type=:type")
                    ->queryScalar(array(
                        ':id' => $modelId,
                        ':modelName' => $modelName,
                        ':type' => $field->linkType
                    ));
            }
        }
        if(count($fields) > 0)
            $model->update(array_map(function($f){return $f->fieldName;},$fields));
    }

    /**
     * Use all email addresses of the model for finding a record
     * @param type $email
     */
    public function findByEmail($email) {
        $criteria = new CDbCriteria;
        $paramCount = 0;
        foreach($this->getFields() as $field) {
            if($field->type == 'email') {
                $paramCount++;
                $params[$param = ":email$paramCount"] = $email;
                $criteria->addCondition("`{$field->fieldName}`=$param", 'OR');
            }
        }
        $criteria->params = $params;
        return self::model(get_class($this))->find($criteria);
    }

    /**
     * Finds a model via a nameId reference
     * @param type $nameId
     * @return type
     */
    public function findByNameId($nameId) {
        return self::model()->findByAttributes(compact('nameId'));
    }

    /**
     * Magic getter for {@link myModelName}
     * @return string
     */
    public function getMyModelName(){
        return self::getModelName(get_class($this));
    }

    /**
     * Magic getter for {@link relatedX2Models}
     * @return array
     */
    public function getRelatedX2Models(){
        if(!isset($this->_relatedX2Models)){
            $myModelName = get_class($this);
            $this->_relatedX2Models = array();
            $relationships = Relationships::model()->findAllBySql(
                "SELECT * FROM ".
                "x2_relationships ".
                "WHERE (firstType=\"{$myModelName}\" AND firstId=\"{$this->id}\") OR ".
                    "(secondType=\"{$myModelName}\" AND secondId=\"{$this->id}\")");
            $modelRelationships = array();
            foreach($relationships as $relationship){
                list($idAttr, $typeAttr) = 
                    ($relationship->firstId == $this->id && 
                     $relationship->firstType == $myModelName) ? 
                        array('secondId', 'secondType') : 
                        array('firstId', 'firstType');
                if(!array_key_exists($relationship->$typeAttr, $modelRelationships))
                    $modelRelationships[$relationship->$typeAttr] = array();
                if(!empty($relationship->$idAttr))
                    $modelRelationships[$relationship->$typeAttr][] = $relationship->$idAttr;
            }
            foreach($modelRelationships as $modelName => $ids) {
                $this->_relatedX2Models = array_merge(
                    $this->_relatedX2Models, X2Model::model($modelName)->findAllByPk($ids));
            }
        }
        return $this->_relatedX2Models;
    }

    /**
     * Queries and caches Fields objects for the model.
     *
     * This method obtains the fields defined for the model in
     * <tt>x2_fields</tt> and makes them available for later usage to ensure
     * that the query does not need to be performed again. The fields are stored
     * as both static attributes of the model and and as Yii cache objects.
     */
    protected function queryFields(){
        $key = $this->tableName();

        if(!isset(self::$_fields[$key])){ // only look up fields if they haven't already been looked up
            self::$_fields[$key] = Yii::app()->cache->get('fields_'.$key); // check the app cache for the data
            if(self::$_fields[$key] === false){ // if the cache is empty, look up the fields
                $fieldList = CActiveRecord::model('Fields')->findAllByAttributes(array('modelName' => get_class($this), 'isVirtual' => 0));
                if(!empty($fieldList)){
                    self::$_fields[$key] = $fieldList;
                    Yii::app()->cache->set('fields_'.$key, self::$_fields[$key], 0); // cache the data
                }else{
                    self::$_fields[$key] = $this->attributeLabels();
                }
            }
        }
    }

    public function relations(){
        $relations = array();
        $myClass = get_class($this);

        // Generate relations from link-type fields.
        foreach(self::$_fields[$this->tableName()] as &$_field){
            if($_field->type === 'link' && class_exists($_field->linkType)){
                $relations[$alias = $_field->fieldName.'Model'] =
                        array(
                            self::BELONGS_TO,
                            $_field->linkType,
                            array($_field->fieldName => 'nameId'),
                );
            }
        }
        if(Yii::app()->contEd('pro')){
            $relations['gallery'] = array(self::HAS_ONE, 'GalleryToModel', 'modelId', 'condition' => 'modelName="'.$myClass.'"');
        }
        return $relations;
    }

    /**
     * Returns a list of behaviors that this model should behave as.
     * @return array the behavior configurations (behavior name=>behavior configuration)
     */
    public function behaviors(){
        $behaviors = array(
            'X2LinkableBehavior' => array('class' => 'X2LinkableBehavior'),
            'X2TimestampBehavior' => array('class' => 'X2TimestampBehavior'),
            'X2FlowTriggerBehavior' => array('class' => 'X2FlowTriggerBehavior'),
            'tags' => array('class' => 'TagBehavior'),
            'changelog' => array('class' => 'X2ChangeLogBehavior'),
            'permissions' => array('class' => 'X2PermissionsBehavior'),
        );
        if(Yii::app()->contEd('pro')){
            $behaviors['galleryBehavior'] = array(
                'class' => 'application.extensions.gallerymanager.GalleryBehavior',
                'idAttribute' => 'galleryId',
                'versions' => array(
                    'small' => array(
                        'centeredpreview' => array(98, 98),
                    ),
                ),
                'name' => true,
                'description' => true,
            );
        }
        return $behaviors;
    }

    /**
     * Saves attributes on initial model lookup
     */
    public function afterFind(){
        $this->_oldAttributes = $this->getAttributes();
        parent::afterFind();
    }

    /**
     * Remembers if this was a new record before saving.
     * @returns the answer from {@link CActiveRecord::beforeSave()}
     */
    public function beforeSave(){
        $this->_runAfterCreate = $this->getIsNewRecord();
        if(!$this->_runAfterCreate) {
            $this->updateNameId();
        } else {
            // Safeguard against duplicate entries (violating unique constraint
            // on the nameId column): set uniqueId before submitting to
            // some unique value, and let it be updated to a proper uniqueId
            // value after saving. This is just in case the nameId update after
            // insertion fails, which is easily corrected.
            if($this->hasAttribute('nameId')) {
                $this->nameId = uniqid();
            }
        }
        return parent::beforeSave();
    }

    public function onAfterCreate($event){
        $this->raiseEvent('onAfterCreate', $event);
    }

    public function afterCreate(){
        $this->_runAfterCreate = false;
        if($this->hasEventHandler('onAfterCreate'))
            $this->onAfterCreate(new CEvent($this));
    }

    public function onAfterInsert($event) {
        $this->raiseEvent('onAfterInsert',$event);
    }

    public function onAfterUpdate($event){
        $this->raiseEvent('onAfterUpdate', $event);
    }

    public function afterUpdate(){
        
        // Update, as necessary, references to this record via the nameId field.
        /* x2tempstart */
        $this->updateNameIdRefs();
        /* x2tempend */

        if($this->hasEventHandler('onAfterUpdate')) {
            $this->onAfterUpdate(new CEvent($this));
        }
    }

    /**
     * Runs when a model is deleted.
     * Clears any entries in <tt>x2_phone_numbers</tt>.
     * Fires onAfterDelete event.
     */
    public function afterDelete(){
        // Clear out old tags:
        $class = get_class($this);
        Tags::model()->deleteAllByAttributes(array(
            'type' => $class,
            'itemId' => $this->id
        ));
        // Clear out old relationships:
        Yii::app()->db->createCommand()
            ->delete(Relationships::model()->tableName(),
                    '(`firstType`=:ft AND `firstId`=:fid) '
                    . 'OR (`secondType`=:st AND `secondId`=:sid)',
                    array(
                        ':ft' => $class,
                        ':fid' => $this->id,
                        ':st' => $class,
                        ':sid' => $this->id
                    ));

        // Clear out old phone numbers
        X2Model::model('PhoneNumber')->deleteAllByAttributes(array(
            'modelId' => $this->id,
            'modelType' => $class
        ));

        // Change all references to this record so that they retain the name but
        // exclude the ID:
        if($this->hasAttribute('nameId') && $this->hasAttribute('name')){
            $this->_oldAttributes = $this->getAttributes();
            $this->nameId = $this->name;
            $this->updateNameIdRefs();
        }

        // clear out associated actions
        Actions::model()->deleteAllByAttributes(
            array(
                'associationType' => strtolower (self::getAssociationType (get_class ($this))),
                'associationId' => $this->id
            ));

        if($this->hasEventHandler('onAfterDelete'))
            $this->onAfterDelete(new CEvent($this));
    }

    /**
     * Modified to enable/disable X2Flow record update trigger.
     * This method is Copyright (c) 2008-2014 by Yii Software LLC
     * http://www.yiiframework.com/license/
     */
	public function save($runValidation=true,$attributes=null)
	{
		if(!$runValidation || $this->validate($attributes)) {
            /* x2modstart */ 
            if ($this->asa ('X2FlowTriggerBehavior')) {
                $this->enableUpdateTrigger ();
            }
			$retVal = $this->getIsNewRecord() ? 
                $this->insert($attributes) : $this->update($attributes);
            if ($this->asa ('X2FlowTriggerBehavior')) {
                $this->disableUpdateTrigger ();
            }
            /* x2modend */ 
            return $retVal;
		} else {
			return false;
        }
	}


    /**
     * Runs when a model is saved.
     * Scans attributes for phone numbers and index them in <tt>x2_phone_numbers</tt>.
     * Updates <tt>x2_relationships</tt> table based on link type fields.
     * Fires onAfterSave event.
     */
    public function afterSave(){

        if($this->_runAfterCreate)
            $this->afterCreate();
        else
            $this->afterUpdate();

        $phoneFields = array();
        $linkFields = array();

        // look through fields for phone numbers and relationships
        foreach(self::$_fields[$this->tableName()] as &$_field){
            if($_field->type === 'phone'){
                $phoneFields[$_field->fieldName] = $this->getAttribute($_field->fieldName);
            }elseif($_field->type === 'link'){
                $nameAndId = 
                    Fields::nameAndId ($this->getAttribute($_field->fieldName));
                $linkFields[$_field->fieldName] = array(
                    'id' => $nameAndId[1],
                    'type' => $_field->linkType
                );
            }
        }

        // deal with phone numbers
        if(count($phoneFields))
            X2Model::model('PhoneNumber')->deleteAllByAttributes(array('modelId' => $this->id, 'modelType' => get_class($this))); // clear out old phone numbers

        foreach($phoneFields as $field => &$number){  // create new entries in x2_phone_numbers
            if(!empty($number)){
                $num = new PhoneNumber;
                $num->number = preg_replace('/\D/', '', $number); // eliminate everything other than digits
                $num->modelId = $this->id;
                $num->modelType = get_class($this);
                $num->fieldName = $field;
                $num->save();
            }
        }

        /////////////// deal with relationships ///////////////
        $oldAttributes = $this->getOldAttributes();

        $relationSql = 
            '(firstType=:type1 AND firstId=:id1 AND secondType=:type2 AND secondId=:id2) OR
             (firstType=:type2 AND firstId=:id2 AND secondType=:type1 AND secondId=:id1)';

        foreach($linkFields as $fieldName => &$relation){
            list($oldLinkName,$oldLinkId) = Fields::nameAndId(isset($oldAttributes[$fieldName])?$oldAttributes[$fieldName]:'');

            if($relation['id'] == $oldLinkId) // skip field if it hasn't changed
                continue;

            // forget old relationship (wouldn't it be nice...)
            if(!empty($oldLinkId)){
                CActiveRecord::model('Relationships')->deleteAll($relationSql, array(
                    ':type1' => get_class($this),
                    ':id1' => $this->id,
                    ':type2' => $relation['type'],
                    ':id2' => $oldLinkId
                ));
            }
            // save new relationship
            if(!empty($relation['id']) && ctype_digit((string) $relation['id'])){
                if(!CActiveRecord::model('Relationships')->exists($relationSql, array(// check if there's already a relationship between these here thingies
                            ':type1' => get_class($this),
                            ':id1' => $this->id,
                            ':type2' => $relation['type'],
                            ':id2' => $relation['id']
                        ))){
                    $rel = new Relationships;
                    $rel->firstType = get_class($this);
                    $rel->secondType = $relation['type'];
                    $rel->firstId = $this->id;
                    $rel->secondId = $relation['id'];

                    $rel->save();
                }
            }
        }

        parent::afterSave(); // raise onAfterSave event for behaviors, such as X2ChangeLogBehavior
    }

    /**
     * Generates validation rules for custom fields
     * @return array validation rules for model attributes.
     */
    public function rules(){
        return self::modelRules(self::$_fields[$this->tableName()]);
    }

    public static function modelRules(&$fields) {
        $fieldTypes = array(
            'required',
            'email',
            'unique',
            'int',
            'numerical',
            //'date',
            //'float',
            'boolean',
            'safe',
            'search',
            'link'
        );
        $fieldRules = array_fill_keys($fieldTypes, array());
        $validators = Fields::getFieldTypes('validator');

        foreach($fields as &$_field){

            $fieldRules['search'][] = $_field->fieldName;
            if(isset($validators[$_field->type]) && $_field->safe){
                $fieldRules[$validators[$_field->type]][] = $_field->fieldName;
            }

            if($_field->required){
                $fieldRules['required'][] = $_field->fieldName;
            }
            if($_field->uniqueConstraint){
                $fieldRules['unique'][] = $_field->fieldName;
            }

            if($_field->type == 'link' && $_field->required)
                $fieldRules['link'][] = $_field->fieldName;
        }

        return array(
            array(implode(',', $fieldRules['required']), 'required'),
            array(implode(',', $fieldRules['unique']), 'unique'),
            array(implode(',', $fieldRules['numerical']), 'numerical'),
            array(implode(',', $fieldRules['email']), 'email'),
            array(implode(',', $fieldRules['int']), 'numerical', 'integerOnly' => true),
            array(implode(',', $fieldRules['boolean']), 'boolean'),
            array(implode(',', $fieldRules['link']), 'application.components.ValidLinkValidator'),
            array(implode(',', $fieldRules['safe']), 'safe'),
            array(implode(',', $fieldRules['search']), 'safe', 'on' => 'search')
        );
    }

    /**
     * Returns the named attribute value.
     * Recognizes linked attributes and looks them up with {@link getLinkedAttribute()}
     * @param string $name the attribute name
     * @param bool $renderFlag
     * @param bool $makeLinks If the render flag is set, determines whether to render attributes
     *  as links
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute
     */
    public function getAttribute($name, $renderFlag = false, $makeLinks = false){
        $nameParts = explode('.', $name); // check for a linked attribute (eg. "account.assignedTo")

        if(count($nameParts) > 1){ // We have a complicated link like "account.primaryContact.email"
            $linkField = array_shift($nameParts); // Remove the current model
            $linkModel = $this->getLinkedModel($linkField);
            $name = implode('.', $nameParts); // Put the name back together e.g. primaryContact.email
            if(isset($linkModel)){
                return $linkModel->getAttribute($name, $renderFlag);
            }else{
                $fieldInfo = $this->getField($linkField); // If it's an assignment field, check the Profile model
                if($fieldInfo instanceof Fields && $fieldInfo->type == 'assignment'){
                    $profRecord = X2Model::model('Profile')->findByAttributes(array('username' => $this->$linkField));

                    if(isset($profRecord)){
                        return $profRecord->getAttribute($name,false);
                    }
                }
            }
        }else{
            if($renderFlag){
                return $this->renderAttribute($name, $makeLinks);
            }else{
                return parent::getAttribute($name);
            }
        }
        return null;
    }

    /**
     * Returns all attributes of the current model that the user has permission
     * to view.
     * 
     * @param type $names
     */
    public function getReadableAttributeNames(){
        return array_keys(array_filter($this->getFieldPermissions(),function($p){return $p>=1;}));
    }

    /**
     * Looks up a linked attribute by loading the linked model and calling getAttribute() on it.
     * @param string $linkField the attribute of $this linking to the external model
     * @param string $attribute the attribute of the external model
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     */
    public function getLinkedAttribute($linkField, $attribute){
        if(null !== $model = $this->getLinkedModel($linkField))
            return $model->getAttribute($attribute);
        return null;
    }



    /**
     * Looks up a linked attribute by loading the linked model and calling renderAttribute() on it.
     * @param string $linkField the attribute of $this linking to the external model
     * @param string $attribute the attribute of the external model
     * @return mixed the properly formatted attribute value. Null if the attribute is not set or does not exist.
     * @see getLinkedAttribute
     */
    public function renderLinkedAttribute($linkField, $attribute){
        if(null !== $model = $this->getLinkedModel($linkField))
            return $model->renderAttribute($attribute);
        return null;
    }

    /**
     * Looks up an external model referenced in a link field.
     * Caches loaded models in X2Model::$_linkedModels
     * @param string $linkField the attribute of $this linking to the external model
     * @param bool $lookup Actually look up the model; otherwise (if false) use
     *  the name/ID to populate a dummy model that can be used for just
     *  generating a link.
     * @return mixed the active record. Null if the attribute is not set or does not exist.
     */
    public function getLinkedModel($linkField,$lookup=true){
        $nameId = $this->getAttribute($linkField);
        list($name, $id) = Fields::nameAndId($nameId);
        if(ctype_digit((string) $id)) {
            $field = $this->getField($linkField);

            if($field !== null && $field->type === 'link'){
                $modelClass = $field->linkType;

                if(!$lookup) {
                    return self::getLinkedModelMock($modelClass,$name,$id);
                }

                // try to look up the linked model
                if(!isset(self::$_linkedModels[$modelClass][$id])){
                    self::$_linkedModels[$modelClass][$id] = X2Model::model($modelClass)->findByPk($id);
                    if(self::$_linkedModels[$modelClass][$id] === null)  // if it doesn't exist, set it to false in the cache
                        self::$_linkedModels[$modelClass][$id] = false;  // so isset() returns false and we can skip this next time
                }

                if(self::$_linkedModels[$modelClass][$id] !== false)
                    return self::$_linkedModels[$modelClass][$id];  // success!
            }
        }
        return null;
    }

    /**
     * Creates a mock-up of a linked model with the minimum requirements for
     * generating a link in a view of another model.
     * 
     * @param string $modelClass
     * @param string $name
     * @param integer $id
     * @param bool $allowEmpty Return the model even if $name/$id are empty.
     * @return \modelClass|string
     */
    public static function getLinkedModelMock($modelClass, $name, $id, $allowEmpty=false){
        if($id !== null || $allowEmpty){
            // Take the shortcut for link generation:
            $model = X2Model::model($modelClass);
            if(!$model instanceof X2Model) {
                throw new CException("Error: model $modelClass does not refer to an existing child class of X2Model.");
            }
            if($model->hasAttribute('id') && $model->hasAttribute('name')){
                $model->id = $id;
                $model->name = $name;
                return $model;
            }else{
                return null;
            }
        }else{
            return null;
        }
    }

    /**
     * Wrapper method for generating a link to the view for a model record.
     *
     * @param int $id the route to this model's AutoComplete data source
     * @param string $class the model class
     * @return string a link to the model, or $id if the model is invalid
     */
    public static function getModelLink($id, $class, $requireAbsoluteUrl = false){
        $model = X2Model::model($class)->findByPk($id);
        if(isset($model) && !is_null($model->asa('X2LinkableBehavior'))){
            if(isset(Yii::app()->controller) && method_exists(Yii::app()->controller, 'checkPermissions')){
                if(Yii::app()->controller->checkPermissions($model, 'view')){
                    if($requireAbsoluteUrl){
                        return $model->getUrlLink();
                    }else{
                        return $model->getLink();
                    }
                }else{
                    return $model->renderAttribute('name');
                }
            }else{
                if($requireAbsoluteUrl){
                    return $model->getUrlLink();
                }else{
                    return $model->getLink();
                }
            }
            // return CHtml::link($model->name,array($model->getDefaultRoute().'/'.$model->id));
        }elseif(is_numeric($id)){
            return '';
        }else{
            return $id;
        }
    }

    /**
     * Link generation shortcut.
     * @param type $modelClass
     * @param type $nameId
     * @param array $htmlOptions options to be applied to the link element
     * @return type
     */
    public static function getModelLinkMock($modelClass,$nameId, $htmlOptions=array ()) {
        list($name,$id) = Fields::nameAndId($nameId);
        $model = self::getLinkedModelMock($modelClass,$name,$id);
        if($model instanceof X2Model && !is_null($model->asa('X2LinkableBehavior'))) {
            return $model->getLink ($htmlOptions);
        } else {
            return CHtml::encode($name);
        }
    }

    /**
     * Returns all possible models, either as a regular array or associative
     * (key and value are the same)
     * @param boolean $assoc
     * @return array
     */
    public static function getModelTypes($assoc = false){
        $modelTypes = Yii::app()->db->createCommand()
                ->selectDistinct('modelName')
                ->from('x2_fields')
                ->where('modelName!="Calendar"')
                ->order('modelName ASC')
                ->queryColumn();

        if($assoc === true){
            return array_combine($modelTypes, array_map(function($term){
                return Yii::t('app', X2Model::getModelTitle ($term));
            }, $modelTypes));
        }
        $modelTypes = array_map(function($term){
                    return Yii::t('app', $term);
                }, $modelTypes);
        return $modelTypes;
    }

    /**
     * Like getModelTypes () except that only types of models whic support relationships are 
     * returned
     * @param boolean $assoc
     * @return array 
     */
    public static function getModelTypesWhichSupportRelationships ($assoc=false) {
        $modelTypes = self::getModelTypes ($assoc);
        $tmp = $assoc ? array_flip ($modelTypes) : $modelTypes;
        $tmp = array_filter ($tmp, function ($a) use ($assoc) {
            return X2Model::Model ($a)->supportsRelationships;
        });
        $tmp = $assoc ? array_flip ($tmp) : $tmp;
        return array_intersect ($modelTypes, $tmp);
    }

    /**
     * Like getModelTypes () except that only types of models which support workflow are 
     * returned
     * @param boolean $assoc
     * @return array 
     */
    public static function getModelTypesWhichSupportWorkflow ($assoc=false) {
        $modelTypes = self::getModelTypes ($assoc);
        $tmp = $assoc ? array_flip ($modelTypes) : $modelTypes;
        $tmp = array_filter ($tmp, function ($a) use ($assoc) {
            return X2Model::Model ($a)->supportsWorkflow;
        });
        $tmp = $assoc ? array_flip ($tmp) : $tmp;
        return array_intersect ($modelTypes, $tmp);
    }

    /**
     * Returns a translated label using "module" defined in 
     * @param type $label
     * @return type
     */
    public function translatedAttributeLabel($label) {
        return Yii::t((bool)$this->asa('X2LinkableBehavior')?(empty($this->module)?'app':$this->module):'app',$label);
    }

    /**
     * Returns custom attribute values defined in x2_fields
     * @return array customized attribute labels (name=>label)
     * @see generateAttributeLabel
     */
    public function attributeLabels(){
        $labels = array();

        foreach(self::$_fields[$this->tableName()] as &$_field){
            $labels[$_field->fieldName] = $this->translatedAttributeLabel($_field->attributeLabel);
        }

        return $labels;
    }

    /**
     * Returns the text label for the specified attribute.
     * This method overrides the parent implementation by supporting
     * returning the label defined in relational object.
     * In particular, if the attribute name is in the form of "post.author.name",
     * then this method will derive the label from the "author" relation's "name" attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel
     * @since 1.1.4
     */
    public function getAttributeLabel($attribute){
        foreach(self::$_fields[$this->tableName()] as &$_field){ // don't call attributeLabels(), just look in self::$_fields
            if(isset($_field->fieldName)){
                if($_field->fieldName == $attribute){                    
                    return $this->translatedAttributeLabel($_field->attributeLabel);
                }
            }else{
                break;
            }
        }
        if(isset(self::$_fields[$this->tableName()][$attribute])){
            return self::$_fields[$this->tableName()][$attribute];
        }
        // original Yii code
        if(strpos($attribute, '.') !== false){
            $segs = explode('.', $attribute);
            $name = array_pop($segs);
            $model = $this;
            foreach($segs as $seg){
                $relations = $model->getMetaData()->relations;
                if(isset($relations[$seg]))
                    $model = X2Model::model($relations[$seg]->className);
                else
                    break;
            }
            return $model->getAttributeLabel($name);
        } else
            return $this->generateAttributeLabel($attribute);
    }

    public function getOldAttributes(){
        return $this->_oldAttributes;
    }

    public function getFields($assoc = false){
        if($assoc){
            $fields = array();
            foreach(self::$_fields[$this->tableName()] as &$field)
                $fields[$field->fieldName] = $field;
            return $fields;
        }else{
            return self::$_fields[$this->tableName()];
        }
    }

    public function getField($fieldName){
        foreach(self::$_fields[$this->tableName()] as &$_field){
            if($_field->fieldName == $fieldName)
                return $_field;
        }
        return null;
    }

    /**
     * Whether to skip applying field level permissions
     *
     * Returns false if the user has any roles and isn't administrator; returns
     * true (meaning, no arbitrary restrictions on field access/editability)
     * 
     * @return boolean
     */
    public function getIsExemptFromFieldLevelPermissions(){
        return Yii::app()->params->isAdmin || empty(Yii::app()->params->roles);
    }

    public function insert($attributes = null){
        $succeeded = parent::insert($attributes);
        // Alter and save the nameId field:
        if($succeeded && self::$autoPopulateFields) {
            $this->updateNameId(true);

            if ($this->hasEventHandler ('onAfterInsert'))
                $this->onAfterInsert (new CEvent ($this));
        }
        return $succeeded;
    }

    /**
     * Renders an attribute of the model based on its field type
     * @param string $fieldName the name of the attribute to be rendered
     * @param boolean $makeLinks whether to create HTML links for certain field types
     * @param boolean $textOnly whether to generate HTML or plain text
     * @return string the HTML or text for the formatted attribute
     */
    public function renderAttribute($fieldName, $makeLinks = true, $textOnly = true,$encode=true){
        $render = function($x)use($encode){return $encode?CHtml::encode($x):$x;};

        $field = $this->getField($fieldName);
        if(!isset($field))
            return null;

        if(Yii::app()->params->noSession){
            $webRequestAttributes = array(
                'rating', // Uses a Yii widget, which requires access to the controller
                'assignment', // Depends on getUserLinks, which depends on the user session
                'optionalAssignment', // Same as above
                'url', // Renders an actual link
                'text', // Uses convertUrls, which is in x2base
            );
            if(in_array($field->type, $webRequestAttributes))
                return $render($this->$fieldName);
        }

        switch($field->type){
            case 'date':
                if(empty($this->$fieldName))
                    return ' ';
                elseif(is_numeric($this->$fieldName))
                    return Formatter::formatLongDate($this->$fieldName);
                else
                    return $render($this->$fieldName);
            case 'dateTime':
                if(empty($this->$fieldName))
                    return ' ';
                elseif(is_numeric($this->$fieldName))
                    return Formatter::formatCompleteDate($this->$fieldName);
                else
                    return $render($this->$fieldName);

            case 'rating':
                if($textOnly){
                    return $render($this->$fieldName);
                }else{
                    return Yii::app()->controller->widget('CStarRating', array(
                                'model' => $this,
                                'name' => str_replace(' ', '-', get_class($this).'-'.$this->id.'-rating-'.$field->fieldName),
                                'attribute' => $field->fieldName,
                                'readOnly' => true,
                                'allowEmpty' => !$field->required, // If not required, render the "cancel" button to clear the rating
                                'minRating' => 1, //minimal valuez
                                'maxRating' => 5, //max value
                                'starCount' => 5, //number of stars
                                'cssFile' => Yii::app()->theme->getBaseUrl().'/css/rating/jquery.rating.css',
                                    ), true);
                }

            case 'assignment':
                return User::getUserLinks($this->$fieldName, $makeLinks);

            case 'optionalAssignment':
                if($this->$fieldName == '')
                    return '';
                else
                    return User::getUserLinks($this->$fieldName);

            case 'visibility':
                switch($this->$fieldName){
                    case '1':
                        return Yii::t('app', 'Public');
                        break;
                    case '0':
                        return Yii::t('app', 'Private');
                        break;
                    case '2':
                        return Yii::t('app', 'User\'s Groups');
                        break;
                    default:
                        return '';
                }

            case 'email':
                if(empty($this->$fieldName)){
                    return '';
                }else{
                    $mailtoLabel = (isset($this->name) && !is_numeric($this->name)) ? '"'.$this->name.'" <'.$this->$fieldName.'>' : $this->$fieldName;
                    return $makeLinks ? CHtml::mailto(CHtml::encode($this->$fieldName), $mailtoLabel) : $render($this->$fieldName);
                }

            case 'phone':
                $value = X2Model::getPhoneNumber($fieldName,get_class($this),$this->id, $encode, $makeLinks,$this->$fieldName);
                return $value;

            case 'url':
                if(!$makeLinks)
                    return CHtml::encode($this->$fieldName);

                if(empty($this->$fieldName)){
                    $text = '';
                }elseif(!empty($field->linkType)){
                    switch($field->linkType){

                        case 'skype':
                            $text = '<a href="callto:'.$render($this->$fieldName).'">'.$render($this->$fieldName).'</a>';
                            break;
                        case 'googleplus':
                            $text = '<a href="http://plus.google.com/'.$render($this->$fieldName).'">'.$render($this->$fieldName).'</a>';
                            break;
                        case 'twitter':
                            $text = '<a href="http://www.twitter.com/#!/'.$render($this->$fieldName).'">'.$render($this->$fieldName).'</a>';
                            break;
                        case 'linkedin':
                            $text = '<a href="http://www.linkedin.com/in/'.$render($this->$fieldName).'">'.$render($this->$fieldName).'</a>';
                            break;
                        default:
                            $text = '<a href="http://www.'.$field->linkType.'.com/'.$render($this->$fieldName).'">'.$render($this->$fieldName).'</a>';
                    }
                }else{
                    $text = trim(preg_replace(
                                    array(
                                '/<a([^>]*)target="?[^"\']+"?/i',
                                '/<a([^>]+)>/i',
                                    ), array(
                                '<a\\1 target="_blank"',
                                '<a\\1 target="_blank">',
                                    ), $render($this->$fieldName)
                            ));
                    $oldText = $text;
                    if(!function_exists('linkReplaceCallback')){

                        function linkReplaceCallback($matches){
                            return stripslashes((strlen($matches[2]) > 0 ? '<a href=\"'.$matches[2].'\" target=\"_blank\">'.$matches[0].'</a>' : $matches[0]));
                        }

                    }

                    $text = trim(preg_replace_callback(
                                    array(
                                '/(?(?=<a[^>]*>.+<\/a>)(?:<a[^>]*>.+<\/a>)|([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+))/ix',
                                    ), 'linkReplaceCallback', $render($this->$fieldName)
                            ));
                    if($text == trim($oldText)){
                        if(!function_exists('linkReplaceCallback2')){

                            function linkReplaceCallback2($matches){
                                return stripslashes((strlen($matches[2]) > 0 ? '<a href=\"http://'.$matches[2].'\" target=\"_blank\">'.$matches[0].'</a>' : $matches[0]));
                            }

                        }

                        $text = trim(preg_replace_callback(
                                        array(
                                    '/(^|\s|>)(www.[^<> \n\r]+)/ix',
                                        ), 'linkReplaceCallback2', $render($this->$fieldName)
                                ));
                    }
                }
                return $text;

            case 'link':
                $linkedModel = $this->getLinkedModel($fieldName,false);
                if($linkedModel === null) {
                    return $render($this->$fieldName);
                } else {
                    return $makeLinks ? $linkedModel->getLink() : $linkedModel->name;
                }
            case 'boolean':
                return $textOnly ? $render($this->$fieldName) : CHtml::checkbox('', $this->$fieldName, array('onclick' => 'return false;', 'onkeydown' => 'return false;'));

            case 'currency':
                if($this instanceof Product) { // products have their own currency
                    return Formatter::formatCurrency ($this->$fieldName, $this->currency);
                } else {
                    return empty($this->$fieldName) ? 
                        "&nbsp;" : 
                        Formatter::formatCurrency ($this->$fieldName);
                }

            case 'percentage':
                return $this->$fieldName !== null && $this->$fieldName !== '' ? (string) ($render($this->$fieldName))."%" : null;

            case 'dropdown':
                return $render(X2Model::model('Dropdowns')->getDropdownValue($field->linkType, $this->$fieldName));

            case 'parentCase':
                return $render(Yii::t(strtolower(Yii::app()->controller->id), $this->$fieldName));

            case 'text':
                return Yii::app()->controller->convertUrls($render($this->$fieldName));

            case 'credentials':
                $sysleg = Yii::t('app', 'System default (legacy)');
                if($this->$fieldName == -1){
                    return $sysleg;
                }else{
                    $creds = Credentials::model()->findByPk($this->$fieldName);
                    if(!empty($creds))
                        return $render($creds->name);
                    else
                        return $sysleg;
                }
            case 'timerSum':
                $t_seconds = $this->$fieldName;
                $t[] = floor($t_seconds/3600); // Hours
                $t[] = floor($t_seconds/60) % 60; // Minutes
                $t[] = $t_seconds % 60; // Seconds
                $pad = function($t){return str_pad((string)$t,2,'0',STR_PAD_LEFT);};
                return implode(':',array_map($pad,$t));
            case 'float':
            case 'int':
                if($fieldName != 'id')
                    return Yii::app()->locale->numberFormatter->formatDecimal ($this->$fieldName);
            default:
                return $render($this->$fieldName);
        }
    }

    /**
     * @param string $field the name of the field
     * @param string $class the name of the class with which the field is associated
     * @param int $id
     * @param bool $encode Whether to html encode the number
     * @param bool $makeLink Whether return a phone link
     * @param string $default What to use in case phone number lookup failed;
     *  circumvents the need to re-query the model if used.
     */
    public static function getPhoneNumber($field, $class, $id, $encode=false, $makeLink=false, $default=null){
        $phoneCheck = CActiveRecord::model('PhoneNumber')
            ->findByAttributes(array('modelId' => $id, 'modelType' => $class, 'fieldName' => $field));
        if($phoneCheck instanceof PhoneNumber && strlen($phoneCheck->number) == 10 &&
           strpos($phoneCheck->number, '0') !== 0 && strpos($phoneCheck->number, '1') !== 0){

            $number = (string) $phoneCheck->number;
            $fmtNumber = "(".substr($number, 0, 3).") ".substr($number, 3, 3)."-".substr($number, 6, 4);
        }elseif($default != null){
            $number = (string) $default;
            $fmtNumber = $encode?CHtml::encode($default):$default;
        }else{
            $record = X2Model::model($class)->findByPk($id);
            if(isset($record) && $record->hasAttribute($field)) {
                $number = (string) $record->$field;
                $fmtNumber = $number;
            }
        }
        if (isset ($fmtNumber) && $makeLink && !Yii::app()->params->profile->disablePhoneLinks) {
            $fmtNumber = $encode ? CHtml::encode ($fmtNumber) : $fmtNumber;
            return '<a href="tel:+1'.$number.'">'.$fmtNumber.'</a>';
        }
        return isset($fmtNumber)? $fmtNumber : '';
    }

    public static function renderModelInput(CModel $model, Fields $field, $htmlOptions=array()) {
        if ($field->required) {
            if (isset ($htmlOptions['class'])) {
                $htmlOptions['class'] .= ' x2-required';
            } else {
                $htmlOptions = array_merge (
                    array (
                        'class' => 'x2-required'
                    ),
                    $htmlOptions
                );
            }
        }
        $fieldName = $field->fieldName;
        if(!isset($field))
            return null;
        switch($field->type){
            case 'text':
                return CHtml::activeTextArea($model, $field->fieldName, array_merge(array(
                                    'title' => $field->attributeLabel,
                                        ), $htmlOptions));
            // array(
            // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
            // 'disabled'=>$item['readOnly']? 'disabled' : null,
            // 'title'=>$field->attributeLabel,
            // 'style'=>$default?'color:#aaa;':null,
            // ));

            case 'date':
                $model->$fieldName = Formatter::formatDate($model->$fieldName, 'medium');
                Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
                return Yii::app()->controller->widget('CJuiDateTimePicker', array(
                            'model' => $model, //Model object
                            'attribute' => $fieldName, //attribute name
                            'mode' => 'date', //use "time","date" or "datetime" (default)
                            'options' => array(// jquery options
                                'dateFormat' => Formatter::formatDatePicker(),
                                'changeMonth' => true,
                                'changeYear' => true,
                            ),
                            'htmlOptions' => array_merge(array(
                                'title' => $field->attributeLabel,
                                    ), $htmlOptions),
                            'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
                                ), true);
            case 'dateTime':
                $model->$fieldName = Formatter::formatDateTime($model->$fieldName);
                Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
                return Yii::app()->controller->widget('CJuiDateTimePicker', array(
                            'model' => $model, //Model object
                            'attribute' => $fieldName, //attribute name
                            'mode' => 'datetime', //use "time","date" or "datetime" (default)
                            'options' => array(// jquery options
                                'dateFormat' => Formatter::formatDatePicker('medium'),
                                'timeFormat' => Formatter::formatTimePicker(),
                                'ampm' => Formatter::formatAMPM(),
                                'changeMonth' => true,
                                'changeYear' => true,
                            ),
                            'htmlOptions' => array_merge(array(
                                'title' => $field->attributeLabel,
                                    ), $htmlOptions),
                            'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
                                ), true);
            case 'dropdown':
                // Note: if desired to translate dropdown options, change the seecond argument to 
                // $model->module
                $om = Dropdowns::getItems($field->linkType, null, true); 
                $multi = (bool) $om['multi'];
                $dropdowns = $om['options'];
                $curVal = $multi ? CJSON::decode($model->{$field->fieldName}) : $model->{$field->fieldName};

                $dependencyCount = X2Model::model('Dropdowns')->countByAttributes(array('parent' => $field->linkType));
                $fieldDependencyCount = X2Model::model('Fields')->countByAttributes(array('modelName' => $field->modelName, 'type' => 'dependentDropdown', 'linkType' => $field->linkType));
                if($dependencyCount > 0 && $fieldDependencyCount > 0){
                    $ajaxArray = array('ajax' => array(
                            'type' => 'GET', //request type
                            'url' => Yii::app()->controller->createUrl('/site/dynamicDropdown'),
                            'data' => 'js:{"val":$(this).val(),"dropdownId":"'.$field->linkType.'", "field":true, "module":"'.$field->modelName.'"}',
                            'success' => 'function(data){
                                            if(data){
                                                data=JSON.parse(data);
                                                if(data[0] && data[1]){
                                                    $("#'.$field->modelName.'_"+data[0]).html(data[1]);
                                                }
                                            }
                                        }',
                            ));
                }else{
                    $ajaxArray = array();
                }
                $htmlOptions = array_merge($htmlOptions, $ajaxArray, array('title' => $field->attributeLabel));
                if($multi){
                    $multiSelectOptions = array();
                    if(!is_array($curVal))
                        $curVal = array();
                    foreach($curVal as $option)
                        $multiSelectOptions[$option] = array('selected' => 'selected');
                    $htmlOptions = array_merge(
                        $htmlOptions, 
                        array(
                            'options' => $multiSelectOptions,
                            'multiple' => 'multiple'
                        ));
                } else{
                    $htmlOptions = array_merge($htmlOptions, array('empty' => Yii::t('app', "Select an option")));
                }
                return CHtml::activeDropDownList($model, $field->fieldName, $dropdowns, $htmlOptions);

            case 'dependentDropdown':
                return CHtml::activeDropDownList($model, $field->fieldName, array('' => '-'), array_merge(
                                        array(
                                    'title' => $field->attributeLabel,
                                        ), $htmlOptions
                                ));
            case 'link':
                $linkSource = null;
                $linkId = '';
                $name = '';

                if(class_exists($field->linkType)){
                    // Create a model for autocompletion:
                    if(!empty($model->$fieldName)){
                        list($name, $linkId) = Fields::nameAndId($model->$fieldName);
                        $linkModel = self::getLinkedModelMock($field->linkType, $name, $linkId, true);
                    }else{
                        $linkModel = X2Model::model($field->linkType);
                    }
                    if($linkModel instanceof X2Model && $linkModel->asa('X2LinkableBehavior') instanceof X2LinkableBehavior){
                        $linkSource = Yii::app()->controller->createUrl($linkModel->autoCompleteSource);
                        $linkId = $linkModel->id;
                        $model->$fieldName = $name;
                    }
                }

                return CHtml::hiddenField($field->modelName.'['.$fieldName.'_id]', $linkId, array('id' => $field->modelName.'_'.$fieldName."_id"))
                        .Yii::app()->controller->widget('zii.widgets.jui.CJuiAutoComplete', array(
                            'model' => $model,
                            'attribute' => $fieldName,
                            // 'name'=>'autoselect_'.$fieldName,
                            'source' => $linkSource,
                            'value' => $name,
                            'options' => array(
                                'minLength' => '1',
                                'select' => 'js:function( event, ui ) {
                                $("#'.$field->modelName.'_'.$fieldName.'_id").val(ui.item.id);
                                $(this).val(ui.item.value);
                                return false;
                            }',
                                'create' => $field->linkType == 'Contacts' ? 'js:function(event, ui) {
                                    $(this).data( "uiAutocomplete" )._renderItem = function(ul,item) {
                                        return $("<li>").data("item.autocomplete",item).append(x2.forms.renderContactLookup(item)).appendTo(ul);
                                    };
                                }' : ($field->linkType == 'BugReports' ? 'js:function(event, ui) {
                                    $(this).data( "uiAutocomplete" )._renderItem = function( ul, item ) {
                                        var label = "<a style=\"line-height: 1;\">" + item.label;

                                        label += "<span style=\"font-size: 0.6em;\">";

                                        // add email if defined
                                        if(item.subject) {
                                            label += "<br>";
                                            label += item.subject;
                                        }

                                        label += "</span>";
                                        label += "</a>";

                                        return $( "<li>" )
                                            .data( "item.autocomplete", item )
                                            .append( label )
                                            .appendTo( ul );
                                    };
                                }' : ''),
                            ),
                            'htmlOptions' => array_merge(array(
                                'title' => $field->attributeLabel,
                                    ), $htmlOptions)
                                ), true);

            case 'rating':
                return Yii::app()->controller->widget('CStarRating', array(
                            'model' => $model,
                            'attribute' => $field->fieldName,
                            'readOnly' => isset($htmlOptions['disabled']) && $htmlOptions['disabled'],
                            'minRating' => Fields::RATING_MIN, //minimal value
                            'maxRating' => Fields::RATING_MAX, //max value
                            'starCount' => Fields::RATING_MAX - Fields::RATING_MIN + 1, //number of stars
                            'cssFile' => Yii::app()->theme->getBaseUrl().'/css/rating/jquery.rating.css',
                            'htmlOptions' => $htmlOptions
                                ), true);

            case 'boolean':
                return '<div class="checkboxWrapper">'
                        .CHtml::activeCheckBox($model, $field->fieldName, array_merge(array(
                                    'unchecked' => 0,
                                    'title' => $field->attributeLabel,
                                        ), $htmlOptions)).'</div>';

            case 'assignment':
                $model->$fieldName = !empty($model->$fieldName) 
                    ? ($field->linkType=='multiple' && !is_array($model->$fieldName)
                        ? explode(', ',$model->$fieldName)
                        : $model->$fieldName)
                    : self::getDefaultAssignment ();
                return CHtml::activeDropDownList(
                    $model, $fieldName, X2Model::getAssignmentOptions(true, true), 
                    array_merge(array(
                        // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
                        // 'disabled'=>$item['readOnly']? 'disabled' : null,
                        'title' => $field->attributeLabel,
                        'id' => $field->modelName.'_'.$fieldName.'_assignedToDropdown',
                        'multiple' => 
                            ($field->linkType == 'multiple' ?  'multiple' : null),
                    ), $htmlOptions)
                );
            /*
              $group = is_numeric($model->$fieldName);
              // if(is_numeric($model->assignedTo)){
              // $group=true;
              // $groups=Groups::getNames();
              // }else{
              // $group=false;
              // }
              if (is_array($model[$fieldName]))
              $model[$fieldName] = implode(', ', $model[$fieldName]);

              if (empty($model->$fieldName))
              $model->$fieldName = Yii::app()->user->getName();
              return CHtml::activeDropDownList($model, $fieldName, $group ? Groups::getNames() : User::getNames(), array_merge(array(
              // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
              // 'disabled'=>$item['readOnly']? 'disabled' : null,
              'title' => $field->attributeLabel,
              'id' => $field->modelName . '_' . $fieldName . '_assignedToDropdown',
              'multiple' => ($field->linkType == 'multiple' ? 'multiple' : null),
              ), $htmlOptions))
              /* x2temp
              . '<div class="checkboxWrapper">'
              . CHtml::checkBox('group', $group, array_merge(array(
              // array(
              // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
              // 'disabled'=>$item['readOnly']? 'disabled' : null,
              'title' => $field->attributeLabel,
              'id' => $field->modelName . '_' . $fieldName . '_groupCheckbox',
              'ajax' => array(
              'type' => 'POST', //request type
              'url' => Yii::app()->controller->createUrl('/groups/getGroups'), //url to call.
              'update' => '#' . $field->modelName . '_' . $fieldName . '_assignedToDropdown', //selector to update
              'data' => 'js:{checked: $(this).attr("checked")=="checked", field:"' . $model->$fieldName . '"}',
              'complete' => 'function(){
              if($("#' . $field->modelName . '_' . $fieldName . '_groupCheckbox").attr("checked")!="checked"){
              $("#' . $field->modelName . '_' . $fieldName . '_groupCheckbox").attr("checked","checked");
              $("#' . $field->modelName . '_' . $fieldName . '_visibility option[value=\'2\']").remove();
              }else{
              $("#' . $field->modelName . '_' . $fieldName . '_groupCheckbox").removeAttr("checked");
              $("#' . $field->modelName . '_' . $fieldName . '_visibility").append(
              $("<option></option>").val("2").html("User\'s Groups")
              );
              }
              }')
              ), array_merge($htmlOptions, array('style' => 'margin-left:10px;'))))
              . '<label for="group" class="groupLabel">' . Yii::t('app', 'Group?') . '</label></div>';
              /* end x2temp

              // case 'association':
              // if($field->linkType!='multiple') {
              // return CHtml::activeDropDownList($model, $fieldName, $contacts,array_merge(array(
              // 'title'=>$field->attributeLabel,
              // ),$htmlOptions));
              // } else {
              // return CHtml::activeListBox($model, $fieldName, $contacts,array_merge(array(
              // 'title'=>$field->attributeLabel,
              // 'multiple'=>'multiple',
              // ),$htmlOptions));
              // }
             */
            case 'optionalAssignment': // optional assignment for users (can be left blank)

                $users = User::getNames();
                unset($users['Anyone']);

                return CHtml::activeDropDownList($model, $fieldName, $users, array_merge(array(
                                    // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
                                    // 'disabled'=>$item['readOnly']? 'disabled' : null,
                                    'title' => $field->attributeLabel,
                                    'empty' => '',
                                        ), $htmlOptions));

            case 'visibility':
                return CHtml::activeDropDownList($model, $field->fieldName, X2PermissionsBehavior::getVisibilityOptions(), array_merge(array(
                                    'title' => $field->attributeLabel,
                                    'id' => $field->modelName."_visibility",
                                        ), $htmlOptions));

            // 'varchar', 'email', 'url', 'int', 'float', 'currency', 'phone'
            // case 'int':
            // return CHtml::activeNumberField($model, $field->fieldNamearray_merge(array(
            // 'title' => $field->attributeLabel,
            // ), $htmlOptions));

            case 'percentage':
                $htmlOptions['class'] = empty($htmlOptions['class']) ? 'input-percentage' : $htmlOptions['class'].' input-percentage';
                return CHtml::activeTextField($model, $field->fieldName, array_merge(array(
                                    'title' => $field->attributeLabel,
                                        ), $htmlOptions));

            case 'currency':
                $fieldName = $field->fieldName;
                $elementId = isset($htmlOptions['id']) ? '#'.$htmlOptions['id'] : '#'.$field->modelName.'_'.$field->fieldName;
                Yii::app()->controller->widget('application.extensions.moneymask.MMask', array(
                    'element' => $elementId,
                    'currency' => Yii::app()->params['currency'],
                    'config' => array(
                        //'showSymbol' => true,
                        'affixStay' => true,
                        'decimal' => Yii::app()->locale->getNumberSymbol('decimal'),
                        'thousands' => Yii::app()->locale->getNumberSymbol('group'),
                    )
                ));

                return CHtml::activeTextField($model, $field->fieldName, array_merge(array(
                                    'title' => $field->attributeLabel,
                                    'class' => 'currency-field',
                                        ), $htmlOptions));
            case 'credentials':
                $typeAlias = explode(':', $field->linkType);
                $type = $typeAlias[0];
                if(count($typeAlias) > 1){
                    $uid = Credentials::$sysUseId[$typeAlias[1]];
                }else{
                    $uid = Yii::app()->user->id;
                }
                return Credentials::selectorField($model, $field->fieldName, $type, $uid);

            case 'timerSum':
                // Sorry, no-can-do. This is field derives its value from a sum over timer records.
                return $model->renderAttribute($field->fieldName);
            case 'float':
            case 'int':
                if (isset ($model->$fieldName)) {
                    $model->$fieldName = Yii::app()->locale->numberFormatter->formatDecimal ($model->$fieldName);
                }
                return CHtml::activeTextField($model, $field->fieldName, array_merge(array(
                    'title' => $field->attributeLabel,
                ), $htmlOptions));
            default:
                return CHtml::activeTextField($model, $field->fieldName, array_merge(array(
                                    'title' => $field->attributeLabel,
                                        ), $htmlOptions));

            // array(
            // 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
            // 'disabled'=>$item['readOnly']? 'disabled' : null,
            // 'title'=>$field->attributeLabel,
            // 'style'=>$default?'color:#aaa;':null,
            // ));
        }
    }

    /**
     * Renders an attribute of the model based on its field type
     * @param string $fieldName the name of the attribute to be rendered
     * @param array $htmlOptions htmlOptions to be used on the input
     * @return string the HTML or text for the formatted attribute
     */
    public function renderInput($fieldName, $htmlOptions = array()){
        $field = $this->getField($fieldName);
        return self::renderModelInput($this,$field,$htmlOptions);
    }

    /**
     * Sets attributes using X2Fields
     * @param array &$data array of attributes to be set (eg. $_POST['Contacts'])
     * @param bool $filter encode all HTML special characters in input
     */
    public function setX2Fields(&$data, $filter = false){
        $editableFieldsFieldNames = $this->getEditableFieldNames ();

        // loop through fields to deal with special types
        foreach(self::$_fields[$this->tableName()] as &$field){
            $fieldName = $field->fieldName;

            // skip fields that are read-only or haven't been set
            if(!isset($data[$fieldName]) ||
               !in_array ($fieldName, $editableFieldsFieldNames)) {

                if (isset ($data[$fieldName]) &&
                    !in_array ($fieldName, $editableFieldsFieldNames)) {

                    if (YII_DEBUG) printR ('setX2Fields: Warning: '.$fieldName.' not set');
                }
                continue;
            }

            // eliminate placeholder values
            if($data[$fieldName] === $this->getAttributeLabel($fieldName)) {
                $data[$fieldName] = null;
            }

            if($field->type === 'link'){
                // Do a preliminary lookup for linkId in case there are
                // duplicates (similar name) and the user selects one of them,
                // in which case there is an ID from the form that was populated
                // via the auto-complete input widget:
                $linkId = null;
                if(isset($data[$fieldName.'_id'])) {
                    // get the linked model's ID from the hidden autocomplete field
                    $linkId = $data[$fieldName.'_id'];
                }

                if(ctype_digit((string) $linkId)){
                    $link = Yii::app()->db->createCommand()
                            ->select('name,nameId')
                            ->from(X2Model::model($field->linkType)->tableName())
                            ->where('id=?', array($linkId))
                            ->queryRow(true);
                    // Make sure the linked model exists and that the name matches:
                    if(isset($link['name']) && $link['name'] === $data[$fieldName]) {
                        $data[$fieldName] = $link['nameId'];
                    }
                }
            }
            $this->$fieldName = $field->parseValue($data[$fieldName], $filter);
        }

        // Set default values.
        //
        // This should only happen in the case that the field was not included
        // in the form submission data (with the exception of assignment fields), and the field is 
        // empty, and the record is new.
        if($this->getIsNewRecord() && $this->scenario == 'insert') {
            // Set default values
            foreach($this->getFields(true) as $fieldName => $field) {
                if(!isset($data[$fieldName]) && $this->$fieldName == '' && 
                   $field->defaultValue != null && !$field->readOnly) {

                    $this->$fieldName = $field->defaultValue;
                } else if ($this->$fieldName === null && $field->defaultValue === null && 
                    $field->type === 'assignment') {
                    
                    $this->$fieldName = self::getDefaultAssignment ();
                }
            }
        }
    }

    /**
     * Base search function, includes Retrieves a list of models based on the current search/filter conditions.
     * @param CDbCriteria $criteria the attribute name
     * @param integer $pageSize If set, will override property of profile model
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function searchBase($criteria, $pageSize=null, $uniqueId=null){
        if($criteria === null)
            $criteria = $this->getAccessCriteria();
        else
            $criteria->mergeWith($this->getAccessCriteria());
        $this->compareAttributes($criteria);
        $criteria->with = array(); // No joins necessary!
        $sort = new SmartSort (get_class($this));
        $sort->multiSort = false;
        $sort->attributes = $this->getSort();
        $sort->defaultOrder = 't.lastUpdated DESC, t.id DESC';
        $sort->sortVar = get_class($this)."_sort";

        if (!$pageSize) {
            if (!Yii::app()->user->isGuest) {
                $pageSize = Profile::getResultsPerPage();
            } else {
                $pageSize = 20;
            }
        }

        $dataProvider = new SmartActiveDataProvider(get_class($this), array(
                    'sort' => $sort,
                    'pagination' => array(
                        'pageSize' => $pageSize,
                    ),
                    'criteria' => $criteria,
                ), $uniqueId);
        $sort->applyOrder($criteria);
        return $dataProvider;
    }

    public function getSort(){
        $attributes = array();
        foreach(self::$_fields[$this->tableName()] as &$field){
            $fieldName = $field->fieldName;
            switch($field->type){
                default:
                    $attributes[$fieldName] = array(
                        'asc' => 't.'.$fieldName.' ASC',
                        'desc' => 't.'.$fieldName.' DESC',
                    );
            }
        }
        return $attributes;
    }

    /**
     * Unshifts valid operators of the front of the string.
     * @return array (<operator>, <remaining string>)
     */
    public function unshiftOperator ($string) {
        $retArr = array ('', $string);
        if (strlen ($string) > 1) {
            if (strlen ($string) > 2 && preg_match ("/^(<>|>=).*/", $string)) {
                $retArr = array (substr ($string, 0, 2), substr ($string, 2));
            } else if (preg_match ("/^(<|>|=).*/", $string)) {
                $retArr = array ($string[0], substr ($string, 1));
            }
        }
        
        return $retArr;
    }

    /**
     * Helper method for compareAttributes 
     */
    protected function compareAttribute (&$criteria, $field) {
        $fieldName = $field->fieldName;
        switch($field->type){
            case 'boolean':
                $criteria->compare(
                    't.'.$fieldName, $this->compareBoolean($this->$fieldName), true);
                break;
            case 'assignment':
                $criteria->compare(
                    't.'.$fieldName, $this->compareAssignment($this->$fieldName), true);
                break;
            case 'dropdown':
                $criteria->compare(
                    't.'.$fieldName, 
                    $this->compareDropdown($field->linkType, $this->$fieldName), false);
                break;
            case 'date':
            case 'dateTime':
                if (!empty($this->$fieldName)) {

                    // grap operator and convert date string to timestamp
                    $retArr = $this->unshiftOperator ($this->$fieldName);
                    $operator = $retArr[0];
                    $timestamp = strtotime ($retArr[1]);

                    if ($operator === '=' || $operator === '') { 
                        $criteria->addBetweenCondition(
                            't.'.$fieldName, $timestamp,
                            $timestamp + 60 * 60 * 24);
                    } else {
                        $value = $operator.$timestamp;
                        $criteria->compare ('t.'.$fieldName, $value);
                    }
                }
                break;
            case 'phone':
            // $criteria->join .= ' RIGHT JOIN x2_phone_numbers ON (x2_phone_numbers.itemId=t.id AND x2_tags.type="Contacts" AND ('.$tagConditions.'))';
            default:
                $criteria->compare('t.'.$fieldName, $this->$fieldName, true);
        }
    }

    public function compareAttributes(&$criteria){
        foreach(self::$_fields[$this->tableName()] as &$field){
            $this->compareAttribute ($criteria, $field);
        }
    }

    protected function compareBoolean($data){
        if(is_null($data) || $data == '')
            return null;

        // default to true unless recognized as false
        return in_array(mb_strtolower(
            trim($data)), array(0, 'f', 'false', Yii::t('actions', 'No')), true) ? 0 : 1;  
    }

    protected function compareAssignment($data){
        if(is_null($data) || $data == '')
            return null;
        $userNames = Yii::app()->db->createCommand()
            ->select('username')
            ->from('x2_users')
            ->where(array('like', 'CONCAT(firstName," ",lastName)', "%$data%"))
            ->queryColumn();
        $groupIds = Yii::app()->db->createCommand()
            ->select('id')
            ->from('x2_groups')
            ->where(array('like', 'name', "%$data%"))
            ->queryColumn();

        return (count($groupIds) + count($userNames) == 0) ? -1 : $userNames + $groupIds;
    }

    protected function compareDropdown($ddId, $value){
        if(is_null($value) || $value == '') {
            return null;
        }
        $dropdown = X2Model::model('Dropdowns')->findByPk($ddId);
        $multi = $dropdown->multi;
        if(isset($dropdown)){
            $index = $dropdown->getDropdownIndex($ddId, $value, $multi);
            if(!is_null($index)){
                if ($multi)
                    return CJSON::encode ($index);
                else 
                    return $index;
            }else{
                return -1;
            }
        }
        return -1;
    }

    /**
     * Attempts to load the model with the given ID, if the current
     * user passes authentication checks. Throws an exception if not.
     * @param Integer $d The ID of the model to load
     * @return mixed The model object
     */
    /*     public static function load($modelName,$id) {
      $model = X2Model::model($modelName)->findByPk($id);
      if($model === null)
      throw new CHttpException(404, Yii::t('app', 'Sorry, this record doesn\'t seem to exist.'));



      $authItem = ucfirst(Yii::app()->controller->id).ucfirst(Yii::app()->controller->action->id);

      // $authItem = ucfirst(Yii::app()->controller->id).'ViewPrivate';

      $result = Yii::app()->user->checkAccess($authItem);

      if($model->hasAttribute('visibility') && $model->hasAttribute('assignedTo')) {
      throw new CHttpException(403, 'You are not authorized to perform this action.');
      }

      return $model;
      } */

    /**
     * Returns a model of the appropriate type with a particular record loaded.
     *
     * @param String $type The type of the model to load
     * @param Integer $id The id of the record to load
     * @return CActiveRecord A database record with the requested type and id
     */
    public static function getAssociationModel($type, $id){
        if($id != 0 && $modelName = X2Model::getModelName($type))
            return X2Model::model($modelName)->findByPk($id);
        else
            return null;
    }

    /**
     * Picks the primary key attribute out of an associative aray and finds the record
     * @param array $params
     * @return type
     */
    public function findByPkInArray(array $params){
        $pkc = $this->tableSchema->primaryKey;
        $pk = null;
        if(is_array($pkc)){ // Composite primary key
            $pk = array();
            foreach($pkc as $colName){
                if(array_key_exists($colName, $params))
                    $pk[$colName] = $params[$colName];
                else // Primary key column missing
                    return null;
            }
        } elseif(array_key_exists($pkc, $params)){ // Single-column primary key
            $pk = $params[$pkc];
        }else{ // Can't do anything; primary key not found in array.
            return null;
        }
        return $this->findByPk($pk);
    }

    /**
     * Sets the nameId field appropriately.
     *
     * The model must be inserted already, so that its primary key can
     * be used.
     * @param bool $save If true, update the model when done.
     */
    public function updateNameId($save = false){
        if(!$this->hasAttribute('nameId')) {
            return;
        }
        $this->_oldAttributes['nameId'] = $this->nameId;
        $this->nameId = Fields::nameId($this->name, $this->id);
        if($save){
            $this->update(array('nameId'));
        }
    }

    /**
     * Populates the nameId field in multiple records (or all) with one query.
     *
     * Note, if the method {@link Fields::nameId()} is ever dramatically changed,
     * this method too will need to be changed accordingly. The unit test,
     * {X2ModelTest::testMassUpdateNameId()}, is designed to fail when this
     * happens in order to draw attention to it.
     *
     * @param string $modelName
     * @param mixed $ids
     */
    public static function massUpdateNameId($modelName,$ids = array()) {
        $param = array();
        $sql = "UPDATE `".self::model($modelName)->tableName()."` "
                ."SET `nameId`=CONCAT(`name`,:delim,`id`)";
        if(is_array($ids) && count($ids) > 1) {
            // Multiple records with IDs specified by $ids parameters
            $count = 0;
            foreach($ids as $id) {
                $param[":id".$count] = $id;
                $count++;
            }
            $sql .= ' WHERE `id` IN ('.implode(',',array_keys($param)).')';
        } else if(!empty($ids)) {
            // One ID specified:
            $param[':id'] = is_array($ids) ? reset($ids) : $ids;
            $sql .= ' WHERE `id`=:id';
        } else {
            // All records to be udpated:
            $sql .= ' WHERE 1';
        }
        $param[':delim'] = Fields::NAMEID_DELIM;
        return Yii::app()->db->createCommand($sql)->execute($param);
    }

    /**
     * Updates references to this model record in other tables.
     *
     * This is a temporary solution to be used until a future version, wherein
     * all tables have been migrated to InnoDB and foreign key constraints can
     * be used to maintain referential integrity. However, this function should
     * still be kept in place for handling the special case of deletion, which
     * cannot be adequately handled by foreign key constraints.
     *
     * It may be a while before we can safely migrate everything to InnoDB,
     * because one or more of our big-ticket customers have FULLTEXT indexes on
     * their contacts table to make searching faster, and FULLTEXT indexes are
     * not supported in InnoDB.
     *
     * @todo Also in said future version would be a suite of unit-tested
     *  methods in Fields (or this model) for creating, deleting and altering
     *  indexes and foreign key constraints, so that new custom fields can
     *  be created properly.
     */
    public function updateNameIdRefs(){
        // Update all references to this model via the nameId field.
        //
        // The name field and thus nameId field may have changed. Update all
        // references to it so that searching/sorting works properly.
        if(!$this->hasAttribute('nameId')) {
            return;
        }
        $nameId = $this->nameId;
        $oldNameId = isset($this->_oldAttributes['nameId']) ? $this->_oldAttributes['nameId'] : null;
        if($nameId !== $oldNameId){
            // First, however, we need to get references:
            $modelName = get_class($this);
            if(!isset(self::$_nameIdRefs[$modelName])){
                // Attempt to get cached values:
                $cacheIndex = 'nameIdRefs_'.$modelName;
                self::$_nameIdRefs[$modelName] = Yii::app()->cache->get($cacheIndex);
                // Generate and store an index of references if not available:
                if(self::$_nameIdRefs[$modelName] == false){
                    $fields = Fields::model()->findAllByAttributes(array(
                        'type' => 'link',
                        'linkType' => $modelName
                    ));
                    self::$_nameIdRefs[$modelName] = array();
                    foreach($fields as $field){
                        $table = X2Model::model($field->modelName)->tableName();
                        self::$_nameIdRefs[$modelName][$table][] = $field->fieldName;
                    }
                    Yii::app()->cache->set($cacheIndex, self::$_nameIdRefs[$modelName], 0); // cache the data
                }
            }

            // Go through references and run updates:
            foreach(self::$_nameIdRefs[$modelName] as $table => $columns){
                foreach($columns as $column){
                    Yii::app()->db->createCommand()->update(
                            $table,
                            array($column => $nameId),
                            "$column=:nid",
                            array(':nid' => $oldNameId)
                    );
                }
            }
        }
        
    }

    /**
     * @return array All error messages for this model as an array of strings 
     */
    public function getAllErrorMessages () {
        $errors = $this->getErrors ();
        $errorMessages = array ();
        foreach ($errors as $attrErrors) {
            foreach ($attrErrors as $errorMessage) {
                if ($errorMessage != '') {
                    $errorMessages[] = $errorMessage;
                }
            }
        }
        return $errorMessages;
    }

    /**
     * Helper method for renderModelInput () and setX2Fields () used to retrieve the application
     * default for the assignment field. This gets superceded by user define default values.
     */
    public static function getDefaultAssignment () {
        return Yii::app()->user->isGuest ? 'Anyone' : Yii::app()->user->getName();
    }

    /**
     * Returns assignment selection options
     * @param type $anyone
     * @param type $showGroups
     * @param type $showSeparator
     * @return type
     */
    public static function getAssignmentOptions($anyone = true, $showGroups = true,$showSeparator = true){
        $users = User::getNames();
        if($anyone !== true)
            unset($users['Anyone']);

        if($showGroups === true){
            $groups = Groups::getNames();
            if(count($groups) > 0) {
                if($showSeparator)
                    $users = $users + array('' => '--------------------') + $groups;
                else
                    $users = $users + $groups;
            }
        }
        return $users;
    }

    /**
     * Returns an array of field names that the user has permission to edit
     * @param boolean if false, get attribute labels as well as field names
     * @return mixed if $suppressAttributeLabels is true, an array of field names is returned,
     *    otherwise an associative array is returned (fieldName => attributeLabel)
     */
    public function getEditableFieldNames ($suppressAttributeLabels=true) {
        $class = get_class($this);
        if(!isset(self::$_editableFieldNames[$class])){
            $editableFields = array_keys(
                array_filter($this->fieldPermissions,function($p){return $p>=2;}));
            if (sizeof ($editableFields)) {
                $params = AuxLib::bindArray($editableFields);
                $in = AuxLib::arrToStrList(array_keys($params));
                self::$_editableFieldNames[$class] = Yii::app()->db->createCommand()
                            ->select('fieldName, attributeLabel')
                            ->from('x2_fields')
                            ->where('readOnly!=1 AND modelName="'.get_class($this).'" '
                                    .'AND fieldName IN '.$in,$params)
                            ->queryAll();
            } else {
                self::$_editableFieldNames[$class] = array ();
            }
        }

        $editableFieldNames= array();
        if(!$suppressAttributeLabels){
            foreach(self::$_editableFieldNames[$class] as $fieldInfo){
                $editableFieldNames[$fieldInfo['fieldName']] = $fieldInfo['attributeLabel'];
            }
        }else{
            foreach(self::$_editableFieldNames[$class] as $fieldInfo) {
                $editableFieldNames[] = $fieldInfo['fieldName'];
            }
        }

        return $editableFieldNames;
    }

    /**
     * Getter for {@link fieldPermissions}
     * @return type
     */
    public function getFieldPermissions(){
        $class = get_class($this);

        if(!isset(self::$_fieldPermissions[$class])) {
            $roles = Roles::getUserRoles(Yii::app()->getSuId());
            if(!$this->isExemptFromFieldLevelPermissions){
                $permRecords = Yii::app()->db->createCommand()
                        ->select("f.fieldName,MAX(rtp.permission),f.readOnly")
                        ->from(RoleToPermission::model()->tableName().' rtp')
                        ->join(Fields::model()->tableName().' f', 'rtp.fieldId=f.id '
                                .'AND rtp.roleId IN '.AuxLib::arrToStrList($roles).' '
                                .'AND f.modelName=:class', array(':class' => $class))
                        ->group('f.fieldName')
                        ->queryAll(false);
            }else{
                $permRecords = Yii::app()->db->createCommand()
                        ->select("fieldName,CAST(2 AS UNSIGNED INTEGER),readOnly")
                        ->from(Fields::model()->tableName().' f')
                        ->where('modelName=:class',array(':class'=>$class))
                        ->queryAll(false);
            }
            $fieldPerms = array();
            foreach($permRecords as $record){
                // If the permissions of the user on the field are "2" (write),
                // subtract the readOnly field
                $fieldPerms[$record[0]] = 
                    $record[1] - (integer) ((integer) $record[1] === 2 ? $record[2] : 0);
            }
            self::$_fieldPermissions[$class] = $fieldPerms;
        }
        return self::$_fieldPermissions[$class];
    }

    /**
     * Build a json-encoded form layout for models whose Forms are not editable or
     * for custom modules that do not yet have a user-created form.
     * @param string $modelname The model for which to build a default layout.
     * @return json The default layout for the selected model.
     */
    public static function getDefaultFormLayout($modelName) {
        $model = X2Model::model($modelName);
        $fields = Fields::model()->findAllByAttributes(
                array('modelName' => $modelName),
                new CDbCriteria(array('order' => 'attributeLabel ASC'))
        );
        $layout = array('sections' => array(array(
            'collapsible' => false,
            'title' => ucfirst($modelName).' Info',
            'rows' => array(array(
                'cols' => array(array(
                    'items' => array(),
                )),
            )),
        )));

        foreach($fields as $field) {
            if ($field->readOnly) continue;
            
            // hide associationType, it will be set in the dialog by associationName
            if ($field->fieldName == 'associationType') continue; 

            $newField = array(
                'name' => 'formItem_'.$field->fieldName,
                'labelType' => 'left',
                'readOnly' => $field->readOnly,
                'height' => 30,
                'width' => 155,
            );
            $layout['sections'][0]['rows'][0]['cols'][0]['items'][] = $newField;
        }

        return json_encode($layout);
    }

    /**
     * Should be used before inserting user-generated input into SQL string in cases
     * where parameter binding cannot be used (e.g. for SQL object names). 
     * @param string $attribute Name of attribute
     * @throws CException If attribute does not exist
     */
    public static function checkThrowAttrError ($attribute) {
        // prevent SQL injection by validating attribute name
        if (!self::model(get_called_class ())->hasAttribute ($attribute)) { 
            throw new CException (
                Yii::t('app', '{attribute} is not an {modelClass} field.', array (
                    '{attribute}' => $attribute,
                    '{modelClass}' => get_called_class ())));
        }
    }

    /**
     * Retrieves model of a specified type with a specified id 
     * @return mixed object or null
     */
    public static function getModelOfTypeWithId ($type, $id) {
        if(!(empty($type) || empty($id)) && 
           X2Model::getModelName($type)){ // both ID and type must be set

            return X2Model::model(X2Model::getModelName($type))->findByPk($id);
        }
        return null; // invalid type or invalid id 
    }

    public static function getModelOfTypeWithName ($type, $name) {
        $modelName = X2Model::getModelName($type); // both ID and type must be set
        if(!(empty($type) || empty($name)) && $modelName){ // both ID and type must be set

            $model = X2Model::model($modelName);
            if ($model->hasAttribute ('name')) {
                return $model->findByAttributes (array (
                    'name' => $name
                ));
            }
        }
        return null; // invalid type or invalid name 
    }

    /**
     * @param string $modelClass the model class for which the autocomplete should be rendered
     * @param bool $ajax if true, registered scripts are processed with ajaxRender
     */
    public static function renderModelAutocomplete (
        $modelClass, $ajax=false, $htmlOptions=array ()) {

        if (!class_exists ($modelClass) || !$modelClass::model ()->asa ('X2LinkableBehavior')) {
            if ($ajax) {
                echo 'failure';
                return;
            } else {
                return 'failure';
            }
            /*throw new CException (
                Yii::t('app', 
                    'Error: renderModelAutocomplete: {modelClass} does not have '.
                     'X2LinkableBehavior', array ('{modelClass}' => $modelClass)));*/
        }

        if ($ajax) Yii::app()->clientScript->scriptMap['*.css'] = false;
        
        $renderWidget = function () use ($modelClass, $htmlOptions) {
            Yii::app ()->controller->widget('zii.widgets.jui.CJuiAutoComplete', array(
                'name'=>(isset ($htmlOptions['name']) ? $htmlOptions['name'] : 'recordName'),
                'source' => Yii::app()->controller->createUrl(
                    X2Model::model ($modelClass)->autoCompleteSource),
                'value'=>Yii::t('app','Start typing to suggest...'),
                'options' => array(
                    'minLength' => '1',
                    'select' => 
                        'js:function (event, ui) {
                            $(this).val(ui.item.value);
                            // expects next input to be a hidden input which will contain the
                            // record id
                            $(this).nextAll ("input").val(ui.item.id);
                            return false;
                        }',
                ),
                'htmlOptions'=>array_merge (array(
                    'class'=>'record-name-autocomplete x2-default-field',
                    'data-default-text' => Yii::t('app','Start typing to suggest...'),
                    'style'=>'color:#aaa',
                ), $htmlOptions),
            ));
            Yii::app()->clientScript->registerScript('renderModelAutocomplete',"
            x2.forms.enableDefaultText ($('.record-name-autocomplete'));
            $('.record-name-autocomplete').unbind ('keyup.renderModelAutocomplete').
                bind ('keyup.renderModelAutocomplete', function () {
                    $(this).nextAll ('input').val ('');
                });
            ");
        };

        if ($ajax) {
            X2Widget::ajaxRender ($renderWidget);
        } else {
            $renderWidget ();
        }
    }

    /**
     * Transfers all relationships of $model to $this 
     */
    public function mergeRelationships (X2Model $model) {
        $modelName = get_class ($model);
        $thisModelName = get_class ($this);

        $relationships = Relationships::model ()->findAllBySql ("
            select * from 
            x2_relationships
            /* use XOR so that self-relationships are ignored */
            where (firstType=:type AND firstId=:id XOR secondType=:type AND secondId=:id)",
            array (
                ':type' => $modelName,
                ':id' => $model->id,
            ));

        // reassign relationships
        foreach ($relationships as $rel) {
            if ($rel->firstType === $modelName && $rel->firstId === $model->id) {
                $rel->firstType = $thisModelName;
                $rel->firstId = $this->id;
            } else { // ($rel->secondType === $modelName && $rel->secondId === $model->id)
                $rel->secondType = $thisModelName;
                $rel->secondId = $this->id;
            }
            $rel->save ();
        }

    }
  
    /**
     * Transfers all events, notifications, and actions related to the give model to this model. 
     */
    public function mergeRelatedRecords (
        X2Model $model, $mergeActions=true, $mergeEvents=true, $mergeNotifs=true) {

        $actions = array ();
        $events = array ();
        $notifs = array ();

        if ($mergeActions)
            $actions = X2Model::model('Actions')
                ->findAllByAttributes(array(
                    'associationType'=>get_class ($model),
                    'associationId'=>$model->id
                ));

        if ($mergeEvents)
            $events = X2Model::model('Events')->findAllByAttributes(array(
                'associationType'=>get_class ($model),
                'associationId'=>$model->id
            ));

        if ($mergeNotifs)
            $notifs = X2Model::model('Notification')
                ->findAllByAttributes(array(
                    'modelType'=>get_class ($model),
                    'modelId'=>$model->id
                ));

        foreach ($actions as $action) {
            $action->associationType = lcfirst (
                X2Model::getModuleName (get_class ($this)));
            $action->associationId = $this->id;
            $action->disableBehavior ('changelog');
            $action->save();
        }

        foreach ($notifs as $notif) {
            $notif->modelType = get_class ($this);
            $notif->modelId = $this->id;
            $notif->save();
        }

        foreach ($events as $event) {
            $event->associationType = get_class ($this);
            $event->associationId = $this->id;
            $event->save();
        }

    }

    /**
     * Returns list of insertable attribute tokens which can be used for this model 
     * @param int $_depth (private) 
     * @return array of strings
     */
    private static $getInsertableAttributeTokensDepth = 0; // limits recursive depth
    public function getInsertableAttributeTokens () {
        X2Model::$getInsertableAttributeTokensDepth++;
        $tokens = array ();
        if (X2Model::$getInsertableAttributeTokensDepth > 2) return $tokens;

        // simple tokens
        $tokens = array_merge (
            $tokens, array_map (function ($elem) { return '{'.$elem.'}'; }, $this->attributeNames ()));

        // assignment tokens
        if (X2Model::$getInsertableAttributeTokensDepth < 2) {
            $assignmentFields = array_filter (
                $this->fields, function ($elem) { return $elem->type === 'assignment'; });
            foreach ($assignmentFields as $field) {
                $assignmentModel = X2Model::model('Profile');
                $tokens = array_merge (
                    $tokens,
                    array_map (function ($elem) use ($field) { 
                        return '{'.$field->fieldName.'.'.$elem.'}';
                    }, $assignmentModel->attributeNames ())
                );
            }
        }

        // link tokens
        if (X2Model::$getInsertableAttributeTokensDepth < 2) {
            $linkFields = array_filter ($this->fields, function ($elem) { return $elem->type === 'link'; });
            foreach ($linkFields as $field) {
                $linkModelName = $field->linkType;
                $linkModel = $linkModelName::model ();
                $tokens = array_merge (
                    $tokens,
                    array_map (function ($elem) use ($field) { 
                        return '{'.$field->fieldName.'.'.preg_replace ('/\{|\}/', '', $elem).'}';
                    }, $linkModel->getInsertableAttributeTokens ())
                );
            }
        }

        X2Model::$getInsertableAttributeTokensDepth--;
        return $tokens;
    }

}
