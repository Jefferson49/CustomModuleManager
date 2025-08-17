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

use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
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
use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\GithubModuleUpdate;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\CustomModuleUpdatePage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleInformationModal;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardPage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardStep;
use Illuminate\Support\Collection;
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
	public const CUSTOM_VERSION = 'v1.0.5';

	//GitHub repository
	public const GITHUB_REPO = 'Jefferson49/CustomModuleManager';

	//Author of custom module
	public const CUSTOM_AUTHOR = 'Markus Hemprich';

    //A list of custom views, which are registered by the module
    private Collection $custom_view_list;

    //Whether a GiHub communication error occured
    private static bool $github_communication_error = false;

    //Whether the current version is lower than the latest version of the module
    private static bool $is_lower_than_latest_version;

    //Prefences, Settings
	public const PREF_MODULE_VERSION      = 'module_version';
    public const PREF_DEBUGGING_ACTIVATED = 'debugging_activated';
	public const PREF_GITHUB_API_TOKEN    = 'github_api_token';
	public const PREF_LAST_UPDATED_MODULE = 'last_updated_module';
    public const PREF_ROLLBACK_ONGOING    = 'rollback_ongoing';
    public const PREF_MODULES_TO_SHOW     = 'modules_to_show';
    public const PREF_SHOW_ALL            = 'show_all_modules';
    public const PREF_SHOW_INSTALLED      = 'show_installed_modules';
    public const PREF_SHOW_NOT_INSTALLED  = 'show_not_installed_modules';
    public const PREF_SHOW_MENU_LIST_ITEM = 'show_menu_list_item';

    //Configuraton
    public const CONFIG_GITHUB_BRANCH     = 'config';
    public const CONFIG_LOCAL_PATH        = 'module_update_service_configuration.json';
    public const CONFIG_GITHUB_PATH       = 'module_update_service_configuration.json';
    public const CONFIG_FILE_NAME         = '';

    //Actions
    public const ACTION_UPDATE            = 'action_update';
    public const ACTION_INSTALL           = 'action_install';

    //Routes
    public const ROUTE_WIZARD_PAGE        = '/module_upgrade_wizard_page';
    public const ROUTE_WIZARD_STEP        = '/module_upgrade_wizard_step';
    public const ROUTE_MODULE_UPDATE_PAGE = '/module_update_page';
    public const ROUTE_MODULE_INFO_MODAL  = '/module_info_modal';

    //Language
    public const DEFAULT_LANGUAGE         = 'en-US';
    public const DEFAULT_LANGUAGE_PREFIX  = "[English:]";


    //Session
    public const SESSION_WIZARD_ABORTED   = 'wizard_aborted';

    //Errors
    public const ERROR_MAX_LENGTH = 500;

    //Supported webtrees version
    public const MINIMUM_WEBTREES_VERSION = '2.2.3';

    //Switch to generate new default titles and description (in class DefaultTitlesAndDescriptions.php)
    public const GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS = false;

    //Switch to generate a json file with the custom module update configuration (in module_update_service_configuration.json)
    public const GENERATE_CUSTOM_MODULE_UPDATE_CONFIG = false;
    

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
        //Check update of module version
        $this->checkModuleVersionUpdate();

        //Initialize custom view list
        $this->custom_view_list = new Collection;

        //If a specific switch is turned on, we generate default titles and descriptions
        if (self::GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS) {
            self::generateDefaultTitlesAndDescriptions();
        }        

        //If a specific switch is turned on, we generate a json file for custom module update configuration
        if (self::GENERATE_CUSTOM_MODULE_UPDATE_CONFIG) {
            CustomModuleManager::generateModuleUpdateServiceConfig();
        }        

		// Register a namespace for the views.
		View::registerNamespace(self::viewsNamespace(), $this->resourcesFolder() . 'views/');

        //Register a route for the upgrade wizard page
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(ModuleUpgradeWizardPage::class, self::ROUTE_WIZARD_PAGE)
        ->allows(RequestMethodInterface::METHOD_POST);

        //Register a route for a upgrade wizard step
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(ModuleUpgradeWizardStep::class, self::ROUTE_WIZARD_STEP)
        ->allows(RequestMethodInterface::METHOD_POST);

        //Register a route for the custom module update page
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(CustomModuleUpdatePage::class, self::ROUTE_MODULE_UPDATE_PAGE)
        ->allows(RequestMethodInterface::METHOD_POST);

        //Register a route for the module information modal
        $router = Registry::routeFactory()->routeMap();                 
        $router
        ->get(ModuleInformationModal::class, self::ROUTE_MODULE_INFO_MODAL)
        ->allows(RequestMethodInterface::METHOD_POST);
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
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string
    {
        return dirname(__DIR__, 1) . '/resources/';
    }

    /**
     * Get the active module name, e.g. the name of the currently running module
     *
     * @return string
     */
    public static function activeModuleName(): string
    {
        return '_' . basename(dirname(__DIR__, 1)) . '_';
    }
    
    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleLatestVersion()
     */
    public function customModuleLatestVersion(): string
    {
        // If no GitHub repo is available
        if (self::GITHUB_REPO === '') {
            return $this->customModuleVersion();
        }

        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {

                try {
                    //Get latest release from GitHub
                    return GithubService::getLatestReleaseTag(self::GITHUB_REPO, $this->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, ''));
                }
                catch (GithubCommunicationError $ex) {
                    // Can't connect to GitHub?
                    if (!self::rememberGithubCommunciationError()) {
                        FlashMessages::addMessage(I18N::translate('Communication error with %s', GithubModuleUpdate::NAME), 'danger');
                    }
                }

                return $this->customModuleVersion();
            },
            86400
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/' . self::GITHUB_REPO;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $language
     *
     * @return array
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customTranslations()
     */
    public function customTranslations(string $language): array
    {
        $lang_dir   = $this->resourcesFolder() . 'lang/';
        $file       = $lang_dir . $language . '.mo';
        if (file_exists($file)) {
            return (new Translation($file))->asArray();
        } else {
            return [];
        }
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
     * Get the namespace for the views
     *
     * @return string
     */
    public static function viewsNamespace(): string
    {
        return self::activeModuleName();
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
        $this->checkCustomViewAvailability();

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

        //Save the received settings to the user preferences
        if ($save === '1') {
			$this->setPreference(self::PREF_GITHUB_API_TOKEN, $github_api_token);
			$this->setPreference(self::PREF_MODULES_TO_SHOW, $modules_to_show);
			$this->setPreference(self::PREF_SHOW_MENU_LIST_ITEM, $show_menu_list_item ? '1' : '0');
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
     * Check availability of the registered custom views and show flash messages with warnings if any errors occur 
     *
     * @return void
     */
    private function checkCustomViewAvailability() : void {

        $module_service = new ModuleService();
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class, true);
        $alternative_view_found = false;

        foreach($this->custom_view_list as $custom_view) {

            [[$namespace], $view_name] = explode(View::NAMESPACE_SEPARATOR, (string) $custom_view, 2);

            foreach($custom_modules->forget($this->activeModuleName()) as $custom_module) {

                $view = new View('test');

                try {
                    $file_name = $view->getFilenameForView($custom_module->name() . View::NAMESPACE_SEPARATOR . $view_name);
                    $alternative_view_found = true;
    
                    //If a view of one of the custom modules is found, which are known to use the same view
                    if (in_array($custom_module->name(), ['_jc-simple-media-display_', '_webtrees-simple-media-display_'])) {
                        
                        $message =  '<b>' . MoreI18N::xlate('Warning') . ':</b><br>' .
                                    I18N::translate('The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior. If using the %s module, it is strongly recommended to deactivate the "%s" module, because the identical functionality is also integrated in the %s module.', 
                                    '<b>' . $custom_module->title() . '</b>', $this->title(), $this->title(), $custom_module->title(), $this->title());
                    }
                    else {
                        $message =  '<b>' . MoreI18N::xlate('Warning') . ':</b><br>' . 
                                    I18N::translate('The custom module "%s" is activated in parallel to the %s custom module. This can lead to unintended behavior, because both of the modules have registered the same custom view "%s". It is strongly recommended to deactivate one of the modules.', 
                                    '<b>' . $custom_module->title() . '</b>', $this->title(),  '<b>' . $view_name . '</b>');
                    }
                    FlashMessages::addMessage($message, 'danger');
                }    
                catch (RuntimeException $e) {
                    //If no file name (i.e. view) was found, do nothing
                }
            }
            if (!$alternative_view_found) {

                $view = new View('test');

                try {
                    $file_name = $view->getFilenameForView($view_name);

                    //Check if the view is registered with a file path other than the current module; e.g. another moduleS probably registered it with an unknown views namespace
                    if (mb_strpos($file_name, $this->resourcesFolder()) === false) {
                        throw new RuntimeException;
                    }
                }
                catch (RuntimeException $e) {
                    $message =  '<b>' . MoreI18N::xlate('Error') . ':</b><br>' .
                                I18N::translate(
                                    'The custom module view "%s" is not registered as replacement for the standard webtrees view. There might be another module installed, which registered the same custom view. This can lead to unintended behavior. It is strongly recommended to deactivate one of the modules. The path of the parallel view is: %s',
                                    '<b>' . $custom_view . '</b>', '<b>' . $file_name  . '</b>');
                    FlashMessages::addMessage($message, 'danger');
                }
            }
        }
        
        return;
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
                $locale = $module->locale();

                return [$locale->languageTag() => $locale->endonym()];
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

        //Create JSON
		$config = ModuleUpdateServiceConfiguration::MODULE_UPDATE_SERVICE_CONFIG;
        $json_config = json_encode($config);

        try {
            fwrite($stream, $json_config);
        }
        catch (Throwable $th) {
            throw new RuntimeException('Cannot write to file: ' . $json_file);
        }

        return;
    }

    /**
     * Compare two module version number strings
     *
     * @param string $version1,
     * @param string $version2,
     * 
     * @return int Returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower
     */
    public static function versionCompare(string $version1, $version2): int
    {
        return version_compare(self::normalizeVersion($version1), self::normalizeVersion($version2));
    }      

    /**
     * Normalize a module version number strings
     *
     * @param string $version,
     * 
     * @return string
     */
    public static function normalizeVersion(string $version): string
    {
        //Remove prefix
        foreach (ModuleUpdateServiceConfiguration::getPrefixList() as $prefix) {
            if (strpos($version, $prefix) === 0) {
                $version = str_replace($prefix, '', $version);
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
            }
            catch (GithubCommunicationError $ex) {
                //Cant connect to GitHub
            }

            //Remember in static variable
            self::$is_lower_than_latest_version = self::versionCompare($current_version, $latest_version) < 0;
        }

        return self::$is_lower_than_latest_version ?? false;
    }
}
