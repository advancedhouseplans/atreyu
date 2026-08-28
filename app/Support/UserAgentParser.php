<?php

namespace App\Support;

use WhichBrowser\Parser;

class UserAgentParser
{
    /**
     * @return array{browser:?string, browser_version:?string, os:?string, os_version:?string, device:?string}
     */
    public static function parse(string $ua): array
    {
        if ($ua === '') {
            return ['browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null, 'device' => null];
        }

        $p = new Parser($ua);

        return [
            'browser' => $p->browser->getName() ?: null,
            'browser_version' => $p->browser->getVersion() ?: null,
            'os' => $p->os->getName() ?: null,
            'os_version' => $p->os->getVersion() ?: null,
            'device' => $p->device->type ?: null,
        ];
    }
}
