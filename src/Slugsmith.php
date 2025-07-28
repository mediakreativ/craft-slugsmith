<?php
// src/Slugsmith.php

namespace mediakreativ\slugsmith;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use craft\services\Plugins;
use craft\events\PluginEvent;
use mediakreativ\slugsmith\assets\SlugsmithAsset;
use mediakreativ\slugsmith\models\SlugsmithSettings;
use mediakreativ\slugsmith\services\SlugsmithService;

/**
 * Slugsmith plugin for Craft CMS
 *
 * Advanced Slug Control for Craft CMS. Refresh & Customize Your Slugs.
 *
 * @author Christian Schindler
 * @copyright (c) 2025
 * @link https://mediakreativ.de
 * @license https://craftcms.github.io/license/ Craft License
 *
 * @method static Slugsmith getInstance()
 * @property-read SlugsmithService $service
 * @property-read SlugsmithSettings $settings
 * @method SlugsmithSettings getSettings()
 */
class Slugsmith extends Plugin
{
    public const TRANSLATION_CATEGORY = "slugsmith";

    public string $schemaVersion = "1.0.0";
    public bool $hasCpSettings = true;

    /**
     * Bootstraps the plugin: registers services, routes, events, assets and defaults.
     */
    public function init(): void
    {
        parent::init();

        Craft::info("Slugsmith plugin initialized.", __METHOD__);

        $this->setComponents([
            "service" => SlugsmithService::class,
        ]);

        $this->registerCpRoutes();
        $this->registerPostInstallDefaults();
        $this->registerTwigFilter();

        $this->initializeCoreFeatures();
        $this->initializeSiteFeatures();
        $this->initializeControlPanelFeatures();
        $this->initializeAsciiPerSiteDefaults();
    }

    /**
     * Registers all Control Panel routes used by the plugin.
     */
    private function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (\craft\events\RegisterUrlRulesEvent $event) {
                $event->rules["slugsmith/settings/general"] =
                    "slugsmith/slugsmith/general";
                $event->rules["slugsmith/settings/save"] =
                    "slugsmith/slugsmith/save-settings";
                $event->rules["slugsmith/settings/slug-rules"] =
                    "slugsmith/slugsmith/slug-rules";
            }
        );
    }

    /**
     * Saves default settings to Project Config immediately after installation.
     */
    private function registerPostInstallDefaults(): void
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $e) {
                if ($e->plugin === $this) {
                    $defaults = $this->getSettings()->toArray();
                    Craft::$app->plugins->savePluginSettings($this, $defaults);
                }
            }
        );
    }

    /**
     * Registers a shorthand Twig filter `{{ 'foo'|st }}` for Slugsmith translations.
     */
    private function registerTwigFilter(): void
    {
        Craft::$app->view->registerTwigExtension(
            new class extends \Twig\Extension\AbstractExtension {
                public function getFilters(): array
                {
                    return [
                        new \Twig\TwigFilter("st", [Slugsmith::class, "t"]),
                    ];
                }
            }
        );
    }

    /**
     * Cleans up all Project Config entries on uninstall.
     */
    public function afterUninstall(): void
    {
        Craft::$app->getProjectConfig()->remove("slugsmith");
        Craft::info("Slugsmith Project Config entries removed.", __METHOD__);
    }

    /**
     * Shorthand translation helper.
     */
    public static function t(string $message, array $params = []): string
    {
        return Craft::t(self::TRANSLATION_CATEGORY, $message, $params);
    }

    /**
     * Returns the plugin settings model.
     */
    protected function createSettingsModel(): ?Model
    {
        return new SlugsmithSettings();
    }

    /**
     * Redirects to the actual plugin settings page in the Control Panel.
     *
     * The default `settingsHtml()` is not used to render a form directly.
     */
    public function settingsHtml(): ?string
    {
        $settings = $this->getSettings();

        if (!$settings->validate()) {
            Craft::error(
                "Validation failed: " . json_encode($settings->getErrors()),
                __METHOD__
            );
        }

        return Craft::$app
            ->getResponse()
            ->redirect(UrlHelper::cpUrl("slugsmith/settings/general"))
            ->send();
    }

    /**
     * Initializes logic shared between Control Panel and site requests.
     */
    private function initializeCoreFeatures(): void
    {
        Craft::$app->onInit(function () {
            Craft::debug("Slugsmith core features initialized.", __METHOD__);
        });
    }

    /**
     * Initializes logic relevant to frontend site requests.
     */
    private function initializeSiteFeatures(): void
    {
        Craft::$app->onInit(function () {
            if (!Craft::$app->getRequest()->getIsSiteRequest()) {
                return;
            }

            $this->registerSiteRequestHandlers();
        });
    }

    /**
     * Initializes Control Panel-specific behavior and assets.
     */
    private function initializeControlPanelFeatures(): void
    {
        Craft::$app->onInit(function () {
            if (!Craft::$app->getRequest()->getIsCpRequest()) {
                return;
            }

            $this->registerControlPanelAssets();
        });
    }

    /**
     * Registers assets for the CP (JS, CSS, translations).
     */
    private function registerControlPanelAssets(): void
    {
        Craft::$app->getView()->registerAssetBundle(SlugsmithAsset::class);
        Craft::info("Slugsmith Control Panel assets registered.", __METHOD__);
    }

    /**
     * Placeholder for future frontend-related behavior (e.g. live preview).
     */
    private function registerSiteRequestHandlers(): void
    {
        Craft::debug("Slugsmith site request handlers registered.", __METHOD__);
    }

    /**
     * Initializes per-site ASCII settings if not already set.
     *
     * Copies the global Craft config `limitAutoSlugsToAscii` as a per-site default.
     */
    private function initializeAsciiPerSiteDefaults(): void
    {
        $settings = $this->getSettings();

        if (!empty($settings->asciiPerSite)) {
            return;
        }

        $default = Craft::$app->getConfig()->getGeneral()
            ->limitAutoSlugsToAscii;

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $settings->asciiPerSite[$site->handle] = $default;
        }

        Craft::$app
            ->getPlugins()
            ->savePluginSettings($this, $settings->toArray());

        Craft::info(
            "asciiPerSite settings initialized from Craft config (limitAutoSlugsToAscii = " .
                ($default ? "true" : "false") .
                ")",
            __METHOD__
        );
    }
}
