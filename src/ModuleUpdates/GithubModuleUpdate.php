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
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Registry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


/**
 * Update API for a custom module, which is hosted in a Github repository
 */
class GithubModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface 
{
    //The custom module
    protected ModuleCustomInterface $module;

    //The custom module name
    protected string $module_name;

    //The Github repository of the module, e.g. Jefferson49/CustomModuleManager
    protected string $github_repo;

    //The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
    protected string $tag_prefix = '';

    //The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
    protected string $asset_name;

    //The installation folder of the custom module (in modules_v4)
    protected string $installation_folder_name;


    /**
     * @param string $github_repo                   The Github repository of the module, e.g. Jefferson49/CustomModuleManager
     * @param string $tag_prefix                    The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
     * @param string $installation_folder_name      The installation folder of the custom module (in modules_v4)
     * @param string $module_name                   The custom module name
     * @param string $asset_name                    The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
     * @param        ModuleCustomInterface $module  The custom module; or null if not installed yet
     * 
     * @return void
     */    
    public function __construct(
        string $github_repo, 
        $tag_prefix, 
        $installation_folder_name, 
        string $module_name = '', 
        $asset_name = '', 
        ?ModuleCustomInterface $module = null
    ) {
        $this->github_repo = $github_repo;
        $this->tag_prefix  = $tag_prefix;
        $this->installation_folder_name = $installation_folder_name;
        $this->module_name = $module_name !== '' ? $module_name : parent::defaultModuleName($installation_folder_name);
        $this->asset_name  = $asset_name !== '' ? $asset_name : $this->defaultAssetName($installation_folder_name);
        $this->module      = $module;
    }

    /**
     * Alternative constructor for an installed custom module
     * 
     * @param ModuleCustomInterface $module       The custom module
     * @param string                $github_repo  The Github repository of the module, e.g. Jefferson49/CustomModuleManager
     * @param string                $tag_prefix   The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
     * @param string                $asset_name   The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
     * 
     * @return GithubModuleUpdate
     */    
    public static function constructFromModule(ModuleCustomInterface $module, string $github_repo, $tag_prefix, $asset_name = '')
    {
        $installation_folder_name = parent::getInstallationFolderFromModuleName($module->name());

        return new self(
            $github_repo, 
            $tag_prefix, 
            $installation_folder_name,
            $module->name(),
            self::defaultAssetName($installation_folder_name),
            $module
        );
    }

    /**
     * A unique internal name for this module (based on the installation folder).
     *
     * @return string
     */
    final public function name(): string
    {
        return $this->module_name;
    }

    /**
     * A URL that will provide the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        //If the installed module is available, get URL from the module
        if ($this->module !== null) {
            return $this->module->customModuleLatestVersionUrl();
        }

        //As default, use the generic Github API
        return 'https://api.github.com/repos/'. $this->github_repo . '/releases/latest';
    } 

    /**
     * Where can we download the latest version of the module
     * 
     * @return string
     */
    public function downloadUrl(): string
    {
        $tag = $this->customModuleLatestVersion();

        if ($tag === '') {
            return '';
        }

        return 'https://github.com/' . $this->github_repo . '/releases/download/'. $this->tag_prefix . $tag . '/' . $this->asset_name . $this->tag_prefix . $tag . '.zip';
    }

    /**
     * Get the custom module installation folder name (within the modules_v4 folder)
     *
     * @return string
     */
    public function getInstallationFolder(): string {

        return $this->installation_folder_name;
    }

    /**
     * Fetch the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        //If the installed module is available, get latest version from the module
        if ($this->module !== null) {
            return $this->module->customModuleLatestVersion();
        }

        //As default, try to get the latest version from Github

        if ($this->github_repo = '') {
            return '';
        }


        return Registry::cache()->file()->remember(
            $this->module_name . '-latest-version',
            function (): string {        

                $latest_tag          = '';
                $url                 =  $this->customModuleLatestVersionUrl();
                $tag_search_pattern  = '"tag_name":"'. $this->tag_prefix;

                try {
                    $client = new Client(
                        [
                        'timeout' => 3,
                        ]
                    );

                    $response = $client->get($url);

                    if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                        $content = $response->getBody()->getContents();
                        preg_match_all('/' . $tag_search_pattern . '\d+\.\d+\.\d+/', $content, $matches, PREG_OFFSET_CAPTURE);

                        if(!empty($matches[0]))
                        {
                            $latest_tag = $matches[0][0][0];
                            $latest_tag = substr($latest_tag, strlen($this->tag_prefix));
                        }
                    }
                } catch (GuzzleException $ex) {
                    // Can't connect to the server?
                }

                return $latest_tag;
            },
            86400
        );        
    }

    /**
     * The default asset name for a Github release (based on the installation folder name)
     * 
     * @param string $installation_folder_name
     * 
     * @return string
     */
    public static function defaultAssetName(string $installation_folder_name): string
    {
        return $installation_folder_name . '_';
    }
}
