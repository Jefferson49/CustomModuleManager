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

use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\Http\Exceptions\HttpServerErrorException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\CustomModuleUpdateInterface;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\StorageAttributes;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function e;
use function intdiv;
use function response;
use function route;
use function version_compare;
use function view;

/**
 * Upgrade to a new version of a custom module.
 */
class ModuleUpgradeWizardStep implements RequestHandlerInterface
{
    // We make the upgrade in a number of small steps to keep within server time limits.
    public const STEP_CHECK    = 'Check';
    public const STEP_PREPARE  = 'Prepare';
    public const STEP_DOWNLOAD = 'Download';
    public const STEP_UNZIP    = 'Unzip';
    public const STEP_COPY     = 'Copy';

    // Where to store our temporary files.
    private const UPGRADE_FOLDER = 'data/tmp/upgrade/';

    // Where to store the downloaded ZIP archive.
    private const ZIP_FILENAME   = 'data/tmp/custom_module.zip';


    // The webtrees upgrade service
    private UpgradeService $webtrees_upgrade_service;

    // The custom module update service
    private CustomModuleUpdateInterface $module_update_service;


    /**
     * @param UpgradeService $webtrees_upgrade_service
     */
    public function __construct(UpgradeService $webtrees_upgrade_service) {
        $this->webtrees_upgrade_service = $webtrees_upgrade_service;
    }

    /**
     * Perform one step of the wizard
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $step           = Validator::queryParams($request)->string('step', self::STEP_CHECK);
        $download_url   = Validator::queryParams($request)->string('download_url', '');
        $update_service = Validator::queryParams($request)->string('update_service', '');
        $module_name    = Validator::queryParams($request)->string('module_name', '');
        $params         = Validator::queryParams($request)->array('params');
 
        $this->module_update_service = CustomModuleUpdateFactory::make($update_service, $module_name, $params);

        $zip_file            = Webtrees::ROOT_DIR . self::ZIP_FILENAME;
        $upgrade_folder      = Webtrees::ROOT_DIR . self::UPGRADE_FOLDER;

        $zip_folder          = $this->module_update_service->getZipFolder();
        $installation_folder = $this->module_update_service->getInstallationFolder();
        $folders_to_clean    = $this->module_update_service->getFoldersToClean();

        switch ($step) {
            case self::STEP_CHECK:
                return $this->wizardStepCheck();

            case self::STEP_PREPARE:
                return $this->wizardStepPrepare();

            case self::STEP_DOWNLOAD:
                return $this->wizardStepDownload($download_url);

            case self::STEP_UNZIP:
                return $this->wizardStepUnzip($zip_file, $upgrade_folder, $zip_folder);

            case self::STEP_COPY:
                return $this->wizardStepCopyAndCleanUp($zip_file, $zip_folder, $installation_folder, $folders_to_clean);

            default:
                return response('', StatusCodeInterface::STATUS_NO_CONTENT);
        }
    }

    /**
     * @return ResponseInterface
     */
    private function wizardStepCheck(): ResponseInterface
    {
        $latest_version = $this->module_update_service->customModuleLatestVersion();

        if ($latest_version === '') {
            throw new HttpServerErrorException(I18N::translate('No upgrade information is available.'));
        }

        if (version_compare($this->module_update_service->customModuleVersion(), $latest_version) >= 0) {
            $message = I18N::translate('This is the latest version of the module %s. No upgrade is available.', $this->module_update_service->getModuleName());
            throw new HttpServerErrorException($message);
        }

        /* I18N: %s is a version number, such as 1.2.3 */
        $alert = I18N::translate('Upgrade the module to version %s.', e($latest_version));

        return response(view('components/alert-success', [
            'alert' => $alert,
        ]));
    }

    /**
     * Make sure the temporary folder exists.
     *
     * @return ResponseInterface
     */
    private function wizardStepPrepare(): ResponseInterface
    {
        $root_filesystem = Registry::filesystem()->root();
        $root_filesystem->deleteDirectory(self::UPGRADE_FOLDER);
        $root_filesystem->createDirectory(self::UPGRADE_FOLDER);

        return response(view('components/alert-success', [
            'alert' => I18N::translate('The folder %s has been created.', e(self::UPGRADE_FOLDER)),
        ]));
    }

    /**
     * @param string $download_url  The URL where we can download the module ZIP file
     * 
     * @return ResponseInterface
     */
    private function wizardStepDownload(string $download_url): ResponseInterface
    {
        $root_filesystem = Registry::filesystem()->root();
        $start_time      = Registry::timeFactory()->now();

        try {
            $bytes = $this->webtrees_upgrade_service->downloadFile($download_url, $root_filesystem, self::ZIP_FILENAME);
        } catch (Throwable $exception) {
            throw new HttpServerErrorException($exception->getMessage());
        }

        $kb       = I18N::number(intdiv($bytes + 1023, 1024));
        $end_time = Registry::timeFactory()->now();
        $seconds  = I18N::number($end_time - $start_time, 2);

        return response(view('components/alert-success', [
            'alert' => I18N::translate('%1$s KB were downloaded in %2$s seconds.', $kb, $seconds),
        ]));
    }

    /**
     * For performance reasons, we use direct filesystem access for this step.
     *
     * @param string $zip_file
     * @param string $upgrade_folder     * 
     * @param string $zip_folder
     *
     * @return ResponseInterface
     */
    private function wizardStepUnzip(string $zip_file, $upgrade_folder, string $zip_folder): ResponseInterface
    {
        $start_time = Registry::timeFactory()->now();
        $this->webtrees_upgrade_service->extractWebtreesZip($zip_file, $upgrade_folder);
        $count    = $this->customModuleZipContents($zip_file, $zip_folder)->count();
        $end_time = Registry::timeFactory()->now();
        $seconds  = I18N::number($end_time - $start_time, 2);

        /* I18N: …from the .ZIP file, %2$s is a (fractional) number of seconds */
        $alert = I18N::plural('%1$s file was extracted in %2$s seconds.', '%1$s files were extracted in %2$s seconds.', $count, I18N::number($count), $seconds);

        return response(view('components/alert-success', [
            'alert' => $alert,
        ]));
    }

    /**
     * @param string                 $zip_file             The ZIP file name
     * @param string                 $zip_folder           The top level folder in the ZIP file of the custom module
     * @param string                 $installation_folder  The installation folder of the custom module
     * @param Collection<int,string> $folders_to_clean     A collection of folder names within the module, which shall be cleaned 
     *
     * @return ResponseInterface
     */
    private function wizardStepCopyAndCleanUp(string $zip_file, string $zip_folder, string $installation_folder, Collection $folders_to_clean = new Collection([])): ResponseInterface
    {
        $source_filesystem      = Registry::filesystem()->root(self::UPGRADE_FOLDER . $zip_folder);
        $destination_filesystem = Registry::filesystem()->root($installation_folder);

        $this->webtrees_upgrade_service->startMaintenanceMode();
        $this->webtrees_upgrade_service->moveFiles($source_filesystem, $destination_filesystem);
        $this->webtrees_upgrade_service->endMaintenanceMode();

        // While we have time, clean up any old files.
        $files_to_keep = $this->customModuleZipContents($zip_file, $zip_folder);

        $this->webtrees_upgrade_service->cleanFiles($destination_filesystem, $folders_to_clean, $files_to_keep);

        $url    = route(CustomModuleUpdatePage::class);
        $alert  = I18N::translate('The upgrade is complete.');
        $button = '<a href="' . e($url) . '" class="btn btn-primary">' . I18N::translate('continue') . '</a>';

        return response(view('components/alert-success', [
            'alert' => $alert . ' ' . $button,
        ]));
    }

    /**
     * Create a list of all the files in a webtrees .ZIP archive
     * Code Ffrom: Fisharebest\Webtrees\Services\UpgradeService
     *
     * @param string $zip_file
     * @param string $zip_folder
     *
     * @return Collection<int,string>
     * @throws FilesystemException
     */
    public function customModuleZipContents(string $zip_file, string $zip_folder): Collection
    {
        $zip_provider   = new FilesystemZipArchiveProvider($zip_file, 0755);
        $zip_adapter    = new ZipArchiveAdapter($zip_provider, $zip_folder);
        $zip_filesystem = new Filesystem($zip_adapter);

        $files = $zip_filesystem->listContents('', FilesystemReader::LIST_DEEP)
            ->filter(static fn (StorageAttributes $attributes): bool => $attributes->isFile())
            ->map(static fn (StorageAttributes $attributes): string => $attributes->path());

        return new Collection($files);
    }
}
