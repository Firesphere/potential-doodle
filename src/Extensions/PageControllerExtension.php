<?php

namespace Pikselin\ModuleHelpers\Extensions;

use PhpTek\Sentry\Adaptor\SentryAdaptor;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\Requirements;

class PageControllerExtension extends Extension
{

    public function onAfterInit()
    {
        // Sentry
        $dsn = SentryAdaptor::get_opts();
        if ($dsn['dsn']) {
            $version = Environment::getEnv('SS_RELEASE_VERSION') ?? '';
            $dsnParts = explode('@', $dsn['dsn']); // To avoid it being seen as an email address
            $data = [
                'DSN'      => $dsnParts[1],
                'ENDPOINT' => $dsnParts[0],
                'VERSION'  => $version
            ];
            $hash = md5(json_encode($data));
            $cache = Injector::inst()->get(CacheInterface::class . '.sentryconf');
            if (!$cache->has($hash)) {
                $rendered = $this->renderJS($data);
                $cache->set($hash, $rendered);
            } else {
                $rendered = $cache->get($hash);
            }

            Requirements::insertHeadTags(
                $rendered,
                'sentryconfig'
            );
            Requirements::javascript("pikselin/module-helpers:dist/js/main.js");
        }
    }

    /**
     * This is a hacky copy-paste, because SSViewer wouldn't cooperate
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
}
