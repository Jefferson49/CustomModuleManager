<?php

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\CustomModuleManager\ModuleUpdates;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Jefferson49\Webtrees\Exceptions\CodebergCommunicationError;
use Jefferson49\Webtrees\Helpers\CodebergService;
use Jefferson49\Webtrees\Module\CustomModuleManager\CustomModuleManager;
use Jefferson49\Webtrees\Module\CustomModuleManager\Exceptions\CustomModuleManagerException;

/**
 * Update API for a custom module, which is hosted in a Codeberg repository.
 */
class CodebergModuleUpdate extends AbstractModuleUpdate implements CustomModuleUpdateInterface
{
    public const NAME = 'Codeberg';

    protected string $codeberg_repo;
    protected bool $get_latest_version_from_codeberg;
    protected string $tag_prefix;
    protected bool $no_release;
    protected string $default_branch;
    protected ModuleService $module_service;
    protected ModuleInterface $custom_module_manager;

    /**
     * @param string $module_name  The custom module name
     * @param array  $params       The configuration parameters of the update service
     */
    public function __construct(string $module_name, array $params)
    {
        $this->module_name = $module_name;
        $this->module_service = new ModuleService();
        $this->custom_module_manager = Registry::container()->get(CustomModuleManager::class);

        if (array_key_exists('codeberg_repo', $params)) {
            $this->codeberg_repo = $params['codeberg_repo'];
        } else {
            throw new CustomModuleManagerException(I18N::translate('Could not create the %s update service. Configuration parameter "%s" missing.', basename(str_replace('\\', '/', __CLASS__)), 'codeberg_repo'));
        }

        $this->conflicts = $params['conflicts'] ?? [];
        $this->get_latest_version_from_codeberg = $params['get_latest_version_from_codeberg'] ?? false;
        $this->tag_prefix = $params['tag_prefix'] ?? '';
        $this->no_release = $params['no_release'] ?? false;
        $this->default_branch = $params['default_branch'] ?? '';
        $this->install_clean = $params['install_clean'] ?? false;
        $this->update_manually = $params['update_manually'] ?? false;
        $this->category = self::identifyCategoryFromConfig($module_name, $params);
    }

    /**
     * The name of the module update service
     *
     * @return string
     */
    public function name(): string
    {
        return self::NAME;
    }

    /**
     * Where can we download the module
     *
     * @param string $version  The version of the module; latest version if empty
     *
     * @throws CustomModuleManagerException  In case of a communication error with Codeberg
     *
     * @return string
     */
    public function downloadUrl(string $version = ''): string
    {
        if ($this->no_release) {
            return 'https://codeberg.org/' . $this->codeberg_repo . '/archive/' . $this->default_branch . '.zip';
        }

        try {
            return CodebergService::downloadUrl($this->codeberg_repo, $version, $this->tag_prefix, $this->getApiToken());
        } catch (CodebergCommunicationError $ex) {
            $message = I18N::translate('Communication error with %s', $this->name()) . ': ' .
                I18N::translate('Cannot retrieve download URL.') . "\n" . $ex->getMessage();
            throw new CustomModuleManagerException($message);
        }
    }

    /**
     * Where can we find a documentation for the module
     *
     * @return string
     */
    public function documentationUrl(): string
    {
        return 'https://codeberg.org/' . $this->codeberg_repo;
    }

    /**
     * Get the Codeberg repository
     *
     * @return string
     */
    public function getRepository(): string
    {
        return $this->codeberg_repo;
    }

    /**
     * Get the hosting platform of the module, e.g. GitHub or Codeberg
     *
     * @return string
     */
    public function getHostingPlatform(): string
    {
        return 'Codeberg';
    }

    /**
     * Fetch the latest version of this module
     *
     * @param bool $fetch_latest  Whether to fetch the latest version from a Codeberg repository
     *
     * @return string
     */
    public function customModuleLatestVersion(bool $fetch_latest = false): string
    {
        $module = $this->getModule();

        if ($module !== null && !$fetch_latest && !$this->get_latest_version_from_codeberg) {
            $latest_version = $module->customModuleLatestVersion();
            $cached_version = $this->fetchReleasesInfoCached(false)['tag'];

            if (CustomModuleManager::versionCompare($module->name(), $cached_version, $latest_version) > 0) {
                $latest_version = $cached_version;
            }

            return $latest_version;
        } elseif ($module !== null && $this->no_release) {
            return self::getLatestVersionByUpdateURL($module);
        } elseif ($this->codeberg_repo !== '') {
            return $this->fetchReleasesInfoCached($fetch_latest)['tag'];
        }

        return '';
    }

    /**
     * Get the release notes for the latest version of this module
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return string
     */
    public function getLatestReleaseNotes(): string
    {
        try {
            return CodebergService::getLatestReleaseNotes($this->codeberg_repo, $this->getApiToken());
        } catch (CodebergCommunicationError) {
            return I18N::translate('Could not retrieve release notes due to a communication error with Codeberg.');
        }
    }

    /**
     * Whether the module provides releases in the repository
     *
     * @return bool
     */
    public function providesReleasesInRepository(): bool
    {
        return !$this->no_release;
    }

    /**
     * Get the cached download count for this module
     *
     * @return int  The download count, or -1 if unavailable
     */
    public function getDownloadCount(): int
    {
        if ($this->no_release) {
            return -1;
        }

        return $this->fetchReleasesInfoCached(false)['max_downloads'];
    }

    /**
     * Fetch combined release information (version tag + download count) from Codeberg,
     * using the webtrees file cache.
     *
     * @param bool   $force_refresh  Whether to invalidate the cache and fetch fresh data from Codeberg
     * @param string $below_tag      If provided, only consider releases below this tag
     *
     * @throws CodebergCommunicationError  In case of a communication error with Codeberg
     *
     * @return array{tag: string, max_downloads: int}
     */
    public function fetchReleasesInfoCached(bool $force_refresh = false, string $below_tag = ''): array
    {
        $cache_key = CustomModuleManager::CACHE_REALEASE_INFO . md5($this->codeberg_repo);

        if ($force_refresh || $below_tag !== '') {
            Registry::cache()->file()->forget($cache_key);
        }

        return Registry::cache()->file()->remember($cache_key, function () use ($below_tag): array {
            try {
                $result = CodebergService::getRecentReleasesInfo($this->codeberg_repo, $this->getApiToken(), $below_tag);
                return ['tag' => $result['tag'], 'max_downloads' => $result['max_downloads']];
            } catch (CodebergCommunicationError) {
                if (!CustomModuleManager::rememberGithubCommunciationError()) {
                    FlashMessages::addMessage(I18N::translate('Communication error with %s', self::NAME), 'danger');
                }

                return ['tag' => '', 'max_downloads' => -1];
            }
        }, 86400 * 30);
    }

    /**
     * Get the latest release URL
     *
     * @return string
     */
    public function getLatestReleaseURL(): string
    {
        return 'https://codeberg.org/' . $this->codeberg_repo . '/releases/latest';
    }

    /**
     * Get the package name (for custom module list)
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return strtolower($this->codeberg_repo);
    }

    /**
     * Get the tag prefix
     *
     * @return string
     */
    public function getTagPrefix(): string
    {
        return $this->tag_prefix;
    }

    /**
     * Whether the Codeberg repository does not provide releases
     *
     * @return bool
     */
    public function noRelease(): bool
    {
        return $this->no_release;
    }

    /**
     * Get the default branch
     *
     * @return string
     */
    public function getDefaultBranch(): string
    {
        return $this->default_branch;
    }

    /**
     * Get the API token
     *
     * @return string
     */
    public function getApiToken(): string {

        return $this->custom_module_manager->getPreference(CustomModuleManager::PREF_CODEBERG_API_TOKEN, '');
    }

    /**
     * Get the text of a file from the module repository
     *
     * @param string $repo       The module repository, e.g. GitHub 'Jefferson49/webtrees-common'
     * @param string $branch     The tag or branch in the module repository
     * @param string $path       The path in the module repository including the file name
     *
     * @throws CodebergCommunicationError  In case of a communcation error with the hosting platform
     *
     * @return string
     */
    public function getTextFileContent(string $repo, string $branch, string $path): string {

        return CodebergService::getTextFileContent($repo, $branch, $path, $this->getApiToken());
    }
}