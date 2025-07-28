<?php
// src/models/SlugsmithSettings.php
namespace mediakreativ\slugsmith\models;

use craft\base\Model;

/**
 * Slugsmith Settings Model
 *
 * Defines the configurable settings for the Slugsmith plugin.
 * These settings are stored in Project Config and exposed to the Control Panel UI.
 */
class SlugsmithSettings extends Model
{
    public bool $enableSlugRefresh = true;
    public bool $autoFormatHashtags = true;
    public bool $limitAutoSlugToAscii = true;
    public array $asciiPerSite = [];

    public function rules(): array
    {
        return [
            ["enableSlugRefresh", "boolean"],
            ["autoFormatHashtags", "boolean"],
            ["limitAutoSlugToAscii", "boolean"],
            ["asciiPerSite", "safe"],
        ];
    }
}
