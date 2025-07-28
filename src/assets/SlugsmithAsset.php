<?php
// src/assets/SlugsmithAsset.php

namespace mediakreativ\slugsmith\assets;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\View;
use craft\web\assets\cp\CpAsset;
use mediakreativ\slugsmith\Slugsmith;

/**
 * Slugsmith Asset Bundle
 *
 * Registers the JS, CSS and translation support required by the plugin
 * within the Craft Control Panel context.
 */
class SlugsmithAsset extends AssetBundle
{
    /**
     * Initializes the asset bundle by defining its source path and asset files.
     */
    public function init(): void
    {
        $this->sourcePath = "@mediakreativ/slugsmith/assets";

        $settings = Craft::$app
            ->getPlugins()
            ->getPlugin("slugsmith")
            ?->getSettings();

        $this->js[] = "js/slugsmith.js";
        $this->js[] = "js/slugsmithSettings.js";

        $this->css = ["css/slugsmith.css"];

        parent::init();
    }

    /**
     * Registers all assets and injects custom JavaScript after the page has loaded.
     *
     * @param View $view The current view instance (usually `craft\web\View`)
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->registerTranslations($view);
            $this->registerPluginJs($view);
        }
    }

    /**
     * Registers the plugin’s translation keys for both PHP and JavaScript use.
     *
     * This ensures translated labels, buttons, and notices are available in the CP
     * and to client-side scripts via a global `window.SlugsmithTranslations` object.
     *
     * @param View $view The current view instance
     */
    private function registerTranslations(View $view): void
    {
        $translations = $this->getTranslationKeysWithFallback();

        if (!empty($translations)) {
            $view->registerTranslations(
                Slugsmith::TRANSLATION_CATEGORY,
                array_keys($translations)
            );

            $view->registerJs(
                "window.SlugsmithTranslations = " . Json::encode($translations),
                View::POS_HEAD
            );
        }
    }

    /**
     * Collects translation keys from the current locale and falls back to `en` if missing.
     *
     * @return array Key-value pairs of translations
     */
    private function getTranslationKeysWithFallback(): array
    {
        $translationsDir = Craft::getAlias(
            "@mediakreativ/slugsmith/translations"
        );
        $defaultLanguage = "en";
        $currentLanguage = Craft::$app->language;
        $allTranslations = [];

        if (is_dir($translationsDir)) {
            // Load current language
            $currentLanguageFile =
                $translationsDir . "/{$currentLanguage}/slugsmith.php";
            if (file_exists($currentLanguageFile)) {
                $currentTranslations = include $currentLanguageFile;
                if (is_array($currentTranslations)) {
                    $allTranslations = $currentTranslations;
                } else {
                    Craft::debug(
                        "The current language file exists but does not return an array: " .
                            $currentLanguageFile,
                        __METHOD__
                    );
                }
            }

            // Load fallback language
            $defaultFile =
                $translationsDir . "/{$defaultLanguage}/slugsmith.php";
            if (file_exists($defaultFile)) {
                $fallbackTranslations = include $defaultFile;
                if (is_array($fallbackTranslations)) {
                    foreach ($fallbackTranslations as $key => $value) {
                        if (!isset($allTranslations[$key])) {
                            $allTranslations[$key] = $value;
                        }
                    }
                    Craft::debug(
                        "Loaded fallback translations from: " . $defaultFile,
                        __METHOD__
                    );
                }
            }
        } else {
            Craft::error(
                "Translation directory not found: {$translationsDir}",
                __METHOD__
            );
        }

        return $allTranslations;
    }

    /**
     * Injects plugin configuration for client-side JavaScript.
     *
     * Initializes Slugsmith in the DOMContentLoaded lifecycle,
     * passing relevant settings such as ASCII fallback and replacements.
     *
     * @param View $view The view instance where JS should be registered
     */
    private function registerPluginJs(View $view): void
    {
        $config = Json::encode(
            $this->getSlugsmithConfig(),
            JSON_THROW_ON_ERROR
        );

        $js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    if (window.Craft && window.Craft.Slugsmith) {
        new window.Craft.Slugsmith($config);
    } else {
        console.warn('Slugsmith could not be initialized. Ensure all scripts are loaded.');
    }
});
JS;

        $view->registerJs($js, View::POS_END);
    }

    /**
     * Prepares plugin configuration for the client-side plugin.
     *
     * @return array Configuration structure passed to `new Craft.Slugsmith({...})`
     */
    private function getSlugsmithConfig(): array
    {
        $settings = Slugsmith::getInstance()->getSettings();

        $replacements = [];
        if (!empty($settings->slugReplacements)) {
            $lines = preg_split('/\r?\n/', $settings->slugReplacements);
            foreach ($lines as $line) {
                if (strpos($line, "=") !== false) {
                    $replacements[] = trim($line);
                }
            }
        }

        return [
            "settings" => [
                "enableSlugRefresh" => (bool) $settings->enableSlugRefresh,
                "limitToAscii" => (bool) $settings->limitAutoSlugToAscii,
                "replacements" => $replacements,
            ],
        ];
    }
}
