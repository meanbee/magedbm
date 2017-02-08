<?php

namespace Meanbee\Magedbm\Repository;

class Strip
{
    const KEY_DEVELOPMENT = '@development';

    /**
     * Get the stripped tables
     *
     * @param string $key
     * @param string $framework
     *
     * @return array
     * @throws \Exception
     */
    static public function get($key, $framework = 'magento2')
    {
        $file = __DIR__. DIRECTORY_SEPARATOR . $framework . DIRECTORY_SEPARATOR . $key . '.php';
        if (!file_exists($file)) {
            throw new \Exception("The strip option {$key} does not exist");
        }

        return include $file;
    }
}
