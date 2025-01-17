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

use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Factory;
use ILIAS\Cron\Schedule\CronJobScheduleType;

class ilCronManagerTableFilterMediator
{
    private const FILTER_PROPERTY_NAME_TITLE = 'title';
    private const FILTER_PROPERTY_NAME_COMPONENT = 'component';
    private const FILTER_PROPERTY_NAME_SCHEDULE = 'schedule';
    private const FILTER_PROPERTY_NAME_STATUS = 'status';
    private const FILTER_PROPERTY_NAME_RESULT = 'result';

    private const FILTER_STATUS_ACTIVE = 1;
    private const FILTER_STATUS_INACTIVE = 2;

    public function __construct(
        private readonly ilCronJobCollection $items,
        private readonly Factory $uiFactory,
        private readonly ilUIService $uiService,
        private readonly ilLanguage $lng
    ) {
    }

    public function filter(string $action): Standard
    {
        $componentOptions = array_unique(
            array_map(function (ilCronJobEntity $entity): string {
                if ($entity->isPlugin()) {
                    return $this->lng->txt('cmps_plugin') . '/' . $entity->getComponent();
                }

                return $entity->getComponent();
            }, $this->items->toArray())
        );
        asort($componentOptions);

        $title = $this->uiFactory->input()->field()->text($this->lng->txt('title'));
        $components = $this->uiFactory->input()->field()->select(
            $this->lng->txt('cron_component'),
            array_combine($componentOptions, $componentOptions)
        );
        $schedule = $this->uiFactory->input()->field()->select(
            $this->lng->txt('cron_schedule'),
            [
                CronJobScheduleType::SCHEDULE_TYPE_DAILY->value => $this->lng->txt('cron_schedule_daily'),
                CronJobScheduleType::SCHEDULE_TYPE_WEEKLY->value => $this->lng->txt('cron_schedule_weekly'),
                CronJobScheduleType::SCHEDULE_TYPE_MONTHLY->value => $this->lng->txt('cron_schedule_monthly'),
                CronJobScheduleType::SCHEDULE_TYPE_QUARTERLY->value => $this->lng->txt('cron_schedule_quarterly'),
                CronJobScheduleType::SCHEDULE_TYPE_YEARLY->value => $this->lng->txt('cron_schedule_yearly'),
                CronJobScheduleType::SCHEDULE_TYPE_IN_MINUTES->value => sprintf($this->lng->txt('cron_schedule_in_minutes'), 'x'),
                CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS->value => sprintf($this->lng->txt('cron_schedule_in_hours'), 'x'),
                CronJobScheduleType::SCHEDULE_TYPE_IN_DAYS->value => sprintf($this->lng->txt('cron_schedule_in_days'), 'x')
            ]
        );
        $status = $this->uiFactory->input()->field()->select(
            $this->lng->txt('cron_status'),
            [
                self::FILTER_STATUS_ACTIVE => $this->lng->txt('cron_status_active'),
                self::FILTER_STATUS_INACTIVE => $this->lng->txt('cron_status_inactive'),
            ]
        );
        $result = $this->uiFactory->input()->field()->select(
            $this->lng->txt('cron_result'),
            [
                ilCronJobResult::STATUS_INVALID_CONFIGURATION => $this->lng->txt(
                    'cron_result_status_invalid_configuration'
                ),
                ilCronJobResult::STATUS_NO_ACTION => $this->lng->txt(
                    'cron_result_status_no_action'
                ),
                ilCronJobResult::STATUS_OK => $this->lng->txt(
                    'cron_result_status_ok'
                ),
                ilCronJobResult::STATUS_CRASHED => $this->lng->txt(
                    'cron_result_status_crashed'
                ),
                ilCronJobResult::STATUS_RESET => $this->lng->txt(
                    'cron_result_status_reset'
                ),
                ilCronJobResult::STATUS_FAIL => $this->lng->txt(
                    'cron_result_status_fail'
                ),
            ]
        );

        $fields = [
            self::FILTER_PROPERTY_NAME_TITLE => $title,
            self::FILTER_PROPERTY_NAME_COMPONENT => $components,
            self::FILTER_PROPERTY_NAME_SCHEDULE => $schedule,
            self::FILTER_PROPERTY_NAME_STATUS => $status,
            self::FILTER_PROPERTY_NAME_RESULT => $result,
        ];

        return $this->uiService->filter()->standard(
            'cron_job_adm_table',
            $action,
            $fields,
            array_fill(0, count($fields), true),
            true,
            true
        );
    }

    public function filteredJobs(Standard $filter): ilCronJobCollection
    {
        $filterValues = $this->uiService->filter()->getData($filter);

        return $this->items->filter(function (ilCronJobEntity $entity) use ($filterValues): bool {
            if (
                isset($filterValues[self::FILTER_PROPERTY_NAME_TITLE]) &&
                is_string($filterValues[self::FILTER_PROPERTY_NAME_TITLE]) &&
                $filterValues[self::FILTER_PROPERTY_NAME_TITLE] !== ''
            ) {
                $titleFilterValue = $filterValues[self::FILTER_PROPERTY_NAME_TITLE];
                if (ilStr::strIPos($entity->getEffectiveTitle(), $titleFilterValue) === false) {
                    return false;
                }
            }

            if (
                isset($filterValues[self::FILTER_PROPERTY_NAME_COMPONENT]) &&
                is_string($filterValues[self::FILTER_PROPERTY_NAME_COMPONENT]) &&
                $filterValues[self::FILTER_PROPERTY_NAME_COMPONENT] !== ''
            ) {
                $component = $entity->getComponent();
                if ($entity->isPlugin()) {
                    $component = $this->lng->txt('cmps_plugin') . '/' . $component;
                }

                if ($filterValues[self::FILTER_PROPERTY_NAME_COMPONENT] !== $component) {
                    return false;
                }
            }

            if (
                isset($filterValues[self::FILTER_PROPERTY_NAME_SCHEDULE]) &&
                is_string($filterValues[self::FILTER_PROPERTY_NAME_SCHEDULE]) &&
                $filterValues[self::FILTER_PROPERTY_NAME_SCHEDULE] !== ''
            ) {
                if ((int) $filterValues[self::FILTER_PROPERTY_NAME_SCHEDULE] !== $entity->getEffectiveScheduleType()->value) {
                    return false;
                }
            }

            if (
                isset($filterValues[self::FILTER_PROPERTY_NAME_STATUS]) &&
                is_string($filterValues[self::FILTER_PROPERTY_NAME_STATUS]) &&
                $filterValues[self::FILTER_PROPERTY_NAME_STATUS] !== ''
            ) {
                if (
                    (int) $filterValues[self::FILTER_PROPERTY_NAME_STATUS] === self::FILTER_STATUS_ACTIVE &&
                    !$entity->getJobStatus()
                ) {
                    return false;
                } elseif (
                    (int) $filterValues[self::FILTER_PROPERTY_NAME_STATUS] === self::FILTER_STATUS_INACTIVE &&
                    $entity->getJobStatus()
                ) {
                    return false;
                }
            }

            if (
                isset($filterValues[self::FILTER_PROPERTY_NAME_RESULT]) &&
                is_string($filterValues[self::FILTER_PROPERTY_NAME_RESULT]) &&
                $filterValues[self::FILTER_PROPERTY_NAME_RESULT] !== ''
            ) {
                if ((int) $filterValues[self::FILTER_PROPERTY_NAME_RESULT] !== $entity->getJobResultStatus()) {
                    return false;
                }
            }

            return true;
        });
    }
}
