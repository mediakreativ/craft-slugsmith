<?php
// src/services/SlugsmithService.php

namespace mediakreativ\slugsmith\services;

use Craft;
use craft\base\Component;
use craft\helpers\StringHelper;
use craft\helpers\ElementHelper;
use mediakreativ\slugsmith\Slugsmith;

/**
 * Slugsmith Service
 *
 * Contains core logic for slug generation, including support for
 * custom replacement rules, hashtag formatting, and ASCII transliteration.
 */
class SlugsmithService extends Component
{
    /**
     * Converts a given title or string into a valid slug.
     *
     * Applies site-specific or global replacement rules,
     * optional hashtag formatting, and slug normalization
     * using either ASCII or native Craft logic.
     *
     * @param string $input The input title or string to convert
     * @param string|null $siteHandle Optional site handle to use for per-site logic
     * @return string The generated slug
     */
    public function slugify(string $input, ?string $siteHandle = null): string
    {
        $settings = Slugsmith::getInstance()->getSettings();
        $siteHandle =
            $siteHandle ?? Craft::$app->getSites()->getCurrentSite()->handle;

        // Apply custom replacements first
        foreach (
            $this->getReplacementMapFromRules($siteHandle)
            as $from => $to
        ) {
            $input = str_replace($from, $to, $input);
        }

        // Optional hashtag formatting
        if ($settings->autoFormatHashtags) {
            $input = $this->autoFormatHashtags($input);
        }

        // Validate site handle
        $allSiteHandles = array_map(
            fn($site) => $site->handle,
            Craft::$app->getSites()->getAllSites()
        );
        if (!in_array($siteHandle, $allSiteHandles, true)) {
            Craft::warning(
                "Slugsmith: Unknown siteHandle '{$siteHandle}', falling back to primary site.",
                __METHOD__
            );
            $siteHandle = Craft::$app->getSites()->getPrimarySite()->handle;
        }

        // Determine whether ASCII transliteration is active
        $asciiEnabled = isset($settings->asciiPerSite[$siteHandle])
            ? (bool) $settings->asciiPerSite[$siteHandle]
            : (bool) ($settings->limitAutoSlugToAscii ?? true);

        // Generate final slug using Craft helpers
        return $asciiEnabled
            ? StringHelper::slugify($input)
            : ElementHelper::normalizeSlug($input);
    }

    /**
     * Transforms hashtags into readable words
     *
     * @param string $input The raw title or string
     * @return string Modified string with formatted hashtags
     */
    private function autoFormatHashtags(string $input): string
    {
        return preg_replace_callback(
            "/#(\p{L}[\p{L}\p{N}]*)/u",
            function ($matches) {
                $hashtag = $matches[1];

                $hashtag = preg_replace(
                    "/(?<=[\p{Ll}])(?=[\p{Lu}])/u",
                    " ",
                    $hashtag
                );

                return "hashtag " . $hashtag;
            },
            $input
        );
    }

    /**
     * Retrieves and sorts all slug rules from Project Config.
     *
     * @return array Sorted rule array with all site UIDs
     */
    private function getCustomRules(): array
    {
        $rules =
            Craft::$app->getProjectConfig()->get("slugsmith.slugsmithRules") ??
            [];

        $rules = array_values($rules);
        usort(
            $rules,
            fn($a, $b) => ($a["sortOrder"] ?? 0) <=> ($b["sortOrder"] ?? 0)
        );

        return $rules;
    }

    /**
     * Builds a key-value map of custom replacement rules for a specific site.
     *
     * Includes global rules and site-specific rules, prioritized by order.
     *
     * @param string $siteHandle
     * @return array<string, string> Map of [search => replace]
     */
    private function getReplacementMapFromRules(string $siteHandle): array
    {
        $map = [];
        $rules = $this->getCustomRules();

        $siteUidToHandle = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteUidToHandle[$site->uid] = $site->handle;
        }

        foreach ($rules as $rule) {
            $ruleHandle = $siteUidToHandle[$rule["siteUid"] ?? ""] ?? "_global";
            if ($ruleHandle === $siteHandle || $ruleHandle === "_global") {
                $map[$rule["search"]] = $rule["replace"];
            }
        }

        return $map;
    }
}
