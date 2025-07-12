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
use Fisharebest\Webtrees\I18N;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jefferson49\Webtrees\Module\CustomModuleManager\Exceptions\CustomModuleManagerException;


/**
 * Update API for a custom module, which is hosted in a Github repository
 */
class GithubModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface 
{#
    const NAME = 'Github';

    //The Github repository of the module, e.g. Jefferson49/CustomModuleManager
    protected string $github_repo;

    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     * 
     * @return void
     */
    public function __construct(string $module_name, array  $params) {

        $this->module_name    = $module_name;

        if (array_key_exists('github_repo', $params)) {
            $this->github_repo = $params['github_repo'];
        }
        else {
            throw new CustomModuleManagerException(I18N::translate('Could not create the %s update service. Configuration parameter "%s" missing.', basename(str_replace('\\', '/', __CLASS__)) , 'github_repo'));
        }
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
     * Where can we download a certain version of the module. Latest release if no tag is provided
     * 
     * @param string $tag  The tag of the release 
     * 
     * @throws CustomModuleManagerException
     * 
     * @return string
     */
    public function downloadUrl(string $tag = ''): string
    {
        $download_url   = '';
        $github_api_url = 'https://api.github.com/repos/'. $this->github_repo . '/releases/';

        // If no tag is provided get the download URL of the latest release
        if ($tag === '') {
            $url = $github_api_url . 'latest';
        }
        // Get the download URL for a certain tag
        else {
            $url = $github_api_url . 'tags/' . $tag;
        }

        // Get the download URL from Github
        try {
            $client = new Client(
                [
                'timeout' => 3,
                ]
            );

            $response = $client->get($url);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $content = $response->getBody()->getContents();
                
                if (preg_match('/"browser_download_url":"([^"]+?)"/', $content, $matches) === 1) {
                    $download_url = $matches[1];
                }
            }
        } catch (GuzzleException $ex) {
            // Can't connect to the server?
            $message = I18N::translate('Communication error with %s', $this->name()) . ': ' . I18N::translate('Cannot retrieve download URL.');
            throw new CustomModuleManagerException($message);
        }

        return $download_url;
    }

    /**
     * Fetch the latest version of this module
     *
     * @return string
     */
    public function customModuleLatestVersion(): string
    {
        $module = $this->getModule();

        //If the installed module is available, try to get latest version from the module
        if ($module !== null) {

            $version = $module->customModuleLatestVersion();

            if ($version !== '') {
                return $version;
            }
        }

        //As default, try to get the latest version from Github

        if ($this->github_repo === '') {
            return '';
        }

        $tag_name = '';
        $github_api_url = 'https://api.github.com/repos/'. $this->github_repo . '/releases/latest';

        try {
            $client = new Client(
                [
                'timeout' => 3,
                ]
            );

            $response = $client->get($github_api_url);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                $content = $response->getBody()->getContents();
                
                if (preg_match('/"tag_name":"([^"]+?)"/', $content, $matches) === 1) {
                    $tag_name = $matches[1];
                }
            }
        } catch (GuzzleException $ex) {
            // Can't connect to the server?
        }

        return $tag_name;
    }
}
