<?php

namespace mexx\craft4elementapicachebuster;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\db\Query;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use mexx\craft4elementapicachebuster\services\ElementApiCacheBusterService;
use yii\base\Event;

/**
 * craft4-element-api-cache-buster plugin
 *
 * @method static Plugin getInstance()
 * @author Martin Lüpold
 * @copyright Martin Lüpold
 * @license MIT
 * @property-read ElementApiCacheBusterService $elementApiCacheBusterService
 */
class Plugin extends BasePlugin
{
  public string $schemaVersion = '1.0.0';

  public static function config(): array
  {
    return [
      'components' => ['elementApiCacheBusterService' => ElementApiCacheBusterService::class],
    ];
  }

  public function init()
  {
    parent::init();

    // Defer most setup tasks until Craft is fully initialized
    Craft::$app->onInit(function () {
      $this->attachEventHandlers();
      // ...
    });
    $this->addEventListeners();
  }

  // Protected Methods
  // =========================================================================
  protected function addEventListeners()
  {
    $invalidateCaches = [$this, 'invalidateCaches'];
    $elements = Craft::$app->get('elements');

    $elements->on(Elements::EVENT_AFTER_SAVE_ELEMENT, $invalidateCaches);
    $elements->on(Elements::EVENT_AFTER_DELETE_ELEMENT, $invalidateCaches);
  }

  // Private Methods
  // =========================================================================
  protected function invalidateCaches(Event $event)
  {
    $element = $event->element;

    // check for assets and clear all caches if so
    if (strpos(get_class($element), 'Asset') != false) {
      Craft::$app->getCache()->flush();
      return;
    }

    // if element has no type property abort if so
    if (strpos(get_class($element), 'Entry') == false) {
      return;
    }

    $token = null;
    if (ElementHelper::isDraftOrRevision($event->element)) {
      $tokenQuery = (new Query())
        ->select('token')
        ->from('tokens')
        ->orderBy('id desc')
        ->limit(1)
        ->all();

      if (count($tokenQuery) > 0) {
        $token = $tokenQuery[0]['token'];
      }
    }


    Plugin::getInstance()->elementApiCacheBusterService->bustEntryCache($element->slug, $element->siteId, $element->type, $token);
    Plugin::getInstance()->elementApiCacheBusterService->bustListsCache($element->siteId, $element->type);
  }

  private function attachEventHandlers(): void
  {
    // Register event handlers here ...
    // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
  }
}
