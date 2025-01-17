<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
class ilTestResultHeaderLabelBuilder
{
    public const LO_TEST_TYPE_INITIAL = 'loTestInitial';
    public const LO_TEST_TYPE_QUALIFYING = 'loTestQualifying';
    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilObjectDataCache
     */
    protected $objCache;

    /**
     * @var integer
     */
    protected $objectiveOrientedContainerId;

    /**
     * @var integer
     */
    protected $testObjId;

    /**
     * @var integer
     */
    protected $testRefId;

    /**
     * @var integer
     */
    protected $userId;

    /**
     * @var string
     */
    protected $crsTitle;

    /**
     * @var string
     */
    protected $testType;

    /**
     * @var array
     */
    protected $objectives;

    /**
     * @param ilLanguage $lng
     * @param ilObjectDataCache $objCache
     */
    public function __construct(ilLanguage $lng, ilObjectDataCache $objCache)
    {
        $this->lng = $lng;
        $this->objCache = $objCache;

        $this->objectiveOrientedContainerId = null;
        $this->testObjId = null;
        $this->testRefId = null;
        $this->userId = null;

        $this->testType = null;
        $this->crsTitle = null;

        $this->objectives = array();
    }

    /**
     * @return int
     */
    public function getObjectiveOrientedContainerId(): ?int
    {
        return $this->objectiveOrientedContainerId;
    }

    /**
     * @param int $objectiveOrientedContainerId
     */
    public function setObjectiveOrientedContainerId($objectiveOrientedContainerId)
    {
        $this->objectiveOrientedContainerId = $objectiveOrientedContainerId;
    }

    /**
     * @return int
     */
    public function getTestObjId(): ?int
    {
        return $this->testObjId;
    }

    /**
     * @param int $testObjId
     */
    public function setTestObjId($testObjId)
    {
        $this->testObjId = $testObjId;
    }

    /**
     * @return int
     */
    public function getTestRefId(): ?int
    {
        return $this->testRefId;
    }

    /**
     * @param int $testRefId
     */
    public function setTestRefId($testRefId)
    {
        $this->testRefId = $testRefId;
    }

    /**
     * @return int
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function initObjectiveOrientedMode()
    {
        $this->initTestType();
        $this->initObjectives();
        $this->initCourseTitle();
    }

    private function initTestType()
    {
        $loSettings = ilLOSettings::getInstanceByObjId($this->getObjectiveOrientedContainerId());

        if ($loSettings->getInitialTest() == $this->getTestRefId()) {
            $this->testType = self::LO_TEST_TYPE_INITIAL;
        } elseif ($loSettings->getQualifiedTest() == $this->getTestRefId()) {
            $this->testType = self::LO_TEST_TYPE_QUALIFYING;
        }
    }

    private function initObjectives()
    {
        $loRuns = ilLOTestRun::getRun($this->getObjectiveOrientedContainerId(), $this->getUserId(), $this->getTestObjId());

        $this->objectives = array();

        foreach ($loRuns as $loRun) {
            /* @var ilLOTestRun $loRun */

            $this->objectives[$loRun->getObjectiveId()] = $this->getObjectiveTitle($loRun);
        }
    }

    private function initCourseTitle()
    {
        $this->crsTitle = $this->objCache->lookupTitle((int) $this->getObjectiveOrientedContainerId());
    }

    /**
     * @return string
     */
    public function getPassOverviewHeaderLabel(): string
    {
        if (!$this->getObjectiveOrientedContainerId()) {
            return $this->lng->txt('tst_results_overview');
        }

        if ($this->isInitialTestForAllObjectives()) {
            return sprintf(
                $this->lng->txt('tst_pass_overview_header_lo_initial_all_objectives'),
                $this->crsTitle
            );
        } elseif ($this->isInitialTestPerObjective()) {
            return sprintf(
                $this->lng->txt('tst_pass_overview_header_lo_initial_per_objective'),
                $this->getObjectivesString(),
                $this->crsTitle
            );
        } elseif ($this->isQualifyingTestForAllObjectives()) {
            return sprintf(
                $this->lng->txt('tst_pass_overview_header_lo_qualifying_all_objectives'),
                $this->crsTitle
            );
        } elseif ($this->isQualifyingTestPerObjective()) {
            return sprintf(
                $this->lng->txt('tst_pass_overview_header_lo_qualifying_per_objective'),
                $this->getObjectivesString(),
                $this->crsTitle
            );
        }

        return '';
    }

    /**
     * @return string
     */
    public function getPassDetailsHeaderLabel($attemptNumber): string
    {
        if (!$this->getObjectiveOrientedContainerId()) {
            return sprintf(
                $this->lng->txt('tst_pass_details_overview_table_title'),
                $attemptNumber
            );
        }

        if ($this->isInitialTest()) {
            return sprintf(
                $this->lng->txt('tst_pass_details_header_lo_initial'),
                $this->getObjectivesString(),
                $this->getAttemptLabel($attemptNumber)
            );
        } elseif ($this->isQualifyingTest()) {
            return sprintf(
                $this->lng->txt('tst_pass_details_header_lo_qualifying'),
                $this->getObjectivesString(),
                $this->getAttemptLabel($attemptNumber)
            );
        }

        return '';
    }

    private function isInitialTest(): bool
    {
        return $this->testType == self::LO_TEST_TYPE_INITIAL;
    }

    private function isQualifyingTest(): bool
    {
        return $this->testType == self::LO_TEST_TYPE_QUALIFYING;
    }

    private function isInitialTestForAllObjectives(): bool
    {
        if ($this->testType != self::LO_TEST_TYPE_INITIAL) {
            return false;
        }

        if (count($this->objectives) <= 1) {
            return false;
        }

        return true;
    }

    private function isInitialTestPerObjective(): bool
    {
        if ($this->testType != self::LO_TEST_TYPE_INITIAL) {
            return false;
        }

        if (count($this->objectives) > 1) {
            return false;
        }

        return true;
    }

    private function isQualifyingTestForAllObjectives(): bool
    {
        if ($this->testType != self::LO_TEST_TYPE_QUALIFYING) {
            return false;
        }

        if (count($this->objectives) <= 1) {
            return false;
        }

        return true;
    }

    private function isQualifyingTestPerObjective(): bool
    {
        if ($this->testType != self::LO_TEST_TYPE_QUALIFYING) {
            return false;
        }

        if (count($this->objectives) > 1) {
            return false;
        }

        return true;
    }

    private function getObjectiveTitle(ilLOTestRun $loRun)
    {
        return ilCourseObjective::lookupObjectiveTitle($loRun->getObjectiveId());
    }

    private function getObjectivesString(): string
    {
        return implode(', ', $this->objectives);
    }

    private function getAttemptLabel($attemptNumber): string
    {
        return sprintf($this->lng->txt('tst_res_lo_try_n'), $attemptNumber);
    }

    public function getListOfAnswersHeaderLabel($attemptNumber): string
    {
        $langVar = 'tst_eval_results_by_pass';

        if ($this->getObjectiveOrientedContainerId()) {
            $langVar = 'tst_eval_results_by_pass_lo';
        }

        return sprintf($this->lng->txt($langVar), $attemptNumber);
    }

    public function getVirtualListOfAnswersHeaderLabel(): string
    {
        return $this->lng->txt('tst_eval_results_lo');
    }

    public function getVirtualPassDetailsHeaderLabel($objectiveTitle): string
    {
        if ($this->isInitialTest()) {
            return sprintf(
                $this->lng->txt('tst_virtual_pass_header_lo_initial'),
                $objectiveTitle
            );
        } elseif ($this->isQualifyingTest()) {
            return sprintf(
                $this->lng->txt('tst_virtual_pass_header_lo_qualifying'),
                $objectiveTitle
            );
        }

        return '';
    }
}
