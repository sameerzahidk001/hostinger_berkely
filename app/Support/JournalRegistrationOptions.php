<?php

namespace App\Support;

class JournalRegistrationOptions
{
    /** @return list<string> */
    public static function professions(): array
    {
        return array_values(config('journal_registration.professions', []));
    }

    /** @return list<string> */
    public static function specializations(): array
    {
        return array_values(config('journal_registration.specializations', []));
    }

    /** @return list<string> */
    public static function workSettings(): array
    {
        return array_values(config('journal_registration.work_settings', []));
    }

    /** @return array<string, string> */
    public static function alertOptions(): array
    {
        return config('journal_registration.alert_options', []);
    }

    /** @return array<string, string> */
    public static function marketingOptions(): array
    {
        return config('journal_registration.marketing_options', []);
    }
}
