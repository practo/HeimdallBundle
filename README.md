# HeimdallBundle

Installation
============

Step 1: Download the Bundle
---------------------------

Add the dependency in your project's `composer.json`:
```JSON

"require": {
    "practo/heimdall-bundle": "dev-master"
},
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:practo/HeimdallBundle.git"
    }
]

```

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer update practo/heimdall-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Practo\HeimdallBundle\PractoHeimdallBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Add required parameters
-------------------------------

Heimdall has 4 required parameters, add them to your `parameters.yml`

```
heimdall.api_secret: 'copy-from-heimdall-settings'
heimdall.base_uri: 'http://heimdall.practo.local'
heimdall.product_name: 'RAY'
heimdall.cache_servers:
    127.0.0.1: 11212 // Memcache host:port
```

Version Control
---------------
There are no release tags for this bundle. If you want to update, please use commit references.

* Update commit reference in your `composer.lock`:
```

{
    "name": "practo/heimdall-bundle",
    "version": "dev-master",
    "target-dir": "Practo/HeimdallBundle",
    "source": {
        "type": "git",
        "url": "git@github.com:practo/HeimdallBundle.git",
        "reference": "" /* Update this reference to your target commit hash */
    },
    "dist": {
      "type": "zip",
      "url": "whatever",
      "reference": "", /* Update this too */
      "shasum": ""
  },
},

```

* Run the following command to update composer hash:
```bash
$ composer update --lock
```
