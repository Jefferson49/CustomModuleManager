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

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleThemeInterface;
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

    //Whether the custom module is a theme
    protected bool $is_theme; 


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
     * Whether the module is a Theme
     * 
     * @return bool
     */
    public function moduleIsTheme(): bool {

        return $this->is_theme ?? false;
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
     * Fetch the latest version of this module
     *
     * @param bool $fetch_latest  Whether to fetch the latest version, e.g. from a Github repository 
     * 
     * @return string
     */
    public function customModuleLatestVersion(bool $fetch_latest = false): string
    {
        $module = $this->getModule();

        if ($module === null) {
            return '';
        }

        return $module->customModuleLatestVersion();
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
    public function testModuleUpdate(): string
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
     * @param  string $module_name
     *  
     * @return string Error message or empty string if no error
     */
    public static function testModule(string $module_name): string
    {
        $message = '';
        $filename = Webtrees::ROOT_DIR . Webtrees::MODULES_PATH . self::getInstallationFolderFromModuleName($module_name) . '/module.php';

        //Try to load module (if not already loaded by webtrees)
        //Code from: Fisharebest\Webtrees\Services\ModuleService
        try {
            $module = include_once $filename;
        } catch (Throwable $exception) {
            $message = 'Fatal error in module: ' . $module_name . '<br>' . $exception;
            return $message;
        }

        //Check flash messages for custom module errors
        $message = self::pullFlashErrorMessage($module_name);

        return $message;
    }

    /**
     * Identify whether a module is a theme
     * 
     * Code from: Fisharebest\Webtrees\Services\ModuleService
     * 
     * @param string $module_name
     * @param array  $configuratio parameters
     *  
     * @return string Error message or empty string if no error
     */
    public static function identifyThemeFromConfig(string $module_name, $params): bool {

        $is_theme = false;

        if (array_key_exists('is_theme', $params)) {
            if ($params['is_theme'] === true) {
                $is_theme = true;
            }
        }
        else {
            $module_service = New ModuleService();
            $module = $module_service->findByName($module_name);
            
            if ($module !== null) {
                $interfaces = class_implements($module);

                if (isset($interfaces[ModuleThemeInterface::class])) {
                    $is_theme = true;
                }            
            } 
        }

        return $is_theme;
    }

    /**
     * Retrieve a flash error message for a certain module
     * 
     * @param string $module_name
     *  
     * @return string Error message or empty string if no error
     */
    public static function pullFlashErrorMessage(string $module_name): string {

        $message = '';
        $module_folder = AbstractModuleUpdate::getInstallationFolderFromModuleName($module_name);
        $flash_error_text = 'Fatal error in module: ' . $module_folder . '<br>';

        foreach(FlashMessages::getMessages() as $flash_message) {
            //If specific error is founmd
            if (strpos($flash_message->text, $flash_error_text, 0) !== false) {
                $message = $flash_message->text;
            }
            //Else write back flash message, since FlashMessages::getMessages() removes all flash messages from session
            else {
                FlashMessages::addMessage($flash_message->text, $flash_message->status);
            }
        }
        return $message;
    }
}
