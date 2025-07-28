<?php
// src/controllers/SlugsmithController.php

namespace mediakreativ\slugsmith\controllers;

use Craft;
use craft\web\Controller;
use mediakreativ\slugsmith\Slugsmith;
use yii\web\Response;
use craft\helpers\StringHelper;

/**
 * Slugsmith Controller
 *
 * Handles all Control Panel and AJAX-based actions for the plugin.
 */
class SlugsmithController extends \craft\web\Controller
{
    /**
     * Determines if the controller allows anonymous access
     *
     * @var array|int|bool
     */
    protected array|int|bool $allowAnonymous = false;

    /**
     * @var \mediakreativ\slugsmith\models\SlugsmithSettings
     */
    private $settings;

    /**
     * Displays the "General Settings" form in the CP.
     */
    public function actionGeneral()
    {
        $settings = Slugsmith::getInstance()->getSettings();
        $settings->validate();

        Craft::info("General settings route triggered.", __METHOD__);

        $overrides = Craft::$app
            ->getConfig()
            ->getConfigFromFile(strtolower(Slugsmith::getInstance()->handle));

        Craft::info("Slugsmith General Settings Route triggered", __METHOD__);

        return $this->renderTemplate("slugsmith/_settings/general", [
            "plugin" => Slugsmith::getInstance(),
            "settings" => $settings,
            "overrides" => array_keys($overrides),
            "translationCategory" => Slugsmith::TRANSLATION_CATEGORY,
            "title" => Craft::t("slugsmith", "Slugsmith"),
            "enableSlugRefresh" => $settings->enableSlugRefresh,
        ]);
    }

    /**
     * Saves plugin settings from the CP form to the Project Config.
     */
    public function actionSaveSettings(): Response
    {
        $request = Craft::$app->getRequest();

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            Craft::$app
                ->getSession()
                ->setError(
                    Craft::t(
                        "slugsmith",
                        "Settings cannot be changed in this environment."
                    )
                );
            return $this->redirectToPostedUrl();
        }

        $settings = Slugsmith::getInstance()->getSettings();

        $settings->enableSlugRefresh = (bool) $request->getBodyParam(
            "enableSlugRefresh"
        );

        $allSites = Craft::$app->getSites()->getAllSites();

        if (count($allSites) === 1) {
            $siteHandle = $allSites[0]->handle;
            $settings->asciiPerSite[
                $siteHandle
            ] = (bool) $request->getBodyParam("limitAutoSlugToAscii");
        } else {
            $asciiPerSite = $request->getBodyParam("asciiPerSite", []);
            $settings->asciiPerSite = array_map(
                fn($v) => (bool) $v,
                $asciiPerSite
            );
        }

        if (!$settings->validate()) {
            Craft::$app->session->setError(
                Craft::t("slugsmith", "Failed to save settings.")
            );
            return $this->redirectToPostedUrl();
        }

        if (
            !Craft::$app->plugins->savePluginSettings(
                Slugsmith::getInstance(),
                $settings->toArray()
            )
        ) {
            Craft::$app->session->setError(
                Craft::t("slugsmith", "Could not save settings.")
            );
            return $this->redirectToPostedUrl();
        }

        Craft::$app->session->setNotice(
            Craft::t("slugsmith", "Settings saved successfully.")
        );
        return $this->redirectToPostedUrl();
    }

    /**
     * Displays the custom slug rule configuration UI.
     */
    public function actionSlugRules(): Response
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $allRules = $projectConfig->get("slugsmith.slugsmithRules") ?? [];

        $rulesBySite = [];

        $allSites = Craft::$app->getSites()->getAllSites();

        foreach ($allRules as $rule) {
            $siteUid = $rule["siteUid"] ?? null;

            if (!$siteUid) {
                $siteUid = "_global";
            }

            $rulesBySite[$siteUid][] = $rule;
        }

        $siteHandles = [];
        foreach ($allSites as $site) {
            $siteHandles[$site->uid] = $site->handle;
        }

        $rulesByHandle = [];

        foreach ($rulesBySite as $siteUid => $rules) {
            $handle = $siteHandles[$siteUid] ?? "_global";
            $rulesByHandle[$handle] = $rules;
        }

        foreach ($rulesByHandle as &$rules) {
            usort(
                $rules,
                fn($a, $b) => ($a["sortOrder"] ?? 0) <=> ($b["sortOrder"] ?? 0)
            );
        }
        unset($rules);

        return $this->renderTemplate("slugsmith/_settings/slug-rules", [
            "rulesBySite" => $rulesByHandle,
            "plugin" => Slugsmith::getInstance(),
            "title" => Craft::t("slugsmith", "Slug Rules"),
            "edition" => Slugsmith::getInstance()->edition,
        ]);
    }

    /**
     * API endpoint: Converts a title string into a slug.
     */
    public function actionSlugify(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $title = $request->getRequiredBodyParam("title");
        $siteHandle = $request->getBodyParam("siteHandle");
        $slug = Slugsmith::getInstance()->service->slugify($title, $siteHandle);

        return $this->asJson(["slug" => $slug]);
    }

    /**
     * API endpoint: Saves a new or updated slug rule to Project Config.
     */
    public function actionSaveSlugRule(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $siteUid = $request->getBodyParam("siteUid");

        if (!$siteUid) {
            $siteUid = Craft::$app->getSites()->getCurrentSite()->uid;
        }

        $uid = $request->getBodyParam("uid");
        $search = trim((string) $request->getBodyParam("search"));
        $replace = trim((string) $request->getBodyParam("replace"));

        if ($search === "") {
            return $this->asFailure(
                Craft::t("slugsmith", "Search value is required.")
            );
        }

        $uid = $uid ?: StringHelper::UUID();
        $path = "slugsmith.slugsmithRules.$uid";

        $existingRule = Craft::$app->getProjectConfig()->get($path);

        // Determine sortOrder (append to end if new)
        $newRule = [
            "uid" => $uid,
            "search" => $search,
            "replace" => $replace,
            "siteUid" => $siteUid,
        ];

        if (!$existingRule) {
            $allRules =
                Craft::$app
                    ->getProjectConfig()
                    ->get("slugsmith.slugsmithRules") ?? [];
            $maxSortOrder = 0;

            foreach ($allRules as $r) {
                if (
                    ($r["siteUid"] ?? null) === $siteUid &&
                    isset($r["sortOrder"])
                ) {
                    $maxSortOrder = max($maxSortOrder, (int) $r["sortOrder"]);
                }
            }

            $newRule["sortOrder"] = $maxSortOrder + 1;
        } elseif (isset($existingRule["sortOrder"])) {
            $newRule["sortOrder"] = $existingRule["sortOrder"];
        }

        Craft::$app->getProjectConfig()->set($path, $newRule);

        return $this->asJson([
            "success" => true,
            "rule" => $newRule,
            "message" => Craft::t("slugsmith", "Rule saved."),
        ]);
    }

    /**
     * API endpoint: Deletes a slug rule from Project Config.
     */
    public function actionDeleteSlugRule(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireLogin();

        $uid = Craft::$app->getRequest()->getRequiredBodyParam("uid");

        $path = "slugsmith.slugsmithRules.$uid";

        $rule = Craft::$app->getProjectConfig()->get($path);

        if (!$rule) {
            return $this->asFailure(Craft::t("slugsmith", "Rule not found."));
        }

        Craft::$app->getProjectConfig()->remove($path);

        return $this->asSuccess(Craft::t("slugsmith", "Rule deleted."));
    }

    /**
     * API endpoint: Reorders rules for a given site based on new order of UIDs.
     */
    public function actionReorderSlugRules(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $newOrder = $request->getBodyParam("ids");
        $siteUid = $request->getBodyParam("siteUid");

        if (!is_array($newOrder) || !$siteUid) {
            return $this->asFailure(Craft::t("slugsmith", "Invalid data."));
        }

        foreach ($newOrder as $index => $uid) {
            $path = "slugsmith.slugsmithRules.$uid";
            $rule = Craft::$app->getProjectConfig()->get($path);

            if (!$rule || ($rule["siteUid"] ?? null) !== $siteUid) {
                continue;
            }

            if (
                !isset($rule["sortOrder"]) ||
                (int) $rule["sortOrder"] !== $index
            ) {
                $rule["sortOrder"] = $index;
                Craft::$app->getProjectConfig()->set($path, $rule);
            }
        }

        return $this->asSuccess(Craft::t("slugsmith", "Rules reordered."));
    }

    /**
     * Pre-action hook to ensure all requests are Control Panel-only (except `slugify`) and init settings.
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id !== "slugify") {
            $this->requireCpRequest();
        }

        $this->settings = Slugsmith::getInstance()->getSettings();
        return true;
    }
}
