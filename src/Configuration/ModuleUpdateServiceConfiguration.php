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


/**
 * Configuration of the module update services
 */
class ModuleUpdateServiceConfiguration 
{
    //The configuration for the module update services
    private const MODULE_UPDATE_SERVICE_CONFIG = [

        '_change_language_with_url_'      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/ChangeLanguageWithURL']],
        '_extended_import_export_'        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/ExtendedImportExport']],
        '_oauth2_client_'                 =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/webtrees-oauth2-client']],
        '_repository_hierarchy_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Jefferson49/RepositoryHierarchy']],

        '_jc-fancy-imagebar'              =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-imagebar']],
        '_jc-fancy-research-links_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-research-links']],
        '_jc-fancy-treeview_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-treeview']],
        '_webtrees-simple-footer_'        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-footer']],
        '_webtrees-simple-media-display_' =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-media-display']],
        '_webtrees-simple-menu_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-menu']],
        '_jc-theme-justlight_'            =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-theme-justlight']],    

        '_webtrees-primer-theme_'         =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'ekdahl/webtrees-primer-theme']],

        '_GVExport_'                      =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'Neriderc/GVExport']],

        '_webtrees-descendants-chart_'    =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-descendants-chart']],
        '_webtrees-fan-chart_'            =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-fan-chart']],
        '_webtrees-pedigree-chart_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'magicsunday/webtrees-pedigree-chart']],

        '_myartjaub_ruraltheme_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'jon48/webtrees-theme-rural']],

        '_huhwt-cce_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-cce']],
        '_huhwt-xtv_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-xtv']],
        '_huhwt-wttam_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-wttam']],
        '_huhwt-wtlin_'                   =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-wtlin']],
        '_huhwt-tsm_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-tsm']],
        '_huhwt-mtv_'                     =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'huhwt/huhwt-mtv']],

        '_vesta_classic_look_and_feel_'   =>  ['update_service' => 'VestaModuleUpdate', 'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_classic_laf']],
        '_vesta_clippings_cart_'          =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
        '_vesta_common_'                  =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
        '_vesta_extended_relationships_'  =>  ['update_service' => 'VestaModuleUpdate', 'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_extended_relationships']],
        '_vesta_personal_facts_'          =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
        '_vesta_relatives_'               =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
        '_vesta_gov4webtrees_'            =>  ['update_service' => 'VestaModuleUpdate', 'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_gov4webtrees']],
        '_vesta_places_and_pedigree_map_' =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
        '_vesta_research_suggestions_'    =>  ['update_service' => 'VestaModuleUpdate', 'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_research_suggestions']],
        '_vesta_shared_places_'           =>  ['update_service' => 'VestaModuleUpdate', 'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_shared_places']],
        '_vesta_location_data_'           =>  ['update_service' => 'VestaModuleUpdate', 'params' => []],
    ];

    private const MODULE_TITLES = [
        '_extended_import_export_'        =>  'Extended Import/Export',
    ];

    private const MODULE_DESCRIPTIONS = [
        '_extended_import_export_'        =>  'A custom module for advanced GEDCOM import, export, and filter operations. The module also supports remote downloads/uploads/filters via URL requests.',
    ];

    /**
     * Get the configuration for the module updates services
     * 
     * @param bool $getVesta Whether to get Vesta modules only
     * 
     * @return array
     */
    public static function getModuleNames(bool $getVesta = false): array
    {
        $module_names = [];
        $module_service = new ModuleService();
        $custom_modules = $module_service->findByInterface(ModuleCustomInterface::class, true);

        //Initialize list with standard module names
        $module_update_service_config = self::MODULE_UPDATE_SERVICE_CONFIG;

        foreach($module_update_service_config as $module_name => $config) {
            if ($getVesta) {
                //Only add to list if has Vesta update service 
                if (isset($module_update_service_config[$module_name]['update_service']) && $module_update_service_config[$module_name]['update_service'] === 'VestaModuleUpdate') {
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

            if (!array_key_exists($module_name, $module_names)) {
                if ($getVesta) {
                    //Only add to list if has Vesta update service 
                    if (isset($module_update_service_config[$module_name]['update_service']) && $module_update_service_config[$module_name]['update_service'] === 'VestaModuleUpdate') {

                        //Add (or replace) module name to list
                        $standard_module_name = self::getStandardModuleName($module_name);
                        $module_names[$standard_module_name] = $module_name;
                    }
                }
                else {
                    //Add (or replace) module name to list
                    $standard_module_name = self::getStandardModuleName($module_name);
                    $module_names[$standard_module_name] = $module_name;
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
        $config = self::MODULE_UPDATE_SERVICE_CONFIG;
        $standard_module_name = self::getStandardModuleName($module_name);

        if (array_key_exists($standard_module_name, $config)) {
            return $config[$standard_module_name]['params'];
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
        $config = self::MODULE_UPDATE_SERVICE_CONFIG;
        $standard_module_name = self::getStandardModuleName($module_name);

        if (array_key_exists($standard_module_name, $config)) {
            return $config[$standard_module_name]['update_service'];
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
        $config = self::MODULE_UPDATE_SERVICE_CONFIG;

        //Take module name if is in list
        if (array_key_exists($module_name, $config)) {
            return $module_name;
        }

        //Otherwise try to get the standard module name by matching the module titles
        $module_service = new ModuleService();
        $module = $module_service->findByName($module_name, true);

        if ($module !== null) {

            $map_titles_to_names = array_flip(self::MODULE_TITLES);
            $current_language = Session::get('language', '');

            //Set language to default language, i.e. en-US
            $default_language = 'en-US';
            I18N::init($default_language);
            Session::put('language', $default_language);

            $english_title = $module->title();

            //Reset language
            I18N::init($current_language);
            Session::put('language', $current_language);

            if (array_key_exists($english_title, $map_titles_to_names)) {
                return $map_titles_to_names[$english_title];
            }
        }

        return '';    
    }

    /**
     * Get the default title
     * 
     * @param string $module_name
     *  
     * @return string
     */
    public static function getDefaultTitle(string $module_name): string {

        $module_titles = self::MODULE_TITLES;

        if (array_key_exists($module_name, $module_titles)) {
            return $module_titles[$module_name];
        }

        else return '';
    }

    /**
     * Get the default description
     * 
     * @param string $module_name
     *  
     * @return string
     */
    public static function getDefaultDescription(string $module_name): string {

        $module_descriptions = self::MODULE_DESCRIPTIONS;

        if (array_key_exists($module_name, $module_descriptions)) {
            return $module_descriptions[$module_name];
        }

        else return '';
    }
}
