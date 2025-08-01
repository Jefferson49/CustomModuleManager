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
use Fisharebest\Webtrees\Services\ModuleService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Exceptions\CustomModuleManagerException;


/**
 * Update API for a custom module, which is hosted in a Github repository
 */
class GithubModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface 
{#
    const NAME = 'GitHub';

    //The Github repository of the module, e.g. Jefferson49/CustomModuleManager
    protected string $github_repo;

    //Whether we shall get the latest version from Github instead from the module itself
    protected bool $get_latest_version_from_github;

    //A tag prefix for the module version, e.g. 'v' ('1.2.3' => 'v1.2.3')
    protected string $tag_prefix;

    //Whether the Github repository does not have a release
    protected bool $no_release;

    //The default branch of the Github repository. Used to download the source ZIP file for some modules, which do not provide a release
    protected string $default_branch;

    
    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     * 
     * @return void
     */
    public function __construct(string $module_name, array $params) {

        $this->module_name = $module_name;

        if (array_key_exists('github_repo', $params)) {
            $this->github_repo = $params['github_repo'];
        }
        else {
            throw new CustomModuleManagerException(I18N::translate('Could not create the %s update service. Configuration parameter "%s" missing.', basename(str_replace('\\', '/', __CLASS__)) , 'github_repo'));
        }

        if (array_key_exists('get_latest_version_from_github', $params)) {
            $this->get_latest_version_from_github = $params['get_latest_version_from_github'];
        }
        else {
            $this->get_latest_version_from_github = false;
        }

        if (array_key_exists('tag_prefix', $params)) {
            $this->tag_prefix = $params['tag_prefix'];
        }
        else {
            $this->tag_prefix = '';
        }

        if (array_key_exists('no_release', $params)) {
            $this->no_release = $params['no_release'];
        }
        else {
            $this->no_release = false;
        }

        if (array_key_exists('default_branch', $params)) {
            $this->default_branch = $params['default_branch'];
        }
        else {
            $this->default_branch = '';
        }

        $this->is_theme = self::identifyThemeFromConfig($module_name, $params);
    }

    /**
     * The name of the module update service
     *
     * @return string
     */
    public function name(): string {

        return self::NAME;
    }

    /**
     * Where can we download the module
     * 
     * @param  string $version  The version of the module; latest version if empty
     * @return string
     */
    public function downloadUrl(string $version = ''): string
    {
        //For certain module, which do not provide a release, we take the source code ZIP file of the default branch
        if ($this->no_release) {
            return 'https://github.com/' . $this->github_repo . '/archive/refs/heads/' . $this->default_branch . '.zip';
        }

        //Remove line feed at end of version
        if (strpos($version, "\n", -1)) {
            $version = substr($version, 0, strlen($version) - 1);
        }

        //Add prefix, if module tags have a prefix
        if ($version !== '' && strlen($version) > strlen($this->tag_prefix)) {

            //If version does not start with prefix, add prefix
            if (substr($version, 0, strlen($this->tag_prefix)) !== $this->tag_prefix) {
                $version = $this->tag_prefix . $version;
            } 
        }

        $download_url   = '';
        $github_api_url = 'https://api.github.com/repos/'. $this->github_repo . '/releases/';

        // If no tag is provided get the download URL of the latest release
        if ($version === '') {
            $url = $github_api_url . 'latest';
        }
        // Get the download URL for a certain tag
        else {
            $url = $github_api_url . 'tags/' . $version;
        }

        // Get the download URL from Github
        try {
            $client = new Client(
                [
                'timeout'       => 3,
                ]
            );

            $options = [];
            $module_service = New ModuleService();
            $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());
            $github_api_token = $custom_module_manager->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, '');

            if ($github_api_token !== '') {
                $options['headers'] = ['Authorization' => 'Bearer ' . $github_api_token];
            }

            $response = $client->get($url, $options);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $content = $response->getBody()->getContents();
                
                if (preg_match('/"browser_download_url":"([^"]+?)"/', $content, $matches) === 1) {
                    $download_url = $matches[1];
                }
                elseif (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                    $download_url = 'https://github.com/' . $this->github_repo . '/archive/refs/tags/' . $matches[1] . '.zip';
                }
            }
        } catch (GuzzleException $ex) {
            // Can't connect to the server?
            $message =  I18N::translate('Communication error with %s', $this->name()) . ': ' . 
                        I18N::translate('Cannot retrieve download URL.') . "\n" .
                        $ex->getMessage();
            throw new CustomModuleManagerException($message);
        }

        return $download_url;
    }

    /**
     * Where can we find a documentation for the module
     * 
     * @return string
     */
    public function documentationUrl(): string
    {
        return 'https://github.com/'. $this->github_repo;
    }

    /**
     * Get the GitHub repository
     * 
     * @return string
     */
    public function getGithubRepo(): string
    {
        return $this->github_repo;
    }

    /**
     * Fetch the latest version of this module
     *
     * @param bool $fetch_latest  Whether to fetch the latest version, e.g. from a GitHub repository 
     * 
     * @return string
     */
    public function customModuleLatestVersion(bool $fetch_latest = false): string
    {
        $module = $this->getModule();

        //If the installed module is available, try to get latest version from the module
        if (!$fetch_latest && $module !== null && !$this->get_latest_version_from_github) {
            $version = $module->customModuleLatestVersion();

            if ($version !== '') {
                return $version;
            }
        }

        //For certain module, which do not provide a release, it is not possible to retrieve the latest version from Github
        if ($this->no_release) {
            return '';
        }

        //Otherwise, try to get the latest version from Github
        if ($this->github_repo !== '') {

            $github_api_url = 'https://api.github.com/repos/'. $this->github_repo . '/releases/latest';

            try {
                $client = new Client(
                    [
                    'timeout' => 3,
                    ]
                );

                $options = [];
                $module_service = New ModuleService();
                $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());
                $github_api_token = $custom_module_manager->getPreference(CustomModuleManager::PREF_GITHUB_API_TOKEN, '');

                if ($github_api_token !== '') {
                    $options['headers'] = ['Authorization' => 'Bearer ' . $github_api_token];
                }

                $response = $client->get($github_api_url, $options);

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
                    FlashMessages::addMessage(I18N::translate('Communication error with %s', self::NAME), 'danger');

                    //Set flag in order to avoid multiple flash messages
                    $custom_module_manager->setPreference(CustomModuleManager::PREF_GITHUB_COM_ERROR, '1');
                }
            }
        }

        return '';
    }
}
