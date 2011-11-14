<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiController
 *
 * @author Jake
 */
class ApiController extends x2base {
    
      // Members
    /**
     * Key which has to be in HTTP USERNAME and PASSWORD headers 
     */
    Const APPLICATION_ID = 'ASCCPE';
    public $modelClass="";
 
    /**
     * Default response format
     * either 'json' or 'xml'
     */
    private $format = 'json';
    /**
     * @return array action filters
     */
    public function filters()
    {
            return array();
    }
    
    public function actionCreate()
    {
        switch($_GET['model'])
        {
            // Get an instance of the respective model
            case 'Contacts':
                $model = new ContactChild;   
                $this->modelClass="ContactChild";
                $temp=$model->attributes;
                break;
            case 'Actions':
                $model = new ActionChild;   
                $this->modelClass="ActionChild";
                $temp=$model->attributes;
                break;
            case 'Accounts':
                $model = new AccountChild;   
                $this->modelClass="AccountChild";
                $temp=$model->attributes;
                break;
            default:
                $this->_sendResponse(501, 
                    sprintf('Mode <b>create</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                    exit;
        }
        // Try to assign POST values to attributes
        foreach($_POST as $var=>$value) {
            // Does the model have this attribute? If not raise an error
            if($model->hasAttribute($var))
                $model->$var = $value;
            else
                $this->_sendResponse(500, 
                    sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var,
                    $_GET['model']) );
        }
        
        switch($_GET['model'])
        {
            // Get an instance of the respective model
            case 'Contacts':
                Yii::import("application.controllers.ContactsController");
                $controller=new ContactsController('ContactsController');
                if($controller->create($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> was created with name <b>%s</b>',
                            $_GET['model'],$model->firstName." ".$model->lastName) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            case 'Accounts':
                Yii::import("application.controllers.AccountsController");
                $controller=new AccountsController('AccountsController');
                if($controller->create($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> was created with name <b>%s</b>',
                            $_GET['model'],$model->name) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            case 'Actions':
                Yii::import("application.controllers.ActionsController");
                $controller=new ActionsController('ActionsController');
                if($controller->create($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> was created with description <b>%s</b>',
                            $_GET['model'],$model->actionDescription) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            default:
                $this->_sendResponse(501, 
                    sprintf('Mode <b>create</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                    exit;
        }
        
    }
    
    public function actionUpdate()
    {

        switch($_GET['model'])
        {
            // Find respective model
            case 'Contacts':
                $model = ContactChild::model()->findByPk($_GET['id']);      
                $this->modelClass="ContactChild";
                $temp=$model->attributes;
                break;
            case 'Actions':
                $model = ActionChild::model()->findByPk($_GET['id']);    
                $this->modelClass="ActionChild";
                $temp=$model->attributes;
                break;
            case 'Accounts':
                $model = AccountChild::model()->findByPk($_GET['id']);     
                $this->modelClass="AccountChild";
                $temp=$model->attributes;
                break;
            default:
                $this->_sendResponse(501, 
                    sprintf( 'Error: Mode <b>update</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                exit;
        }
        // Did we find the requested model? If not, raise an error
        if(is_null($model))
            $this->_sendResponse(400, 
                    sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );

        // Try to assign PUT parameters to attributes
        foreach($_POST as $var=>$value) {
            // Does the model have this attribute? If not raise an error
            if($model->hasAttribute($var))
                $model->$var = $value;
            else
                $this->_sendResponse(500, 
                    sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var,
                    $_GET['model']) );
        }
        // Try to save the model
        switch($_GET['model'])
        {
            // Get an instance of the respective model
            case 'Contacts':
                Yii::import("application.controllers.ContactsController");
                $controller=new ContactsController('ContactsController');
                if($controller->create($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> was updated with name <b>%s</b>',
                            $_GET['model'],$model->firstName." ".$model->lastName) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't update model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            case 'Accounts':
                Yii::import("application.controllers.AccountsController");
                $controller=new AccountsController('AccountsController');
                if($controller->update($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> with ID <b>%s</b> was updated.',
                            $_GET['model'],$model->id) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't update model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            case 'Actions':
                Yii::import("application.controllers.ActionsController");
                $controller=new ActionsController('ActionsController');
                if($controller->update($model,$temp,'1')){
                    $this->_sendResponse(200, 
                        sprintf('Model <b>%s</b> with ID <b>%s</b> was updated.',
                            $_GET['model'],$model->id) );  
                } else {
                    // Errors occurred
                    $msg = "<h1>Error</h1>";
                    $msg .= sprintf("Couldn't update model <b>%s</b>", $_GET['model']);
                    $msg .= "<ul>";
                    foreach($model->errors as $attribute=>$attr_errors) {
                        $msg .= "<li>Attribute: $attribute</li>";
                        $msg .= "<ul>";
                        foreach($attr_errors as $attr_error)
                            $msg .= "<li>$attr_error</li>";
                        $msg .= "</ul>";
                    }
                    $msg .= "</ul>";
                    $this->_sendResponse(500, $msg );
                }
                break;
            default:
                $this->_sendResponse(501, 
                    sprintf('Mode <b>create</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                    exit;
        }
    }
    
    public function actionView()
    {
        // Check if id was submitted via GET
        if(!isset($_GET['id']))
            $this->_sendResponse(500, 'Error: Parameter <b>id</b> is missing' );

        switch($_GET['model'])
        {
            // Find respective model    
            case 'Contacts':
                $model = ContactChild::model()->findByPk($_GET['id']);
                break;
            case 'Actions':
                $model = ActionChild::model()->findByPk($_GET['id']);
                break;
            case 'Accounts':
                $model = AccountChild::model()->findByPk($_GET['id']);
                break;
            default:
                $this->_sendResponse(501, sprintf(
                    'Mode <b>view</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                exit;
        }
        // Did we find the requested model? If not, raise an error
        if(is_null($model))
            $this->_sendResponse(404, 'No Item found with id '.$_GET['id']);
        else
            $this->_sendResponse(200, CJSON::encode($model->attributes));
    }
    
    public function actionLookup(){

        switch($_GET['model'])
        {
            // Find respective model    
            case 'Contacts':
                $attributes=array();
                if(isset($_GET['firstName']))
                    $attributes['firstName']=$_GET['firstName'];
                if(isset($_GET['lastName']))
                    $attributes['lastName']=$_GET['lastName'];
                if(isset($_GET['email']))
                    $attributes['email']=$_GET['email'];
                $model = ContactChild::model()->findByAttributes($attributes);
                break;
            default:
                $this->_sendResponse(501, sprintf(
                    'Mode <b>view</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                exit;
        }
        // Did we find the requested model? If not, raise an error
        if(is_null($model))
            $this->_sendResponse(404, 'No Item found with specified attributes.');
        else
            $this->_sendResponse(200, CJSON::encode($model->attributes));
    }
    
    public function actionDelete()
    {
        switch($_GET['model'])
        {
            // Load the respective model
            case 'Contacts':
                $model = ContactChild::model()->findByPk($_GET['id']);                    
                break;
            case 'Actions':
                $model = ActionChild::model()->findByPk($_GET['id']);
                break;
            case 'Accounts':
                $model = AccountChild::model()->findByPk($_GET['id']);
                break;
            default:
                $this->_sendResponse(501, 
                    sprintf('Error: Mode <b>delete</b> is not implemented for model <b>%s</b>',
                    $_GET['model']) );
                exit;
        }
        // Was a model found? If not, raise an error
        if(is_null($model))
            $this->_sendResponse(400, 
                    sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );

        // Delete the model
        $num = $model->delete();
        if($num>0)
            $this->_sendResponse(200, 
                    sprintf("Model <b>%s</b> with ID <b>%s</b> has been deleted.",
                    $_GET['model'], $_GET['id']) );
        else
            $this->_sendResponse(500, 
                    sprintf("Error: Couldn't delete model <b>%s</b> with ID <b>%s</b>.",
                    $_GET['model'], $_GET['id']) );
    }
    

    private function _sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if($body != '')
        {
            // send the body
            echo $body;
            exit;
        }
        // we need to create the body if none is passed
        else
        {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch($status)
            {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on 
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templated in a real-world solution
            $body = '
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
    </head>
    <body>
        <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
        <p>' . $message . '</p>
        <hr />
        <address>' . $signature . '</address>
    </body>
    </html>';

            echo $body;
            exit;
        }
    }
    private function _getStatusCodeMessage($status)
    {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}
?>
