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

        '_jc-fancy-imagebar_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-imagebar']],
        '_jc-fancy-research-links_'       =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-research-links']],
        '_jc-fancy-treeview_'             =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-fancy-treeview']],
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

        '_vesta_classic_look_and_feel_'   =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_classic_laf']],
        '_vesta_clippings_cart_'          =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],
        '_vesta_common_'                  =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],
        '_vesta_extended_relationships_'  =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_extended_relationships']],
        '_vesta_personal_facts_'          =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],
        '_vesta_relatives_'               =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],
        '_vesta_gov4webtrees_'            =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_gov4webtrees']],
        '_vesta_places_and_pedigree_map_' =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],
        '_vesta_research_suggestions_'    =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_research_suggestions']],
        '_vesta_shared_places_'           =>  ['update_service' => 'VestaModuleUpdate',  'params' => ['github_repo' => 'vesta-webtrees-2-custom-modules/vesta_shared_places']],
        '_vesta_location_data_'           =>  ['update_service' => 'VestaModuleUpdate',  'params' => []],

        '_sosa20_'                        =>  ['update_service' => 'UrlModuleUpdate',    'params' => ['download_url' => 'https://gustine.eu/mode_emploi/sosa/sosa20-variant-2025-06b.zip', 'documentation_url' => 'https://gustine.eu/mode_emploi/sosa.php', 'latest_version' => '2025.06.06']],
    ];

    private const MODULES_TO_CHECK = [
        '_webtrees-simple-media-display_' =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-media-display']],
        '_webtrees-simple-footer_'        =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-footer']],
        '_webtrees-simple-menu_'          =>  ['update_service' => 'GithubModuleUpdate', 'params' => ['github_repo' => 'JustCarmen/webtrees-simple-menu']],
    ];

    private const MODULE_TITLES = [
        '_extended_import_export_'        =>  'Extended Import/Export',
        '_vesta_common_'                  =>  '⚶ Vesta Common'
    ];

    private const MODULE_TITLE_JSON       = '{"_change_language_with_url_":"ChangeLanguageWithURL module","_custom_module_manager_":"Custom Module Manager","_extended_import_export_":"Extended Import\/Export","_GVExport_":"GVExport","_huhwt-cce_":"\u210d Clippings cart enhanced","_huhwt-mtv_":"\u210d&\u210dwt MultTreeView","_huhwt-tsm_":"\u210d&\u210dwt Tagging service manager","_huhwt-wtlin_":"\u210d&\u210dwt LINchart","_huhwt-wttam_":"\u210d&\u210dwt TAMchart","_huhwt-xtv_":"Interactive tree XT \u210d&\u210dwt","_jc-fancy-imagebar_":"Fancy Imagebar","_jc-fancy-research-links_":"Fancy Research Links","_jc-fancy-treeview_":"Fancy Treeview","_jc-theme-justlight_":"JustLight","_myartjaub_ruraltheme_":"Rural","_my_custom_tags_":"My Custom Tags","_oauth2_client_":"OAuth2 Client","_ourfamilies-changes_":"Changes","_repository_hierarchy_":"Repository Hierarchy","_sosa20_":"Sosa-Stradonitz (Ahnentafel)","_sources_reference_numbers_list_":"Sources Ref.Nr.","_vesta_classic_look_and_feel_":"\u26b6 Vesta Classic Look & Feel","_vesta_clippings_cart_":"\u26b6 Vesta Clippings Cart","_vesta_common_":"\u26b6 Vesta Common","_vesta_extended_relationships_":"\u26b6 Vesta Extended Relationships","_vesta_gov4webtrees_":"\u26b6 Vesta Gov4Webtrees","_vesta_location_data_":"\u26b6 Vesta Webtrees Location Data Provider","_vesta_personal_facts_":"\u26b6 Vesta Facts and events","_vesta_places_and_pedigree_map_":"\u26b6 Vesta Places and Pedigree map","_vesta_relatives_":"\u26b6 Vesta Families","_vesta_research_suggestions_":"\u26b6 Vesta Research Suggestions","_vesta_shared_places_":"\u26b6 Vesta Shared Places","_webtrees-branch-statistics_":"Branch statistics","_webtrees-descendants-chart_":"Descendants chart","_webtrees-fan-chart_":"Fan chart","_webtrees-pedigree-chart_":"Pedigree chart","_webtrees-primer-theme_":"Primer"}';

    private const MODULE_DESCRIPTION_JSON = '{"_change_language_with_url_":"ChangeLanguageWithURL module","_custom_module_manager_":"A custom module to manage webtrees custom modules.","_extended_import_export_":"A custom module for advanced GEDCOM import, export, and filter operations. The module also supports remote downloads\/uploads\/filters via URL requests.","_GVExport_":"This is the \"GVExport\" module","_huhwt-cce_":"Add records from your family tree to the clippings cart and execute an action on them.","_huhwt-mtv_":"A treeview-diagram, showing the ancestors and descendants of an individual.","_huhwt-tsm_":"View and manage Tags for better structuring your Family Tree","_huhwt-wtlin_":"Download Gedcom information to client-side for postprocessing in LINEAGE.","_huhwt-wttam_":"Download Gedcom information to client-side for postprocessing in TAM.","_huhwt-xtv_":"An interactive tree, showing all the ancestors and descendants of an individual.","_jc-fancy-imagebar_":"An imagebar with small images between header and content.","_jc-fancy-research-links_":"A sidebar tool to provide quick links to popular research web sites.","_jc-fancy-treeview_":"A narrative overview of the descendants or ancestors of one family (branch).","_jc-theme-justlight_":"Theme \u2014 JustLight","_myartjaub_ruraltheme_":"Theme \u2014 Rural","_my_custom_tags_":"A module to provide custom tags, types, relationship descriptors, and roles in events","_oauth2_client_":"A custom module to implement a OAuth2 client for webtrees.","_ourfamilies-changes_":"A tab showing recent GEDCOM data changes for an individual.","_repository_hierarchy_":"A hierarchical structured list of the sources of an archive based on the call numbers of the sources","_sosa20_":"A sidebar showing Sosa-Stradonitz number of individuals.","_sources_reference_numbers_list_":"A list of reference numbers\/types for all sources in a repository","_vesta_classic_look_and_feel_":"A module adjusting all themes and other features, providing a look & feel closer to the webtrees 1.x version.","_vesta_clippings_cart_":"Select records from your family tree and save them as a GEDCOM file. Replacement for the original \'Clippings Cart\' module.","_vesta_common_":"A module providing common classes and translations for other \'Vesta\' custom modules. Make sure to enable this module if any other Vesta module is enabled.","_vesta_extended_relationships_":"A module providing various algorithms used to determine relationships. Includes a chart displaying relationships between two individuals, as a replacement for the original \'Relationships\' module. Also includes an extended \'Who is online\' block.","_vesta_gov4webtrees_":"A module integrating GOV (historic gazetteer) data.","_vesta_location_data_":"A module providing (non-GEDCOM-based) webtrees location data to other modules.","_vesta_personal_facts_":"A tab showing the facts and events of an individual. Replacement for the original \'Facts and events\' module.  Also extends facts and events on the family page. Also provides additional map links.","_vesta_places_and_pedigree_map_":"The Place hierarchy. Also show the location of events and the birthplace of ancestors on a map. Replacement for the original \'Place hierarchy\', \'Places\' and  \'Pedigree map\' modules.","_vesta_relatives_":"A tab showing the close relatives of an individual. Replacement for the original \'Families\' module.","_vesta_research_suggestions_":"A module providing suggestions for additional research, based on available sources.","_vesta_shared_places_":"A module providing support for shared places.","_webtrees-branch-statistics_":"Statistics of an individual\u2019s ancestors.","_webtrees-descendants-chart_":"An overview of an individual\u2019s descendants.","_webtrees-fan-chart_":"A fan chart of an individual\u2019s ancestors.","_webtrees-pedigree-chart_":"A pedigree chart of an individual\u2019s ancestors.","_webtrees-primer-theme_":"Theme \u2014 Primer"}';

    /**
     * Get a list of all module names
     * 
     * @param bool $getVesta Whether to get Vesta modules only
     * 
     * @return array<string> standard_module_name => module_name
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

            if (!array_key_exists($module_name, $module_names)) {

                $standard_module_name = self::getStandardModuleName($module_name);

                if ($getVesta) {
                    //Only add to list if has Vesta update service                    
                    if (    isset($module_update_service_config[$standard_module_name]['update_service'])
                         && $module_update_service_config[$standard_module_name]['update_service'] === 'VestaModuleUpdate') {

                        //Add (or replace) module name to list
                        $module_names[$standard_module_name] = $module_name;
                    }
                }
                else {
                    //Add (or replace) module name to list
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

        $module_titles = json_decode(self::MODULE_TITLE_JSON, true);

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

        $module_descriptions = json_decode(self::MODULE_DESCRIPTION_JSON, true);

        if (array_key_exists($module_name, $module_descriptions)) {
            return $module_descriptions[$module_name];
        }

        else return '';
    }
}
