<?php

namespace Pikselin\ModuleHelpers\Extensions;

use Composer\InstalledVersions;
use PageController;
use PhpTek\Sentry\Adaptor\SentryAdaptor;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\Requirements;

/**
 * Class \Pikselin\ModuleHelpers\Extensions\PageControllerExtension
 *
 * @property PageController|PageControllerExtension $owner
 */
class PageControllerExtension extends Extension
{
    const SLW_NOOP = 'Unavailable';

    public function onAfterInit()
    {
        // Sentry
        $appData = $this->getAppdata();
        $cache = Injector::inst()->get(CacheInterface::class . '.sentryconf');
        if ($appData['commit'] !== self::SLW_NOOP && !$cache->has($appData['commit'])) {
            $dsn = SentryAdaptor::get_opts();
            if ($dsn['dsn']) {
                $version = $this->getVersion($appData);
                $data = [
                    'DSN'     => $dsn['dsn'],
                    'VERSION' => $version
                ];
                $rendered = $this->renderJS($data);
                $cache->set($appData['commit'], $rendered);
            }
        } else {
            $rendered = $cache->get($appData['commit']);
        }

        Requirements::insertHeadTags(
            $rendered,
            'sentryconfig'
        );
        Requirements::javascript("pikselin/module-helpers:dist/js/main.js");
    }

    /**
     * This is a hacky copy-paste, because SSViewer doesn't work properly, and
     * Requirements::javascriptTemplate() immediately adds it as a template
     * Which we don't want, because it would lack the ID of the element.
     * @param $data
     * @return array|string|string[]
     */
    private function renderJS($data)
    {
        $file = ModuleResourceLoader::singleton()->resolvePath("pikselin/module-helpers:templates/sentryconf.js");
        $absolutePath = Director::getAbsFile($file);
        if (!file_exists($absolutePath ?? '')) {
            throw new \InvalidArgumentException("Javascript template file {$file} does not exist");
        }

        $script = file_get_contents($absolutePath ?? '');
        $search = [];
        $replace = [];

        if ($data) {
            foreach ($data as $k => $v) {
                $search[] = '$' . $k;
                $replace[] = str_replace("\\'", "'", Convert::raw2js($v) ?? '');
            }
        }

        return str_replace($search ?? '', $replace ?? '', $script ?? '');
    }

    /**
     * @return array
     */
    public function getAppdata(): array
    {
        $meta = InstalledVersions::getRootPackage();

        return [
            'project' => explode('/', $meta['name'] ?? self::SLW_NOOP),
            'branch'  => $meta['version'] ?? self::SLW_NOOP,
            'commit'  => $meta['reference'] ?? self::SLW_NOOP,
        ];
    }

    /**
     * @param array $appData
     * @return string
     */
    public function getVersion(array $appData): string
    {
        // Start with the Env
        $version = Environment::getEnv('SS_RELEASE_VERSION');
        if (!$version) {
            // Default to the branch name
            $version = $appData['branch'];
            $releaseFile = Director::baseFolder() . '/.release';
            if (file_exists($releaseFile) && filesize($releaseFile) >= 1) {
                $version = @file_get_contents(Director::baseFolder() . '/.release');
                // Some release files contain the commit hash with a +, we need to strip that out
                if (strpos('+', $version) !== false) {
                    $version = substr($version, 0, strpos($version, '+')-1);
                }
            }
        }

        // Return a format like modulehelper@1.0.0+commithash
        return sprintf('%s@%s+%s', $appData['project'][1] ?? $appData['project'][0], trim($version), $appData['commit']);
    }
}
