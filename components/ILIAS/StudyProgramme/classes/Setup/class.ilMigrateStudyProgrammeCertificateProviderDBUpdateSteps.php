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

use ILIAS\StudyProgramme\Certificate\ilStudyProgrammePlaceholderValues;

class ilMigrateStudyProgrammeCertificateProviderDBUpdateSteps implements ilDatabaseUpdateSteps
{
    protected ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function step_1(): void
    {
        $this->db->update(
            'il_cert_cron_queue',
            ['adapter_class' => [ilDBConstants::T_TEXT, ilStudyProgrammePlaceholderValues::class]],
            ['adapter_class' => [ilDBConstants::T_TEXT, 'ilStudyProgrammePlaceholderValues']]
        );
    }
}
