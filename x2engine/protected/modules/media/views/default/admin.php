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
?>

<?php
$this->menu=array(
	array('label'=>Yii::t('media', 'List')),
	array('label'=>Yii::t('media', 'Upload'), 'url'=>array('upload')),
);
?>

<h1><?php echo Yii::t('media','Media'); ?></h1>
<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'media-grid',
	'template'=>'{summary}{items}{pager}',
	'dataProvider' => $model->searchAdmin(),
	'summaryText' => Yii::t('app','Displaying {start}-{end} of {count} result(s).') . '<br />'
		. '<div class="form" style="border:none; margin: 0; padding: 2px 3px; display: inline-block; vertical-align: middle;"> '
		. CHtml::dropDownList('resultsPerPage', Profile::getResultsPerPage(), Profile::getPossibleResultsPerPage(), array(
		    	'ajax' => array(
		    		'url' => $this->createUrl('/profile/setResultsPerPage'),
		    		'complete' => "function(response) { $.fn.yiiGridView.update('media-grid', {data: {'id_page': 1}}) }",
		    		'data' => "js: {results: $(this).val()}",
		    	),
		    	'style' => 'margin: 0;',
		    ))
		. ' </div>'
		. Yii::t('app', 'results per page.'),
	'baseScriptUrl'=>Yii::app()->request->baseUrl.'/themes/'.Yii::app()->theme->name.'/css/gridview',
	'filter'=>$model,
	'columns' => array(
		array(
			'name' => 'fileName',
			'header' => Yii::t('media','File Name'),
			'type' => 'raw',
			'value' => 'CHtml::link(CHtml::encode($data["fileName"]), array("view","id"=>$data->id))', 
		),
		array(
			'name' => 'uploadedBy',
			'header' => Yii::t('media','Uploaded By'),
			'type' => 'raw',
			'value' => 'User::getUserLinks($data["uploadedBy"])'
		),
		array(
			'name' => 'associationType',
			'header' => Yii::t('media','Association'),
			'type' => 'raw',
			'value' => '$data["associationType"] == "contacts"? X2Model::getModelLink($data->associationId, "contacts") : $data->associationType'
		),
		array(
			'name' => 'createDate',
			'header' => Yii::t('media','Create Date'),
			'type' => 'raw',
			'value' => 'Yii::app()->controller->formatLongDate($data->createDate)'
		),
	),
));
?>