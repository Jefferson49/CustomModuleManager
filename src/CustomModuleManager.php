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

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Log\CustomModuleLogInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use RuntimeException;

use function substr;


class CustomModuleManager extends AbstractModule implements
	ModuleCustomInterface, 
	ModuleConfigInterface,
    ModuleGlobalInterface,
    ModuleMenuInterface,
    CustomModuleLogInterface
{
    use ModuleCustomTrait;
    use ModuleConfigTrait;
    use ModuleGlobalTrait;
    use ModuleMenuTrait;

	//Custom module version
	public const CUSTOM_VERSION = '1.0.0';

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
	public const PREF_MODULE_VERSION = 'module_version';
    public const PREF_DEBUGGING_ACTIVATED = 'debugging_activated';

    //Prefences, Settings
	public const PREF_SETTING = 'setting';


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

		// Register a namespace for the views.
		View::registerNamespace(self::viewsNamespace(), $this->resourcesFolder() . 'views/');
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
                        preg_match_all('/' . self::GITHUB_API_TAG_NAME_PREFIX . '\d+\.\d+\.\d+/', $content, $matches, PREG_OFFSET_CAPTURE);

						if(!empty($matches[0]))
						{
							$version = $matches[0][0][0];
							$version = substr($version, strlen(self::GITHUB_API_TAG_NAME_PREFIX));	
						}
						else
						{
							$version = $this->customModuleVersion();
						}

                        return $version;
                    }
                } catch (GuzzleException $ex) {
                    // Can't connect to the server?
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
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleMenuInterface::getMenu()
     */
    public function getMenu(Tree $tree): ?Menu
    {
		return null;
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
                'title'              => $this->title(),
                self::PREF_SETTING   => boolval($this->getPreference(self::PREF_SETTING, '1')),
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
        $save      = Validator::parsedBody($request)->string('save', '');
		
        $setting   = Validator::parsedBody($request)->boolean(self::PREF_SETTING, false);

        //Save the received settings to the user preferences
        if ($save === '1') {
			$this->setPreference(self::PREF_SETTING, $setting ? '1' : '0');
        }

        //Finally, show a success message
        $message = I18N::translate('The preferences for the module "%s" were updated.', $this->title());
        FlashMessages::addMessage($message, 'success');	

        return redirect($this->getConfigLink());
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
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class);
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
}
