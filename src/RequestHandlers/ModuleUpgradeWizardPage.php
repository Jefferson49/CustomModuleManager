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
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
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

        $continue       = Validator::queryParams($request)->string('continue', '');
        $module_name    = Validator::queryParams($request)->string('module_name', '');

        $module_upgrade_service = CustomModuleUpdateFactory::make($module_name);

        $title = MoreI18N::xlate('Upgrade wizard');

        $upgrade_available = $module_upgrade_service->upgradeAvailable();

        if ($upgrade_available && $continue === '1') {
            return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::steps', [
                'steps'       => $this->wizardSteps($module_name, $module_upgrade_service->downloadUrl()),
                'title'       => $title,
            ]);
        }

        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::wizard', [
            'module_name'     => $module_name,
            'current_version' => $module_upgrade_service->customModuleVersion(),
            'latest_version'  => $module_upgrade_service->customModuleLatestVersion(),
            'title'           => $title,
        ]);
    }

    /**
     * @param string $download_url
     * @param string $module_name
     * 
     * @return array<string>
     */
    private function wizardSteps(string $module_name, string $download_url): array
    {
        return [
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_CHECK, 'module_name' => $module_name])    => MoreI18N::xlate('Upgrade wizard'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_PREPARE, 'module_name' => $module_name])  => I18N::translate('Create temporary folders…'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_BACKUP, 'module_name' => $module_name])   => I18N::translate('Backup…'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_DOWNLOAD, 'module_name' => $module_name]) => MoreI18N::xlate('Download %s…', e($download_url)),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_UNZIP, 'module_name' => $module_name])    => MoreI18N::xlate('Unzip %s to a temporary folder…', e(basename($download_url))),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_COPY, 'module_name' => $module_name])     => MoreI18N::xlate('Copy files…'),
            ];
    }
}
