<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2026 webtrees development team
 *                    <http://webtrees.net>
 *
 * CustomModuleManager (webtrees custom module):
 * Copyright (C) 2026 Markus Hemprich
 *                    <http://www.familienforschung-hemprich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 *
 * CustomModuleManager
 *
 * A weebtrees(https://webtrees.net) 2.2 custom module to manage custom modules
 *
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates;

use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;


/**
 * Update API for a custom module, which is hosted in a Github repository
 */
class GithubModuleUpdate extends PlatformModuleUpdate implements CustomModuleUpdateInterface
{
    const string NAME = 'GitHub';
    const string URL  = 'https://github.com/';

    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     *
     * @return void
     */
    public function __construct(string $module_name, array $params) {

        $this->platform_name    = self::NAME;
        $this->platform_url     = self::URL;
        $this->platform_service = GithubService::class;

        parent::__construct($module_name, $params);
    }

    /**
     * Get the API token
     *
     * @return string
     */
    public function getApiToken(): string {

        return $this->custom_module_manager->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, '');
    }
}
