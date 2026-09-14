<?php

abstract class ObjectModel extends ObjectModelCore
{
    public function getAssociatedLanguage(): Language
    {
        $language = new Language(Context::getContext()->language->id);
        if ($language->id === null) {
            $language = new Language(Configuration::get('PS_LANG_DEFAULT'));
        }

        return $language;
    }
}
