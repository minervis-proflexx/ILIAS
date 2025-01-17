<?php

declare(strict_types=1);

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

namespace ILIAS\Notes;

use ILIAS\DI\Container;
use ILIAS\Repository\GlobalDICDomainServices;

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class InternalDomainService
{
    use GlobalDICDomainServices;

    protected InternalRepoService $repo_service;
    protected InternalDataService $data_service;
    protected array $instances = [];

    public function __construct(
        Container $DIC,
        InternalRepoService $repo_service,
        InternalDataService $data_service
    ) {
        $this->repo_service = $repo_service;
        $this->data_service = $data_service;
        $this->initDomainServices($DIC);
    }

    public function noteAccess(): AccessManager
    {
        return $this->instances[AccessManager::class] = $this->instances[AccessManager::class] ?? new AccessManager(
            $this->data_service,
            $this->repo_service,
            $this
        );
    }

    public function notes(): NotesManager
    {
        return $this->instances[NotesManager::class] = $this->instances[NotesManager::class] ?? new NotesManager(
            $this->data_service,
            $this->repo_service,
            $this
        );
    }

    public function notification(): NotificationsManager
    {
        return $this->instances[NotificationsManager::class] = $this->instances[NotificationsManager::class] ?? new NotificationsManager(
            $this->data_service,
            $this->repo_service,
            $this
        );
    }
}
