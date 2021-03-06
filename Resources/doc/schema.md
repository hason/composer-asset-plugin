Composer Schema of Asset
========================

### Properties

##### requires

Lists packages required by this package. The package will not be installed unless those requirements
can be met.

##### requires-dev (root-only)

Lists packages required for developing this package, or running tests, etc. The dev requirements
of the root package are installed by default. Both `install` or `update` support the `--no-dev`
option that prevents dev dependencies from being installed.

##### extra.asset-repositories (root-only)

Because the plugin is installed after the analysis of type repositories, the custom types must
be included in a special property in `extra` composer.

Custom package repositories to use.

By default composer just uses the packagist repository. By specifying
repositories you can get packages from elsewhere.

Repositories are not resolved recursively. You only can add them to your
main `composer.json`. Repository declarations of dependencies' composer.jsons are ignored.

The following repository types are supported:

- **npm-vcs**: The version control system repository can fetch packages from git with `package.json`
               file dedicated to NPM. The `url` property of git source code is required.
- **bower-vcs**: The version control system repository can fetch packages from git with `bower.json`
                 file dedicated to Bower. The `url` property of git source code is required.

### Mapping asset file to composer package

##### NPM mapping

The `package.json` of asset repository is automatically converted to a Complete Package instance with:

| NPM Package          | Composer Package                      |
|----------------------|---------------------------------------|
| name                 | name (`npm-asset/{name}`)             |
| `npm-asset`          | type                                  |
| description          | description                           |
| version              | version                               |
| keywords             | keywords                              |
| homepage             | homepage                              |
| license              | license                               |
| author               | authors [0]                           |
| contributors         | authors [n], merging with `author`    |
| dependencies         | require                               |
| devDependencies      | require-dev                           |
| bin                  | bin                                   |
| bugs                 | extra.npm-asset-bugs                  |
| files                | extra.npm-asset-files                 |
| main                 | extra.npm-asset-main                  |
| man                  | extra.npm-asset-man                   |
| directories          | extra.npm-asset-directories           |
| repository           | extra.npm-asset-repository            |
| scripts              | extra.npm-asset-scripts               |
| config               | extra.npm-asset-config                |
| bundledDependencies  | extra.npm-asset-bundled-dependencies  |
| optionalDependencies | extra.npm-asset-optional-dependencies |
| engines              | extra.npm-asset-engines               |
| engineStrict         | extra.npm-asset-engine-strict         |
| os                   | extra.npm-asset-os                    |
| cpu                  | extra.npm-asset-cpu                   |
| preferGlobal         | extra.npm-asset-prefer-global         |
| private              | extra.npm-asset-private               |
| publishConfig        | extra.npm-asset-publish-config        |
| `not used`           | time                                  |
| `not used`           | support                               |
| `not used`           | conflict                              |
| `not used`           | replace                               |
| `not used`           | provide                               |
| `not used`           | suggest                               |
| `not used`           | autoload                              |
| `not used`           | autoload-dev                          |
| `not used`           | include-path                          |
| `not used`           | target-dir                            |
| `not used`           | extra                                 |
| `not used`           | archive                               |

##### Bower mapping

The `bower.json` of asset repository is automatically converted to a Complete Package instance with:

| Bower Package        | Composer Package                      |
|----------------------|---------------------------------------|
| name                 | name (`bower-asset/{name}`)           |
| `bower-asset`        | type                                  |
| description          | description                           |
| version              | version                               |
| keywords             | keywords                              |
| license              | license                               |
| dependencies         | require                               |
| devDependencies      | require-dev                           |
| bin                  | bin                                   |
| main                 | extra.bower-asset-main                |
| ignore               | extra.bower-asset-ignore              |
| private              | extra.bower-asset-private             |
| `not used`           | homepage                              |
| `not used`           | time                                  |
| `not used`           | authors                               |
| `not used`           | support                               |
| `not used`           | conflict                              |
| `not used`           | replace                               |
| `not used`           | provide                               |
| `not used`           | suggest                               |
| `not used`           | autoload                              |
| `not used`           | autoload-dev                          |
| `not used`           | include-path                          |
| `not used`           | target-dir                            |
| `not used`           | extra                                 |
| `not used`           | archive                               |

## Asset Repository

Automatically, the plugin creates `Composer Repositories` to find and create
automatically the VCS repository of the asset defined in the `require` and `require-dev`.

### NPM Composer Repository

[NPM Package](https://www.npmjs.org) is the main NPM repository. A NPM Composer repository
is basically a package source: a place where you can get packages from. NPM Package aims to
be the central repository that everybody uses. This means that you can automatically `require`
any package that is available there.

If you go to the [NPM website](https://www.npmjs.org), you can browse and search for packages.

All packages are automatically prefixed with `npm-asset/`.

### Bower Composer Repository

[Bower Package](http://bower.io) is the main Bower repository. A Bower Composer repository
is basically a package source: a place where you can get packages from. Bower Package aims to
be the central repository that everybody uses. This means that you can automatically `require`
any package that is available there.

If you go to the [Bower website](http://bower.io/search/), you can browse and search for packages.

All packages are automatically prefixed with `bower-asset/`.
