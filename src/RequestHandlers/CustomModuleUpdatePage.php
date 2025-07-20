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
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleThemeInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\Configuration\ModuleUpdateServiceConfiguration;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Manage custom module updates
 */
class CustomModuleUpdatePage implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $fetch_latest    = Validator::queryParams($request)->boolean('fetch_latest', false);
        $modules_to_show = Validator::queryParams($request)->string('modules_to_show', CustomModuleManager::PREF_SHOW_ALL);

        $this->layout = 'layouts/administration';
        
        $module_service = New ModuleService();

        //If a specific switch is turned on, we generate default titles and descriptions.
        if (CustomModuleManager::GENERATE_DEFAULT_TITLES_AND_DESCRIPTIONS) {
            CustomModuleManager::generateDefaultTitlesAndDescriptions();
        }

        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::module_update', [
            'title'           => I18N::translate('Custom Module Updates'),
            'module_names'    => ModuleUpdateServiceConfiguration::getModuleNames(),
            'custom_modules'  => $module_service->findByInterface(ModuleCustomInterface::class, true),
            'themes'          => $module_service->findByInterface(ModuleThemeInterface::class, true),
            'fetch_latest'    => $fetch_latest,
            'modules_to_show' => $modules_to_show,
        ]);
    }
}
