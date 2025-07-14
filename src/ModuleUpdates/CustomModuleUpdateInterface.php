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

use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Illuminate\Support\Collection;


/**
 * Interface for custom module updates 
 */
interface CustomModuleUpdateInterface
{
    /**
     * The name of the module update service
     *
     * @return string
     */
    public function name(): string;
  
    /**
     * How should the module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string;

    /**
     * A description of the module
     *
     * @return string
     */
    public function description(): string;

    /**
     * A unique internal name for the module (during runtime, based on the installation folder).
     *
     * @return string
     */
    public function getModuleName(): string;

    /**
     * Get the module
     *
    * @return ?ModuleCustomInterface
     */
    public function getModule(): ?ModuleCustomInterface;
    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string;    

    /**
     * Fetch the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string;

    /**
     * Where can we download the latest version of the module.
     * 
     * @return string
     */
    public function downloadUrl(): string;

    /**
     * Where can we find a documentation for the module
     * 
     * @return string
     */
    public function documentationUrl(): string;

    /**
     * Get the folder, into which the module zip-file shalled be unzipped
     *
     * @return string
     */
    public function getUnzipFolder(): string;
    
    /**
     * A collection of folder names within the module, which shall be cleaned after an upgrade
     *
     * @return Collection<int,string>
     */
    public function getFoldersToClean(): Collection;

    /**
     * Whether an upgrade is available for the custom module
     *
     * @return bool
     */
    public function upgradeAvailable(): bool;

    /**
     * Get a list of all module names, which are needed to perform updates with this update service
     * Background: Update services like Vesta might need several modules in parallel
     * 
     * @return array<string> standard_module_name => module_name
     */
    public function getModuleNamesToUpdate(): array;

    /**
     * Test a module update
     * 
     * @return string Error message or empty string if no error
     */
    public function testModuleUpdate(): string;

    /**
     * Whether the module is a Theme
     * 
     * @return bool
     */
    public function moduleIsTheme(): bool;
}
