<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025 webtrees development team
 *                    <http://webtrees.net>
 *
 * Fancy Research Links (webtrees custom module):
 * Copyright (C) 2024 Carmen Just
 *                    <https://justcarmen.nl>
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

namespace Jefferson49\Webtrees\Module\CustomModuleManager;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleLanguageInterface;
use Fisharebest\Webtrees\Module\ModuleListInterface;
use Fisharebest\Webtrees\Module\ModuleListTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;
use Jefferson49\Webtrees\Helpers\Functions;
use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\DefaultTitlesAndDescriptions;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\GithubModuleUpdate;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ColumnConfigurationAction;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ColumnConfigurationModal;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\CustomModuleActivateAction;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\CustomModuleUpdatePage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\IgnoreUpdateAction;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleInformationModal;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardPage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardStep;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ReleaseNotesModal;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\VestaInformationAction;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\VestaInformationModal;
use Jefferson49\Webtrees\Module\ModuleCustomTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use RuntimeException;
use Throwable;


class CustomModuleManager extends AbstractModule implements
    MiddlewareInterface,
    ModuleCustomInterface,
	ModuleConfigInterface,
    ModuleGlobalInterface,
    ModuleListInterface,
    CustomModuleLogInterface
{
    use ModuleCustomTrait;
    use ModuleConfigTrait;
    use ModuleGlobalTrait;
    use ModuleListTrait;

	//Custom module version
	public const CUSTOM_VERSION = 'v2.0.6';

	//GitHub repository
	public const GITHUB_REPO = 'Jefferson49/CustomModuleManager';

	//Author of custom module
	public const CUSTOM_AUTHOR = 'Markus Hemprich';

    //Whether a GiHub communication error occured
    private static bool $github_communication_error = false;

    //Whether the current version is lower than the latest version of the module
    private static bool $is_lower_than_latest_version;

    //Prefences, Settings
	public const PREF_MODULE_VERSION          = 'module_version';
    public const PREF_DEBUGGING_ACTIVATED     = 'debugging_activated';
	public const PREF_GITHUB_API_TOKEN        = 'github_api_token';
	public const PREF_LAST_UPDATED_MODULE     = 'last_updated_module';
    public const PREF_ROLLBACK_ONGOING        = 'rollback_ongoing';
    public const PREF_MODULES_TO_SHOW         = 'modules_to_show';
    public const PREF_SHOW_ALL                = 'show_all_modules';
    public const PREF_SHOW_INSTALLED          = 'show_installed_modules';
    public const PREF_SHOW_NOT_INSTALLED      = 'show_not_installed_modules';
    public const PREF_SHOW_MENU_LIST_ITEM     = 'show_menu_list_item';
    public const PREF_IGNORE_VERSION          = 'ignore';
    public const PREF_SHOW_COLUMN_DESCR       = 'show_column_description';
    public const PREF_SHOW_COLUMN_CATEGORY    = 'show_column_category';
    public const PREF_SHOW_COLUMN_DATE_ADDED  = 'show_column_date_added';
    public const PREF_SHOW_COLUMN_UPD_SERV    = 'show_column_update_service';
    public const PREF_SHOW_COLUMN_DOWNLOADS   = 'show_column_downloads';
    public const PREF_TABLE_LAYOUT            = 'table_layout';
    public const PREF_VESTA_CONFIRMED         = 'vesta_confirmed';

    //Configuraton
    public const CONFIG_GITHUB_BRANCH     = 'config';
    public const CONFIG_LOCAL_PATH        = 'module_update_service_configuration.json';
    public const CONFIG_GITHUB_PATH       = 'module_update_service_configuration.json';
    public const CONFIG_FILE_NAME         = '';

    //Table layout
    public const TABLE_LAYOUT_TABLE       = 'table_layout_table';
    public const TABLE_LAYOUT_STICKY_HEAD = 'table_layout_sticky_head';
    public const TABLE_LAYOUT_RESPONSIVE  = 'table_layout_responsive';

    //Actions
    public const ACTION_DELETE            = 'action_delete';
    public const ACTION_UPDATE            = 'action_update';
    public const ACTION_INSTALL           = 'action_install';

    //Routes
    public const ROUTE_WIZARD_PAGE         = '/module-upgrade-wizard_page';
    public const ROUTE_WIZARD_STEP         = '/module-upgrade-wizard-step';
    public const ROUTE_MODULE_UPDATE_PAGE  = '/module-update-page';
    public const ROUTE_MODULE_INFO_MODAL   = '/module-info-modal';
    public const ROUTE_RELEASE_NOTES_MODAL = '/release-notes-modal';
    public const ROUTE_ACTIVATE_ACTION     = '/activate-action';
    public const ROUTE_IGNORE_UPDATE       = '/ignore-update';
    public const ROUTE_COLUMN_CONF_MODAL   = '/column-config-modal';
    public const ROUTE_COLUMN_CONF_ACTION  = '/column-config-action';
    public const ROUTE_VESTA_INFORMATION   = '/vesta-information';
    public const ROUTE_VESTA_INFO_ACTION   = '/vesta-information-action';

    //Language
    public const DEFAULT_LANGUAGE         = 'en-US';
    public const DEFAULT_LANGUAGE_PREFIX  = "[English:]";

    //Session
    public const SESSION_WIZARD_ABORTED   = 'wizard_aborted';

    //Errors
    public const ERROR_MAX_LENGTH = 500;

    //Cache
    public const CACHE_REALEASE_INFO      = 'cmm-release-info-';

    //Supported webtrees version
    public const MINIMUM_WEBTREES_VERSION = '2.2.3';

    //Switch to generate new default titles and description (in class DefaultTitlesAndDescriptions.php)
    public const GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS = false;

    //Switch to generate a JSON file with the custom module update configuration (in module_update_service_configuration.json)
    public const GENERATE_CUSTOM_MODULE_UPDATE_CONFIG = false;

    //Switch to generate a JSON file with the custom module list
    public const GENERATE_CUSTOM_MODULE_LIST = false;

    //Switch to add conflicts with the current webtrees version for
    public const ADD_CONFLICTS_FOR_MODULES_NOT_EXISTING = false;

    //Use the local json file for the custom module update configuration (in module_update_service_configuration.json)
    public const USE_LOCAL_CONFIG = false;

    //Whether the enabled status is included during submitting the update form
    public const ENABLED_STATUS_INCLUDED = 'enabled_status_included';


    /**
     * CustomModuleManager constructor.
     */
    public function __construct()
    {
        //Caution: Do not use the shared library jefferson47/webtrees-common within __construct(),
        //         because it might result in wrong autoload behavior
    }

    /**
     * Initialization.
     *
     * @return void
     */
    public function boot(): void
    {
        //Register the custom module in the webtrees container
        Registry::container()->set(CustomModuleManager::class, $this);

        //Check update of module version
        $this->checkModuleVersionUpdate();

        //If the corresponding switch is turned on, we generate default titles and descriptions
        if (self::GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS) {
            self::generateDefaultTitlesAndDescriptions();
        }

        //If the corresponding switch is turned on, we generate a JSON file for custom module update configuration
        if (self::GENERATE_CUSTOM_MODULE_UPDATE_CONFIG) {
            self::generateModuleUpdateServiceConfig();
        }

        //If the corresponding switch is turned on, we generate a JSON file for custom module list
        if (self::GENERATE_CUSTOM_MODULE_LIST) {
            self::generateCustomModuleList();
        }

		// Register a namespace for the views.
		View::registerNamespace(self::viewsNamespace(), $this->resourcesFolder() . 'views/');

        //Register the routes for the custom module
        Functions::registerRoute(self::ROUTE_WIZARD_PAGE, ModuleUpgradeWizardPage::class);
        Functions::registerRoute(self::ROUTE_WIZARD_STEP, ModuleUpgradeWizardStep::class);
        Functions::registerRoute(self::ROUTE_MODULE_UPDATE_PAGE, CustomModuleUpdatePage::class);
        Functions::registerRoute(self::ROUTE_MODULE_INFO_MODAL, ModuleInformationModal::class);
        Functions::registerRoute(self::ROUTE_RELEASE_NOTES_MODAL, ReleaseNotesModal::class);
        Functions::registerRoute(self::ROUTE_ACTIVATE_ACTION, CustomModuleActivateAction::class);
        Functions::registerRoute(self::ROUTE_IGNORE_UPDATE, IgnoreUpdateAction::class);
        Functions::registerRoute(self::ROUTE_COLUMN_CONF_MODAL, ColumnConfigurationModal::class);
        Functions::registerRoute(self::ROUTE_COLUMN_CONF_ACTION, ColumnConfigurationAction::class);
        Functions::registerRoute(self::ROUTE_VESTA_INFORMATION, VestaInformationModal::class);
        Functions::registerRoute(self::ROUTE_VESTA_INFO_ACTION, VestaInformationAction::class);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     */
    public function title(): string
    {
        return I18N::translate('Custom Module Manager');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::description()
     */
    public function description(): string
    {
        /* I18N: Description of the “AncestorsChart” module */
        return I18N::translate('A custom module to manage webtrees custom modules.');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleGlobalInterface::headContent()
     */
    public function headContent(): string
    {
        //Include CSS file in head of webtrees HTML to make sure it is always found
        $css = '<link href="' . $this->assetUrl('css/custom-module-manager.css') . '" type="text/css" rel="stylesheet" />';

        return $css;
    }

    /**
     * {@inheritDoc}
     *
     * @param Tree  $tree
     * @param array $parameters
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleListInterface::listUrl()
     */

    public function listUrl(Tree $tree, array $parameters = []): string
    {
        return route(CustomModuleUpdatePage::class);
    }

    /**
     * {@inheritDoc}
     *
     * @param Tree  $tree
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleListInterface::listIsEmpty()
     */
    public function listIsEmpty(Tree $tree): bool
    {
        return (   !Auth::isAdmin()
                OR !boolval($this->getPreference(self::PREF_SHOW_MENU_LIST_ITEM, '1'))
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleListInterface::listMenuClass()
     */
    public function listMenuClass(): string
    {
        //CSS class for module Icon (included in CSS file) is returned to be shown in the list menu
        return 'menu-list-custom-module-manager';
    }

    /**
     * Get the prefix for custom module specific logs
     *
     * @return string
     */
    public static function getLogPrefix() : string {
        return 'Custom Module Manager';
    }

    /**
     * Whether debugging is activated
     *
     * @return bool
     */
    public function debuggingActivated(): bool {
        return boolval($this->getPreference(self::PREF_DEBUGGING_ACTIVATED, '0'));
    }

    /**
     * View module settings in control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        return $this->viewResponse(
            self::viewsNamespace() . '::settings',
            [
                'runs_with_webtrees_version'   => CustomModuleManager::runsWithInstalledWebtreesVersion(),
                'php_extension_zip_missing'    => !extension_loaded('zip'),
                'title'                        => $this->title(),
                self::PREF_GITHUB_API_TOKEN    => $this->getPreference(self::PREF_GITHUB_API_TOKEN, ''),
                self::PREF_MODULES_TO_SHOW     => $this->getPreference(self::PREF_MODULES_TO_SHOW, self::PREF_SHOW_ALL),
				self::PREF_SHOW_MENU_LIST_ITEM => boolval($this->getPreference(self::PREF_SHOW_MENU_LIST_ITEM, '1')),
				self::PREF_TABLE_LAYOUT        => $this->getPreference(self::PREF_TABLE_LAYOUT, self::TABLE_LAYOUT_STICKY_HEAD),
            ]
        );
    }

    /**
     * Save module settings after returning from control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $save                = Validator::parsedBody($request)->string('save', '');
        $github_api_token    = Validator::parsedBody($request)->string(self::PREF_GITHUB_API_TOKEN, '');
        $modules_to_show     = Validator::parsedBody($request)->string(self::PREF_MODULES_TO_SHOW, self::PREF_SHOW_ALL);
        $show_menu_list_item = Validator::parsedBody($request)->boolean(self::PREF_SHOW_MENU_LIST_ITEM, false);
        $table_layout        = Validator::parsedBody($request)->string(self::PREF_TABLE_LAYOUT, SELF::TABLE_LAYOUT_STICKY_HEAD);

        //Save the received settings to the user preferences
        if ($save === '1') {
			$this->setPreference(self::PREF_GITHUB_API_TOKEN, $github_api_token);
			$this->setPreference(self::PREF_MODULES_TO_SHOW, $modules_to_show);
			$this->setPreference(self::PREF_SHOW_MENU_LIST_ITEM, $show_menu_list_item ? '1' : '0');
			$this->setPreference(self::PREF_TABLE_LAYOUT, $table_layout);
        }

        //Finally, show a success message
        $message = I18N::translate('The preferences for the module "%s" were updated.', $this->title());
        FlashMessages::addMessage($message, 'success');

        return redirect($this->getConfigLink());
    }

    /**
     * Code here is executed before and after we process the request/response.
     * We can block access by throwing an exception.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $updated_module_name = $this->getPreference(CustomModuleManager::PREF_LAST_UPDATED_MODULE, '');

        //If a module has recently been updated
        if ($updated_module_name !== '') {

            $rollback_ongoing = boolval($this->getPreference(CustomModuleManager::PREF_ROLLBACK_ONGOING, '0'));

            //If we are not already in the middle of an ongoing rollback
            if (!$rollback_ongoing) {

                $module_update_service = CustomModuleUpdateFactory::make($updated_module_name);
                $test_result = $module_update_service !== null ? substr($module_update_service->testModuleUpdate(), 0, self::ERROR_MAX_LENGTH) : 'Error';

                if ($test_result !== '') {
                    //Trigger rollback of the udpated module
                    $this->setPreference(CustomModuleManager::PREF_ROLLBACK_ONGOING, '1');

                    $modal = Validator::queryParams($request)->boolean('modal', false);

                    if ($modal) {
                        $this->layout = 'layouts/ajax';
                        $view         = '::modals/steps-modal';
                    }
                    else{
                        $this->layout = 'layouts/administration';
                        $view         = '::steps';
                    }

                    return $this->viewResponse(CustomModuleManager::viewsNamespace() . $view, [
                        'title'    => I18N::translate('Rollback Custom Module Update'),
                        'steps'    => [route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_ROLLBACK, 'module_name' => $updated_module_name, 'error_message' => $test_result, 'modal' => $modal]) => I18N::translate('Rollback')],
                    ]);
                }
                //After successful test, reset update information
                $this->setPreference(CustomModuleManager::PREF_LAST_UPDATED_MODULE, '');
            }
        }
        return $handler->handle($request);
    }

    /**
     * Check if module version is new and start update activities if needed
     *
     * @return void
     */
    public function checkModuleVersionUpdate(): void
    {
        $updated = false;

        // Update custom module version if changed
        if($this->getPreference(self::PREF_MODULE_VERSION, '') !== self::CUSTOM_VERSION) {

			$updated = false;
        }

        if ($updated) {
            //Show flash message for update of preferences
            $message = I18N::translate('The preferences for the custom module "%s" were sucessfully updated to the new module version %s.', $this->title(), self::CUSTOM_VERSION);
            FlashMessages::addMessage($message, 'success');
        }
    }

    /**
     * Gemerate default titles and descriptions for all custom modules, which are available in this webtrees installation
     *
     * If a (complete) list of modules is installed, we can use the generate a (complete) list of default values for all languages,
     * The default values are written to a PHP file, which is delivered with the Custom Module Manager code.
     *
     * @return void
     */
    public static function generateDefaultTitlesAndDescriptions(): void {

        $module_service = New ModuleService();
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class, true);
        $titles = [];
        $descriptions = [];

        //Remember current language
        $current_language = Session::get('language', '');

        $languages = $module_service->findByInterface(ModuleLanguageInterface::class, true, true)
            ->mapWithKeys(static function (ModuleLanguageInterface $module): array {
                if (version_compare(Webtrees::VERSION, '2.3', '>=')) {
                    $language = $module->language();
                }
                else {
                    $language = $module->locale();
                }
                return [$language->languageTag() => $language->endonym()];
            });

        foreach ($languages as $language_tag => $language_name) {

            //Activate the language
            I18N::init($language_tag);
            Session::put('language', $language_tag);

            foreach ($custom_modules as $module) {

                $title = $module->title();
                $title = json_encode($title) !== false ? $title : mb_convert_encoding($title, 'UTF-8');

                $description = $module->description();
                $description = json_encode($description) !== false ? $description : mb_convert_encoding($description, 'UTF-8');

                $titles[$language_tag][$module->name()]       = $title;
                $descriptions[$language_tag][$module->name()] = $description;
            }
        }

        //Reset language
        I18N::init($current_language);
        Session::put('language', $current_language);

        //Delete values, which are identical to default language
        $titles_for_default_language = $titles[CustomModuleManager::DEFAULT_LANGUAGE];
        $descriptions_for_default_language = $descriptions[CustomModuleManager::DEFAULT_LANGUAGE];

        foreach ($languages as $language_tag => $language_name) {

            //Skip default language
            if ($language_tag === CustomModuleManager::DEFAULT_LANGUAGE) continue;

            $titles_for_language = $titles[$language_tag];

            foreach ($titles_for_language as $module_name => $title) {

                if ($title === $titles_for_default_language[$module_name]) {
                    unset($titles[$language_tag][$module_name]);
                }
            }

            $descriptions_for_language = $descriptions[$language_tag];

            foreach ($descriptions_for_language as $module_name => $description) {

                if ($description === $descriptions_for_default_language[$module_name]) {
                    unset($descriptions[$language_tag][$module_name]);
                }
            }
        }

        $json_file = __DIR__ . '/Configuration/DefaultTitlesAndDescriptions.php';

        //Delete file if already existing
        if (file_exists($json_file)) {
            unlink($json_file);
        }

        //Open stream
        if (!$stream = fopen($json_file, "c")) {
            throw new RuntimeException('Cannot open file: ' . $json_file);
        }

        if (fwrite($stream, "<?php\n\n") === false) {
            throw new RuntimeException('Cannot write to file: ' . $json_file);
        }

        fwrite($stream, "declare(strict_types=1);\n\n");
        fwrite($stream, "namespace Jefferson49\Webtrees\Module\CustomModuleManager\Configuration;\n\n");
        fwrite($stream, "/**\n");
        fwrite($stream, " * Default titles and descriptions\n");
        fwrite($stream, " */\n");
        fwrite($stream, "class DefaultTitlesAndDescriptions \n");
        fwrite($stream, "{\n");
        fwrite($stream, "    public const MODULE_TITLES = [\n");

        foreach ($languages as $language_tag => $language_name) {

            //Generate JSON
            $titles_for_language = $titles[$language_tag];
            $title_json = json_encode($titles_for_language);
            $title_json = str_replace("'", "\'", $title_json);

            fwrite($stream, "        '" . $language_tag . "' => '");
            fwrite($stream, $title_json . "',\n");
        }

        fwrite($stream, "    ];\n\n");
        fwrite($stream, "    public const MODULE_DESCRIPTIONS = [\n");

        foreach ($languages as $language_tag => $language_name) {

            //Generate JSON
            $descriptions_for_language = $descriptions[$language_tag];
            $description_json = json_encode($descriptions_for_language);
            $description_json = str_replace("'", "\'", $description_json);

            fwrite($stream, "        '" . $language_tag . "' => '");
            fwrite($stream, $description_json . "',\n");
        }

        fwrite($stream, "    ];\n\n");
        fwrite($stream, "}\n");
        fclose($stream);
    }

    /**
     * Gemerate a JSON file from the module update service configuration
     *
     * @return void
     */
    public static function generateModuleUpdateServiceConfig(): void {

        $json_file = __DIR__ . '/Configuration/module_update_service_configuration.json';

        //Delete file if already existing
        if (file_exists($json_file)) {
            unlink($json_file);
        }

        //Open stream
        if (!$stream = fopen($json_file, "c")) {
            throw new RuntimeException('Cannot open file: ' . $json_file);
        }

        //Create JSON from configuration
        $json_config = json_encode(self::getConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        try {
            fwrite($stream, $json_config);
        }
        catch (Throwable $th) {
            throw new RuntimeException('Cannot write to file: ' . $json_file);
        }

        return;
    }

    /**
     * Gemerate a custom module list in based on the Packagist API v2 JSON format
     *
     * Documentation:     https://packagist.org/apidoc#get-package-data
     * Example JSON file: https://repo.packagist.org/p2/monolog/monolog.json
     *
     * @return void
     */
    public static function generateCustomModuleList(): void {

        $json_file = __DIR__ . '/Configuration/custom_module_list.json';

        //Get data from current JSON custom module list
        $custom_module_list = json_decode(self::readFromFile($json_file), true);

        //Create custom modules list
        $config = self::getConfig();

        foreach ($config as $module_name => $module_config) {

            /** @var GithubModuleUpdate $module_update_service */
            $module_update_service = CustomModuleUpdateFactory::make($module_name);

            if ($module_update_service === null) {
                break;
            }

            //Get existing module versions
            $module_versions = $custom_module_list['packages'][$module_update_service->getPackageName()] ?? [];

            //Only proceed if the current module version is available
            if (    $module_update_service->customModuleVersion() !== '') {

                //Get the content of the current composer.json file
                $composer_json = self::getComposerJson($module_update_service::getInstallationFolderFromModuleName($module_name));

                //Add additional content to composer.json data
                $composer_json['version'] = $module_update_service->customModuleVersion();
                //$composer_json['time']  = $module_update_service->releaseDate();

                if (!isset($composer_json['description'])) {
                    $composer_json['description'] = $module_update_service->description();
                }
                if (!isset($composer_json['name'])) {
                    $composer_json['name'] = $module_update_service->getPackageName();
                }
                if (!isset($composer_json['conflict'])) {

                    $version_before = self::getVersionBefore($module_versions, $module_update_service->customModuleVersion());

                    if (isset($module_versions[$version_before]['conflict'])) {

                        $composer_json['conflict'] = $module_versions[$version_before]['conflict'];
                    }
                }

                $composer_json['extra'] = ['custom-module-manager' => $config[$module_name]];

                //Sort composer.json data
                self::sortCpomposerJsonData($composer_json);

                //Remove data for version if already exists
                self::removeVersion($custom_module_list, $module_update_service->getPackageName(), $module_update_service->customModuleVersion());

                //Add the data of the new version to the module list
                $custom_module_list['packages'][$module_update_service->getPackageName()][] = $composer_json;
            }
        }

        //Create JSON
        $json_custom_module_list = json_encode($custom_module_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        //Delete file if already existing
        if (file_exists($json_file)) {
            unlink($json_file);
        }

        //Write JSON to file
        try {
            if (!$stream = fopen($json_file, "c")) {
                throw new RuntimeException('Cannot open file: ' . $json_file);
            }
            fwrite($stream, $json_custom_module_list);
            fclose($stream);
        }
        catch (Throwable $th) {
            throw new RuntimeException('Cannot write to file: ' . $json_file);
        }

        return;
    }

    /**
     * Get the configuration
     *
     * @return array
     */
    public static function getConfig(): array {

        //Get data from current json file
        $local_config = ModuleUpdateServiceConfiguration::getModuleUpdateServiceConfig();

        //Get configuration
		$config = (array) ModuleUpdateServiceConfiguration::MODULE_UPDATE_SERVICE_CONFIG;

        //Add titles and descriptions
        $titles_all_languages = DefaultTitlesAndDescriptions::MODULE_TITLES;
        $descriptions_all_languages = DefaultTitlesAndDescriptions::MODULE_DESCRIPTIONS;
        $titles = json_decode($titles_all_languages[CustomModuleManager::DEFAULT_LANGUAGE], true);
        $descriptions = json_decode($descriptions_all_languages[CustomModuleManager::DEFAULT_LANGUAGE], true);

        foreach ($config as $module_name => $module_config) {
            $config[$module_name]['params']['title']       = $titles[$module_name] ?? '';
            $config[$module_name]['params']['description'] = $descriptions[$module_name] ?? '';
        }

        //Date added
        foreach ($config as $module_name => $module_config) {

            //If date added does not exist already, we insert the current date
            if (!isset($local_config[$module_name]['date_added'])) {
                $config[$module_name]['date_added'] = date("Y-m-d");
            }
            //Otherwise, we take the existing value
            else {
                $config[$module_name]['date_added'] = $local_config[$module_name]['date_added'];
            }
        }

        return $config;
    }

    /**
     * Get content of a module composer.json file decoded as an array
     *
     * @param string $module_folder
     *
     * @return array
     */
    public static function getComposerJson(string $module_folder): array {

        $json_file = __DIR__ . '/../../' . $module_folder . '/composer.json';

        //Get data from current JSON custom module list
        $json = self::readFromFile($json_file);

        $composer_json = json_decode($json, true);

        return $composer_json ?? [];
    }

    /**
     * Sort composer.json data
     *
     * @return void
     */
    public static function sortCpomposerJsonData(array &$composer_json): void {

        uksort($composer_json, function ($a, $b) {

                $property_order = [
                    'version',
                    'time',
                    'name',
                    'description',
                    'extra',
                    'require',
                    'replace',
                    'conflict',
                    'authors',
                    'homepage',
                    'support',
                    'type',
                    'keywords',
                    'license',
                    'repositories',
                    'custom_repositories',
                    'config',
                    'autoload',
                ];

                $orderMap = array_flip($property_order);

                $hasA = isset($orderMap[$a]);
                $hasB = isset($orderMap[$b]);

                if ($hasA && $hasB) {
                    return $orderMap[$a] <=> $orderMap[$b];
                }
                else if ($hasA) {
                    return -1;
                }
                else if ($hasB) {
                    return 1;
                }
                else {
                    return strcmp($a, $b);
                }
            }
        );

        return;
    }

    /**
     * Whether a certain module version exists
     *
     * @param array  $module_versions
     * @param string $version
     *
     * @return bool
     */
    public static function versionExists(array $module_versions, string $version): bool {

        foreach($module_versions as $module_version) {

            if ($module_version['version'] === $version) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get version before
     *
     * @param array  $module_versions
     * @param string $version
     *
     * @return string
     */
    public static function getVersionBefore(array $module_versions, string $version): string {

        uasort($module_versions, function (array $a, array $b) {
                version_compare($a['version'], $b['version'], ">=");
            }
        );

        $version_before = '';

        foreach($module_versions as $module_version) {

            if ($module_version['version'] === $version) {
                return $version_before;
            }

            $version_before = $module_version['version'];
        }

        return '';
    }

    /**
     * Remove version
     *
     * @param array  $custom_module_list
     * @param string $package_name
     * @param string $version
     *
     * @return void
     */
    public static function removeVersion(array &$custom_module_list, string $package_name, string $version): void {

        $reduced_module_versions = [];

        if (isset($custom_module_list['packages'][$package_name])) {
            $module_versions = $custom_module_list['packages'][$package_name];
        }
        else {
            return;
        }

        foreach($module_versions as $module_version) {
            if ($module_version['version'] !== $version) {
                $reduced_module_versions[] = $module_version;
            }
        }

        //Replace module versions with reduced versions
        $custom_module_list['packages'][$package_name] = $reduced_module_versions;

        return;
    }

    /**
     * Compare two module version number strings
     *
     * @param string $module_name
     * @param string $version1,
     * @param string $version2,
     *
     * @return int Returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower
     */
    public static function versionCompare(string $module_name, string $version1, $version2): int
    {
        return version_compare(self::normalizeVersion($module_name, $version1), self::normalizeVersion($module_name, $version2));
    }

    /**
     * Normalize a module version number strings
     *
     * @param string $module_name
     * @param string $version,
     *
     * @return string
     */
    public static function normalizeVersion(string $module_name, string $version): string
    {
        $module_name = ModuleUpdateServiceConfiguration::getStandardModuleName($module_name);
        $prefix_list = ModuleUpdateServiceConfiguration::getPrefixList();

        //Only proceed, if prefix is found
        if (array_key_exists($module_name, $prefix_list)) {

            $replaced_version = str_replace($prefix_list[$module_name], '', $version, $count);

            // Only replace if prefix found once at start of version string
            if (strpos($version, $prefix_list[$module_name], 0) === 0 && $count === 1) {

                // Replace
                $version = $replaced_version;
            }
        }

        return $version;
    }

    /**
     * Whether the module runs with the webtrees version of this installation
     *
     * @return bool
     */
    public static function runsWithInstalledWebtreesVersion(): bool
    {
        if (version_compare(Webtrees::VERSION, self::MINIMUM_WEBTREES_VERSION, '>=')) {
            return true;
        }

        return false;
    }

    /**
     * Remember if a GitHub communication occured. Return true if it is the force occurance
     *
     * @return bool
     */
    public static function rememberGithubCommunciationError(): bool {

        //If GitHub communication has already occured before
        if (self::$github_communication_error) {
            return true;
        }

        //Remember error for further requests
        self::$github_communication_error = true;

        return false;
    }

    /**
     * Whether the current version is the latest version of the module
     *
     * @return bool
     */
    public function isLowerThanLatestVersion(): bool {

        //If latest version information is already available
        if (isset(self::$is_lower_than_latest_version)) {
            return self::$is_lower_than_latest_version;
        }
        else {
            $current_version = self::CUSTOM_VERSION;

            //Get the latest release from GitHub
            $github_api_token = $this->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, '');

            try {
                $latest_version = GithubService::getLatestReleaseTag(self::GITHUB_REPO, $github_api_token);

                //Remember in static variable
                self::$is_lower_than_latest_version = version_compare($current_version, $latest_version) < 0;
            }
            catch (GithubCommunicationError $ex) {
                //Cant connect to GitHub
            }
        }

        return self::$is_lower_than_latest_version ?? false;
    }

    /**
     * Get a short module name, for example to be used for storing module preferences
     *
     * @param string $module_name
     *
     * @return string
     */
    public static function getShortModuleName(string $module_name): string {

        return substr($module_name, 0, 25) . '_';
    }

    /**
     * Read the content of a file to a string
     *
     * @param string $file
     *
     * @return string
     */
    public static function readFromFile(string $file): string {

        try {
            //Code from: Fisharebest\Webtrees\Cli\Commands\TreeImport, last check: 2026-08-28
            $total_bytes  = filesize($file);
            $bytes_loaded = 0;

            $fp = fopen($file, 'rb');
            $buffer = '';

            while ($bytes_loaded < $total_bytes) {
                $tmp = fread($fp, 8192);
                $buffer .= $tmp;
                $bytes_loaded += strlen($tmp);
            }
        }
        catch (Throwable $th) {
            // Fail gracefully
            $buffer = '';
        }

        return $buffer;
    }
}
