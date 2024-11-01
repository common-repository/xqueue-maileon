<?php return array(
    'root' => array(
        'name' => 'xqueue/wordpress',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'xqueue/addresscheck-api-client' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '01f1352e34d4362cf474dec495cbedcabd009425',
            'type' => 'library',
            'install_path' => __DIR__ . '/../xqueue/addresscheck-api-client',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'xqueue/maileon-api-client' => array(
            'pretty_version' => 'v1.9.9',
            'version' => '1.9.9.0',
            'reference' => '56f7ba3af6c37e99f09b4fd104d26fa434c86167',
            'type' => 'library',
            'install_path' => __DIR__ . '/../xqueue/maileon-api-client',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'xqueue/wordpress' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
