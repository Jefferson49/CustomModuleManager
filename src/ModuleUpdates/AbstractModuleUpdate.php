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

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;
use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Enums\CustomModuleCompatibility;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;

use InvalidArgumentException;
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

    //The list of conflicts of the custom module
    /** @var $conflicts array<string>  version => conflict rule for version*/
    protected array $conflicts = [];


    //Whether the module shall be installed clean, i.e. all earlier files are deleted before installation
    protected bool $install_clean = false;

    //Whether the module can only be updated manually (and shall not be updated with CustomModuleManager)
    protected bool $update_manually = false;


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

        $standard_module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($this->module_name);

        return ModuleUpdateServiceConfiguration::getTitle($standard_module_name, $language_tag);
    }

    /**
     * A description of the module
     *
     * @param string $language_tag
     *
     * @return string
     */
    public function description(string $language_tag = CustomModuleManager::DEFAULT_LANGUAGE): string {

        $standard_module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($this->module_name);

        return ModuleUpdateServiceConfiguration::getDescription($standard_module_name, $language_tag);
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
            case ModuleUpdateServiceConfiguration::CATEGORY_EMAIL:
                return MoreI18N::xlate('Email');
            case ModuleUpdateServiceConfiguration::CATEGORY_FACT:
                return MoreI18N::xlate('Facts and events');
            case ModuleUpdateServiceConfiguration::CATEGORY_FOOTER:
                return MoreI18N::xlate('Footer');
            case ModuleUpdateServiceConfiguration::CATEGORY_FRONTEND:
                return I18N::translate('Frontend');
            case ModuleUpdateServiceConfiguration::CATEGORY_FRONTEND_TAB:
                return I18N::translate('Frontend') . ' ' . MoreI18N::xlate('Tab');
            case ModuleUpdateServiceConfiguration::CATEGORY_FRONTEND_SIDEBAR:
                return I18N::translate('Frontend') . ' ' . MoreI18N::xlate('Sidebar');
            case ModuleUpdateServiceConfiguration::CATEGORY_GEDCOM:
                return MoreI18N::xlate('GEDCOM');
            case ModuleUpdateServiceConfiguration::CATEGORY_LANGUAGE:
                return MoreI18N::xlate('Language');
            case ModuleUpdateServiceConfiguration::CATEGORY_MAP:
                return MoreI18N::xlate('Map');
            case ModuleUpdateServiceConfiguration::CATEGORY_MESSAGES:
                return MoreI18N::xlate('Messages');
            case ModuleUpdateServiceConfiguration::CATEGORY_MEDIA:
                return MoreI18N::xlate('Media');
            case ModuleUpdateServiceConfiguration::CATEGORY_MENU:
                return MoreI18N::xlate('Menu');
            case ModuleUpdateServiceConfiguration::CATEGORY_NONE:
                return MoreI18N::xlate('None');
            case ModuleUpdateServiceConfiguration::CATEGORY_PLACES:
                return MoreI18N::xlate('Places');
            case ModuleUpdateServiceConfiguration::CATEGORY_REPORTS:
                return MoreI18N::xlate('Reports');
            case ModuleUpdateServiceConfiguration::CATEGORY_SIGNIN:
                return MoreI18N::xlate('Sign in');
            case ModuleUpdateServiceConfiguration::CATEGORY_SOURCES:
                return MoreI18N::xlate('Sources');
            case ModuleUpdateServiceConfiguration::CATEGORY_TAGS:
                return I18N::translate('Tags');
            case ModuleUpdateServiceConfiguration::CATEGORY_THEME:
                return MoreI18N::xlate('Theme');
            case ModuleUpdateServiceConfiguration::CATEGORY_DATA_VALIDATION:
                return I18N::translate('Data validation');
            default:
                return  '';
        }
    }

    /**
     * Get the date when the module was added to the module list of Custom Module Manager
     *
     * @return string
     */
    public function getDateAdded(): string {

        $standard_module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($this->module_name);

        return ModuleUpdateServiceConfiguration::getDateAdded($standard_module_name);
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
        $flash_error_text1 = 'Fatal error in module: ' . $module_folder . '<br>';
        $flash_error_text2 = 'Fatal error in module: ' . $module_name . '<br>';

        foreach(FlashMessages::getMessages() as $flash_message) {
            //If specific error is founmd
            if (    strpos($flash_message->text, $flash_error_text1, 0) !== false
                 OR strpos($flash_message->text, $flash_error_text2, 0) !== false ) {

                $message = $flash_message->text;
            }
            //Else write back flash message, since FlashMessages::getMessages() removes all flash messages from session
            else {
                FlashMessages::addMessage($flash_message->text, $flash_message->status);
            }
        }
        return $message;
    }

    /**
     * Get the latest version of a module from an update URL.
     * Code from: Fisharebest\Webtrees\Module\ModuleCustomTrait
     *
     * @param  ModuleCustomInterface $module
     * @return string
     */
    public static function getLatestVersionByUpdateURL(ModuleCustomInterface $module): string
    {
        // No update URL provided.
        if ($module->customModuleLatestVersionUrl() === '') {
            return '';
        }

        try {
            $response = GithubService::getResponse($module->customModuleLatestVersionUrl());
        }
        catch (GithubCommunicationError $ex) {
            // Can't connect to the server?
            return '';
        }

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
            $version = $response->getBody()->getContents();

            // Does the response look like a version?
            if (preg_match('/^\d+\.\d+\.\d+/', $version)) {
                return $version;
            }
        }

        return '';
    }

    /**
     * Whether the module provides releases in the repository
     *
     * @return bool
     */
    public function providesReleasesInRepository(): bool {
        return false;
    }

    /**
     * Get the release notes for the latest version of this module
     *
     * @return string
     */
    public function getLatestReleaseNotes(): string {
        return '';
    }

    /**
     * Get the latest release URL
     *
     * @return string
     */
    public function getLatestReleaseURL(): string {

        return '';
    }

    /**
     * Whether the module shall be installed clean, i.e. all earlier files are deleted before installation
     *
     * @return bool
     */
    public function installClean(): bool {

        return $this->install_clean;
    }

    /**
     * Whether the module can only be updated manually (and shall not be updated with CustomModuleManager)
     *
     * @return bool
     */
    public function updateManually(): bool {

        return $this->update_manually;
    }

    /**
     * Get the earliest version of the module, which is incompatible with the given webtrees version; i.e. has conflicts
     *
     * @param string $webtrees_version The version of webtrees, for which the module shall be compatible
     *
     * @return string  The earliest incompatible version of the module with conflicts; empty if not found
     */
    public function getEarliestIncompatibleVersion(string $webtrees_version = Webtrees::VERSION): string {

        foreach ($this->conflicts as $version => $conflict_rule) {

            if ($conflict_rule === '') {
                continue;
            }

            try {
                //If the webtrees version satisfies the conflict rule, then the module version is incompatible
                if (CustomModuleManager::webtreesVersionSatifiesConflictRule($webtrees_version, $conflict_rule)) {

                    //The list of conflicts is sorted by release time, so the first match is the earliest incompatible version
                    return $version;
                }
            }
            catch (InvalidArgumentException $ex) {
                //Invalid webtrees version or conflict rule, e.g. empty string
                //Ignore and continue with next version
            }
        }

        return '';
    }

    /**
     * Get the latest version of the module, which is compatible with the given webtrees version; i.e. has no conflicts
     *
     * @param string $webtrees_version The version of webtrees, for which the module shall be compatible
     *
     * @return string  The latest compatible version of the module with no conflicts; empty if not found
     */
    public function getLatestCompatibleVersion(string $webtrees_version = Webtrees::VERSION): string {

        $latest_version = '';

        //The list of conflicts is sorted by release time, so we iterate and take the last compatible version in the list
        foreach ($this->conflicts as $version => $conflict_rule) {

            //If there is no conflict rule, then the module version is compatible with all webtrees versions
            if ($conflict_rule === '') {

                $latest_version = $version;
            }

            try {
                //If the webtrees version does not satisfy the conflict rule, then the module version is compatible
                if (!CustomModuleManager::webtreesVersionSatifiesConflictRule($webtrees_version, $conflict_rule)) {

                    $latest_version = $version;
                }
            }
            catch (InvalidArgumentException $ex) {
                //Invalid webtrees version or conflict rule, e.g. empty string
                //Ignore and continue with next version
            }
        }

        return $latest_version;
    }

    /**
     * Get the latest version of the module in the custom module list
     *
     * @param string $webtrees_version The version of webtrees, for which the module shall be compatible
     *
     * @return string  The latest version in the custom module list
     */
    public function getLatestVersionInCustomModuleList(string $webtrees_version = Webtrees::VERSION): string {

        return array_key_last($this->conflicts) ?? '';
    }

    /**
     * Get the compatiblilty infoprmation for a module, which contains a version and its compatibility level
     *
     * @param bool   $fetch_latest     Whether to fetch the latest version, e.g. from a Github repository
     * @param string $webtrees_version The version of webtrees, for which the module shall be compatible
     *
     * @return array  An array with a version and its compatibility level
     */
    public function getCompatibleVersionInfo(bool $fetch_latest = false, string $webtrees_version = Webtrees::VERSION): array {

        $module_name                   = $this->getModuleName();
        $current_version               = $this->customModuleVersion();
        $latest_version                = $this->customModuleLatestVersion($fetch_latest);
        $latest_compatible_version     = $this->getLatestCompatibleVersion($webtrees_version);
        $latest_version_in_module_list = $this->getLatestVersionInCustomModuleList($webtrees_version);
        $earlies_incompatible_version  = $this->getEarliestIncompatibleVersion($webtrees_version);

        $version = '';
        $compatiblilty = CustomModuleCompatibility::NOT_COMPATIBLE;

        if (strpos($module_name, 'change_language_with_url') !== false) {
            $debug = true;
        }

        // If the latest version in the module list is compatible, we assume that any latest version can be installed
        if ($latest_compatible_version === $latest_version_in_module_list) {

            if (CustomModuleManager::versionCompare($module_name, $latest_compatible_version, $current_version) >= 0) {
                $version = $latest_compatible_version;
                $compatiblilty = CustomModuleCompatibility::COMPATIBLE;

                // If the latest version is greater than in the module list, the latest version might be incompatible
                if (CustomModuleManager::versionCompare($module_name, $latest_version, $latest_version_in_module_list) > 0) {
                    $compatiblilty = CustomModuleCompatibility::LIKELY_COMPATIBLE;
                }
            }
        }
        // If no compatible version is known and a newer version than in the module list is available
        elseif ($latest_compatible_version === '') {
            if (    CustomModuleManager::versionCompare($module_name, $latest_version, $latest_version_in_module_list) > 0
                &&  CustomModuleManager::versionCompare($module_name, $latest_version, $current_version) >= 0) {

                $version = $latest_version;

                // If the latest version is smaller than the earliest incompatible version
                if (CustomModuleManager::versionCompare($module_name, $latest_version, $earlies_incompatible_version) < 0) {
                    $compatiblilty = CustomModuleCompatibility::LIKELY_COMPATIBLE;
                }
                else {
                    $compatiblilty = CustomModuleCompatibility::POSSIBLY_COMPATIBLE;
                }
            }
        }
        // If the module is not installed yet, take the latest compatible version
        elseif ($this->getModule() === null && $latest_compatible_version !== '') {
            $version = $latest_compatible_version;
            $compatiblilty = CustomModuleCompatibility::COMPATIBLE;
        }

        return [
            'version'       => $version,
            'compatiblilty' => $compatiblilty,
        ];
    }
}
