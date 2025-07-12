<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
 * CustomModuleManager (webtrees custom module):
 * Copyright (C) 2025 Markus Hemprich
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

use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;

/**
 * Update API for Vesta custom modules
 */
class VestaModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface
{
    const NAME = 'Vesta';

    //The Github repository of the module, e.g. Jefferson49/CustomModuleManager
    protected string $github_repo;

    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     * 
     * @return void
     */
    public function __construct(string $module_name, array  $params) {

        $this->module_name = $module_name;

        if (array_key_exists('github_repo', $params)) {
            $this->github_repo = $params['github_repo'];
        }
        else {
            $this->github_repo = '';
        }
    }

    /**
     * The name of the module update service
     *
     * @return string
     */
    public function name(): string {

        return self::NAME;
    }

    /**
     * Where can we download a certain version of the module. Latest release if no tag is provided
     * 
     * @param string $tag  The tag of the release 
     * 
     * @return string
     */
    public function downloadUrl(string $tag = ''): string
    {
        return 'https://cissee.de/vesta.latest.zip';
    }

    /**
     * Where can we find a documentation for the module
     * 
     * @return string
     */
    public function documentationUrl(): string
    {
        if ($this->github_repo === '') {
            return '';
        }

        return 'https://github.com/'. $this->github_repo;
    }

    /**
     * Get the folder, into which the module zip-file shalled be unzipped
     *
     * @return string
     */
    public function getUnzipFolder(): string {

        return '';
    }

    /**
     * Get a list of all module names, which are needed to perform updates with this update service
     * Background: Update services like Vesta might need several modules in parallel
     * 
     * @return array<string> standard_module_name => module_name
     */
    public function getModuleNamesToUpdate(): array {

        $getVesta = true; 

        return ModuleUpdateServiceConfiguration::getModuleNames($getVesta);
    }
}
