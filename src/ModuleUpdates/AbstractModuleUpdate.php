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

use Exception;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Throwable;


/**
 * Abstract class with common functions for custom module updates
 */
abstract class AbstractModuleUpdate
{
    public const DEFAULT_LANGUAGE_PREFIX = '[English:] ';

    //The custom module name
    protected string $module_name; 


    /**
     * The name of the module update service
     *
     * @return string
     */
    abstract public function name(): string;
    
    /**
     * A unique internal name for the module (based on the installation folder).
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module_name;
    }

    /**
     * Get the module
     *
    * @return ?ModuleCustomInterface
     */
    public function getModule(): ?ModuleCustomInterface
    {
        $module_service = New ModuleService();
        $module = $module_service->findByName($this->module_name, true);

        if ($module !== null && class_implements(ModuleCustomInterface::class)) {
            return $module;            
        }

        return null;
    }

    /**
     * How should the module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string {

        $module = $this->getModule();

        if ($module !== null) {
            return $module->title();
        }
        else {
            $default_title = ModuleUpdateServiceConfiguration::getDefaultTitle($this->module_name);

            if ($default_title !== '') {
                return self::DEFAULT_LANGUAGE_PREFIX . $default_title;
            }
        }

        return '';
    }

    /**
     * A description of the module
     *
     * @return string
     */
    public function description(): string {

        $module = $this->getModule();

        if ($module !== null) {
            return $module->description();
        }
        else {
            $default_description = ModuleUpdateServiceConfiguration::getDefaultDescription($this->module_name);

            if ($default_description !== '') {
                return self::DEFAULT_LANGUAGE_PREFIX . $default_description;
            }
        }

        return '';
    }
    
    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        $module = $this->getModule();

        if ($module === null) {
            return '';
        }

        return $module->customModuleVersion();
    }

    /**
     * Get the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        $module = $this->getModule();

        if ($module === null) {
            return '';
        }

        return $module->customModuleLatestVersion();
    }

    /**
     * Whether an upgrade is available for the custom module
     *
     * @return bool
     */
    public function upgradeAvailable(): bool
    {
        $latest_version  = $this->customModuleLatestVersion();
        $current_version = $this->customModuleVersion();

        return version_compare($latest_version, $current_version) > 0;
    }      


    /**
     * A default name for a custom module based on the installation folder
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
     * Get the custom module installation folder name within webtrees, i.e. including the modules_v4 custom modules folder
     *
     * @return string
     */
    public function getInstallationFolder(): string 
    {
        return Webtrees::MODULES_PATH . self::getInstallationFolderFromModuleName($this->module_name);
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
     * A collection of folder names within the module, which shall be cleaned after an upgrade
     *
     * @return Collection<int,string>
     */
    public function getFoldersToClean(): Collection
    {
        return new Collection([]);
    }

    /**
     * Get the folder, into which the module zip-file shalled be unzipped
     *
     * @return string
     */
    public function getUnzipFolder(): string {

        return str_replace('/', '', Webtrees::MODULES_PATH);
    }

    /**
     * Get a list of all module names, which are needed to perform updates with this update service
     * Background: Update services like Vesta might need several modules in parallel
     * 
     * @return array<string> standard_module_name => module_name
     */
    public function getModuleNamesToUpdate(): array {

        $standard_module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($this->module_name);

        return [$standard_module_name => $this->module_name];
    }

    /**
     * Test a module update
     * 
     * @return string Error message or empty string if no error
     */
    public function testModuleUpdate(string $module_name): string
    {
        $module_names = $this->getModuleNamesToUpdate();

        foreach ($module_names as $standard_module_name => $module_name) {

            //If test for the updated module fails
            $error = self::testModule($module_name);
            if ($error !== '') {
                return $error;
            }
        }

        return '';
    }

    /**
     * Test a custom module by loading it in a static scope
     * 
     * Code from: Fisharebest\Webtrees\Services\ModuleService
     * 
     * @param  string $module_name
     *  
     * @return string Error message or empty string if no error
     */
    public static function testModule(string $module_name): string
    {
        $filename = Webtrees::ROOT_DIR . Webtrees::MODULES_PATH . AbstractModuleUpdate::getInstallationFolderFromModuleName($module_name) . '/module.php';
        $message = '';

        try {
            $module = include $filename;
        } catch (Throwable $exception) {
            $message = 'Fatal error in module: ' . $module_name . '<br>' . $exception;
        }

        return $message;
    }
}
