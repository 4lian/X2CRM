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


Yii::import('application.components.X2LinkableBehavior');

/**
 * General model class that uses dynamic fields
 * 
 * @package X2CRM.models
 */
abstract class X2Model extends CActiveRecord {

	/**
	 * List of mapping between module names/associationType values and model class names
	 */
	public static $associationModels = array(
		'actions' => 'Actions',
		'contacts' => 'Contacts',
		'projects' => 'ProjectChild',
		'accounts' => 'Accounts',
		'product' => 'Product',
		'products' => 'Product',
		'Campaign' => 'Campaign',
		'marketing' => 'Campaign',
		'quote' => 'Quote',
		'quotes' => 'Quote',
		'opportunities' => 'Opportunity',
		'social' => 'SocialChild',
		'services' => 'Services',
		'' => ''
	);
	protected static $_fields; // one copy of fields for all instances of this model
	protected static $_linkedModels; // cache for models loaded for link field attributes (used by automation system)

	/**
	 * Calls {@link queryFields()} before CActiveRecord::__constructo() is called
	 */

	public function __construct($scenario = 'insert') {
		$this->queryFields();

		parent::__construct($scenario);
	}

	public static function model($className = 'CActiveRecord') {
		if (class_exists($className))
			return parent::model($className);
		else
			throw new CHttpException(500, 'Class: ' . $className . " not found.");
	}

	/**
	 * Queries and caches Fields objects for the model.
	 * 
	 * This method obtains the fields defined for the model in 
	 * <tt>x2_fields</tt> and makes them avaialble for later usage to ensure 
	 * that the query does not need to be performed again. The vields are stored
	 * as both static attributes of the model and and as Yii cache objects.
	 */
	protected function queryFields() {
		$key = $this->tableName();

		if (!isset(self::$_fields[$key])) { // only look up fields if they haven't already been looked up
			self::$_fields[$key] = Yii::app()->cache->get('fields_' . $key); // check the app cache for the data
			if (self::$_fields[$key] === false) { // if the cache is empty, look up the fields
				self::$_fields[$key] = CActiveRecord::model('Fields')->findAllByAttributes(array('modelName' => get_class($this), 'isVirtual' => 0));
				Yii::app()->cache->set('fields_' . $key, self::$_fields[$key], 0); // cache the data
			}
		}
	}

	public function relations() {
		$relations = array();
		foreach (self::$_fields[$this->tableName()] as &$_field) {
			if ($_field->type === 'link')
				$relations[$_field->fieldName . 'Model'] = array(self::BELONGS_TO, $_field->linkType, $_field->fieldName);
		}
		return $relations;
	}

	/**
	 * Returns a list of behaviors that this model should behave as.
	 * @return array the behavior configurations (behavior name=>behavior configuration)
	 */
	public function behaviors() {
		return array(
			'X2LinkableBehavior' => array(
				'class' => 'X2LinkableBehavior',
			),
				// 'X2ChangeLogBehavior' => array(
				// 'class' => 'X2ChangeLogBehavior',
				// )
		);
	}

	/**
	 * Runs when a model is saved.
	 * Scans attributes for phone numbers and index them in <tt>x2_phone_numbers</tt>.
	 * Updates <tt>x2_relationships</tt> table based on link type fields.
	 * Fires onAfterSave event.
	 */
	public function afterSave() {
		$this->queryFields();

		$phoneNumbers = array();
		$relationships = array();

		$modelClass = get_class($this);


		// Phone number indexing
		foreach (self::$_fields[$this->tableName()] as &$_field) { // loop through fields
			if ($_field->type === 'phone')
				$phoneNumbers[$_field->fieldName] = $this->getAttribute($_field->fieldName);  // find phone numbers for phone number table
			elseif ($_field->type === 'link')
				$relationships[$_field->fieldName] = array($this->getAttribute($_field->fieldName), $_field->linkType); // find link type fields for relationships
		}

		if (count($phoneNumbers)) // if there are any phone fields, clear out any pre-existing entries in x2_phone_numbers
			X2Model::model('PhoneNumber')->deleteAllByAttributes(array('modelId' => $this->id, 'modelType' => get_class($this)));

		foreach ($phoneNumbers as $field => &$number) { // create new entries in x2_phone_numbers
			if (!empty($number)) {
				$num = new PhoneNumber;
				$num->number = preg_replace('/\D/', '', $number);  // eliminate everything other than digits
				$num->modelId = $this->id;
				$num->modelType = get_class($this);
				$num->fieldName = $field;
				$num->save();
			}
		}

		/* foreach($relationships as $fieldName => &$link) {
		  if(!empty($link[0]) && ctype_digit($link[0])) {
		  if(CActiveRecord::model('Relationships')->exists(
		  '(firstType=:type1 AND firstId=:id1 AND secondType=:type2 AND secondId=:id2) OR
		  (firstType=:type2 AND firstId=:id2 AND secondType=:type1 AND secondId=:id1)'
		  )) {

		  $rel = new Relationships;
		  $rel->firstType = $modelClass;
		  $rel->secondType = ucfirst($field->linkType);
		  $rel->firstId = $model->id;
		  $rel->secondId = $model->$fieldName;
		  if ($rel->save()) {
		  $lookup = Relationships::model()->findBySql("SELECT * FROM x2_relationships WHERE
		  (firstType='$name' AND firstId='$model->id' AND secondType='" . ucfirst($field->linkType) . "' AND secondId='" . $oldAttributes[$fieldName] . "')
		  OR (secondType='$name' AND secondId='$model->id' AND firstType='" . ucfirst($field->linkType) . "' AND firstId='" . $oldAttributes[$fieldName] . "')");
		  if (isset($lookup))
		  $lookup->delete();
		  }
		  }
		  }
		  } */

		parent::afterSave();
	}

	/**
	 * Generates validation rules for custom fields
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		$fieldTypes = array(
			'required',
			'email',
			'int',
			'date',
			'float',
			'boolean',
			'safe',
			'search',
			'currency',
			'percentage'
		);
		$fieldRules = array_fill_keys($fieldTypes, array());

		foreach (self::$_fields[$this->tableName()] as &$_field) {

			$fieldRules['search'][] = $_field->fieldName;

			switch ($_field->type) {
				case 'varchar':
				case 'text':
				case 'url':
				case 'dropdown':
					$fieldRules['safe'][] = $_field->fieldName; // these field types have no rules, but still need to be allowed
					break;
				case 'date':
					$fieldRules['int'][] = $_field->fieldName;  // date is actually an int
					break;
				case 'currency':
					$fieldRules['currency'][] = $_field->fieldName;
					break;
				case 'percentage':
					$fieldRules['percentage'][] = $_field->fieldName;
				default:
					$fieldRules[$_field->type][] = $_field->fieldName;  // otherwise use the type as the validator name
			}

			if ($_field->required)
				$fieldRules['required'][] = $_field->fieldName;
		}

		return array(
			array(implode(',', $fieldRules['required']), 'required'),
			array(implode(',', $fieldRules['email']), 'email'),
			array(implode(',', $fieldRules['int'] + $fieldRules['date']), 'numerical', 'integerOnly' => true),
			array(implode(',', $fieldRules['currency'] + $fieldRules['percentage']), 'numerical'),
			array(implode(',', $fieldRules['float']), 'numerical'),
			array(implode(',', $fieldRules['boolean']), 'boolean'),
			array(implode(',', $fieldRules['safe']), 'safe'),
			array(implode(',', $fieldRules['search']), 'safe', 'on' => 'search')
		);
	}

	/**
	 * Returns the named attribute value.
	 * Recognizes linked attributes and looks them up with {@link getLinkedAttribute()}
	 * @param string $name the attribute name
	 * @return mixed the attribute value. Null if the attribute is not set or does not exist.
	 * @see hasAttribute
	 */
	public function getAttribute($name) {
		$nameParts = explode('.', $name); // check for a linked attribute (eg. "account.assignedTo")
		if (count($nameParts) === 2)
			return $this->getLinkedAttribute($nameParts[0], $nameParts[0]);
		else
			return parent::getAttribute($name);
	}

	/**
	 * Looks up a linked attribute by loading the linked model and calling getAttribute() on it.
	 * @param string $linkField the attribute of $this linking to the external model
	 * @param string $attribute the attribute of the external model
	 * @return mixed the attribute value. Null if the attribute is not set or does not exist.
	 */
	public function getLinkedAttribute($linkField, $attribute) {
		if (null !== $model = $this->getLinkedModel($linkField))
			return $model->getAttribute($attribute);
		return null;
	}

	/**
	 * Looks up a linked attribute by loading the linked model and calling getAttribute() on it.
	 * @param string $linkField the attribute of $this linking to the external model
	 * @param string $attribute the attribute of the external model
	 * @return mixed the properly formatted attribute value. Null if the attribute is not set or does not exist.
	 * @see getLinkedAttribute
	 */
	public function renderLinkedAttribute($linkField, $attribute) {
		if (null !== $model = $this->getLinkedModel($linkField))
			return $model->renderAttribute($attribute);
		return null;
	}

	/**
	 * Looks up an external model referenced in a link field.
	 * Caches loaded models in X2Model::$_linkedModels
	 * @param string $linkField the attribute of $this linking to the external model
	 * @return mixed the active record. Null if the attribute is not set or does not exist.
	 */
	public function getLinkedModel($linkField) {
		$id = $this->getAttribute($linkField);

		if (ctype_digit($id)) {
			$field = $this->getField($linkField);

			if ($field !== null && $field->type === 'link') {
				$modelClass = $field->linkType;

				// try to look up the linked model
				if (!isset(self::$_linkedModels[$modelClass][$id])) {
					self::$_linkedModels[$modelClass][$id] = X2Model::model($modelClass)->findByPk($id);
					if (self::$_linkedModels[$modelClass][$id] === null)  // if it doesn't exist, set it to false in the cache 
						self::$_linkedModels[$modelClass][$id] = false;  // so isset() returns false and we can skip this next time
				}

				if (self::$_linkedModels[$modelClass][$id] !== false)
					return self::$_linkedModels[$modelClass][$id];  // success!
			}
		}
		return null;
	}

	/**
	 * Wrapper method for generating a link to the view for a model record.
	 *  
	 * @param int $id the route to this model's AutoComplete data source
	 * @param string $class the model class
	 * @return string a link to the model, or $id if the model is invalid
	 */
	public static function getModelLink($id, $class) {
		$model = X2Model::model($class)->findByPk($id);
		if (isset($model) && !is_null($model->asa('X2LinkableBehavior')))
			return $model->getLink();
		// return CHtml::link($model->name,array($model->getDefaultRoute().'/'.$model->id));
		elseif (is_numeric($id))
			return '';
		else
			return $id;
	}

	/**
	 * Returns custom attribute values defined in x2_fields
	 * @return array customized attribute labels (name=>label)
	 * @see generateAttributeLabel
	 */
	public function attributeLabels() {
		$labels = array();

		foreach (self::$_fields[$this->tableName()] as &$_field) {
			if (get_class($this) == "Opportunity") {
				$labels[$_field->fieldName] = Yii::t('opportunities', $_field->attributeLabel);
			} elseif (get_class($this) == "Quote") {
				$labels[$_field->fieldName] = Yii::t('quotes', $_field->attributeLabel);
			} elseif (get_class($this) == "Product") {
				$labels[$_field->fieldName] = Yii::t('products', $_field->attributeLabel);
			} else {
				$labels[$_field->fieldName] = Yii::t(strtolower(get_class($this)), $_field->attributeLabel);
			}
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
	public function getAttributeLabel($attribute) {
		foreach (self::$_fields[$this->tableName()] as &$_field) { // don't call attributeLabels(), just look in self::$_fields
			if ($_field->fieldName == $attribute) {
				if (get_class($this) == "Opportunity") {
					return Yii::t('opportunities', $_field->attributeLabel);
				} elseif (get_class($this) == "Quote") {
					return Yii::t('quotes', $_field->attributeLabel);
				} elseif (get_class($this) == "Product") {
					return Yii::t('products', $_field->attributeLabel);
				} else {
					return Yii::t(strtolower(get_class($this)), $_field->attributeLabel);
				}
			}
		}
		// original Yii code
		if (strpos($attribute, '.') !== false) {
			$segs = explode('.', $attribute);
			$name = array_pop($segs);
			$model = $this;
			foreach ($segs as $seg) {
				$relations = $model->getMetaData()->relations;
				if (isset($relations[$seg]))
					$model = X2Model::model($relations[$seg]->className);
				else
					break;
			}
			return $model->getAttributeLabel($name);
		} else
			return $this->generateAttributeLabel($attribute);
	}

	public function getFields($assoc = false) {
		if ($assoc) {
			$fields = array();
			foreach (self::$_fields[$this->tableName()] as &$field)
				$fields[$field->fieldName] = $field;
			return $fields;
		} else {
			return self::$_fields[$this->tableName()];
		}
	}

	public function getField($fieldName) {
		foreach (self::$_fields[$this->tableName()] as &$_field) {
			if ($_field->fieldName == $fieldName)
				return $_field;
		}
		return null;
	}

	/**
	 * Renders an attribute of the model based on its field type
	 * @param string $fieldName the name of the attribute to be rendered
	 * @param boolean $makeLinks whether to create HTML links for certain field types
	 * @param boolean $textOnly whether to generate HTML or plain text
	 * @return string the HTML or text for the formatted attribute
	 */
	public function renderAttribute($fieldName, $makeLinks = true, $textOnly = true) {

		$field = $this->getField($fieldName);
		if (!isset($field))
			return null;

		switch ($field->type) {
			case 'date':
				if (empty($this->$fieldName))
					return ' ';
				elseif (is_numeric($this->$fieldName))
					return Yii::app()->controller->formatLongDate($this->$fieldName);
				else
					return $this->$fieldName;
			case 'dateTime':
				if (empty($this->$fieldName))
					return ' ';
				elseif (is_numeric($this->$fieldName))
					return Actions::formatCompleteDate($this->$fieldName);
				else
					return $this->$fieldName;

			case 'rating':
				if ($textOnly) {
					return $this->$fieldName;
				} else {
					return Yii::app()->controller->widget('CStarRating', array(
								'model' => $this,
								'name' => str_replace(' ', '-', get_class($this) . '-' . $this->id . '-rating-' . $field->fieldName),
								'attribute' => $field->fieldName,
								'readOnly' => true,
								'minRating' => 1, //minimal valuez
								'maxRating' => 5, //max value
								'starCount' => 5, //number of stars
								'cssFile' => Yii::app()->theme->getBaseUrl() . '/css/rating/jquery.rating.css',
									), true);
				}

			case 'assignment':
				return User::getUserLinks($this->$fieldName, $makeLinks);

			case 'optionalAssignment':
				if ($this->$fieldName == '')
					return '';
				else
					return User::getUserLinks($this->$fieldName);

			case 'visibility':
				switch ($this->$fieldName) {
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
				if (empty($this->$fieldName)) {
					return '';
				} else {
					$mailtoLabel = isset($this->name) ? '"' . $this->name . '" <' . $this->$fieldName . '>' : $this->$fieldName;
					return $makeLinks ? CHtml::mailto($this->$fieldName, $mailtoLabel, array('onclick' => 'toggleEmailForm();return false;')) : $this->$fieldName;
				}

			case 'phone':
				if (empty($this->$fieldName)) {
					return '';
				} else {
					$phoneCheck = PhoneNumber::model()->findByAttributes(array('modelId' => $this->id, 'modelType' => get_class($this), 'fieldName' => $fieldName));
					if (isset($phoneCheck) && strlen($phoneCheck->number) == 10) {
						$temp = $phoneCheck->number;
						$this->$fieldName = "(" . substr($temp, 0, 3) . ") " . substr($temp, 3, 3) . "-" . substr($temp, 6, 4);
					}
					return $this->$fieldName;
				}

			case 'url':
				if (!$makeLinks)
					return $this->$fieldName;

				if (empty($this->$fieldName)) {
					$text = '';
				} elseif (!empty($field->linkType)) {
					switch ($field->linkType) {
						case 'skype':
							$text = '<a href="callto:' . $this->$fieldName . '">' . $this->$fieldName . '</a>';
							break;
						case 'googleplus':
							$text = '<a href="http://plus.google.com/' . $this->$fieldName . '">' . $this->$fieldName . '</a>';
							break;
						case 'twitter':
							$text = '<a href="http://www.twitter.com/#!/' . $this->$fieldName . '">' . $this->$fieldName . '</a>';
							break;
						case 'linkedin':
							$text = '<a href="http://www.linkedin.com/in/' . $this->$fieldName . '">' . $this->$fieldName . '</a>';
							break;
						default:
							$text = '<a href="http://www.' . $field->linkType . '.com/' . $this->$fieldName . '">' . $this->$fieldName . '</a>';
					}
				} else {
					$text = trim(preg_replace(
									array(
								'/(?(?=<a[^>]*>.+<\/a>)(?:<a[^>]*>.+<\/a>)|([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+))/iex',
								'/<a([^>]*)target="?[^"\']+"?/i',
								'/<a([^>]+)>/i',
								'/(^|\s|>)(www.[^<> \n\r]+)/iex',
									), array(
								"stripslashes((strlen('\\2')>0?'\\1<a href=\"\\2\" target=\"_blank\">" . $this->$fieldName . "</a>\\3':'\\0'))",
								'<a\\1 target="_blank"',
								'<a\\1 target="_blank">',
								"stripslashes((strlen('\\2')>0?'\\1<a href=\"http://\\2\" target=\"_blank\">" . $this->$fieldName . "</a>\\3':'\\0'))",
									), $this->$fieldName
							));
				}
				return $text;

			case 'link':
				if (!empty($this->$fieldName) && is_numeric($this->$fieldName)) {
					$className = ucfirst($field->linkType);
					if (class_exists($className))
						$linkModel = X2Model::model($className)->findByPk($this->$fieldName);
					if (isset($linkModel))
						return $makeLinks ? $linkModel->createLink() : $linkModel->name;
					else
						return '';
				} else {
					return $this->$fieldName;
				}

			case 'boolean':
				return $textOnly ? $this->$fieldName : CHtml::checkbox('', $this->$fieldName, array('onclick' => 'return false;', 'onkeydown' => 'return false;'));

			case 'currency':
				if ($this instanceof Product) // products have their own currency
					return Yii::app()->locale->numberFormatter->formatCurrency($this->$fieldName, $this->currency);
				else
					return empty($this->$fieldName) ? "&nbsp;" : Yii::app()->locale->numberFormatter->formatCurrency($this->$fieldName, Yii::app()->params['currency']);

			case 'percentage':
				return $this->$fieldName!==null&&$this->$fieldName!==''?(string)($this->$fieldName)."%":null;

			case 'dropdown':
				return empty($this->$fieldName) ? "" : Yii::t(strtolower(Yii::app()->controller->id), $this->$fieldName);

			case 'parentCase':
				return Yii::t(strtolower(Yii::app()->controller->id), $this->$fieldName);

			case 'text':
				return Yii::app()->controller->convertUrls($this->$fieldName);

			default:
				return $this->$fieldName;
		}
	}

	public static function getPhoneNumber($field, $class, $id) {
		$phoneCheck = CActiveRecord::model('PhoneNumber')->findByAttributes(array('modelId' => $id, 'modelType' => $class, 'fieldName' => $field));
		if (isset($phoneCheck) && strlen($phoneCheck->number) == 10) {
			$temp = $phoneCheck->number;
			return "(" . substr($temp, 0, 3) . ") " . substr($temp, 3, 3) . "-" . substr($temp, 6, 4);
		} else {
			$record = X2Model::model($class)->findByPk($id);
			if (isset($record))
				return $record->$field;
		}
	}

	/**
	 * Renders an attribute of the model based on its field type
	 * @param string $fieldName the name of the attribute to be rendered
	 * @param array $htmlOptions htmlOptions to be used on the input
	 * @return string the HTML or text for the formatted attribute
	 */
	public function renderInput($fieldName, $htmlOptions = array()) {

		$field = $this->getField($fieldName);
		if (!isset($field))
			return null;

		switch ($field->type) {
			case 'text':
				return CHtml::activeTextArea($this, $field->fieldName, array_merge(array(
									'title' => $field->attributeLabel,
										), $htmlOptions));
			// array(
			// 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
			// 'disabled'=>$item['readOnly']? 'disabled' : null,
			// 'title'=>$field->attributeLabel,
			// 'style'=>$default?'color:#aaa;':null,
			// ));

			case 'date':
				$this->$fieldName = Yii::app()->controller->formatDate($this->$fieldName);
				Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
				return Yii::app()->controller->widget('CJuiDateTimePicker', array(
							'model' => $this, //Model object
							'attribute' => $field->fieldName, //attribute name
							'mode' => 'date', //use "time","date" or "datetime" (default)
							'options' => array(// jquery options
								'dateFormat' => Yii::app()->controller->formatDatePicker(),
								'changeMonth' => true,
								'changeYear' => true,
							),
							'htmlOptions' => array_merge(array(
								'title' => $field->attributeLabel,
									), $htmlOptions),
							'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
								), true);
			case 'dateTime':
				$this->$fieldName = Yii::app()->controller->formatDateTime($this->$fieldName);
				Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
				return Yii::app()->controller->widget('CJuiDateTimePicker', array(
							'model' => $this, //Model object
							'attribute' => $field->fieldName, //attribute name
							'mode' => 'datetime', //use "time","date" or "datetime" (default)
							'options' => array(// jquery options
								'dateFormat' => Yii::app()->controller->formatDatePicker(),
								'timeFormat' => Yii::app()->controller->formatTimePicker(),
								'changeMonth' => true,
								'changeYear' => true,
							),
							'htmlOptions' => array_merge(array(
								'title' => $field->attributeLabel,
									), $htmlOptions),
							'language' => (Yii::app()->language == 'en') ? '' : Yii::app()->getLanguage(),
								), true);
			case 'dropdown':
				$dropdowns = Dropdowns::getItems($field->linkType);
				return CHtml::activeDropDownList($this, $field->fieldName, $dropdowns, array_merge(
										array(
									'title' => $field->attributeLabel,
									'empty' => Yii::t('app', "Select an option"),
										), $htmlOptions
								));

			case 'parentCase':
				$caseIds = Yii::app()->db->createCommand()->select('id')->from('x2_services')->queryAll();
				$cases = array();

				foreach ($caseIds as $c) {
					$cases[$c['id']] = $c['id'];
				}
				unset($cases[$model->id]);

				return CHtml::activeDropDownList($this, $field->fieldName, $cases, array_merge(
										array(
									'title' => $field->attributeLabel,
									'empty' => Yii::t('app', ""),
										), $htmlOptions
								));

			case 'link':
				$linkSource = null;
				$linkId = '';

				if (class_exists($field->linkType)) {
					// if the field is an ID, look up the actual name
					if (isset($this->$fieldName) && ctype_digit($this->$fieldName)) {
						$linkModel = X2Model::model($field->linkType)->findByPk($this->$fieldName);
						if (isset($linkModel)) {
							$this->$fieldName = $linkModel->name;
							$linkId = $linkModel->id;
						} else {
							$this->$fieldName = '';
						}
					}
					$staticLinkModel = X2Model::model($field->linkType);

					if (array_key_exists('X2LinkableBehavior', $staticLinkModel->behaviors()))
						$linkSource = Yii::app()->controller->createUrl($staticLinkModel->autoCompleteSource);

					/* $count = $staticLinkModel->count();
					  if($count <= 50) {
					  $names = array(''=>'');
					  $data =	Yii::app()->db->createCommand()
					  ->select('id,name')
					  ->from($staticLinkModel->tableName())
					  ->order('name ASC')
					  ->queryAll();

					  foreach($data as $row)
					  $names[$row['id']] = $row['name'];
					  return CHtml::dropDownList($field->modelName.'['.$fieldName.']',$linkId,$names);
					  } */
				}

				return CHtml::hiddenField($field->modelName . '[' . $fieldName . '_id]', $linkId, array('id' => $field->modelName . '_' . $fieldName . "_id"))
						. Yii::app()->controller->widget('zii.widgets.jui.CJuiAutoComplete', array(
							'model' => $this,
							'attribute' => $fieldName,
							// 'name'=>'autoselect_'.$fieldName,
							'source' => $linkSource,
							'value' => $this->$fieldName,
							'options' => array(
								'minLength' => '1',
								'select' => 'js:function( event, ui ) {
								$("#' . $field->modelName . '_' . $fieldName . '_id").val(ui.item.id);
								$(this).val(ui.item.value);
								return false;
							}',
								'create' =>
								$field->linkType == 'Contacts' ?
										'js:function(event, ui) {
									$(this).data( "autocomplete" )._renderItem = function( ul, item ) {
										var label = "<a style=\"line-height: 1;\">" + item.label;
										
										label += "<span style=\"font-size: 0.6em;\">";
										
										// add email if defined
										if(item.email) {
											label += "<br>";
											label += item.email;
										}
										
										if(item.city || item.state || item.country || item.email) {
										    label += "<br>";
										
											if(item.email) {
											
											}
											
										    if(item.city) {
										    	label += item.city;
										    }
										    
										    if(item.state) {
										    	if(item.city) {
										    		label += ", ";
										    	}
										    	label += item.state;
										    }
										    
										    if(item.country) {
										    	if(item.city || item.state) {
										    		label += ", ";
										    	}
										    	label += item.country;
										    }
										}
										
										label += "<br>" + item.assignedTo;
										label += "</span>";
										label += "</a>";
										
        							    return $( "<li>" )
        							        .data( "item.autocomplete", item )
        							        .append( label )
        							        .appendTo( ul );
        							};	
								}' :
										"",
							),
							'htmlOptions' => array_merge(array(
								'title' => $field->attributeLabel,
									), $htmlOptions)
								), true);

			case $field->type == 'rating':
				return Yii::app()->controller->widget('CStarRating', array(
							'model' => $this,
							'attribute' => $field->fieldName,
							'minRating' => 1, //minimal value
							'maxRating' => 5, //max value
							'starCount' => 5, //number of stars
							'cssFile' => Yii::app()->theme->getBaseUrl() . '/css/rating/jquery.rating.css',
							'htmlOptions' => $htmlOptions
								), true);

			case 'boolean':
				return '<div class="checkboxWrapper">'
						. CHtml::activeCheckBox($this, $field->fieldName, array_merge(array(
									'unchecked' => 0,
									'title' => $field->attributeLabel,
										), $htmlOptions)) . '</div>';

			case 'assignment':

				$group = is_numeric($this->$fieldName);
				// if(is_numeric($this->assignedTo)){
				// $group=true;
				// $groups=Groups::getNames();
				// }else{
				// $group=false;
				// }
				if (is_array($this[$fieldName]))
					$this[$fieldName] = implode(', ', $this[$fieldName]);

				if (empty($this->$fieldName))
					$this->$fieldName = Yii::app()->user->getName();
				return CHtml::activeDropDownList($this, $fieldName, $group ? Groups::getNames() : User::getNames(), array_merge(array(
									// 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
									// 'disabled'=>$item['readOnly']? 'disabled' : null,
									'title' => $field->attributeLabel,
									'id' => $field->modelName . '_' . $fieldName . '_assignedToDropdown',
									'multiple' => ($field->linkType == 'multiple' ? 'multiple' : null),
										), $htmlOptions))
						/* x2temp */
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
										'data' => 'js:{checked: $(this).attr("checked")=="checked", field:"' . $this->$fieldName . '"}',
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
			/* end x2temp */

			// case 'association':
			// if($field->linkType!='multiple') {
			// return CHtml::activeDropDownList($this, $fieldName, $contacts,array_merge(array(
			// 'title'=>$field->attributeLabel,
			// ),$htmlOptions));
			// } else {
			// return CHtml::activeListBox($this, $fieldName, $contacts,array_merge(array(
			// 'title'=>$field->attributeLabel,
			// 'multiple'=>'multiple',
			// ),$htmlOptions));
			// }
			case 'optionalAssignment': // optional assignment for users (can be left blank)

				$users = User::getNames();
				unset($users['Anyone']);

				return CHtml::activeDropDownList($this, $fieldName, $users, array_merge(array(
									// 'tabindex'=>isset($item['tabindex'])? $item['tabindex'] : null,
									// 'disabled'=>$item['readOnly']? 'disabled' : null,
									'title' => $field->attributeLabel,
									'empty' => Yii::t('app', ""),
										), $htmlOptions));

			case 'visibility':
				return CHtml::activeDropDownList($this, $field->fieldName, array(1 => 'Public', 0 => 'Private', 2 => 'User\'s Groups'), array_merge(array(
									'title' => $field->attributeLabel,
									'id' => $field->modelName . "_visibility",
										), $htmlOptions));

			// 'varchar', 'email', 'url', 'int', 'float', 'currency', 'phone'
			// case 'int':
			// return CHtml::activeNumberField($this, $field->fieldNamearray_merge(array(
			// 'title' => $field->attributeLabel,
			// ), $htmlOptions));

			case 'percentage':
				$htmlOptions['class'] = empty($htmlOptions['class'])?'input-percentage':$htmlOptions['class'].' input-percentage';
				return CHtml::activeTextField($this, $field->fieldName, array_merge(array(
									'title' => $field->attributeLabel,
										), $htmlOptions));
				
			default:
				return CHtml::activeTextField($this, $field->fieldName, array_merge(array(
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
	 * Sets attributes using X2Fields
	 * @param array &$data array of attributes to be set (eg. $_POST['Contacts'])
	 */
	public function setX2Fields(&$data) {
		foreach (self::$_fields[$this->tableName()] as &$_field) { // loop through fields to deal with special types
			$fieldName = $_field->fieldName;

			if ($_field->readOnly || !isset($data[$fieldName]))  // skip fields that are read-only or haven't been set
				continue;

			$value = $data[$fieldName];
			if ($value == $this->getAttributeLabel($fieldName)) // eliminate placeholder values
				$value = '';

			if ($_field->type == 'assignment' && $_field->linkType == 'multiple') {
				$value = Accounts::parseUsers($value);
			} elseif ($_field->type == 'date') {
				$value = Yii::app()->controller->parseDate($value);
				if ($value === false)
					$value = null;
			}elseif ($_field->type == 'dateTime') {
				$value = Yii::app()->controller->parseDateTime($value);
				if ($value === false)
					$value = null;
			}elseif ($_field->type == 'link' && !empty($_field->linkType)) {
				$modelType = ucfirst($_field->linkType);

				if (!empty($value)) {
					$linkId = isset($data[$fieldName . '_id']) ? $data[$fieldName . '_id'] : false;
					$linkModel = X2Model::model($modelType)->findByPk($linkId);
					// if the ID is sent, try to load the model
					if (ctype_digit($linkId) && isset($linkModel) && $linkModel->name === $value) {  // if the model exists, make sure the name matches 
						$value = $linkId;  // and use the ID as the field value if it does
					} else {
						$linkModel = X2Model::model($modelType)->findByAttributes(array('name' => $value));
						// otherwise, if the field is a string, try to find the ID based on the name
						if (isset($linkModel)) { // look in name field
							$value = $linkModel->id;
						} elseif ($modelType === 'Contacts') { // if it's a contact, we can also try firstName + lastName
							$fullName = explode(' ', $value);
							if (count($fullName) === 2 && $linkModel = X2Model::model('Contacts')->findByAttributes(array(
								'firstName' => $fullName[0],
								'lastName' => $fullName[1])))
								$value = $linkModel->id;
						}
					}
				}
			}elseif (in_array($_field->type, array('int', 'float', 'currency', 'percentage'))) {
				$value = self::strToNumeric($value, $_field->type);
			}

			if (!empty($value) && !is_bool($value)) {
				$this->$fieldName = trim($value);
			} else {
				$this->$fieldName = $value;
			}
		}
	}

	/**
	 * Base search function, includes Retrieves a list of models based on the current search/filter conditions.
	 * @param CDbCriteria $criteria the attribute name
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function searchBase($criteria) {
		$this->compareAttributes($criteria);

		return new SmartDataProvider(get_class($this), array(
					'sort' => array(
						'defaultOrder' => 'lastUpdated DESC, id DESC',
					),
					'pagination' => array(
						'pageSize' => ProfileChild::getResultsPerPage(),
					),
					'criteria' => $criteria,
				));
	}

	public function compareAttributes(&$criteria) {
		foreach (self::$_fields[$this->tableName()] as &$field) {
			$fieldName = $field->fieldName;
			switch ($field->type) {
				case 'boolean':
					$criteria->compare($fieldName, $this->compareBoolean($this->$fieldName), true);
					break;
				case 'link':
					$criteria->compare($fieldName, $this->compareLookup($field->linkType, $this->$fieldName), true);
					$criteria->compare($fieldName, $this->$fieldName, true, 'OR');
					break;
				case 'assignment':
					$criteria->compare($fieldName, $this->compareAssignment($this->$fieldName), true);
					break;
				case 'phone':
				// $criteria->join .= ' RIGHT JOIN x2_phone_numbers ON (x2_phone_numbers.itemId=t.id AND x2_tags.type="Contacts" AND ('.$tagConditions.'))';
				default:
					$criteria->compare($fieldName, $this->$fieldName, true);
			}
		}
	}

	protected function compareLookup($linkType, $value) {
		if (is_null($value) || $value == '')
			return null;

		$linkType = ucfirst($linkType);

		if (class_exists($linkType)) {
			$class = new $linkType;
			$tableName = $class->tableName();

			if ($linkType == 'Contacts')
				$linkIds = Yii::app()->db->createCommand()->select('id')->from($tableName)->where(array('like', 'CONCAT(firstName," ",lastName)', "%$value%"))->queryColumn();
			else
				$linkIds = Yii::app()->db->createCommand()->select('id')->from($tableName)->where(array('like', 'name', "%$value%"))->queryColumn();

			return empty($linkIds) ? -1 : $linkIds;
		}
		return -1;
	}

	protected function compareBoolean($data) {
		if (is_null($data) || $data == '')
			return null;

		return in_array(mb_strtolower(trim($data)), array(0, 'f', 'false', Yii::t('actions', 'No'))) ? 0 : 1;  // default to true unless recognized as false
	}

	protected function compareAssignment($data) {
		if (is_null($data) || $data == '')
			return null;
		$userNames = Yii::app()->db->createCommand()->select('username')->from('x2_users')->where(array('like', 'CONCAT(firstName," ",lastName)', "%$data%"))->queryColumn();
		$groupIds = Yii::app()->db->createCommand()->select('id')->from('x2_groups')->where(array('like', 'name', "%$data%"))->queryColumn();

		return (count($groupIds) + count($userNames) == 0) ? -1 : $userNames + $groupIds;
	}

	/**
	 * Attempts to load the model with the given ID, if the current 
	 * user passes authentication checks. Throws an exception if not.
	 * @param Integer $d The ID of the model to load
	 * @return mixed The model object
	 */
	/* 	public static function load($modelName,$id) {
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
	 * Returns a CDbCriteria containing record-level access conditions.
	 * @return CDbCriteria
	 */
	public function getAccessCriteria() {
		$criteria = new CDbCriteria;

		$accessLevel = $this->getAccessLevel();

		if ($this->hasAttribute('visibility')) {
			$visFlag = true;
		} else {
			$visFlag = false;
		}

		$criteria->addCondition(X2Model::getAccessConditions($accessLevel, $visFlag), 'AND');

		return $criteria;
	}

	/**
	 * Returns a number from 0 to 3 representing the current user's access level using the Yii auth manager
	 * Assumes authItem naming scheme like "ContactsViewPrivate", etc.
	 * This method probably ought to overridden, as there is no reliable way to determine the module a model "belongs" to.
	 * @return integer The access level. 0=no access, 1=own records, 2=public records, 3=full access
	 */
	public function getAccessLevel() {

		if (!is_null($this->baseRoute)) {
			$module = ucfirst(substr($this->baseRoute, 1));
		} else {
			$module = ucfirst(get_class($this));
		}

		if (Yii::app()->user->checkAccess($module . 'Admin'))
			return 3;
		elseif (Yii::app()->user->checkAccess($module . 'View'))
			return 2;
		elseif (Yii::app()->user->checkAccess($module . 'PrivateReadOnlyAccess'))
			return 1;
		else
			return 0;
	}

	/**
	 * Generates SQL condition to filter out records the user doesn't have permission to see.
	 * This method is used by the 'accessControl' filter.
	 * @param Integer $accessLevel The user's access level. 0=no access, 1=own records, 2=public records, 3=full access
	 * @param Boolean $useVisibility Whether to consider the model's visibility setting
	 * @param String $user The username to use in these checks (defaults to current user)
	 * @return String The SQL conditions
	 */
	public static function getAccessConditions($accessLevel, $useVisibility = true, $user = null) {
		if ($user === null)
			$user = Yii::app()->user->getName();

		if ($accessLevel === 2 && $useVisibility === false) // level 2 access only works if we consider visibility,
			$accessLevel = 3;  // so upgrade to full access

		switch ($accessLevel) {
			case 3:  // user can view everything
				return 'TRUE';
			case 1:  // user can view records they (or one of their groups) own
				return 't.assignedTo="' . $user . '"
					OR t.assignedTo IN (SELECT groupId FROM x2_group_to_user WHERE username="' . $user . '")';
			case 2:  // user can view any public (shared) record
				return 't.visibility=1
					OR t.assignedTo="' . $user . '"
					OR t.assignedTo IN (SELECT groupId FROM x2_group_to_user WHERE username="' . $user . '")
					OR (t.visibility=2 AND t.assignedTo IN (SELECT DISTINCT b.username FROM x2_group_to_user a INNER JOIN x2_group_to_user b ON a.groupId=b.groupId WHERE a.username="' . $user . '"))';
			default:
			case 0:  // can't view anything
				return 'FALSE';
		}
	}

	public static function strToNumeric($input, $type) {
		$value = trim($input, ' %' . Yii::app()->locale->getCurrencySymbol(Yii::app()->params->admin->currency));
		if($value === null || $value === '')
			return null;
		else if (!in_array($type, array('int', 'currency', 'float', 'percentage')))
			throw new CException("Invalid numeric type \"$type\"");
		else if (!preg_match('/\d+\.?\d*e?[\+\-]?\d*/', $value))
			throw new CException("Invalid number format for $type: \"$value\"");
		switch ($type) {
			case 'int':
				return (int) $value;
			case 'float':
				return (float) $value;
			case 'currency':
				return (float) $value;
			case 'percentage':
				return ((float) $value);
			default:
				return (float) $value;
		}
	}

}
