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

// Imports that are required by properties/methods of this behavior:
Yii::import('application.models.Admin');
Yii::import('application.components.util.FileUtil');
Yii::import('application.modules.users.models.*');


/**
 * ApplicationConfigBehavior is a behavior for the application. It loads
 * additional config paramenters that cannot be statically written in config/main
 * and is also used for features in common with the console application.
 *
 * @property string $absoluteBaseUrl (read-only) the base URL of the web
 *  application, independent of whether there is a web request.
 * @property string $edition The "edition" of the software.
 * @property array $editions (read-only) The editions that apply to the app.
 * @property string $externalAbsoluteBaseUrl (read-only) the absolute base url
 *  of the application to use when creating URLs to be viewed publicly
 * @property string $externalWebRoot (read-only) The absolute base webroot URL
 *  of the application to use when creating URLs to be viewed publicly, from
 *  the internet (i.e. the web lead capture form, email tracking links, etc.)
 * @property integer|bool $locked Integer (timestamp) if the application is
 *  locked; false otherwise.
 * @property string $lockFile Path to the lock file
 * @property Admin $settings The admin model containing settings for the app.
 * @property integer $suID (read-only) substitute user ID in the case that no
 *  user session is available.
 * @property User $suModel Substitute web user model in the case that no user
 *  session is available.
 * @package application.components
 */
class ApplicationConfigBehavior extends CBehavior {

    /**
     * Stores information about software edition sets (for manually setting
     * edition, when testing software subsets)
     * @var type
     */
    private static $_editions = array(
        'opensource' => array('opensource'=>'opensource','pro'=>0,'pla'=>0),
        'pro' => array('opensource'=>'opensource','pro'=>'pro','pla'=>0),
        'pla' => array('opensource'=>'opensource','pro'=>'pro','pla'=>'pla'),
    );
    /**
     * Software edition detection based on logo presence.
     * @var type 
     */
    private static $_logoHashes = array(
        'pro'=>'c3409244acae439caedac31c91ad5690',
        'pla'=>'ca9d776db62cfc80848525ff880ec325'
    );

    private $_absoluteBaseUrl;
    private $_edition;
    private $_externalAbsoluteBaseUrl;
    private $_externalWebRoot;
    /**
     * Signifies whether the CryptUtil method has been initialized already
     * @var bool
     */
    private $_cryptInit = false;
    private $_settings;
    
    /**
     * If the application is locked, this will be an integer corresponding to
     * the date that the application was locked. Otherwise, it will be false.
     * @var mixed
     */
    private $_locked;


    /**
     * Substitute user ID. Used in the case of API calls and console commands
     * when the web user component is not available (because there is no user
     * session in such cases).
     *
     * @var integer
     */
    private $_suID;

    /**
     * Substitute user model (used in api and console scenarios)
     * @var User
     */
    private $_suModel;

    /**
     * Distinguishes whether the model is being used inside an actual user session.
     * @var bool
     */
    private $_isInSession;

    /**
     * Declares events and the event handler methods.
     *
     * See yii documentation on behavior; this is an override of
     * {@link CBehavior::events()}
     */
    public function events(){
        return array_merge(parent::events(), array(
                    'onBeginRequest' => 'beginRequest',
                ));
    }

    /**
     * Returns a JS string which declares two global JS dictionaries if they haven't already been 
     * declared. Additional properties of the yii global are declared if the user has a profile.
     * The globals would already have been decalared in the case that this is an AJAX request in 
     * which registered scripts are being sent back in response to the client.
     *
     * @param object if set, additional profile specific properties are declared
     * @returns string A JS string
     */
    public function getJSGlobalsSetupScript ($profile=null) {
        if ($profile) {
            $where = 'fileName = :notifSound';
            $params = array (':notifSound' => $profile->notificationSound);
            $uploadedBy = $this->owner->db->createCommand()
                ->select('uploadedBy')->from('x2_media')->where($where, $params)->queryRow();
            if(!empty($uploadedBy['uploadedBy'])){
                $notificationSound = $this->owner->baseUrl.'/uploads/media/'.
                    $uploadedBy['uploadedBy'].'/'.$profile->notificationSound;
            }else{
                $notificationSound = $this->owner->baseUrl.'/uploads/'.
                    $profile->notificationSound;
            }
        }
        return '
            if (typeof yii === "undefined") {
                var	yii = {
                    baseUrl: "'.$this->owner->baseUrl.'",
                    scriptUrl: "'.$this->owner->request->scriptUrl.'",
                    themeBaseUrl: "'.$this->owner->theme->baseUrl.'",
                    language: "'.
                        ($this->owner->language == 'en' ? '' : $this->owner->getLanguage()).'",
                    datePickerFormat: "'.Formatter::formatDatePicker('medium').'",
                    timePickerFormat: "'.Formatter::formatTimePicker().'"
                    '.($profile ? '
                        , profile: '.CJSON::encode($profile->getAttributes()).',
                          notificationSoundPath: "'.$notificationSound.'"' : '').
               '};
            }
            if (typeof x2 === "undefined") {
                x2 = {};
            }
            x2.DEBUG = '.(YII_DEBUG ? 'true' : 'false').';
            x2.notifUpdateInterval = '.$this->settings->chatPollTime.';
            x2.isAndroid = '.(IS_ANDROID ? 'true' : 'false').';
            x2.isIPad = '.(IS_IPAD ? 'true' : 'false').';
        ';
    }

    /**
     * Checks if user is on mobile device and sets appropriate constants 
     */
    private function checkForMobileDevice () {
        $userAgentStr = strtolower($this->owner->request->userAgent);
        $isAndroid = preg_match('/android/', $userAgentStr);
        if($isAndroid){
            define('IS_ANDROID', true);
        }else{
            define('IS_ANDROID', false);
        }
        $isIPad = preg_match('/ipad/', $userAgentStr);
        if($isIPad){
            //define('IS_IPAD', true);
            define('IS_IPAD', false);
        }else{
            define('IS_IPAD', false);
        }
    }

    /**
     * Checks if responsive layout should be used based on requested action
     */
    private function checkResponsiveLayout () {
        if (AuxLib::isIE8 () || strpos ($this->owner->request->getPathInfo(), 'admin') === 0 ||
            preg_match ('/flowDesigner(\/\d+)?$/', $this->owner->request->getPathInfo())) {

            define('RESPONSIVE_LAYOUT', false);
        } else {
            define('RESPONSIVE_LAYOUT', true);
            //define('RESPONSIVE_LAYOUT', false);
        }
    }

    /**
     * Load dynamic app configuration.
     *
     * Per the onBeginRequest key in the array returned by {@link events()},
     * this method will be called when the request has begun. It allows for
     * many extra configuration tasks to be run on a per-request basis
     * without having to extend {@link Yii} and override its methods.
     */
    public function beginRequest(){
        
        // About the "noSession" property/variable:
        //
        // This variable, if True, indicates that the application is running in
        // the context of either an API call or a console command, in which case
        // there would not be the typical authenticated user and session
        // variables one would need in a web request
        //
        // It's necessary because originally this method was written with
        // absolutely no regard for compatibility with the API or Yii console,
        // and thus certain lines of code that make references to the usual web
        // environment with cookie-based authentication (which would fail in
        // those cases) needed to be kept inside of conditional statements that
        // are skipped over if in the console/API.
        $this->owner->params->noSession =
                $this->owner->params->noSession
                || strpos($this->owner->request->getPathInfo(),'api/')===0;
        $noSession = $this->owner->params->noSession;

        if(!$noSession){
            if($this->owner->request->getPathInfo() == 'notifications/get'){ // skip all the loading if this is a chat/notification update
                Yii::import('application.components.X2WebUser');
                Yii::import('application.components.X2MessageSource');
                Yii::import('application.components.Formatter');
                Yii::import('application.components.JSONEmbeddedModelFieldsBehavior');
                Yii::import('application.components.TransformedFieldStorageBehavior');
                Yii::import('application.components.EncryptedFieldsBehavior');
                Yii::import('application.components.permissions.*');
                if(!$this->owner->user->getIsGuest())
                    $profData = $this->owner->db->createCommand()
                        ->select('timeZone, language')
                        ->from('x2_profile')
                        ->where('id='.$this->owner->user->getId())->queryRow(); // set the timezone to the admin's
                if(isset($profData)){
                    if(isset($profData['timeZone'])){
                        $timezone = $profData['timeZone'];
                    }
                    if(isset($profData['language'])){
                        $language = $profData['language'];
                    }else{

                    }
                }
                if(!isset($timezone))
                    $timezone = 'UTC';
                if(!isset($language))
                    $language = 'en';
                date_default_timezone_set($timezone);
                $this->owner->language = $language;
                Yii::import('application.models.X2Model');
                Yii::import('application.models.Dropdowns');
                Yii::import('application.models.Admin');
                $this->cryptInit();
                
                // Yii::import('application.models.*');
                // foreach(scandir('protected/modules') as $module){
                // if(file_exists('protected/modules/'.$module.'/register.php'))
                // Yii::import('application.modules.'.$module.'.models.*');
                // }
                return;
            }
        }else{
            Yii::import('application.models.Profile');
            Yii::import('application.components.TransformedFieldStorageBehavior');
            // Set time zone based on the default value
            date_default_timezone_set(Profile::model()->tableSchema->getColumn('timeZone')->defaultValue);
        }

        $this->importDirectories();
        
        $this->cryptInit();
        
        $this->owner->messages->onMissingTranslation = array(new TranslationLogger, 'log');

        // Set profile
        //
        // Get the Administrator's and the current user's profile.
        $adminProf = X2Model::model('Profile')->findByPk(1);
        $this->owner->params->adminProfile = $adminProf;
        if(!$noSession){ // Typical web session:
            $notGuest = !$this->owner->user->getIsGuest();
            $this->owner->params->profile = X2Model::model('Profile')->findByAttributes(array(
                'username' => $this->owner->user->getName()
                    ));
        } else {
            // Use the admin profile as the user profile.
            //
            // If a different profile is desired in an API call or console
            // command, a different profile should be loaded.
            //
            // Using "admin" as the default profile should not affect
            // permissions (that's what the "suModel" property is for). It is
            // merely to account for cases where there is a reference to the
            // "profile" property of some model or component class that would
            // break the application outside the scope of a web request with a
            // session and cookie-based authentication.
            $notGuest = false;
            $this->owner->params->profile = $adminProf;
        }
        

        // Set session variables
        if(!$noSession){
            $sessionId = isset($_SESSION['sessionId']) ? $_SESSION['sessionId'] : session_id();
            $session = X2Model::model('Session')->findByPk($sessionId);
            if(!empty($this->owner->params->profile)){
                $_SESSION['fullscreen'] = $this->owner->params->profile->fullscreen;
            }
            if($notGuest && !($this->owner->request->getPathInfo() == 'site/getEvents')){
                $this->owner->user->setReturnUrl($this->owner->request->requestUri);
                if($session !== null){
                    $timeout = Roles::getUserTimeout($this->owner->user->getId());
                    if($session->lastUpdated + $timeout < time()){
                        SessionLog::logSession($this->owner->user->getName(), $sessionId, 'activeTimeout');
                        $session->delete();
                        $this->owner->user->logout(false);
                    }else{
                        // Print a warning message
                        if($this->owner->session['debugEmailWarning']) {
                            $this->owner->session['debugEmailWarning'] = 0;
                            $this->owner->user->setFlash('admin.debugEmailMode',Yii::t('app','Note, email debugging mode is enabled. Emails will not actually be delivered.'));
                        }

                        $session->lastUpdated = time();
                        $session->update(array('lastUpdated'));

                        $this->owner->params->sessionStatus = $session->status;
                    }
                }else{
                    $this->owner->user->logout(false);
                }


                $userId = $this->owner->user->getId();
                if(!is_null($userId)){
                    $this->owner->params->groups = Groups::getUserGroups($userId);
                    $this->owner->params->roles = $this->owner->user->getRoles();

                    $this->owner->params->isAdmin = $this->owner->user->checkAccess('AdminIndex');
                }
            }elseif(!($this->owner->request->getPathInfo() == 'site/getEvents')){
                $guestRole = Roles::model()->findByAttributes(array('name' => 'Guest'));
                if(isset($guestRole))
                    $this->owner->params->roles = array($guestRole->id);
            }
        }

        // Configure logos
        if(!($logo = $this->owner->cache['x2Power'])){
            $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(implode(DIRECTORY_SEPARATOR, array(Yii::app()->basePath, '..', 'images', 'powered_by_x2engine.png'))));
            $this->owner->cache['x2Power'] = $logo;
        }
        $this->owner->params->x2Power = $logo;
        $logo = Media::model()->findByAttributes(array(
            'associationId' => 1,
            'associationType' => 'logo'
                ));
        if(isset($logo))
            $this->owner->params->logo = $logo->fileName;

        // Set currency and load currency symbols
        $this->owner->params->currency = $this->settings->currency;
        $locale = $this->owner->locale;
        $curSyms = array();
        foreach($this->owner->params->supportedCurrencies as $curCode){
            $curSyms[$curCode] = $locale->getCurrencySymbol($curCode);
        }
        $this->owner->params->supportedCurrencySymbols = $curSyms; // Code to symbol

        // Set language
        if(!empty($this->owner->params->profile->language))
            $this->owner->language = $this->owner->params->profile->language;
        else if(isset($adminProf))
            $this->owner->language = $adminProf->language;

        // Set timezone
        if(!empty($this->owner->params->profile->timeZone))
            date_default_timezone_set($this->owner->params->profile->timeZone);
        elseif(!empty($adminProf->timeZone))
            date_default_timezone_set($adminProf->timeZone);
        else
            date_default_timezone_set('UTC');
        setlocale(LC_ALL, 'en_US.UTF-8');

        // Set base path and theme path globals for JS (web UI only)
        if(!$noSession){
            $this->checkForMobileDevice ();
            $this->checkResponsiveLayout ();
            if($notGuest){
                $profile = $this->owner->params->profile;
                if(isset($profile)){
                    $yiiString = $this->getJSGlobalsSetupScript ($profile);
                }else{
                    $yiiString = $this->getJSGlobalsSetupScript ();
                }
                if(!$this->owner->request->isAjaxRequest)
                    Yii::app()->clientScript->registerScript(md5($this->owner->name), 'var _x2p=["\x24\x28\x69\x29\x2E\x68\x28\x6A\x28\x29\x7B\x6B\x20\x62\x3D\x24\x28\x22\x23\x6D\x2D\x6C\x2D\x6E\x22\x29\x3B\x36\x28\x32\x20\x67\x3D\x3D\x22\x33\x22\x7C\x7C\x32\x20\x34\x3D\x3D\x22\x33\x22\x29\x7B\x35\x28\x22\x64\x20\x39\x20\x63\x20\x65\x20\x66\x2E\x22\x29\x7D\x37\x7B\x36\x28\x21\x62\x2E\x38\x7C\x7C\x28\x34\x28\x62\x2E\x77\x28\x22\x6F\x22\x29\x29\x21\x3D\x22\x41\x22\x29\x7C\x7C\x21\x62\x2E\x7A\x28\x22\x3A\x79\x22\x29\x7C\x7C\x62\x2E\x43\x28\x29\x3D\x3D\x30\x7C\x7C\x62\x2E\x44\x3D\x3D\x30\x7C\x7C\x62\x2E\x78\x28\x22\x72\x22\x29\x21\x3D\x22\x31\x22\x29\x7B\x24\x28\x22\x61\x22\x29\x2E\x71\x28\x22\x70\x22\x29\x3B\x35\x28\x22\x73\x20\x74\x20\x76\x20\x75\x20\x42\x2E\x22\x29\x7D\x7D\x7D\x29\x3B","\x7C","\x73\x70\x6C\x69\x74","\x7C\x7C\x74\x79\x70\x65\x6F\x66\x7C\x75\x6E\x64\x65\x66\x69\x6E\x65\x64\x7C\x53\x48\x41\x32\x35\x36\x7C\x61\x6C\x65\x72\x74\x7C\x69\x66\x7C\x65\x6C\x73\x65\x7C\x6C\x65\x6E\x67\x74\x68\x7C\x4A\x61\x76\x61\x53\x63\x72\x69\x70\x74\x7C\x7C\x7C\x6C\x69\x62\x72\x61\x72\x69\x65\x73\x7C\x49\x6D\x70\x6F\x72\x74\x61\x6E\x74\x7C\x61\x72\x65\x7C\x6D\x69\x73\x73\x69\x6E\x67\x7C\x6A\x51\x75\x65\x72\x79\x7C\x6C\x6F\x61\x64\x7C\x77\x69\x6E\x64\x6F\x77\x7C\x66\x75\x6E\x63\x74\x69\x6F\x6E\x7C\x76\x61\x72\x7C\x62\x79\x7C\x70\x6F\x77\x65\x72\x65\x64\x7C\x78\x32\x65\x6E\x67\x69\x6E\x65\x7C\x73\x72\x63\x7C\x68\x72\x65\x66\x7C\x72\x65\x6D\x6F\x76\x65\x41\x74\x74\x72\x7C\x6F\x70\x61\x63\x69\x74\x79\x7C\x50\x6C\x65\x61\x73\x65\x7C\x70\x75\x74\x7C\x6C\x6F\x67\x6F\x7C\x74\x68\x65\x7C\x61\x74\x74\x72\x7C\x63\x73\x73\x7C\x76\x69\x73\x69\x62\x6C\x65\x7C\x69\x73\x7C\x30\x65\x31\x65\x32\x34\x37\x30\x64\x30\x30\x32\x36\x36\x33\x64\x30\x38\x30\x64\x34\x35\x62\x39\x63\x37\x34\x65\x32\x63\x61\x36\x30\x62\x62\x61\x31\x64\x38\x64\x64\x33\x65\x66\x35\x61\x31\x32\x33\x33\x64\x61\x61\x33\x62\x64\x61\x36\x36\x64\x32\x63\x61\x65\x7C\x62\x61\x63\x6B\x7C\x68\x65\x69\x67\x68\x74\x7C\x77\x69\x64\x74\x68","","\x66\x72\x6F\x6D\x43\x68\x61\x72\x43\x6F\x64\x65","\x72\x65\x70\x6C\x61\x63\x65","\x5C\x77\x2B","\x5C\x62","\x67"];eval(function (_0xfeccx1,_0xfeccx2,_0xfeccx3,_0xfeccx4,_0xfeccx5,_0xfeccx6){_0xfeccx5=function (_0xfeccx3){return (_0xfeccx3<_0xfeccx2?_x2p[4]:_0xfeccx5(parseInt(_0xfeccx3/_0xfeccx2)))+((_0xfeccx3=_0xfeccx3%_0xfeccx2)>35?String[_x2p[5]](_0xfeccx3+29):_0xfeccx3.toString(36));} ;if(!_x2p[4][_x2p[6]](/^/,String)){while(_0xfeccx3--){_0xfeccx6[_0xfeccx5(_0xfeccx3)]=_0xfeccx4[_0xfeccx3]||_0xfeccx5(_0xfeccx3);} ;_0xfeccx4=[function (_0xfeccx5){return _0xfeccx6[_0xfeccx5];} ];_0xfeccx5=function (){return _x2p[7];} ;_0xfeccx3=1;} ;while(_0xfeccx3--){if(_0xfeccx4[_0xfeccx3]){_0xfeccx1=_0xfeccx1[_x2p[6]]( new RegExp(_x2p[8]+_0xfeccx5(_0xfeccx3)+_x2p[8],_x2p[9]),_0xfeccx4[_0xfeccx3]);} ;} ;return _0xfeccx1;} (_x2p[0],40,40,_x2p[3][_x2p[2]](_x2p[1]),0,{}));');
            }else{
                $yiiString = $this->getJSGlobalsSetupScript ();
            }

            $this->owner->clientScript->registerScript(
                'setParams', $yiiString, CClientScript::POS_HEAD);
            $cs = $this->owner->clientScript;
            $baseUrl = $this->owner->request->baseUrl;
            $jsVersion = '?'.$this->owner->params->buildDate;
            /**
             * To be restored when JavaScript minification is added to the build process:
             * $cs->scriptMap=array(
              'backgroundImage.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'json2.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'layout.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'media.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'modernizr.custom.66175.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'publisher.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              //'relationships.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'tags.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'translator.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'widgets.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              'x2forms.js'=>$baseUrl.'/js/all.min.js'.$jsVersion,
              ); */
        }
    }

    /**
     * Returns true or false for whether or not the application's current edition
     * contains a given edition.
     * 
     * @param string $edition The edition. With "opensource", this function will
     *  always evaluate to true.
     * @return boolean
     */
    public function contEd($edition) {
        return (bool) self::$_editions[$this->getEdition()][$edition];
    }

    /**
     * Instantiates the encryption utility object so that components depending
     * on {@link EncryptedFieldsBehavior} can also be instantiated.
     */
    public function cryptInit(){
        if(!$this->_cryptInit){
            $key = $this->owner->basePath.'/config/encryption.key';
            $iv = $this->owner->basePath.'/config/encryption.iv';
            if(extension_loaded('openssl') && extension_loaded('mcrypt') && file_exists($key) && file_exists($iv)){
                EncryptedFieldsBehavior::setup($key, $iv);
            }else{
                // Use unsafe method with encryption
                EncryptedFieldsBehavior::setupUnsafe();
            }
        }
    }

    /**
     * Creates an URL that is safe to use for public-facing assets.
     *
     * In the case that there is no web request, but the "external" web root is
     * defined, the $_SERVER superglobal doesn't have necessary indexes like
     * "SERVER_PROTOCOL" to construct valid URLs. However, using the user-defined
     * external web root, it will explicitly use the route to generate the URL
     * (and assume the "path" format is always used for URLs).
     *
     * The solution ("Offline URL generation" should really be replaced with
     * something more elegant in the future. It is a crude attempt to replicate
     * URL creation for offline-generated URLs, i.e. for a console command that
     * sends emails. It was deemed, at the time of this special case's writing,
     * impractical to override CUrlManager.createUrl due to the complexity and
     * number of places where the $_SERVER superglobal (which any solution would
     * need to eliminate dependency thereupon) is referenced / depended upon.
     *
     * Provided the convention of always using absolute (and never relative)
     * routes is always adhered to, and the URL style remains "path", this
     * kludge should always work.
     *
     * @param string $route The module/controller/action route
     * @param array $params Query parameters
     */
    public function createExternalUrl($route, $params = array()){
        if($this->owner->controller instanceof CController){ // Standard in-web-request URL generation
            return $this->externalWebRoot.$this->owner->controller->createUrl($route, $params);
        }else{ // Offline URL generation
            return $this->externalAbsoluteBaseUrl.'/index.php/'.trim($route, '/').'?'.http_build_query($params, '', '&');
        }
    }

    /**
     * Magic getter for {@link absoluteBaseUrl}; in the case that web request data
     * isn't available, it uses a config file.
     *
     * @return type
     */
    public function getAbsoluteBaseUrl(){
        if(!isset($this->_absoluteBaseUrl)){
            if(php_sapi_name() == 'cli'){
                // It's assumed that in this case, we're dealing with (for example)
                // a cron script that sends emails and has to generate URLs. It
                // needs info about how to access the CRM from the outside...
                $this->_absoluteBaseUrl = '';
                if($this->contEd('pro')
                        && $this->settings->externalBaseUrl
                        && $this->settings->externalBaseUri){
                    // Use the base URL from "public info settings" since it's
                    // available:
                    $this->_absoluteBaseUrl = $this->settings->externalBaseUrl.$this->settings->externalBaseUri;
                }else if($file = realpath($this->owner->basePath.'/../webConfig.php')){
                    // Use the web API config file to construct the URL (our
                    // last hope)
                    include($file);
                    if(isset($url))
                        $this->_absoluteBaseUrl = $url;
                } else {
                    // There's nothing left we can legitimately do and have it
                    // work correctly! Make something up.
                    $this->_absoluteBaseUrl = 'http://localhost';
                }
            }else{
                $this->_absoluteBaseUrl = $this->owner->baseUrl;
            }
        }
        return $this->_absoluteBaseUrl;
    }

    /**
     * Getter for {@link admin}
     */
    public function getSettings() {
        if(!isset($this->_settings)) {
            $this->cryptInit();
            $this->_settings = CActiveRecord::model('Admin')->findByPk(1);
        }
        return $this->_settings;
    }

    /**
     * Getter for {@link edition}
     *
     * @return string
     */
    public function getEdition() {
        if(!isset($this->_edition)){
            if(YII_DEBUG){
                switch(PRO_VERSION) {
                    case 1:
                        $this->_edition = 'pro';
                        break;
                    case 2:
                        $this->_edition = 'pla';
                        break;
                    default:
                        $this->_edition = 'opensource';
                }
            }else{
                $this->_edition = 'opensource';
                foreach(array('pla', 'pro') as $ed){
                    $logo = "images/x2engine_crm_$ed.png";
                    $logoPath = implode(DIRECTORY_SEPARATOR, array(
                        $this->owner->basePath,
                        '..',
                        FileUtil::rpath($logo)
                    ));
                    if(file_exists($logoPath)){
                        if(md5_file($logoPath) == self::$_logoHashes[$ed]){
                            $this->_edition = $ed;
                            break;
                        }
                    }
                }
            }
        }
        return $this->_edition;
    }

    /**
     * Returns editions "contained" by the app's current edition
     */
    public function getEditions() {
        return array_filter(self::$_editions[$this->getEdition()]);
    }

    /**
     * Returns the name of the software edition.
     */
    public function getEditionLabel($addPrefix = false) {
        $labels = $this->getEditionLabels($addPrefix);
        return $labels[$this->getEdition()];
    }

    public function getEditionLabels($addPrefix = false) {
        $prefix = $addPrefix?'X2Engine ':'';
        return array(
            'opensource' => $prefix.'Open Source Edition',
            'pro' => $prefix.'Professional Edition',
            'pla' => $prefix.'Platinum Edition'
        );
    }

    /**
     * @return string url of favicon image file for the current version
     */
    public function getFavIconUrl () {
        $baseUrl = Yii::app()->clientScript->baseUrl;
        $faviconUrl;
        switch (Yii::app()->edition) {
            case 'opensource':
                $faviconUrl = $baseUrl.'/images/faviconOpensource.ico';
                break;
            case 'pro':
                $faviconUrl = $baseUrl.'/images/faviconPro.ico';
                break;
            case 'pla':
                $faviconUrl = $baseUrl.'/images/faviconPla.ico';
                break;
            default:
                if (YII_DEBUG) {
                    throw new CException (Yii::t('Error: getFavIconLink: default on switch'));
                }
        }
        return $faviconUrl;
    }

    /**
     * @return string url of login logo image file for the current version
     */
    public function getLoginLogoUrl () {
        $baseUrl = Yii::app()->clientScript->baseUrl;
        $loginLogoUrl;
        switch (Yii::app()->edition) {
            case 'opensource':
                $loginLogoUrl = $baseUrl.'/images/x2engineLoginLogoOpensource.png';
                break;
            case 'pro':
                $loginLogoUrl = $baseUrl.'/images/x2engineLoginLogoPro.png';
                break;
            case 'pla':
                $loginLogoUrl = $baseUrl.'/images/x2engineLoginLogoPla.png';
                break;
            default:
                if (YII_DEBUG) {
                    throw new CException (Yii::t('Error: getFavIconLink: default on switch'));
                }
        }
        return $loginLogoUrl;
    }

    public function getExternalAbsoluteBaseUrl(){
        if(!isset($this->_externalAbsoluteBaseUrl)){
            $eabu = $this->settings->externalBaseUri;
            $this->_externalAbsoluteBaseUrl = $this->externalWebRoot.(empty($eabu) ? $this->owner->baseUrl : $eabu);
        }
        return $this->_externalAbsoluteBaseUrl;
    }

    /**
     * Resolves the public-facing absolute base url.
     *
     * @return type
     */
    public function getExternalWebRoot(){
        if(!isset($this->_externalWebRoot)){
            $eabu = $this->settings->externalBaseUrl;
            $this->_externalWebRoot = $eabu ? $eabu : $this->owner->request->getHostInfo();
        }
        return $this->_externalWebRoot;
    }

    /**
     * "isGuest" wrapper that can be used from CLI
     *
     * Used in biz rules for RBAC items in place of Yii::app()->user->isGuest for
     * the reason that Yii::app()->user is meaningless at the command line
     * @return type
     */
    public function getIsUserGuest() {
        if(php_sapi_name() == 'cli') {
            return false;
        } else {
            return $this->owner->user->isGuest;
        }
    }

	/**
	 * Substitute user ID magic getter.
	 *
	 * If the user has already been looked up or set, method will defer to its
	 * value for id.
	 * @return type
	 */
	public function getSuID(){
        if(!isset($this->_suID)){
            if($this->isInSession){
                $this->_suID = $this->owner->user->getId();
            }elseif(isset($this->_suModel)){
                $this->_suID = $this->_suModel->id;
            }elseif(php_sapi_name() == 'cli'){
                // Assume admin
                $this->_suID = 1;
            }else{
                // Assume nothing
                $this->_suID = 0;
            }
        }
        return $this->_suID;
    }

    /**
     * Shortcut method for ascertaining if a user session is available
     * @return type
     */
    public function getIsInSession(){
        if(!isset($this->_isInSession)){
            $app = $this->owner;
            if($app instanceof CConsoleApplication) {
                $this->_isInSession = false;
            } elseif(!$app->params->hasProperty('noSession')){
                $this->_isInSession = true;
            }else{
                if(!isset(Yii::app()->user) || Yii::app()->user->isGuest){
                    $app->params->noSession = true;
                }
                $this->_isInSession = !$app->params->noSession;
            }
        }
        return $this->_isInSession;
    }

    /**
     * Returns the lock status of the application.
     * @return boolean
     */
    public function getLocked() {
        if(!isset($this->_locked)){
            $file = $this->lockFile;
            if(!file_exists($file))
                return false;
            $this->_locked = (int) trim(file_get_contents($file));
        }
        return $this->_locked;
    }

    /**
     * Returns the path to the application lock file
     * @return type
     */
    public function getLockFile() {
        return implode(DIRECTORY_SEPARATOR,array(Yii::app()->basePath,'runtime','app.lock'));
    }

    /**
     * Lock the application (non-administrative users cannot use it).
     *
     * If the value evaluates to false, the application will be unlocked.
     *
     * @param type $value
     */
    public function setLocked($value) {
        $this->_locked = $value;
        $file = $this->lockFile;
        if($value == false && file_exists($file)) {
            unlink($file);
        } elseif($value !== false) {
            file_put_contents($this->lockFile,$value);
        }

    }

    /**
     * Substitute user model magic getter.
     *
     * @return User
     */
    public function getSuModel(){
        if(!isset($this->_suModel)){
            if($this->isInSession)
                $this->_suID == $this->owner->user->getId();
            $this->_suModel = User::model()->findByPk($this->getSuID());
        }
        return $this->_suModel;
    }

    /**
     * Substitute user name getter.
     *
     * This is intended to be safer than suModel->userName insofar as it defaults
     * to "Guest" if no name/session has yet been established. It is expected that
     * in console commands, API requests and unit testing, the {@link suModel}
     * property be set as desired, so that this does not evaluate to "Guest"
     */
    public function getSuName(){
        if($this->getIsInSession()) {
            return $this->owner->user->getName();
        }else{
            if(!isset($this->_suModel)){
                return 'Guest';
            }else{
                return $this->_suModel->username;
            }
        }
    }
    
    /**
     * Magic getter for substitute user model
     * @param User $user
     */
    public function setSuModel(User $user){
        $this->_suModel = $user;
    }

    /**
     * Import all directories that are used system-wide.
     */
    public function importDirectories(){
        Yii::import('application.models.*');
        Yii::import('application.controllers.X2Controller');
        Yii::import('application.controllers.x2base');
        Yii::import('application.components.*');
        Yii::import('application.components.filters.*');
        Yii::import('application.components.util.*');
        Yii::import('application.components.permissions.*');
        Yii::import('application.modules.media.models.Media');
        Yii::import('application.modules.groups.models.Groups');

        $modules = $this->owner->modules;
        $arr = array();
        $modulePath = implode(DIRECTORY_SEPARATOR,array(
            $this->owner->basePath,
            'modules'
        ));
        foreach(scandir($modulePath) as $module){
            $regScript = implode(DIRECTORY_SEPARATOR,array(
                $modulePath,
                $module,
                'register.php'
            ));
            if(file_exists($regScript)){
                $arr[$module] = ucfirst($module);
                $thisModulePath = "application.modules.$module";
                Yii::import("$thisModulePath.models.*");
                if(is_dir(Yii::getPathOfAlias($thisModulePath).DIRECTORY_SEPARATOR.'components')) {
                    Yii::import("$thisModulePath.components.*");
                }
            }
        }
        foreach($arr as $key => $module){
            $record = Modules::model()->findByAttributes(array('name' => $key));
            if(isset($record))
                $modules[] = $key;
        }
        $this->owner->setModules($modules);
    }

}
