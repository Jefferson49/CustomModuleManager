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

namespace Jefferson49\Webtrees\Module\CustomModuleManager\Configuration;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Session;
use Jefferson49\Webtrees\Exceptions\GithubCommunicationError;
use Jefferson49\Webtrees\Helpers\GithubService;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

use RuntimeException;

/**
 * Configuration of the module update services
 */
class ModuleUpdateServiceConfiguration 
{
    //The language used
    private static $language = '';

    //The (default) titles corresponding to the current language
    private static $titles = [];

    //The (default) descriptions corresponding to the current language
    private static $descriptions = [];

    //A list with all tag prefixes, which module use at GitHub
    private static $tag_prefixes = [];

    //The module update service configuration
    private static $module_update_service_config = [];

    //The configuration for the module update services
	//Note: Still needed to generate the JSON configuration file!
    public const MODULE_UPDATE_SERVICE_CONFIG = [

        '_change_language_with_url_'      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/ChangeLanguageWithURL', 'tag_prefix' => 'v']],
        '_custom_filesystem_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/CustomFilesystem', 'tag_prefix' => 'v']],
        '_custom_module_manager_'         =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/CustomModuleManager', 'tag_prefix' => 'v']],
        '_extended_import_export_'        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/ExtendedImportExport', 'tag_prefix' => 'v']],
        '_my_custom_tags_'                =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/MyCustomTags', 'tag_prefix' => 'v']],
        '_oauth2_client_'                 =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/webtrees-oauth2-client', 'tag_prefix' => 'v']],
        '_repository_hierarchy_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/RepositoryHierarchy', 'tag_prefix' => 'v']],

        '_jc-fancy-imagebar_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-imagebar']],
        '_jc-fancy-research-links_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-research-links']],
        '_jc-fancy-treeview_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-treeview']],
        '_jc-theme-justlight_'            =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-theme-justlight', 'is_theme' => true]],
        '_jc-simple-footer_'              =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-footer']],        
        '_jc-simple-media-display_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-media-display']],
        '_jc-simple-menu_'                =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-menu']],          

        '_webtrees-lantmateriet_'         =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'ekdahl/webtrees-lantmateriet', 'no_release' => true, 'default_branch' => 'main']],       
        '_webtrees-primer-theme_'         =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'ekdahl/webtrees-primer-theme', 'is_theme' => true]],

        '_GVExport_'                      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Neriderc/GVExport', 'tag_prefix' => 'v']],

        '_webtrees-descendants-chart_'    =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-descendants-chart']],
        '_webtrees-fan-chart_'            =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-fan-chart']],
        '_webtrees-pedigree-chart_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-pedigree-chart']],

        '_myartjaub_ruraltheme_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'jon48/webtrees-theme-rural', 'is_theme' => true]],

        '_huhwt-cce_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-cce', 'tag_prefix' => 'v']],
        '_huhwt-xtv_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-xtv', 'tag_prefix' => 'v']],
        '_huhwt-wttam_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-wttam', 'tag_prefix' => 'v']],
        '_huhwt-wtlin_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-wtlin', 'tag_prefix' => 'v']],
        '_huhwt-tsm_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-tsm', 'tag_prefix' => 'v']],
        '_huhwt-mtv_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-mtv', 'tag_prefix' => 'v']],

        '_vesta_classic_look_and_feel_'   =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_classic_laf']],
        '_vesta_clippings_cart_'          =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_clippings_cart']],
        '_vesta_common_'                  =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_common']],
        '_vesta_extended_relationships_'  =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_extended_relationships']],
        '_vesta_personal_facts_'          =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_personal_facts']],
        '_vesta_relatives_'               =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_relatives']],
        '_vesta_gov4webtrees_'            =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_gov4webtrees']],
        '_vesta_places_and_pedigree_map_' =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_places_and_pedigree_map']],
        '_vesta_research_suggestions_'    =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_research_suggestions']],
        '_vesta_shared_places_'           =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_shared_places']],
        '_vesta_location_data_'           =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_location_data']],

        '_sosa20_'                        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'gustine/sosa20']],

        '_hh_extended_family_'            =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'hartenthaler/hh_extended_family', 'tag_prefix' => 'v']],
        '_hh_legal_notice_'               =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'hartenthaler/hh_legal_notice', 'tag_prefix' => 'v']],
        '_german-chancellors-presidents_' =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'hartenthaler/german-chancellors-presidents', 'tag_prefix' => 'v']],
        '_german-wars-battles-worldwide_' =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'hartenthaler/german-wars-battles-worldwide', 'tag_prefix' => 'v']],
        '_gramps-historical-facts_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'hartenthaler/gramps-historical-facts', 'tag_prefix' => 'v']],

        '_family-tree-home_'              =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'miqrogroove/family-tree-home', 'get_latest_version_from_github' => true]],

        '_ArgonLight_'                    =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => '06Games/Webtrees-ArgonLight', 'is_theme' => true]],       
        '_evang_mailsystem_'              =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => '06Games/Webtrees-MailSystem', 'no_release' => true, 'default_branch' => 'main']],

        '_webtrees-branch-statistics_'    =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'squatteur/webtrees-branch-statistics', 'tag_prefix' => 'v']],       

        '_topola_'                        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'PeWu/topola-webtrees']],

        '_mitalteli-show-xref_'           =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'elysch/webtrees-mitalteli-show-xref']], 
        '_mitalteli-chart-family-book_'   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'elysch/webtrees-mitalteli-chart-family-book']],

        '_webtrees-HTML-block-advanced_'  =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'photon-flip/webtrees-HTML-block-advanced', 'tag_prefix' => 'v']], 
        '_watermark-module_'              =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'photon-flip/watermark-module', 'tag_prefix' => 'v']],

        '_webtrees-faces_'                =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'UksusoFF/webtrees-faces', 'tag_prefix' => 'v']],   
        '_webtrees-photos_'               =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'UksusoFF/webtrees-photos', 'tag_prefix' => 'v']],
        '_webtrees-reminder_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'UksusoFF/webtrees-reminder', 'tag_prefix' => 'v']],
        '_webtrees-tree_view_full_screen_'=>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'UksusoFF/webtrees-tree_view_full_screen', 'tag_prefix' => 'v']],
        '_webtrees-mdi_'                  =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'UksusoFF/webtrees-mdi', 'tag_prefix' => 'v']],
        
        '_jp-theme-colors_'               =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'jpretired/jp-theme-colors', 'is_theme' => true, 'get_latest_version_from_github' => true , 'tag_prefix' => 'v']],       
        '_jp-main-menu-manual_'           =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'jpretired/jp-main-menu-manual', 'tag_prefix' => 'v']],

        '_telegram_'                      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Tywed/telegram', 'tag_prefix' => 'v.']],       
        '_news-menu_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Tywed/news-menu', 'tag_prefix' => 'v']],

        '_finnish-historical-facts_'      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'ardhtu/finnish-historical-facts', 'no_release' => true, 'default_branch' => 'master']],

        '_fam-nav-parents-last_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'tronsmit/fam-nav-parents-last', 'no_release' => true, 'default_branch' => 'main']],

        '_linkenhancer_'                  =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'bschwede/linkenhancer', 'tag_prefix' => 'v']],

    ];

    private const MODULES_INSTALLATION_FAILS = [

        '_custom-css_'                    =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo'  => 'makitso/custom-css']],
        //Unusual folder structure; disabled by default: modules_v4/custom-css-1.0.19/custom-css.disable/module.php

        '_SA-history-4-webtrees_'         =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo'  => 'tronsmit/SA-history-4-webtrees']],
        //Creates errors if module folder is renamed (e.g. from "SA-history-4-webtrees-1.1.0" to "SA-history-4-webtrees")
        //Seems not to occur in control panel, even if installed manually

    ];

    private const MODULES_NOT_RELEASED = [

        '_my_custom_tags_'                =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/MyCustomTags']],

    ];

    private const MODULES_TO_CLARIFY = [

        //"Changes" module (?)

        //No module, but substitute of webtrees core code
        '_new_modules_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'sevtor/modules']],
        '_new_reports_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'sevtor/modules']],

        //No top level folder

    ];

    /**
     * Get the module update service configuration
     * 
     * @param bool $use_local Use the local configuraton
     * 
     * @return array<string> module_name => module_config
     */
    public static function getModuleUpdateServiceConfig(bool $use_local = false): array {

        //If we shall use the local configuration
        if ($use_local) {
            return self::getLocalConfiguration();
        }

        //If the config is not already available, try to load the configuration from GitHub
        if (self::$module_update_service_config === []) {
            $module_service = New ModuleService();
            /** @var CustomModuleManager $custom_module_manager To avoid IDE warnings */
            $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());
            $github_api_token = $custom_module_manager->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, '');            

            //If we use a module version, which is suitable to the remote module update service configuration on GitHub.
            if (!$custom_module_manager->isLowerThanLatestVersion()) {
                //Rational: The remote configuration file on GitHub might not be compatible with the local code if the module is not updated to the latest version
                try {
                    $json_config = GithubService::getTextFileContent(CustomModuleManager::GITHUB_REPO, CustomModuleManager::CONFIG_GITHUB_BRANCH, CustomModuleManager::CONFIG_GITHUB_PATH, $github_api_token);
                    self::$module_update_service_config = json_decode($json_config);
                }
                catch (GithubCommunicationError $ex) {
                    //Take local configuration if we cannot download it from GitHub
                    self::$module_update_service_config = self::getLocalConfiguration();
                }
            }
        }

        //If we still dont have a configuration, take the local one
        if (self::$module_update_service_config === []) {
            self::$module_update_service_config = self::getLocalConfiguration();
        }

        return (array) self::$module_update_service_config;
    }

    /**
     * Get the local configuration (from a JSON file in the module)
     *
     * @return array<string> module_name => module_config
     */
    public static function getLocalConfiguration(bool $load_from_internet = true): array {

        $json_file = __DIR__ . '/' . CustomModuleManager::CONFIG_LOCAL_PATH;

        //Open file
        $file_system = new Filesystem(new LocalFilesystemAdapter(__DIR__));
        if (!$file_system->fileExists(CustomModuleManager::CONFIG_LOCAL_PATH)) {
             throw new RuntimeException('Cannot open file: ' . $json_file);
        }

        $local_json_config = $file_system->read(CustomModuleManager::CONFIG_LOCAL_PATH);
        $local_config = (array) json_decode($local_json_config);

        return $local_config;
    }

    /**
     * Get a list of all module names
     * 
     * @param bool $getVesta Whether to get Vesta modules only
     * 
     * @return array<string> module_name => standard_module_name
     */
    public static function getModuleNames(bool $getVesta = false): array
    {
        $module_names = [];
        $module_service = new ModuleService();
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class, true);

        //Initialize list with standard module names
        $module_update_service_config = self::getModuleUpdateServiceConfig();

        foreach($module_update_service_config as $module_name => $config) {
            if ($getVesta) {
                //Only add to list if has Vesta update service 
                if (    isset($module_update_service_config[$module_name]['update_service'])
                     && $module_update_service_config[$module_name]['update_service'] === 'VestaModuleUpdate') {

                    $module_names[$module_name] = $module_name;
                }
            }
            else {
                $module_names[$module_name] = $module_name;
            }
        }

        //Add non-standard module names (with non-standard folder names) to list
        foreach ($custom_modules as $custom_module) {
            $module_name = $custom_module->name();

            if (!in_array($module_name, $module_names)) {

                $standard_module_name = self::getStandardModuleName($module_name);
                unset($module_names[$standard_module_name]);

                if ($getVesta) {
                    //Only add to list if has Vesta update service                    
                    if (   isset($module_update_service_config[$standard_module_name]['update_service'])
                        && $module_update_service_config[$standard_module_name]['update_service'] === 'VestaModuleUpdate') {

                        //Add (or replace) module name to list
                        $module_names[$module_name] = $standard_module_name;
                    }
                }
                else {
                    //Add (or replace) module name to list
                    $module_names[$module_name] = $standard_module_name;
                }
            }
        }

        return $module_names;
    }

    /**
     * Get the configuration parameters for the update service of a module
     * 
     * @param string $module_name
     *  
     * @return array
     */
    public static function getParams(string $module_name): array
    {
        $config = self::getModuleUpdateServiceConfig();
        $standard_module_name = self::getStandardModuleName($module_name);

        if (array_key_exists($standard_module_name, $config)) {
            $module_config = (array) $config[$standard_module_name];
            $params = (array) $module_config['params'];
            return $params;
        }

        return [];
    }      

    /**
     * Get the update service name for a module
     * 
     * @param string $module_name
     *  
     * @return string
     */
    public static function getUpdateServiceName(string $module_name): string
    {
        $config = self::getModuleUpdateServiceConfig();
        $standard_module_name = self::getStandardModuleName($module_name);

        if (array_key_exists($standard_module_name, $config)) {
            $module_config = (array) $config[$standard_module_name];
            $update_service = (string) $module_config['update_service'];
            return $update_service;
        }

        return '';
    }

    /**
     * Get the standard module name; if not in list, match name by the module title
     * 
     * @param string $module_name
     *  
     * @return string
     */
    public static function getStandardModuleName(string $module_name): string
    {
        $default_language = CustomModuleManager::DEFAULT_LANGUAGE;
        $config = self::getModuleUpdateServiceConfig();
        $module_service = new ModuleService();
        $module = $module_service->findByName($module_name, true);

        if ($module !== null) {

            $titles_all_languages = DefaultTitlesAndDescriptions::MODULE_TITLES;
            $descriptions_all_languages = DefaultTitlesAndDescriptions::MODULE_TITLES;

            $map_titles_to_names = array_flip( json_decode($titles_all_languages[$default_language], true));
            $map_description_to_names = array_flip( json_decode($descriptions_all_languages[$default_language], true));
            $current_language = Session::get('language', '');

            //Set language to default language, i.e. en-US
            $default_language = CustomModuleManager::DEFAULT_LANGUAGE;
            I18N::init($default_language);
            Session::put('language', $default_language);

            $english_title = $module->title();
            $english_title = json_encode($english_title) !== false ? $english_title : mb_convert_encoding($english_title, 'UTF-8');

            $english_description = $module->description();
            $english_description = json_encode($english_description) !== false ? $english_description : mb_convert_encoding($english_description, 'UTF-8');

            //Reset language
            I18N::init($current_language);
            Session::put('language', $current_language);

            //Try to identify by title (if different from default title in AbstractModule)
            if ($module->title() !== 'Module name goes here'  && array_key_exists($english_title, $map_titles_to_names)) {
                return $map_titles_to_names[$english_title];
            }
            //Try to identify by description
            elseif (array_key_exists($english_description, $map_description_to_names)) {
                return $map_description_to_names[$english_description];
            }
        }

        //Try to identify by module name (e.g. based on folder in modules_v4)
        if (array_key_exists($module_name, $config)) {
            return $module_name;
        }

        return '';    
    }

    /**
     * Get a title from the stored configuration
     * 
     * @param string $module_name
     * @param string $language_tag
     *  
     * @return string
     */
    public static function getTitle(string $module_name, string $language_tag = CustomModuleManager::DEFAULT_LANGUAGE): string {

        self::initializeTitlesAndDescriptions();

        return self::$titles[$language_tag][$module_name] ?? '';
    }

    /**
     * Get a description from the stored configuration
     * 
     * @param string $module_name
     * @param string $language_tag
     *  
     * @return string
     */
    public static function getDescription(string $module_name, string $language_tag = CustomModuleManager::DEFAULT_LANGUAGE): string {

        self::initializeTitlesAndDescriptions();

        return self::$descriptions[$language_tag][$module_name] ?? '';
    }

    /**
     * Initialize the values (i.e. titles, descriptions) for a certain language
     */
    public static function initializeTitlesAndDescriptions() {

        $current_language = Session::get('language', CustomModuleManager::DEFAULT_LANGUAGE);

        //If language has changed or not initialied yet
        if (!in_array($current_language, [self::$language, CustomModuleManager::DEFAULT_LANGUAGE]) OR self::$titles === []) {

            //Set language
            self::$language = $current_language;

            $titles_all_languages = DefaultTitlesAndDescriptions::MODULE_TITLES;
            $descriptions_all_languages = DefaultTitlesAndDescriptions::MODULE_DESCRIPTIONS;

            //Values for default language
            $titles = json_decode($titles_all_languages[CustomModuleManager::DEFAULT_LANGUAGE]);
            $descriptions = json_decode($descriptions_all_languages[CustomModuleManager::DEFAULT_LANGUAGE]);

            foreach ($titles as $module_name => $title) {
                self::$titles[CustomModuleManager::DEFAULT_LANGUAGE][$module_name] = $title;
            }

            foreach ($descriptions as $module_name => $description) {
                self::$descriptions[CustomModuleManager::DEFAULT_LANGUAGE][$module_name] = $description;
            }

            //Values for current language
            if (array_key_exists($current_language, $titles_all_languages)) {
                $titles = json_decode($titles_all_languages[$current_language]);

                foreach ($titles as $module_name => $title) {
                    self::$titles[$current_language][$module_name] = $title;
                }
            }
            if (array_key_exists($current_language, $descriptions_all_languages)) {
                $descriptions = json_decode($descriptions_all_languages[$current_language]);

                foreach ($descriptions as $module_name => $description) {
                    self::$descriptions[$current_language][$module_name] = $description;
                }
            }
        }

        return;
    }

    /**
     * Get a list with all tag prefixes used by the custom modules
     * 
     * @return array
     */
    public static function getPrefixList(): array {

        self::initializePrefixList();

        return self::$tag_prefixes;
    }

    /**
     * Initialize the prefix list for GitHub tags
     */
    public static function initializePrefixList() {

        //If prefixes are already initialized or no config is available (at early module start)
        if (self::$tag_prefixes !== [] OR self::$module_update_service_config === []) {
            return;
        }

        foreach (self::getModuleUpdateServiceConfig() as $module_name => $module_config) {

            $module_config = (array) $module_config;
            $params = (array) $module_config['params'];
            if (isset($params['tag_prefix'])) {
                $tag_prefix = $params['tag_prefix'];

                if (!in_array($tag_prefix, self::$tag_prefixes)) {
                    self::$tag_prefixes[] = $tag_prefix;
                }
            }
        }

        // Sort by string length (to avoid shorter prefixes to be substituted before longer prefixes)
        usort(self::$tag_prefixes, function($a, $b) {
            return strlen($b) - strlen($a); // Descending order
        });
    }
}
