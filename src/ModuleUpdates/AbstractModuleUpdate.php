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


/**
 * Abstract class with common functions for custom module updates
 */
abstract class AbstractModuleUpdate
{
    /**
     * A unique internal name for this module (during runtime, based on the installation folder).
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * A default name for a custom module
     * 
     * @param string $installation_folder_name  The installation folder in modules_v4
     * 
     * @return string
     */
    public static function defaultModuleName(string $installation_folder_name): string
    {
        return '_' . $installation_folder_name . '_';
    }

    /**
     * Get installation folder name from custom module name
     * 
     * @param string $module_name  A custom module name
     * 
     * @return string
     */
    public static function getInstallationFolderFromModuleName(string $module_name): string
    {
        if (preg_match("/_.*_/", $module_name) === false) {
            return '';
        }

        //Return module name without leading and trailing '_'
        return substr($module_name, 1, strlen($module_name) -2);
    }    
}
