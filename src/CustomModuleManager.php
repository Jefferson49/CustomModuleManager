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
use Fig\Http\Message\StatusCodeInterface;
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
use Fisharebest\Webtrees\Module\ModuleListInterface;
use Fisharebest\Webtrees\Module\ModuleListTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Webtrees;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\GithubModuleUpdate;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\CustomModuleUpdatePage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleInformationModal;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardPage;
use Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers\ModuleUpgradeWizardStep;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use RuntimeException;


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
	public const CUSTOM_VERSION = 'v1.0.0-beta.2';

	//Github repository
	public const GITHUB_REPO = 'Jefferson49/CustomModuleManager';

	//Github API URL to get the information about the latest releases
	public const GITHUB_API_LATEST_VERSION = 'https://api.github.com/repos/'. self::GITHUB_REPO . '/releases/latest';
	public const GITHUB_API_TAG_NAME_PREFIX = '"tag_name":"v';

	//Author of custom module
	public const CUSTOM_AUTHOR = 'Markus Hemprich';

    //A list of custom views, which are registered by the module
    private Collection $custom_view_list;

    //Prefences, Settings
	public const PREF_MODULE_VERSION      = 'module_version';
    public const PREF_DEBUGGING_ACTIVATED = 'debugging_activated';
	public const PREF_GITHUB_API_TOKEN    = 'github_api_token';
	public const PREF_LAST_UPDATED_MODULE = 'last_updated_module';
    public const PREF_ROLLBACK_ONGOING    = 'rollback_ongoing';
    public const PREF_GITHUB_COM_ERROR    = 'Github_communication_error';
    public const PREF_MODULES_TO_SHOW     = 'modules_to_show';
    public const PREF_SHOW_ALL            = 'show_all_modules';
    public const PREF_SHOW_INSTALLED      = 'show_installed_modules';
    public const PREF_SHOW_NOT_INSTALLED  = 'show_not_installed_modules';
    public const PREF_SHOW_MENU_LIST_ITEM = 'show_menu_list_item';

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
    public const DEFAULT_LANGUAGE_PREFIX  = '[English:] ';


    //Session
    public const SESSION_WIZARD_ABORTED   = 'wizard_aborted';

    //Errors
    public const ERROR_MAX_LENGTH = 500;

    //Supported webtrees version
    public const SUPPORTED_WEBTREES_VERSION = '2.2';

    //Switch to generate new default titles and description (in class DefaultTitlesAndDescriptions.php)
    public const GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS = false;
    

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

        //Reset Github communication error
        $this->setPreference(self::PREF_GITHUB_COM_ERROR, '0');

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
        // No update URL provided.
        if (self::GITHUB_API_LATEST_VERSION === '') {
            return $this->customModuleVersion();
        }
        return Registry::cache()->file()->remember(
            $this->name() . '-latest-version',
            function (): string {
                try {
                    $client = new Client(
                        [
                        'timeout' => 3,
                        ]
                    );

                    $response = $client->get(self::GITHUB_API_LATEST_VERSION);

                    if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                        $content = $response->getBody()->getContents();

                        if (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                            return $matches[1];
                        }
                    }
                } catch (GuzzleException $ex) {
                    $module_service = New ModuleService();
                    $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());

                    if (!boolval($custom_module_manager->getPreference(CustomModuleManager::PREF_GITHUB_COM_ERROR, '0'))) {
                        FlashMessages::addMessage(I18N::translate('Communication error with %s', GithubModuleUpdate::NAME), 'danger');

                        //Set flag in order to avoid multiple flash messages
                        $custom_module_manager->setPreference(CustomModuleManager::PREF_GITHUB_COM_ERROR, '1');
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
        return (!self::runsWithInstalledWebtreesVersion() OR !Auth::isAdmin());
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
                'activated'                    => CustomModuleManager::runsWithInstalledWebtreesVersion(),
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
                $test_result = substr($module_update_service->testModuleUpdate(), 0, self::ERROR_MAX_LENGTH);

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

        //Set language to default language, i.e. en-US
        $current_language = Session::get('language', '');

        //Generate English titles and descriptions
        $language_tag = CustomModuleManager::DEFAULT_LANGUAGE;
        I18N::init($language_tag);
        Session::put('language', $language_tag);

        foreach ($custom_modules as $module) {

            $title = $module->title();
            $title = json_encode($title) !== false ? $title : mb_convert_encoding($title, 'UTF-8');

            $description = $module->description();
            $description = json_encode($description) !== false ? $description : mb_convert_encoding($description, 'UTF-8');

            $titles[$module->name()]       = $title;
            $descriptions[$module->name()] = $description;
        }

        //Reset language
        I18N::init($current_language);
        Session::put('language', $current_language);

        //Generate JSON for titles and descriptions
        $title_json       = json_encode($titles);
        $title_json       = str_replace("'", "\'", $title_json);
        $description_json = json_encode($descriptions);
        $description_json = str_replace("'", "\'", $description_json);

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
        fwrite($stream, "    public const MODULE_TITLES_JSON = '");
        fwrite($stream, $title_json . "';\n\n");
        fwrite($stream, "    public const MODULE_DESCRIPTIONS_JSON = '");
        fwrite($stream, $description_json . "';\n\n");
        fwrite($stream, "}\n");
        fclose($stream);
    }

    /**
     * Compare two module version number strings
     *
     * @param string $version1,
     * @param string $version2,
     * 
     * @return int|bool
     */
    public static function versionCompare(string $version1, $version2): int|bool
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
        //If version starts with 'v', remove first character
        if (strpos($version, 'v') === 0) {
            $version = substr($version, 1);
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
        if (substr(Webtrees::VERSION, 0, 3) === self::SUPPORTED_WEBTREES_VERSION) {
            return true;
        }

        return false;
    }
}
