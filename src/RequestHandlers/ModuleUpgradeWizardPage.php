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
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\GithubModuleUpdate;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function basename;
use function e;
use function route;

/**
 * Upgrade to a new version of webtrees.
 */
class ModuleUpgradeWizardPage implements RequestHandlerInterface
{
    use ViewResponseTrait;

    private TreeService $tree_service;

    // The webtrees upgrade service
    private UpgradeService $upgrade_service;


    /**
     * @param TreeService                 $tree_service
     * @param UpgradeService              $upgrade_service
     */
    public function __construct(TreeService $tree_service, UpgradeService $upgrade_service)
    {
        $this->tree_service           = $tree_service;
        $this->upgrade_service        = $upgrade_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $continue    = Validator::queryParams($request)->string('continue', '');

        $module_upgrade_service = GithubModuleUpdate::getModuleUpdateServiceFromRequest($request);
        $params                 = GithubModuleUpdate::getParams($module_upgrade_service);      
 
        $title = I18N::translate('Upgrade wizard');

        $upgrade_available = $module_upgrade_service->upgradeAvailable();

        if ($upgrade_available && $continue === '1') {
            return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::steps', [
                'steps' => $this->wizardSteps($module_upgrade_service->downloadUrl(), $params),
                'title' => $title,
            ]);
        }

        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::wizard', [
            'current_version' => $module_upgrade_service->customModuleVersion(),
            'latest_version'  => $module_upgrade_service->customModuleLatestVersion(),
            'title'           => $title,            
        ] + $params);
    }

    /**
     * @return array<string>
     * 
     * @param string        $download_url
     * @param array<string> $params
     */
    private function wizardSteps(string $download_url, array $params): array
    {
        return [
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_CHECK] + $params)    => I18N::translate('Upgrade wizard'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_PREPARE] + $params)  => I18N::translate('Create a temporary folder…'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_DOWNLOAD] + $params) => I18N::translate('Download %s…', e($download_url)),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_UNZIP] + $params)    => I18N::translate('Unzip %s to a temporary folder…', e(basename($download_url))),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_COPY] + $params)     => I18N::translate('Copy files…'),
            ];
    }
}
