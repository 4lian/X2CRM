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

Yii::import('application.tests.functional.webTrackingTests.WebTrackingTestBase');
Yii::import('application.modules.contacts.models.Contacts');
Yii::import('application.modules.actions.models.Actions');
Yii::import('application.modules.accounts.models.Accounts');

/**
 * 
 * @package application.tests.functional.modules.contacts
 */
class WebTrackerTest extends WebTrackingTestBase {

    public $autoLogin = false;

    public $fixtures = array(
        // disables fingerprinting
        'admin' => array ('Admin', '.cookieTrackingTests'),
    );

    /**
     * Assert that tracking cooldown is disabled 
     */
    public function testAssertDebugMode () {
        $this->assertTrue (YII_DEBUG && WebListenerAction::DEBUG_TRACK);
    }

    /**
     * Submit the web lead form and then visit a page that has the web tracker on it
     */
    public function testWebTracker () {
        $this->deleteAllVisibleCookies ();
        $this->assertNotCookie ('regexp:.*x2_key.*');

        // initiate tracking by submitting the web form
        $this->submitWebForm ();
        $this->assertContactCreated ();
        $this->assertCookie ('regexp:.*x2_key.*');

        // visit page with web tracker on it
        $this->assertWebTrackerTracksWithCookie ();

    }

    /**
     * Initiates tracking using the test web root, and then, using a separate subdomain, visits a 
     * page containing the web tracker and asserts that tracking does work
     */
    public function testWebTrackerAcrossSubDomains () {
        $this->deleteAllVisibleCookies ();
        $this->assertNotCookie ('regexp:.*x2_key.*');

        // initiate tracking by submitting the web form
        $this->submitWebForm ('differentSubdomain');
        $this->assertContactCreated ();
        $this->assertCookie ('regexp:.*x2_key.*');

        // ensure that when the webtracker makes requests to a different subdomain 
        // the cookies can be accessed on the server

        // visit the page with the web tracker on it
        $this->clearWebActivity ();
        $this->openPublic ('x2WebTrackingTestPages/webTrackerTestDifferentSubdomain.html');
        $this->assertCookie ('regexp:.*x2_key.*');
        $this->pause (5000); // wait for database changes to enact
        $this->assertWebActivityGeneration ();

    }

    /**
     * Initiates tracking using the test web root, and then, using a separate domain, visits a 
     * page containing the web tracker .
     * In browsers that block third party cookies by default, web tracking should fail.
     * Unlike with the custom web form, if third party cookies are not blocked, having the tracker
     * make requests to a separate domain should not prevent web tracking.
     */
    public function testWebTrackerAcrossDomains () {
        VERBOSE_MODE && println ('testWebTrackerAcrossDomains: isIE8 () === '.$this->isIE8());

        $this->deleteAllVisibleCookies ();
        $this->assertNotCookie ('regexp:.*x2_key.*');

        // initiate tracking by submitting the web form
        $this->submitWebForm ('differentDomain');
        $this->assertContactCreated ();

        // even though the cookie is set, it's set on a domain that's different than the one
        // that the web form is being accessed through, so selenium can't read it
        // Commented out because, for some reason, ie8 can read this cookie
        // $this->assertNotCookie ('regexp:.*x2_key.*'); 

        // ensure that when the webtracker makes requests to a different domain 
        // the cookies can be accessed on the server

        // visit the page with the web tracker on it
        $this->clearWebActivity ();
        $this->openPublic ('x2WebTrackingTestPages/webTrackerTestDifferentDomain.html');
        $this->assertCookie ('regexp:.*x2_key.*');
        $this->pause (5000); // wait for database changes to enact

        if ($this->isIE8 ()) // ie8 blocks third party cookies by default
            $this->assertNoWebActivityGeneration ();
        else // chrome and ff do not
            $this->assertWebActivityGeneration ();

    }
}

?>
