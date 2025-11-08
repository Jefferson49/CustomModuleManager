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
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;

use Throwable;


/**
 * Abstract class with common functions for custom module updates
 */
abstract class AbstractModuleUpdate
{
    //The custom module name
    protected string $module_name; 

    //The category of the custom module
    protected string $category = '';


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
     * @param string $language_tag
     * 
     * @return string
     */
    public function title(string $language_tag = CustomModuleManager::DEFAULT_LANGUAGE): string {

        $title = '';

        //Remember current language
        $current_language = Session::get('language', '');

        //Activate language
        I18N::init($language_tag);
        Session::put('language', $language_tag);

        $module = $this->getModule();

        if ($module !== null) {
            //Get descripton from module
            $title = $module->title();
        }
        else {
            //Get descripton from configuration
            $title = ModuleUpdateServiceConfiguration::getTitle($this->module_name, $language_tag);
        }

        //Reset to current language
        I18N::init($current_language);
        Session::put('language', $current_language);

        return $title;
    }

    /**
     * A description of the module
     *
     * @param string $language_tag
     * 
     * @return string
     */
    public function description(string $language_tag = CustomModuleManager::DEFAULT_LANGUAGE): string {

        $description = '';

        //Remember current language
        $current_language = Session::get('language', '');

        //Activate language
        I18N::init($language_tag);
        Session::put('language', $language_tag);

        $module = $this->getModule();

        if ($module !== null) {
            //Get descripton from module
            $description = $module->description();
        }
        else {
            //Get descripton from configuration
            $description = ModuleUpdateServiceConfiguration::getDescription($this->module_name, $language_tag);
        }

        //Reset to current language
        I18N::init($current_language);
        Session::put('language', $current_language);

        return $description;
    }

    /**
     * Whether the module is a Theme
     * 
     * @return bool
     */
    public function moduleIsTheme(): bool {

        return $this->category === ModuleUpdateServiceConfiguration::CATEGORY_THEME;
    }
    
    /**
     * Get the module category
     * 
     * @return string
     */
    public function getCategory(): string {

        switch ($this->category) {
            case ModuleUpdateServiceConfiguration::CATEGORY_ADMIN:
                return MoreI18N::xlate('Administrator');
            case ModuleUpdateServiceConfiguration::CATEGORY_CHARTS: 
                return MoreI18N::xlate('Charts');
            case ModuleUpdateServiceConfiguration::CATEGORY_CLIPPINGS_CART: 
                return MoreI18N::xlate('Clippings cart');
            case ModuleUpdateServiceConfiguration::CATEGORY_FACT: 
                return MoreI18N::xlate('Facts and events');
            case ModuleUpdateServiceConfiguration::CATEGORY_FOOTER: 
                return MoreI18N::xlate('Footer');
            case ModuleUpdateServiceConfiguration::CATEGORY_FRONTEND: 
                return I18N::translate('Frontend');
            case ModuleUpdateServiceConfiguration::CATEGORY_GEDCOM:
                return MoreI18N::xlate('GEDCOM');
            case ModuleUpdateServiceConfiguration::CATEGORY_LANGUAGE: 
                return MoreI18N::xlate('Language');
            case ModuleUpdateServiceConfiguration::CATEGORY_MAP: 
                return MoreI18N::xlate('Map');
            case ModuleUpdateServiceConfiguration::CATEGORY_MEDIA: 
                return MoreI18N::xlate('Media');
            case ModuleUpdateServiceConfiguration::CATEGORY_MENU: 
                return MoreI18N::xlate('Menu');
            case ModuleUpdateServiceConfiguration::CATEGORY_NONE: 
                return MoreI18N::xlate('None');
            case ModuleUpdateServiceConfiguration::CATEGORY_PLACES: 
                return MoreI18N::xlate('Places');
            case ModuleUpdateServiceConfiguration::CATEGORY_SIGNIN: 
                return MoreI18N::xlate('Sign in');
            case ModuleUpdateServiceConfiguration::CATEGORY_SOURCES: 
                return MoreI18N::xlate('Sources');
            case ModuleUpdateServiceConfiguration::CATEGORY_THEME: 
                return MoreI18N::xlate('Theme');
            default:
                return  '';
        }
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
     * @return array<string> module_name => standard_module_name
     */
    public function getModuleNamesToUpdate(): array {

        $standard_module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($this->module_name);

        return [$this->module_name => $standard_module_name];
    }    

    /**
     * Test a module update
     * 
     * @return string Error message or empty string if no error
     */
    public function testModuleUpdate(): string
    {
        //ToDo: How to check updates of themes

        $module_names = $this->getModuleNamesToUpdate();

        foreach ($module_names as $module_name => $standard_module_name) {

            //If error message detected within flash messages
            $error = self::pullFlashErrorMessage($module_name);

            if ($error !== '') {
                return $error;
            }
        }

        return '';
    }

    /**
     * Test a module after installation
     * 
     * @return string Error message or empty string if no error
     */
    public function testModuleInstallation(): string
    {
        $module_names = $this->getModuleNamesToUpdate();

        foreach ($module_names as $module_name => $standard_module_name) {

            $message = '';
            $filename = Webtrees::ROOT_DIR . Webtrees::MODULES_PATH . self::getInstallationFolderFromModuleName($module_name) . '/module.php';
            $module_upgrade_service = CustomModuleUpdateFactory::make($module_name);

            //Code from: Fisharebest\Webtrees\Services\ModuleService
            try {
                //Try to load module
                $module = include_once $filename;

            } catch (Throwable $exception) {
                //Code from Fisharebest\Webtrees\Services\ModuleService
                $message = 'Fatal error in module: ' . $module_name . '<br>' . $exception;
                return $message;
            }
        }

        return '';
    }

    /**
     * Identify the module category from the configuration
     * 
     * @param string $module_name
     * @param array  $params        config parameters
     *  
     * @return string
     */
    public function identifyCategoryFromConfig(string $module_name, $params): string {

        if (array_key_exists(ModuleUpdateServiceConfiguration::CATEGORY, $params)) {
            return $params[ModuleUpdateServiceConfiguration::CATEGORY];
        }
        else {
            return '';
        }
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
