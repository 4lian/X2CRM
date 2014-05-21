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

/**
 * @package application.tests.unit.modules.contacts.models
 */
class WorkflowTest extends X2DbTestCase {

    public $fixtures = array (
        'contacts' => array ('Contacts', '.WorkflowTests'),
        'actions' => array ('Actions', '.WorkflowTests'),
    );

    public static function referenceFixtures(){
        return array(
            'x2flow' => array ('X2Flow', '.WorkflowTests'),
            'workflows' => array ('Workflow', '.WorkflowTests'),
            'workflowStages' => array ('WorkflowStage', '.WorkflowTests'),
            'roleToWorkflow' => array (':x2_role_to_workflow', '.WorkflowTests'),
        );
    }

    public function testGetStagePermissions () {
        $workflow = $this->workflows ('workflow2'); 
        $status = Workflow::getWorkflowStatus ($workflow->id);
        $permissions = Workflow::getStagePermissions ($status);

        // admin should have permissions for all stages
        $this->assertTrue (!in_array (0, $permissions));

        $this->assertTrue (TestingAuxLib::login ('testuser', 'password'));
        $status = Workflow::getWorkflowStatus ($workflow->id);
        $permissions = Workflow::getStagePermissions ($status);
        VERBOSE_MODE && print_r ($permissions);

        // testuser does not have permission for stage 4
        $this->assertFalse ($permissions[3]);

        $this->assertTrue (TestingAuxLib::login ('admin', 'admin'));
    }

    public function testCanUncomplete () {
        $workflow = $this->workflows ('workflow2'); 
        $model = $this->contacts ('contact935');
        Yii::app()->settings->workflowBackdateWindow = 0;

        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);

        // admin user unaffected by backdate window
        $this->assertTrue ($success);
        
        $this->assertTrue (TestingAuxLib::login ('testuser', 'password'));

        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);

        // can't revert because backdate window has passed
        $this->assertFalse ($success);

        $this->assertTrue (TestingAuxLib::login ('admin', 'admin'));
    }

    public function testMoveFromStageAToStageB () {
        $workflow = $this->workflows ('workflow2'); 
        $model = $this->contacts ('contact935');

        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 4, 5, $model, array ('4' => 'test comment'));
        if (!$retVal[0]) println ($retVal[1]);
        $this->assertTrue ($retVal[0]);

        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 5, 1, $model);
        if (!$retVal[0]) println ($retVal[1]);
        $this->assertTrue ($retVal[0]);

        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 1, 5, $model);
        if (!$retVal[0]) println ($retVal[1]);
        // should fail since stage 4 requires a comment
        $this->assertFalse ($retVal[0]);


        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 1, 4, $model);
        if (!$retVal[0]) println ($retVal[1]);
        $this->assertTrue ($retVal[0]);

        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 4, 1, $model);
        if (!$retVal[0]) println ($retVal[1]);
        $this->assertTrue ($retVal[0]);

        $this->assertTrue (TestingAuxLib::login ('testuser', 'password'));
        $retVal = Workflow::moveFromStageAToStageB (
            $workflow->id, 1, 4, $model);
        if (!$retVal[0]) println ($retVal[1]);
        // should fail since testuser doesn't have permission to go through stage 3
        $this->assertFalse ($retVal[0]);

        $this->assertTrue (TestingAuxLib::login ('admin', 'admin'));
    }

    public function testCompleteStage () {
        $workflow = $this->workflows ('workflow2'); 
        $model = $this->contacts ('contact935');

        list ($success, $status) = Workflow::completeStage (
            $workflow->id, 4, $model, '');

        // failed to completed next stage because comment is required
        $this->assertFalse ($success);

        list ($success, $status) = Workflow::completeStage (
            $workflow->id, 4, $model, 'test comment');

        // completed next stage
        $this->assertTrue ($success);

        list ($success, $status) = Workflow::completeStage (
            $workflow->id, 3, $model, 'test comment');

        // couldn't complete already completed stage
        $this->assertFalse ($success);


        // unstart stage 4 by reverting it twice
        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);
        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);

        list ($success, $status) = Workflow::completeStage (
            $workflow->id, 4, $model, 'test comment');

        // can't complete stages which haven't been started yet
        $this->assertFalse ($success);

    }

    public function testRevertStage () {
        $workflow = $this->workflows ('workflow2'); 
        $model = $this->contacts ('contact935');

        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);

        // reverted a started stage
        $this->assertTrue ($success);

        list ($success, $status) = Workflow::revertStage (
            $workflow->id, 4, $model);

        // couldn't revert an unstarted stage
        $this->assertFalse ($success);
    }

    public function testStartStage () {
        $workflow = $this->workflows ('workflow2'); 
        $model = $this->contacts ('contact935');

        list ($success, $status) = Workflow::startStage (
            $workflow->id, 5, $model);

        // couldn't start a stage which requires previous, uncompleted stage
        $this->assertFalse ($success);

        // complete stage 4 and disable auto start so that stage 5 doesn't get started
        list ($success, $status) = Workflow::completeStage (
            $workflow->id, 4, $model, 'test comment', false);
        list ($success, $status) = Workflow::startStage (
            $workflow->id, 5, $model);

        // should have been able to start stage 5 now that 4 is completed
        $this->assertTrue ($success);

    }



}

?>
