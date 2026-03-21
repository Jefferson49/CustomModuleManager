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

namespace Jefferson49\Webtrees\Module\CustomModuleManager\RequestHandlers;

use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\GithubModuleUpdate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 *  Show release notes modal
 */
class ReleaseNotesModal implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $module_name    = Validator::queryParams($request)->string('module_name', '');
        $module_title   = Validator::queryParams($request)->string('module_title', '');
        $latest_version = Validator::queryParams($request)->string('latest_version', '');

        /** @var CustomModuleManager $custom_module_manager  To avoid IDE warnings */
        $module_service = New ModuleService();
        $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());   

        /** @var GithubModuleUpdate $module_update_service */
        $module_update_service = CustomModuleUpdateFactory::make($module_name);

        $short_module_name = substr($module_name, 0, 25) . '_';
        $release_note = $module_update_service->getLatestReleaseNotes();

        if ($release_note === '') {
            $html = I18N::translate('No release notes available');
        }
        else {
            $html = Registry::markdownFactory()->markdown($release_note);
        }

        $this->layout = 'layouts/ajax';
        
        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/release_notes', [
                'title'          => I18N::translate('Release notes'),
                'module_name'    => $module_name,
                'module_title'   => $module_title,
                'latest_version' => $latest_version,
                'ignore_version' => $custom_module_manager->getPreference($short_module_name . CustomModuleManager::PREF_IGNORE_VERSION, ''),
                'release_notes'  => $html,
                'release_url'    => $module_update_service->getLatestReleaseURL(),
        ]);
    }
}
