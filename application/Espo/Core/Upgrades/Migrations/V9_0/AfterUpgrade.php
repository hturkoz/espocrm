<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Upgrades\Migrations\V9_0;

use Espo\Core\Upgrades\Migration\Script;
use Espo\Entities\Preferences;
use Espo\Entities\ScheduledJob;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class AfterUpgrade implements Script
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(): void
    {
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->sth()
            ->where([
                'isActive' => true,
                'type' => [
                    User::TYPE_ADMIN,
                    User::TYPE_REGULAR,
                    User::TYPE_PORTAL,
                ]
            ])
            ->find();

        foreach ($users as $user) {
            $preferences = $this->entityManager->getRepositoryByClass(Preferences::class)->getById($user->getId());

            if (!$preferences) {
                continue;
            }

            $preferences->set('reactionNotifications', true);
            $this->entityManager->saveEntity($preferences);
        }

        $this->createScheduledJob();
    }

    private function createScheduledJob(): void
    {
        $this->entityManager->createEntity(ScheduledJob::ENTITY_TYPE, [
            'name' => 'Send Scheduled Emails',
            'job' => 'SendScheduledEmails',
            'status' => 'Active',
            'scheduling' => '*/10 * * * *',
        ]);
    }
}
