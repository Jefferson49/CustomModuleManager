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
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\TimeoutService;
use Fisharebest\Webtrees\Services\UpgradeService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Jefferson49\Webtrees\Internationalization\MoreI18N;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Factories\CustomModuleUpdateFactory;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\AbstractModuleUpdate;
use Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates\CustomModuleUpdateInterface;
use League\Flysystem\DirectoryAttribute;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
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
    public const STEP_BACKUP   = 'Backup';
    public const STEP_DOWNLOAD = 'Download';
    public const STEP_UNZIP    = 'Unzip';
    public const STEP_COPY     = 'Copy';
    public const STEP_ROLLBACK = 'Rollback';
    public const STEP_ERROR    = 'Error';
    
    // Alert types
    public const ALERT_DANGER  = 'alert-danger';
    public const ALERT_SUCCESS = 'alert-success';
    

    // Where to store our temporary files.
    private const UPGRADE_FOLDER = 'data/tmp/upgrade/';

    // Where to store our backup files.
    private const BACKUP_FOLDER = 'data/tmp/backup/';

    // Where to store the downloaded ZIP archive.
    private const ZIP_FILENAME   = 'data/tmp/custom_module.zip';


    // The webtrees upgrade service
    private UpgradeService $webtrees_upgrade_service;

    // The custom module update service
    private CustomModuleUpdateInterface $module_update_service;


    /**
     * @param UpgradeService $webtrees_upgrade_service
     */
    public function __construct() {
        $this->webtrees_upgrade_service = new UpgradeService(new TimeoutService());
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
        $module_name    = Validator::queryParams($request)->string('module_name', '');
        $download_url   = Validator::queryParams($request)->string('download_url', '');
        $message        = Validator::queryParams($request)->string('message', '');
        $action         = Validator::queryParams($request)->string('action', '');      
        $error_message  = Validator::queryParams($request)->string('error_message', '');

        $this->module_update_service = CustomModuleUpdateFactory::make($module_name);

        $zip_file         = Webtrees::ROOT_DIR . self::ZIP_FILENAME;
        $module_names     = $this->module_update_service->getModuleNamesToUpdate();
        $unzip_folder     = $this->module_update_service->getUnzipFolder();
        $folders_to_clean = $this->module_update_service->getFoldersToClean();

        switch ($step) {
            case self::STEP_CHECK:
                return $this->wizardStepCheck();

            case self::STEP_PREPARE:
                return $this->wizardStepPrepare($action);

            case self::STEP_BACKUP:
                return $this->wizardStepBackup($module_names);

            case self::STEP_DOWNLOAD:
                return $this->wizardStepDownload($download_url);

            case self::STEP_UNZIP:
                return $this->wizardStepUnzip($zip_file, $unzip_folder);

            case self::STEP_COPY:
                return $this->wizardStepCopyAndCleanUp($module_names, $zip_file,$folders_to_clean, $action);

            case self::STEP_ROLLBACK:
                return $this->wizardStepRollback($module_names, CustomModuleManager::ACTION_UPDATE, $error_message);

            case self::STEP_ERROR:
                return $this->wizardStepError($message);

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
            throw new HttpServerErrorException(MoreI18N::xlate('No upgrade information is available.'));
        }

        if (version_compare($this->module_update_service->customModuleVersion(), $latest_version) >= 0) {
            $message = I18N::translate('This is the latest version of the custom module. No upgrade is available.');
            throw new HttpServerErrorException($message);
        }

        /* I18N: %s is a version number, such as 1.2.3 */
        $alert = MoreI18N::xlate('Upgrade the module to version %s.', e($latest_version));

        return self::viewAlert($alert);
    }

    /**
     * Make sure the temporary folder exists.
     * 
     * @param string $action The action to be performed, i.e. update or install
     *
     * @return ResponseInterface
     */
    private function wizardStepPrepare(string $action = CustomModuleManager::ACTION_UPDATE): ResponseInterface
    {
        if (!in_array($action, [CustomModuleManager::ACTION_UPDATE, CustomModuleManager::ACTION_INSTALL])) {
            $action = CustomModuleManager::ACTION_UPDATE;
        }

        try {
            $root_filesystem = Registry::filesystem()->root();
            $root_filesystem->deleteDirectory(self::UPGRADE_FOLDER);
            $root_filesystem->createDirectory(self::UPGRADE_FOLDER);

            $alert = MoreI18N::xlate('The folder %s has been created.', e(self::UPGRADE_FOLDER));

            if ($action === CustomModuleManager::ACTION_UPDATE) {
                $root_filesystem->deleteDirectory(self::BACKUP_FOLDER);
                $root_filesystem->createDirectory(self::BACKUP_FOLDER);
                $alert.= "\n" . MoreI18N::xlate('The folder %s has been created.', e(self::BACKUP_FOLDER));
            }

            $alert_type = self::ALERT_SUCCESS;
                          
        } 
        catch (Throwable $exception) {
            $alert_type = self::ALERT_DANGER;
            $alert      = MoreI18N::xlate('Error during creating the temporary backup and upgrade folders'); 
        }

        return self::viewAlert($alert, $alert_type);
    }

    /**
     * Create a backup of the current module
     * 
     * @param array  $module_names         A list with all module names, which shall be updated
     *
     * @return ResponseInterface
     */
    private function wizardStepBackup(array $module_names): ResponseInterface
    {
        /** @var AbstractModuleUpdate $module_update_service  To avoid IDE warnings */
        $module_update_service = $this->module_update_service;
        $start_time = Registry::timeFactory()->now();

        try {
            foreach ($module_names as $standard_module_name => $module_name) {
                $installation_folder    = $module_update_service::getInstallationFolderFromModuleName($module_name);
                $source_filesystem      = Registry::filesystem()->root(Webtrees::MODULES_PATH . $installation_folder);
                $destination_filesystem = Registry::filesystem()->root(self::BACKUP_FOLDER . Webtrees::MODULES_PATH . $installation_folder);

                self::copyFiles($source_filesystem, $destination_filesystem);
            }

            $end_time   = Registry::timeFactory()->now();
            $seconds    = MoreI18N::number($end_time - $start_time, 2);
            $alert      = I18N::translate('A backup of the current module was created in %s seconds.', $seconds);
            $alert_type = self::ALERT_SUCCESS;
        } 
        catch (Throwable $exception) {
            $alert_type = self::ALERT_DANGER;
            $alert      = I18N::translate('Failed to create a backup of the current module.');
        }

        return self::viewAlert($alert, $alert_type);
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
            $bytes      = $this->webtrees_upgrade_service->downloadFile($download_url, $root_filesystem, self::ZIP_FILENAME);
            $kb         = I18N::number(intdiv($bytes + 1023, 1024));
            $end_time   = Registry::timeFactory()->now();
            $seconds    = I18N::number($end_time - $start_time, 2);
            $alert      = MoreI18N::xlate('%1$s KB were downloaded in %2$s seconds.', $kb, $seconds);
            $alert_type = self::ALERT_SUCCESS;
        }
        catch (Throwable $exception) {
            $alert      = I18N::translate('Error during downloading the module zip file.');
            $alert_type = self::ALERT_DANGER;
        }

        return self::viewAlert($alert, $alert_type);
    }

    /**
     * For performance reasons, we use direct filesystem access for this step.
     *
     * @param string $zip_file
     * @param string $unzip_folder
     *
     * @return ResponseInterface
     */
    private function wizardStepUnzip(string $zip_file, string $unzip_folder): ResponseInterface
    {
        $start_time = Registry::timeFactory()->now();

        try{
            $this->webtrees_upgrade_service->extractWebtreesZip($zip_file, Webtrees::ROOT_DIR . self::UPGRADE_FOLDER . $unzip_folder);
            $count    = $this->customModuleZipContents($zip_file)->count();
            $end_time = Registry::timeFactory()->now();
            $seconds  = I18N::number($end_time - $start_time, 2);

            /* I18N: …from the .ZIP file, %2$s is a (fractional) number of seconds */
            $alert      = I18N::plural('%1$s file was extracted in %2$s seconds.', '%1$s files were extracted in %2$s seconds.', $count, I18N::number($count), $seconds);
            $alert_type = self::ALERT_SUCCESS;
        }
        catch (Throwable $exception) {
            $alert      = I18N::translate('Error during unzipping the module zip file.');
            $alert_type = self::ALERT_DANGER;
        }

        return self::viewAlert($alert, $alert_type);
    }

    /**
     * @param array                  $module_names         A list with all module names, which shall be updated
     * @param string                 $zip_file             The ZIP file name
     * @param Collection<int,string> $folders_to_clean     A collection of folder names within the module, which shall be cleaned 
     * @param string                 $action               The action to be performed, i.e. update or install
     *
     * @return ResponseInterface
     */
    private function wizardStepCopyAndCleanUp(
        array      $module_names, 
        string     $zip_file, 
        Collection $folders_to_clean = new Collection([]),
        string     $action = CustomModuleManager::ACTION_UPDATE 
    ): ResponseInterface {

        if (!in_array($action, [CustomModuleManager::ACTION_UPDATE, CustomModuleManager::ACTION_INSTALL])) {
            $action = CustomModuleManager::ACTION_UPDATE;
        }        

        /** @var AbstractModuleUpdate $module_update_service  To avoid IDE warnings */
        $module_update_service = $this->module_update_service;
        $module_service = New ModuleService();
        $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());        

        $this->webtrees_upgrade_service->endMaintenanceMode();

        try {            
            foreach ($module_names as $standard_module_name => $module_name) {
                $installation_folder    = $module_update_service::getInstallationFolderFromModuleName($module_name);
                $update_filesystem      = Registry::filesystem()->root(self::UPGRADE_FOLDER . Webtrees::MODULES_PATH);
                $update_folder          = self::getInstallationFolder($update_filesystem);
                $source_filesystem      = Registry::filesystem()->root(self::UPGRADE_FOLDER . Webtrees::MODULES_PATH . $update_folder);
                $destination_filesystem = Registry::filesystem()->root(Webtrees::MODULES_PATH . $installation_folder);

                $this->webtrees_upgrade_service->moveFiles($source_filesystem, $destination_filesystem);
            }
            $alert      = MoreI18N::xlate('The upgrade is complete.');
            $alert_type = self::ALERT_SUCCESS;
        }
        catch (Throwable $exception) {
            //Rollback
            $this->webtrees_upgrade_service->endMaintenanceMode();        
            return $this->wizardStepRollback($module_names, $action, $exception->getMessage());
        }

        $this->webtrees_upgrade_service->endMaintenanceMode();        

        // While we have time, clean up any old files.
        $files_to_keep = $this->customModuleZipContents($zip_file);
        $this->webtrees_upgrade_service->cleanFiles($destination_filesystem, $folders_to_clean, $files_to_keep);

        //If module has just been installed, we can immediately test it
        if ($action === CustomModuleManager::ACTION_INSTALL) {
            $test_result = $module_update_service->testModuleUpdate();
            
            if ($test_result !== '') {
                return $this->wizardStepRollback($module_names, $action, $test_result);
            }
        }
        else {
            //If module was updated, remember the module name for a test and potential rollback at the next start of webtrees
            $custom_module_manager->setPreference(CustomModuleManager::PREF_LAST_UPDATED_MODULE, $this->module_update_service->getModuleName());
            $custom_module_manager->setPreference(CustomModuleManager::PREF_ROLLBACK_ONGOING, '0');
        }

        $url = route(CustomModuleUpdatePage::class);
        return self::viewAlert($alert, $alert_type, $url);
    }

    /**
     * @param array  $module_names  A list with all module names, which shall be updated
     * @param string $action        The action to be performed, i.e. update or install
     * @param string $error         An error message to show
     *
     * @return ResponseInterface
     */
    private function wizardStepRollback(array $module_names,string $action, string $error = ''): ResponseInterface
    {
        if (!in_array($action, [CustomModuleManager::ACTION_UPDATE, CustomModuleManager::ACTION_INSTALL])) {
            $action = CustomModuleManager::ACTION_UPDATE;
        }

        /** @var AbstractModuleUpdate $module_update_service  To avoid IDE warnings */
        $module_update_service = $this->module_update_service;

        $this->webtrees_upgrade_service->startMaintenanceMode();
                
        try {
            foreach ($module_names as $standard_module_name => $module_name) {
                $installation_folder = $module_update_service::getInstallationFolderFromModuleName($module_name);
                $this->rollback($installation_folder);
            }

            if ($action === CustomModuleManager::ACTION_UPDATE) {
                $alert = I18N::translate('The module was rolled back to the current version, because the update created errors.');
            } 
            else {
                $alert = I18N::translate('The module installation was rolled back, because the module created errors.');
            }

            if ($error !== '') {
                $alert .= "\n" . substr($error, 0, 500). "\n";
            }
        }
        catch (Throwable $exception) {
            $this->webtrees_upgrade_service->endMaintenanceMode();    
    
            if ($action === CustomModuleManager::ACTION_UPDATE) {
                $alert =    I18N::translate('A roll back of the module to the current version failed.') . "\n" .
                            I18N::translate('Please try to manually roll back by copying the files from "/data/tmp/backup/modules_4" to "/modules_v4".');
            } 
            else {
                $alert =    I18N::translate('A roll back of the module installation failed.') . "\n" .
                            I18N::translate('Please try to manually roll back by deleting the files from "/modules_v4".');
            }
        }

        $this->webtrees_upgrade_service->endMaintenanceMode();        

        //Reset update information
        $module_service = New ModuleService();
        $custom_module_manager = $module_service->findByName(CustomModuleManager::activeModuleName());
        $custom_module_manager->setPreference(CustomModuleManager::PREF_LAST_UPDATED_MODULE, '');
        $custom_module_manager->setPreference(CustomModuleManager::PREF_ROLLBACK_ONGOING, '0');

        $url = route(CustomModuleUpdatePage::class);
        
        return self::viewAlert($alert, self::ALERT_DANGER, $url);
    }

    /**
     * @return ResponseInterface
     */
    private function wizardStepError(string $message): ResponseInterface
    {
        $url    = route(CustomModuleUpdatePage::class);

        return self::viewAlert($message, self::ALERT_DANGER, $url);
    }

    /**
     * Rollback the module to the backup (e.g. if a test failed)
     *
     * @return void
     */
    private function rollback($installation_folder): void
    {
        //Delete files of updated module
        $root_filesystem = Registry::filesystem()->root();
        $root_filesystem->deleteDirectory(Webtrees::MODULES_PATH . $installation_folder);

        //Restore files from backup to modules_v4
        $source_filesystem      = Registry::filesystem()->root(self::BACKUP_FOLDER . Webtrees::MODULES_PATH . $installation_folder);
        $destination_filesystem = Registry::filesystem()->root(Webtrees::MODULES_PATH . $installation_folder);
        self::copyFiles($source_filesystem, $destination_filesystem);

        return;
    }

    /**
     * Create a list of all the files in a webtrees .ZIP archive
     * 
     * Code from: Fisharebest\Webtrees\Services\UpgradeService
     *
     * @param string $zip_file
     *
     * @return Collection<int,string>
     * @throws FilesystemException
     */
    public function customModuleZipContents(string $zip_file): Collection
    {
        $zip_provider   = new FilesystemZipArchiveProvider($zip_file, 0755);
        $zip_adapter    = new ZipArchiveAdapter($zip_provider);
        $zip_filesystem = new Filesystem($zip_adapter);

        $files = $zip_filesystem->listContents('', FilesystemReader::LIST_DEEP)
            ->filter(static fn (StorageAttributes $attributes): bool => $attributes->isFile())
            ->map(static fn (StorageAttributes $attributes): string => $attributes->path());

        return new Collection($files);
    }

    /**
     * Copy all files from one filesystem to another
     * 
     * Code from: Fisharebest\Webtrees\Services\UpgradeService
     *
     * @param FilesystemOperator $source
     * @param FilesystemOperator $destination
     *
     * @return void
     * @throws FilesystemException
     */
    public static function copyFiles(FilesystemOperator $source, FilesystemOperator $destination): void
    {
        $timeout_service = new TimeoutService();

        foreach ($source->listContents('', FilesystemReader::LIST_DEEP) as $attributes) {
            if ($attributes->isFile()) {
                $destination->write($attributes->path(), $source->read($attributes->path()));

                if ($timeout_service->isTimeNearlyUp()) {
                    throw new HttpServerErrorException(MoreI18N::xlate('The server’s time limit has been reached.'));
                }
            }
        }
    }

    /**
     * Alert view as response
     *      
     * @param string $alert       The alert text
     * @param string $alert_type  The alert type, e.g. danger, sucess
     * @param string $url         The URL to be called if button is pressed
     *  
     * @return ResponseInterface
     */
    public static function viewAlert(string $alert, string $alert_type = self::ALERT_SUCCESS, string $url = ''): ResponseInterface
    {    
        if (!in_array($alert_type, [self::ALERT_DANGER, self::ALERT_SUCCESS])) {
            $alert_type = self::ALERT_SUCCESS;
        }

        //If URL is provided, include a continue button
        if ($url !== '') {
            $button = '<a href="' . e($url) . '" class="btn btn-primary">' . MoreI18N::xlate('continue') . '</a>';
            $alert.= ' ' . $button;
        }

        return response(view('components/' . $alert_type, [
            'alert' => $alert,
        ]));
    }

    /**
     * Get the installation folder 
     *      
     * @param FilesystemOperator $file_system
     *  
     * @return string
     */
    public static function getInstallationFolder(FilesystemOperator $file_system): string
    {
        $directory_name = '';
        
        try {
            $listing = $file_system->listContents('');

            /** @var StorageAttributes $item */
            foreach ($listing as $item) {

                if ($item->type() === 'dir') {
                    $directory_name = $item->path();
                }
            }
        } catch (FilesystemException $exception) {
            // do nothing
        }

        return $directory_name;
    }
}
