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

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\User;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Exceptions\CustomModuleManagerException;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
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

    // The webtrees upgrade service
    private UpgradeService $upgrade_service;


    /**
     * @param UpgradeService              $upgrade_service
     */
    public function __construct(UpgradeService $upgrade_service)
    {
        $this->upgrade_service = $upgrade_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/ajax';

        $tree            = Validator::attributes($request)->treeOptional();
        $user            = Validator::attributes($request)->user();
        $module_name     = Validator::queryParams($request)->string('module_name', '');
        $current_version = Validator::queryParams($request)->string('current_version', '');
        $latest_version  = Validator::queryParams($request)->string('latest_version', '');
        $action          = Validator::queryParams($request)->string('action', '');

        // If no administrator, redirect to home page
        if (!($user instanceof User) OR !Auth::isAdmin($user)) {
            return redirect(route(HomePage::class, ['tree' => $tree?->name()]));
        }

        $module_upgrade_service = CustomModuleUpdateFactory::make($module_name);

        //Reset aborted flag before start of wizard
        Session::forget(CustomModuleManager::activeModuleName() . CustomModuleManager::SESSION_WIZARD_ABORTED);

        try {
            if ($module_upgrade_service === null) {
                throw new CustomModuleManagerException(I18N::translate('Could not identify a suitable module upgrade service for custom module'));
            }

            $download_url = $module_upgrade_service->downloadUrl($latest_version);
        }
        catch (CustomModuleManagerException $exception) {
            return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/steps-modal', [
                'steps' => [route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_ERROR, 'module_name' => $module_name, 'message' => $exception->getMessage(), 'modal' => true]) => MoreI18N::xlate('Error')],
                'title' => I18N::translate('Error during retrieving download URL'),
                'modal' => true,
            ]);
        }

        return $this->viewResponse(CustomModuleManager::viewsNamespace() . '::modals/steps-modal', [
            'steps' => $this->wizardSteps($module_name, $download_url, $action, $current_version, $latest_version),
            'title' => MoreI18N::xlate('Upgrade wizard'),
            'modal' => true,
        ]);
    }

    /**
     * @param string $download_url
     * @param string $module_name
     * @param string $action         The action to be performed, i.e. update or install
     * @param string $current_version
     * @param string $latest_version
     * 
     * @return array<string>
     */
    private function wizardSteps(string $module_name, string $download_url, string $action = CustomModuleManager::ACTION_UPDATE, $current_version, $latest_version): array
    {
        if (!in_array($action, [CustomModuleManager::ACTION_UPDATE, CustomModuleManager::ACTION_INSTALL])) {
            $action = CustomModuleManager::ACTION_UPDATE;
        }

        $params = [
            'module_name'     => $module_name,
            'download_url'    => $download_url,
            'action'          => $action,
            'current_version' => $current_version,
            'latest_version'  => $latest_version,
            'modal'           => true,
        ];

        $steps = [
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_CHECK] + $params)    => I18N::translate('Check version...'),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_PREPARE] + $params)  => I18N::translate('Create temporary folders…'),
            ];

        if ($action === CustomModuleManager::ACTION_UPDATE) {
            $steps+= [
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_BACKUP] + $params)   => I18N::translate('Backup…')
            ];
        }

        $steps += [
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_DOWNLOAD] + $params) => MoreI18N::xlate('Download %s…', e($download_url)),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_UNZIP] + $params)    => MoreI18N::xlate('Unzip %s to a temporary folder…', e(basename($download_url))),
                route(ModuleUpgradeWizardStep::class, ['step' => ModuleUpgradeWizardStep::STEP_COPY] + $params)     => MoreI18N::xlate('Copy files…'),
        ];

        return $steps;
    }
}
