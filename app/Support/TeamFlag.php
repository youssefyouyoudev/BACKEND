<?php

namespace App\Support;

use Illuminate\Support\Str;

class TeamFlag
{
    private const COUNTRY_CODES = [
        'algeria' => 'dz',
        'argentina' => 'ar',
        'australia' => 'au',
        'austria' => 'at',
        'belgium' => 'be',
        'bosnia and herzegovina' => 'ba',
        'brazil' => 'br',
        'canada' => 'ca',
        'cape verde' => 'cv',
        'colombia' => 'co',
        'croatia' => 'hr',
        'curacao' => 'cw',
        'czech republic' => 'cz',
        'dr congo' => 'cd',
        'ecuador' => 'ec',
        'egypt' => 'eg',
        'england' => 'gb-eng',
        'france' => 'fr',
        'germany' => 'de',
        'ghana' => 'gh',
        'haiti' => 'ht',
        'iran' => 'ir',
        'iraq' => 'iq',
        'ivory coast' => 'ci',
        'japan' => 'jp',
        'jordan' => 'jo',
        'mexico' => 'mx',
        'morocco' => 'ma',
        'netherlands' => 'nl',
        'new zealand' => 'nz',
        'norway' => 'no',
        'panama' => 'pa',
        'paraguay' => 'py',
        'portugal' => 'pt',
        'qatar' => 'qa',
        'saudi arabia' => 'sa',
        'scotland' => 'gb-sct',
        'senegal' => 'sn',
        'south africa' => 'za',
        'south korea' => 'kr',
        'spain' => 'es',
        'sweden' => 'se',
        'switzerland' => 'ch',
        'tunisia' => 'tn',
        'turkey' => 'tr',
        'united states' => 'us',
        'uruguay' => 'uy',
        'uzbekistan' => 'uz',
    ];

    public static function code(?string $team): ?string
    {
        if (blank($team)) {
            return null;
        }

        $normalized = Str::of($team)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        return self::COUNTRY_CODES[$normalized] ?? null;
    }

    public static function url(?string $team, ?string $override = null): ?string
    {
        if (filled($override)) {
            return Str::startsWith($override, ['http://', 'https://', 'data:', '/'])
                ? $override
                : asset($override);
        }

        $code = self::code($team);

        return $code ? asset("images/flags/{$code}.svg") : null;
    }
}
