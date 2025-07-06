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
     * A unique internal name for this module (during runtime, based on the installation folder).
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
     * Get the custom module installation folder name within webtrees, i.e. including the "modules_v4" custom modules folder
     *
     * @return string
     */
    public function getInstallationFolder(): string;

    /**
     * The top level folder in the ZIP file of the custom module
     *
     * @return string
     */
    public function getZipFolder(): string;
    
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
}
