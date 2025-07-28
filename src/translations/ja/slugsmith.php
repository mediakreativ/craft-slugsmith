<?php
// src/translations/ja/slugsmith.php

return [
    "Settings cannot be changed in this environment." =>
        "この環境では設定を変更できません。",
    "Failed to save settings." => "設定の保存に失敗しました。",
    "Could not save settings." => "設定を保存できませんでした。",
    "Settings saved successfully." => "設定が正常に保存されました。",
    "Refresh slug from title" => "タイトルからスラッグを更新",

    "Slug Rules" => "スラッグルール",
    "New Rule" => "新しいルール",
    "Create new Rule" => "新しいルールを作成",
    "No rules exist yet." => "まだルールが存在しません。",
    "Delete" => "削除",
    "Reorder" => "並べ替え",

    "Configure Custom Slug Rules" => "カスタムスラッグルールの設定",
    "These rules are applied before Craft’s default transliteration and slug generation. Useful for brand names, abbreviations, or custom character replacements." =>
        "これらのルールは、Craftのデフォルトの変換・スラッグ生成の前に適用されます。ブランド名、省略形、カスタム文字置換に便利です。",

    "General Settings" => "一般設定",
    "Custom Slug Rules" => "カスタムスラッグルール",
    "Enable Slug Refresh Button" => "スラッグ更新ボタンを有効化",
    "If the slug differs from the current title, a button is added to the slug field to regenerate the slug based on the current title." =>
        "スラッグが現在のタイトルと異なる場合、タイトルに基づいて再生成するためのボタンがスラッグフィールドに追加されます。",

    "Limit slugs to ASCII" => "スラッグをASCIIに制限",
    "Transliterate non-ASCII characters into Latin/ASCII." =>
        "非ASCII文字をラテン文字/ASCIIに変換します。",
    "This setting overrides Craft’s global <code>limitAutoSlugToAscii</code> config value." =>
        "この設定はCraftのグローバル設定 <code>limitAutoSlugToAscii</code> を上書きします。",
    "Transliterate non-ASCII characters into Latin/ASCII. This setting overrides Craft’s global <code>limitAutoSlugToAscii</code> config value. Please select your preferred setting for each site." =>
        "非ASCII文字をASCIIに変換します。この設定はCraftのグローバル設定を上書きします。サイトごとに希望の設定を選択してください。",
    "Transliterate slugs to ASCII on site “{site}”." =>
        "サイト「{site}」でスラッグをASCIIに変換する。",

    "Convert hashtags to slugs" => "ハッシュタグをスラッグに変換",
    "Automatically turns hashtags like #WeLoveCraft into readable slugs like <code>hashtag-we-love-craft</code>." =>
        "#WeLoveCraft のようなハッシュタグを <code>hashtag-we-love-craft</code> のような読みやすいスラッグに自動変換します。",

    "Rule not found." => "ルールが見つかりません。",
    "Rule saved." => "ルールが保存されました。",
    "Could not save rule." => "ルールを保存できませんでした。",
    "Could not find rule list." => "ルールリストが見つかりませんでした。",
    "Delete this rule?" => "このルールを削除しますか？",
    "Rule deleted." => "ルールが削除されました。",
    "Could not delete rule." => "ルールを削除できませんでした。",
    "Rules reordered." => "ルールの並び順が更新されました。",
    "Could not reorder rules." => "ルールの並び替えに失敗しました。",

    "Search value is required." => "検索値は必須です。",
    "Invalid data." => "無効なデータです。",
];
