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

use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Illuminate\Support\Collection;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Abstract class with common functions for custom module updates
 */
abstract class AbstractModuleUpdate
{
    //The custom module
    protected ?AbstractModule $module;

    //The custom module name
    protected string $module_name;

    /**
     * A unique internal name for this module (based on the installation folder).
     *
     * @return string
     */
    public function name(): string
    {
        return $this->module_name;
    }

    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        if ($this->module === null) {
            return '';
        }

        $module = $this->module;

        //Dummy PHPDoc to avoid IDE warnings
        /** @var CustomModuleManager $module */        

        return $module->customModuleVersion();
    }

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
     * Get the custom module installation folder name (within the modules_v4 folder)
     *
     * @return string
     */
    public function getInstallationFolder(): string {

        if ($this->module === null ) {
            return '';
        }

        return self::getInstallationFolderFromModuleName($this->module->name());
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

    /**
     * Whether an upgrade is available for the custom module
     *
     * @return bool
     */
    public function upgradeAvailable(): bool
    {
        if ($this->module === null) {
            return false;
        }

        $module = $this->module;

        //Dummy PHPDoc to avoid IDE warnings
        /** @var CustomModuleManager $module */           
        
        $latest_version  = $module->customModuleLatestVersion();
        $current_version = $module->customModuleVersion();

        return version_compare($latest_version, $current_version) > 0;
    }

    /**
     * A collection of folder names within the module, which shall be cleaned after an upgrade
     *
     * @return Collection<int,string>
     */
    public function getFoldersToClean(): Collection
    {
        return new Collection([]);
    }

    /**
     * Get params for this custom module update service
     * 
     * @param  AbstractModuleUpdate $module_update
     * 
     * @return array<string>
     */
    abstract static function getParams(AbstractModuleUpdate $module_update): array;
    
    /**
     * Create a custom module upgrade service from a request
     * 
     * @param  ServerRequestInterface $request
     *
     * @return AbstractModuleUpdate
     */    
    abstract static function getModuleUpdateServiceFromRequest(ServerRequestInterface $request) : AbstractModuleUpdate;   
}
