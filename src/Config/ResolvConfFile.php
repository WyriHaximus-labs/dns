<?php

namespace React\Dns\Config;

use RuntimeException;

final class ResolvConfFile
{
    /**
     * Returns the default path for the resolver configuration file on this system
     *
     * @return string
     * @codeCoverageIgnore
     */
    public static function getDefaultPath()
    {
        // use static path for all Unix-based systems
        if (DIRECTORY_SEPARATOR !== '\\') {
            return '/etc/resolv.conf';
        }

        throw new RuntimeException('Unable to load resolver configuration on Windows');
    }

    /**
     * @param ?string $path (optional) path to hosts file or null=load default location
     * @return self
     * @throws RuntimeException if the path can not be loaded (does not exist)
     */
    public static function loadFromPathBlocking($path = null)
    {
        if ($path === null) {
            $path = self::getDefaultPath();
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to load resolver configuration file "' . $path . '"');
        }

        return new self($contents);
    }

    /**
     * @param string $contents
     */
    public function __construct($contents)
    {
        // remove all comments from the contents
        $contents = preg_replace('/[ \t]*#.*/', '', strtolower($contents));

        $this->contents = $contents;
    }

    /**
     * @throws RuntimeException When no nameserver can be found
     * @return string
     */
    public function getNameserver()
    {
        foreach (explode(PHP_EOL, $this->contents) as $line) {
            $nameserverPosition = stripos($line, 'nameserver');
            if ($nameserverPosition !== false) {
                $nameserverLine = trim(substr($line, $nameserverPosition + 11));
                list ($nameserver) = explode(' ', $nameserverLine);
                return $nameserver;
            }
        }

        throw new RuntimeException('No nameservers found in resolver configuration');
    }
}
