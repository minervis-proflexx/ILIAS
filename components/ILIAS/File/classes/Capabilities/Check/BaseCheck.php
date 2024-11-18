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

namespace ILIAS\File\Capabilities\Check;

use ILIAS\File\Capabilities\Permissions;
use ILIAS\components\WOPI\Discovery\ActionTarget;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
abstract class BaseCheck implements Check
{
    public function __construct()
    {

    }

    protected function hasPermission(
        CheckHelpers $helpers,
        int $ref_id,
        Permissions ...$permission
    ): bool {
        $permission_string = implode(
            ',',
            array_map(static fn(Permissions $permission) => $permission->value, $permission)
        );

        return $helpers->access->checkAccess($permission_string, '', $ref_id, 'file');
    }

    protected function hasWopiAction(CheckHelpers $helpers, string $suffix, ActionTarget ...$action): bool
    {
        return $helpers->action_repository->hasActionForSuffix($suffix, $action);
    }

    public function hasWopiEditAction(CheckHelpers $helpers, string $suffix): bool
    {
        return $helpers->action_repository->hasEditActionForSuffix($suffix);
    }

    public function hasWopiViewAction(CheckHelpers $helpers, string $suffix): bool
    {
        return $helpers->action_repository->hasViewActionForSuffix($suffix);
    }

}
