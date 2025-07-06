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
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Update API for a custom module, which is hosted in a Github repository
 */
class GithubModuleUpdate implements CustomModuleUpdateInterface 
{
    //The custom module
    protected ?AbstractModule $module;

    //The custom module name
    protected string $module_name;

    //The Github repository of the module, e.g. Jefferson49/CustomModuleManager
    protected string $github_repo;

    //The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
    protected string $tag_prefix = '';

    //The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
    protected string $asset_name;

    //The top level folder in the ZIP file of the custom module
    protected string $zip_folder;


    /**
     * @param string $github_repo                   The Github repository of the module, e.g. Jefferson49/CustomModuleManager
     * @param string $tag_prefix                    The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
     * @param string $zip_folder                    The top level folder in the ZIP file of the custom module
     * @param string $module_name                   The custom module name
     * @param string $asset_name                    The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
     * @param AbstractModule $module  The custom module; or null if not installed yet
     * 
     * @return void
     */    

    public function __construct(
        string $github_repo,
        string $tag_prefix, 
        string $zip_folder,
        string $module_name = '',
        string $asset_name = '',
        ?AbstractModule $module = null
    ) {
        $this->github_repo = $github_repo;
        $this->tag_prefix  = $tag_prefix;
        $this->zip_folder  = $zip_folder;
        $this->module_name = $module_name !== '' ? $module_name : self::defaultModuleName($zip_folder);
        $this->asset_name  = $asset_name !== '' ? $asset_name : $this->defaultAssetName($zip_folder);
        $this->module      = $module;
    }

    /**
     * Alternative constructor for an installed custom module
     * 
     * @param AbstractModule $module       The custom module
     * @param string                $github_repo  The Github repository of the module, e.g. Jefferson49/CustomModuleManager
     * @param string                $tag_prefix   The tag prefix, which is used for Github releases, e.g. "v" in "v1.2.3"
     * @param string                $asset_name   The asset name (without tag and tag prefix), which the module uses for downloads in Github releases
     * @param string                $zip_folder   The top level folder in the ZIP file of the custom module
     * 
     * @return GithubModuleUpdate
     */    
    public static function constructFromModule(
        AbstractModule $module,
        string $github_repo,
        string $tag_prefix,
        string $zip_folder = '',
        string $asset_name = ''
    ): GithubModuleUpdate 
    {
        $installation_folder = self::getInstallationFolderFromModuleName($module->name());

        return new self(
            $github_repo, 
            $tag_prefix, 
            $zip_folder !== '' ? $zip_folder : $installation_folder,
            $module->name(),
            $asset_name !== '' ? $asset_name  : self::defaultAssetName($installation_folder),
            $module
        );
    }

    /**
     * A unique internal name for this module (based on the installation folder).
     *
     * @return string
     */
    public function name(): string
    {
        return $this->module_name;
    }

    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        if ($this->module === null) {
            return '';
        }

        return $this->module->customModuleVersion();
    }  

    /**
     * A URL that will provide the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        //If the installed module is available, try to get the URL from the module
        if ($this->module !== null) {
            $url = $this->module->customModuleLatestVersionUrl();

            if ($url !== '') {
                return $url;
            }
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

        if ($this->module === null ) {
            return '';
        }

        return self::getInstallationFolderFromModuleName($this->module->name());
    }

    /**
     * The top level folder in the ZIP file of the custom module
     *
     * @return string
     */
    public function getZipFolder(): string {

        return $this->zip_folder;
    }

    /**
     * Fetch the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        //If the installed module is available, try to get latest version from the module
        if ($this->module !== null) {
            $version = $this->module->customModuleLatestVersion();

            if ($version !== '') {
                return $version;
            }
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
     * Whether an upgrade is available for the custom module
     *
     * @return bool
     */
    public function upgradeAvailable(): bool
    {
        if ($this->module === null) {
            return false;
        }
        
        $latest_version  = $this->module->customModuleLatestVersion();
        $current_version = $this->module->customModuleVersion();

        return version_compare($latest_version, $current_version) > 0;
    }

    /**
     * The default asset name for a Github release (based on the installation folder name)
     * 
     * @param string $installation_folder The installation folder of the custom module
     * 
     * @return string
     */
    public static function defaultAssetName(string $installation_folder): string
    {
        return $installation_folder . '_';
    }

    /**
     * Get params for this custom module update service
     * 
     * @param GithubModuleUpdate $github_module_update
     * 
     * @return array<string>
     */
    public static function getParams(GithubModuleUpdate $github_module_update): array
    {
        return [
            'module_name' => $github_module_update->module_name,
            'github_repo' => $github_module_update->github_repo,
            'tag_prefix'  => $github_module_update->tag_prefix,
            'zip_folder'  => $github_module_update->zip_folder,
            'asset_name'  => $github_module_update->asset_name,
        ];
    }

    /**
     * Create a custom module upgrade service from a request
     * 
     * @param ServerRequestInterface $request         T
     *
     * @return GithubModuleUpdate
     */    
    public static function getModuleUpdateServiceFromRequest(ServerRequestInterface $request) : GithubModuleUpdate {

        $module_name         = Validator::queryParams($request)->string('module_name', '');
        $github_repo         = Validator::queryParams($request)->string('github_repo', '');
        $tag_prefix          = Validator::queryParams($request)->string('tag_prefix', '');
        $zip_folder          = Validator::queryParams($request)->string('zip_folder', '');
        $asset_name          = Validator::queryParams($request)->string('asset_name', '');

        $module_service = New ModuleService();
        $module = $module_service->findByName($module_name);

        //Create the upgrade service for the module
        if ($module !== null) {
            return GithubModuleUpdate::constructFromModule(
                $module,
                $github_repo,
                $tag_prefix,
                $zip_folder,
                $asset_name 
            );
        }
        else {
            return new GithubModuleUpdate(
                $github_repo,
                $tag_prefix,
                $zip_folder,
                $module_name,
                $asset_name
            );           
        }
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
     * A default name for a custom module
     * 
     * @param string $installation_folder_name  The installation folder in modules_v4
     * 
     * @return string
     */
    public static function defaultModuleName(string $installation_folder_name): string
    {
        return '_' . $installation_folder_name . '_';
    }  
}
