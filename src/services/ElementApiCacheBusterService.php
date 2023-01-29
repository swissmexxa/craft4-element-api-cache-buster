<?php

namespace mexx\craft4elementapicachebuster\services;

use Craft;
use yii\base\Component;

/**
 * Element Api Cache Buster service
 */
class ElementApiCacheBusterService extends Component
{
  public string $elementApiPrefix = 'elementapi:';
    public string $elementApiAffix = ':';
    public array $dependencies = array(
        'page' => array( 'url' => 'api/page/', 'lists' => []),
        'news' => array( 'url' => 'api/news/', 'lists' => ['api/news']),
        'pictureGallery' => array( 'url' => 'api/galleries/', 'lists' => ['api/galleries']),
        'event' => array( 'url' => null, 'lists' => ['api/events']),
        'navigation' => array( 'url' => null, 'lists' => ['api/navigation/header', 'api/navigation/footer']),
    );

    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     ElementApiCacheBuster::$plugin->elementApiCacheBusterService->exampleService()
     *
     * @return void
     */
    public function bustEntryCache($slug, $siteId, $type, $token)
    {
        if (!$this->safetyChecks($type, false)) {
            return;
        }

        // Cash key example of element api: elementapi:1:api/news/turnplausch-mit-ariella-kaeslin:
        // => elementapi:siteid:url:
        $cacheKey = $this->getCacheKey($siteId, $this->dependencies['' . $type]['url']  . $slug);
        Craft::$app->getCache()->delete($cacheKey);
        Craft::$app->getCache()->delete($cacheKey . 'token=' . $token);
    }

    public function bustListsCache($siteId, $type)
    {
        if (!$this->safetyChecks($type, true)) {
            return;
        }

        foreach ($this->dependencies[''. $type]['lists'] as $url) {
            $cacheKey = $this->getCacheKey($siteId, $url);
            Craft::$app->getCache()->delete($cacheKey);
        }
    }

    // Private Methods
    // =========================================================================
    private function safetyChecks($type, $isList): bool
    {
        // check if key exists
        if (!array_key_exists('' . $type, $this->dependencies)) {
            return false;
        }

        // check if single entry cache should be cleared
        if (!$isList && $this->dependencies['' . $type]['url'] == null) {
            return false;
        }

        return true;
    }

    private function getCacheKey($siteId, $url): string
    {
        return $this->elementApiPrefix . $siteId . ':' . $url . $this->elementApiAffix;
    }
}
